<?php
namespace App\Services;
use App\Core\Env;
use PDO;
final class BackupService {
 public static function run(PDO $db): array {
  $dir=dirname(__DIR__,2).'/storage/backups'; if(!is_dir($dir)) @mkdir($dir,0775,true);
  $stamp=date('Ymd_His'); $file=$dir.'/podnikatel_'.$stamp.'.sqlite';
  $path=(string)Env::get('DB_PATH','database/app.sqlite'); if(!str_starts_with($path,'/'))$path=dirname(__DIR__,2).'/'.$path;
  $backup=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$backup->exec('PRAGMA busy_timeout=30000');$backup->exec('VACUUM INTO '.$backup->quote($file));$backup=null;
  $gz=$file.'.gz';$in=fopen($file,'rb');$out=gzopen($gz,'wb9');while(!feof($in))gzwrite($out,fread($in,1048576));fclose($in);gzclose($out);@unlink($file);
  $hash=hash_file('sha256',$gz);$remote=self::remote($gz);$keep=max(1,(int)Env::get('BACKUP_RETENTION_DAYS','30'));foreach(glob($dir.'/*.gz')?:[] as $old)if($old!==$gz&&filemtime($old)<time()-$keep*86400)@unlink($old);
  return ['file'=>$gz,'sha256'=>$hash,'bytes'=>(int)filesize($gz),'remote'=>$remote];
 }
 private static function remote(string $file):string{$target=trim((string)Env::get('BACKUP_SCP_TARGET',''));if(!$target)return 'local-only';$remoteDir=trim((string)Env::get('BACKUP_REMOTE_DIR','backups'));$cmd='scp -q '.escapeshellarg($file).' '.escapeshellarg($target.':'.$remoteDir.'/');exec($cmd,$o,$code);if($code!==0)throw new \RuntimeException('Odeslání zálohy na vzdálený server selhalo.');return $target.':'.$remoteDir;}
}
