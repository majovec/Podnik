<?php
namespace App\Services;
use PDO;
use App\Core\Env;
final class BackupService
{
    public static function run(PDO $db):array
    {
        $base=dirname(__DIR__,2);$dir=$base.'/storage/backups';if(!is_dir($dir))@mkdir($dir,0775,true);
        $source=Env::get('DB_PATH','database/app.sqlite');if(!str_starts_with($source,'/'))$source=$base.'/'.$source;
        $stamp=date('Ymd_His');$tmp=$dir.'/podnikatel_'.$stamp.'.sqlite';$gz=$tmp.'.gz';
        $b=new PDO('sqlite:'.$source,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$b->exec('PRAGMA busy_timeout=30000');$b->exec('VACUUM INTO '.$b->quote($tmp));$b=null;
        $in=fopen($tmp,'rb');$out=gzopen($gz,'wb9');while(!feof($in)){gzwrite($out,(string)fread($in,1024*1024));}fclose($in);gzclose($out);@unlink($tmp);
        $hash=hash_file('sha256',$gz);$remote='';$target=trim((string)Env::get('BACKUP_SCP_TARGET',''));$remoteDir=trim((string)Env::get('BACKUP_REMOTE_DIR',''));if($target&&$remoteDir){$remote=$target.':'.$remoteDir.'/'.basename($gz);@exec('scp -q '.escapeshellarg($gz).' '.escapeshellarg($remote).' 2>&1',$o,$rc);if($rc!==0)$remote='';}
        $ret=max(1,(int)Env::get('BACKUP_RETENTION_DAYS','30'));foreach(glob($dir.'/podnikatel_*.sqlite.gz')?:[] as $f)if(@filemtime($f)<time()-$ret*86400)@unlink($f);
        return ['file'=>$gz,'sha256'=>$hash,'bytes'=>filesize($gz),'remote'=>$remote];
    }
}
