<?php
namespace App\Core;
final class Response {
    public static function redirect(string $to): never {header('Location: '.$to); exit;}
    public static function abort(int $code,string $message): never {http_response_code($code); echo '<h1>'.$code.'</h1><p>'.htmlspecialchars($message).'</p>'; exit;}
    public static function json(mixed $data,int $code=200): never {http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;}
}
