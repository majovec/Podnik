<?php
namespace App\Core;
final class Env {
    private static array $v=[];
    public static function load(string $file): void {
        if (!is_file($file)) return;
        foreach (file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
            $line=trim($line); if ($line==='' || $line[0]==='#' || !str_contains($line,'=')) continue;
            [$k,$v]=explode('=',$line,2); $v=trim($v);
            if (($v[0]??'')==='"' && substr($v,-1)==='"') $v=substr($v,1,-1);
            self::$v[trim($k)]=$v;
        }
    }
    public static function get(string $key, ?string $default=null): ?string {
        return $_ENV[$key] ?? $_SERVER[$key] ?? self::$v[$key] ?? $default;
    }
}
