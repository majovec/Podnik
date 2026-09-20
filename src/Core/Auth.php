<?php
namespace App\Core;
final class Auth {
    private static ?array $user=null;
    public static function boot(): void {
        if(session_status()!==PHP_SESSION_ACTIVE){
            session_name('podnikatel_sid');
            session_set_cookie_params(['httponly'=>true,'secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'),'samesite'=>'Lax']);
            session_start();
        }
        if(isset($_SESSION['user_id'])){
            $s=Database::pdo()->prepare('SELECT * FROM users WHERE id=? AND active=1'); $s->execute([$_SESSION['user_id']]);
            self::$user=$s->fetch() ?: null;
        }
    }
    public static function user(): ?array{return self::$user;}
    public static function id(): ?int{return self::$user?(int)self::$user['id']:null;}
    public static function workspaceId(): ?int{return self::$user?(int)self::$user['workspace_id']:null;}
    public static function check(): bool{return self::$user!==null;}
    public static function require(): void {
        if(!self::check()){Response::redirect('/login');}
        $uri=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
        $map=[
            '/customers'=>'crm','/documents'=>'invoicing','/documents/files'=>'documents','/jobs'=>'jobs','/expenses'=>'expenses','/products'=>'inventory',
            '/bank'=>'bank','/calendar'=>'calendar','/tasks'=>'tasks','/ai'=>'ai','/recurring'=>'invoicing','/tax'=>'tax',
            '/automation'=>'automation','/settings/api-keys'=>'api','/admin/users'=>'team','/admin'=>'admin','/reminders'=>'invoicing'
        ];
        foreach($map as $prefix=>$permission){if($uri===$prefix||str_starts_with($uri,$prefix.'/')){if(!self::can($permission))Response::abort(403,'Nedostatečné oprávnění.');break;}}
    }
    public static function can(string $permission): bool {
        if(!self::$user)return false;
        if(in_array(self::$user['role'],['owner','admin'],true))return true;
        $raw=self::$user['permissions_json']??'';
        if($raw!==''){
            $p=json_decode($raw,true); if(is_array($p)&&array_key_exists($permission,$p))return (bool)$p[$permission];
        }
        if(!in_array(self::$user['role'],['employee','accountant','owner','admin'],true)){
            $s=Database::pdo()->prepare('SELECT permissions_json FROM custom_roles WHERE workspace_id=? AND name=? LIMIT 1');$s->execute([(int)self::$user['workspace_id'],(string)self::$user['role']]);$custom=$s->fetchColumn();if($custom!==false){$p=json_decode((string)$custom,true);if(is_array($p)&&array_key_exists($permission,$p))return (bool)$p[$permission];}
        }
        $defaults=[
            'crm'=>true,'invoicing'=>true,'jobs'=>true,'expenses'=>true,'inventory'=>true,'bank'=>false,
            'calendar'=>true,'tasks'=>true,'ai'=>false,'tax'=>false,'automation'=>false,'api'=>false,'documents'=>true,'team'=>false,'admin'=>false
        ];
        if(self::$user['role']==='accountant')$defaults=array_merge($defaults,['bank'=>true,'tax'=>true,'api'=>true]);
        return (bool)($defaults[$permission]??false);
    }
    public static function requirePermission(string $permission): void {self::require(); if(!self::can($permission))Response::abort(403,'Nedostatečné oprávnění: '.$permission.'.');}
    public static function requireRole(array $roles): void {self::require(); if(!in_array(self::$user['role'],$roles,true)) Response::abort(403,'Nedostatečné oprávnění.');}
    public static function login(array $u): void {session_regenerate_id(true); $_SESSION['user_id']=(int)$u['id']; self::$user=$u;}
    public static function logout(): void {$_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),'',['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>$p['samesite']??'Lax']);} session_destroy(); self::$user=null;}
    public static function csrf(): string {if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf'];}
    public static function verifyCsrf(): void {if(!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')) Response::abort(419,'Neplatný CSRF token.');}
}
