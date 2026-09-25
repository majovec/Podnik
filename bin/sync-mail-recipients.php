<?php
declare(strict_types=1);
// Generate Postfix recipient/transport maps from the live Byznio SQLite database.
// Run as root from cron/systemd so Postfix can read the resulting map.
$root=dirname(__DIR__);
require $root.'/vendor/autoload.php';
require $root.'/src/bootstrap.php';
use App\Core\Database;
use App\Core\Env;
$domain=strtolower(trim((string)Env::get('MAIL_DOMAIN','byznio.cz')));
$out=trim((string)Env::get('MAIL_POSTFIX_MAP','/etc/postfix/byznio_transport'));
if($domain===''||$out==='') throw new RuntimeException('MAIL_DOMAIN/MAIL_POSTFIX_MAP není nastaven.');
$pdo=Database::pdo();
$st=$pdo->query("SELECT lower(trim(email_localpart)) AS localpart FROM workspaces WHERE COALESCE(mail_enabled,1)=1 AND email_localpart IS NOT NULL AND trim(email_localpart)!=''");
$rows=[];
foreach($st as $r){
    $local=(string)$r['localpart'];
    if(!preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i',$local)) continue;
    $rows[$local.'@'.$domain]=true;
}
ksort($rows,SORT_STRING);
$dir=dirname($out); if(!is_dir($dir)) @mkdir($dir,0755,true);
$tmp=$out.'.tmp.'.getmypid();
$fh=fopen($tmp,'wb'); if(!$fh) throw new RuntimeException('Nelze vytvořit '.$tmp);
foreach(array_keys($rows) as $addr) fwrite($fh,$addr." byznio-pipe:\n");
fclose($fh);
chmod($tmp,0644);
rename($tmp,$out);
$cmd='postmap '.escapeshellarg($out);
exec($cmd,$dummy,$code);
if($code!==0) throw new RuntimeException('postmap selhal pro '.$out);
$access=preg_replace('/_transport$/','_recipient_access',$out) ?: $out.'_recipient_access';
$tmp2=$access.'.tmp.'.getmypid();
$fh=fopen($tmp2,'wb'); if(!$fh) throw new RuntimeException('Nelze vytvořit '.$tmp2);
foreach(array_keys($rows) as $addr) fwrite($fh,$addr." OK\n");
// Explicitly reject the whole domain after the known-address checks.
fwrite($fh,'@'.$domain." REJECT Neznámá Byznio adresa\n");
fclose($fh); chmod($tmp2,0644); rename($tmp2,$access);
exec('postmap '.escapeshellarg($access),$dummy2,$code2);
if($code2!==0) throw new RuntimeException('postmap selhal pro '.$access);
echo 'Byznio mail recipients synced: '.count($rows).PHP_EOL;
