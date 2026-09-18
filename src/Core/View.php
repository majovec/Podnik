<?php
namespace App\Core;
final class View {
    public static function render(string $view,array $data=[]): void {
        extract($data); $file=dirname(__DIR__).'/Views/'.$view.'.php';
        if(!is_file($file)) Response::abort(500,'View not found');
        if ($view === 'landing') { include $file; return; }
        include dirname(__DIR__).'/Views/layouts/app.php';
    }
    public static function e(mixed $v): string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
    public static function money(mixed $v): string{return number_format((float)$v,2,',',' ').' Kč';}
}
