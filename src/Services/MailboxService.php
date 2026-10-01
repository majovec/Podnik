<?php
namespace App\Services;

use App\Core\Env;
use PDO;

final class MailboxService {
    private const RESERVED=['info','help','support','admin','contact','office','billing','sales','ceo','director','team','service','noreply','no-reply','postmaster','webmaster','root','security','privacy','legal','finance','accounting','invoice','invoices','system','byznio'];
    public static function domain():string{return strtolower(trim((string)Env::get('MAIL_DOMAIN','byznio.cz')));}
    public static function reserved():array{return self::RESERVED;}
    public static function normalizeLocalpart(string $local):string{return strtolower(trim($local));}
    public static function validateLocalpart(string $local,bool $system=false):?string{
        $local=self::normalizeLocalpart($local);
        if($local===''||strlen($local)>64||!preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/',$local))return 'Adresa může obsahovat jen malá písmena, čísla, tečku, pomlčku a podtržítko.';
        if(!$system && in_array($local,self::RESERVED,true))return 'Tento název je vyhrazený pro Byznio a nelze jej použít pro zákaznickou schránku.';
        return null;
    }
    public static function address(string $local):string{return self::normalizeLocalpart($local).'@'.self::domain();}
    public static function ensureWorkspace(PDO $db,int $workspaceId):int{
        $q=$db->prepare('SELECT id,localpart,display_name,active FROM email_mailboxes WHERE workspace_id=? AND is_system=0 LIMIT 1');$q->execute([$workspaceId]);$row=$q->fetch();
        $w=$db->prepare('SELECT name,email_localpart,mail_enabled,mail_display_name FROM workspaces WHERE id=?');$w->execute([$workspaceId]);$workspace=$w->fetch();if(!$workspace)throw new \RuntimeException('Firma nenalezena.');
        $local=self::normalizeLocalpart((string)$workspace['email_localpart']);if($local==='')throw new \RuntimeException('Firma nemá nastavenou e-mailovou adresu.');
        if($err=self::validateLocalpart($local,false))throw new \RuntimeException($err);
        $name=trim((string)($workspace['mail_display_name']?:$workspace['name']));
        if($row){$db->prepare('UPDATE email_mailboxes SET localpart=?,display_name=?,active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$local,$name,!empty($workspace['mail_enabled'])?1:0,(int)$row['id']]);return (int)$row['id'];}
        $db->prepare('INSERT INTO email_mailboxes(workspace_id,localpart,display_name,active,is_system) VALUES(?,?,?,?,0)')->execute([$workspaceId,$local,$name,!empty($workspace['mail_enabled'])?1:0]);return (int)$db->lastInsertId();
    }
    public static function syncWorkspace(PDO $db,int $workspaceId):void{self::ensureWorkspace($db,$workspaceId);}
}
