<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Env;

final class BankTokenService
{
    private static function key(): string
    {
        $appKey = (string) Env::get('APP_KEY', 'change-me');
        if ($appKey === '' || $appKey === 'change-me') {
            throw new \RuntimeException('APP_KEY není bezpečně nastaven.');
        }
        return hash('sha256', $appKey, true);
    }

    public static function encrypt(string $plain): string
    {
        $key = self::key();
        $iv = random_bytes(16);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false || strlen($tag) !== 16) {
            throw new \RuntimeException('Šifrování bankovního tokenu selhalo.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $encoded): string
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 33) {
            throw new \RuntimeException('Neplatný uložený bankovní token.');
        }

        $key = self::key();
        $iv = substr($raw, 0, 16);
        $tag = substr($raw, 16, 16);
        $cipher = substr($raw, 32);

        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain !== false) {
            return $plain;
        }

        // Backward compatibility for accounts encrypted by the previous sodium format.
        if (function_exists('sodium_crypto_secretbox_open') && strlen($raw) > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            $legacy = sodium_crypto_secretbox_open(
                $raw,
                str_repeat("\0", SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
                $key
            );
            if ($legacy !== false) {
                return $legacy;
            }
        }

        throw new \RuntimeException('Dešifrování bankovního tokenu selhalo.');
    }
}
