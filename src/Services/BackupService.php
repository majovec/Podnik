<?php
namespace App\Services;
use App\Core\Env;
use PDO;
final class BackupService {
 public static function run(PDO $db): array {
  $dir=dirname(__DIR__,2).'/storage/backups'; if(!is_dir($dir)) @mkdir($dir,0775,true);
  $stamp=date('Ymd_His'); $file=$dir.'/podnikatel_'.$stamp.'.sqlite';
  $db->exec('VACUUM INTO '. $db->quote($file));
  $gz=$file.'.gz'; $in=fopen($file,'rb'); $out=gzopen($gz,'wb9'); while(!feof($in)) gzwrite($out,fread($in,1048576)); fclose($in); gzclose($out); @unlink($file);
  $hash=hash_file('sha256',$gz); $remote=self::remote($gz);
  return ['file'=>$gz,'sha256'=>$hash,'bytes'=>(int)filesize($gz),'remote'=>$remote];
 }
 private static function remote(string $file): string {
  $target=trim((string)Env::get('BACKUP_SCP_TARGET','')); if(!$target)return 'local-only';
  $remoteDir=trim((string)Env::get('BACKUP_REMOTE_DIR','backups')); $cmd='scp -q '.escapeshellarg($file).' '.escapeshellarg($target.':'.$remoteDir.'/');
  exec($cmd,$o,$code); if($code!==0) throw new \RuntimeException('Odeslání zálohy na vzdálený server selhalo.'); return $target.':'.$remoteDir;
 }
}
