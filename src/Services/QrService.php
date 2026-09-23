<?php
declare(strict_types=1);
namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

final class QrService
{
    public static function normalizeIban(string $value): ?string
    {
        $v = strtoupper(preg_replace('/\s+/', '', trim($value)));
        if ($v === '') return null;

        if (preg_match('/^CZ[0-9]{22}$/', $v)) {
            $check = substr($v, 2, 2);
            $bban = substr($v, 4);
            $num = $bban . '1235' . $check;
            $rem = 0;
            for ($i = 0, $n = strlen($num); $i < $n; $i++) {
                $rem = (($rem * 10) + (int)$num[$i]) % 97;
            }
            return $rem === 1 ? $v : null;
        }

        if (preg_match('/^(?:(\d{1,10})-)?(\d{1,10})\/(\d{4})$/', $v, $m)) {
            $prefix = str_pad($m[1] ?? '', 6, '0', STR_PAD_LEFT);
            $account = str_pad($m[2], 10, '0', STR_PAD_LEFT);
            $bban = $m[3] . $prefix . $account;
            $num = $bban . '123500';
            $rem = 0;
            for ($i = 0, $n = strlen($num); $i < $n; $i++) {
                $rem = (($rem * 10) + (int)$num[$i]) % 97;
            }
            return 'CZ' . str_pad((string)(98 - $rem), 2, '0', STR_PAD_LEFT) . $bban;
        }

        return null;
    }

    public static function spayd(string $bankAccount, float $amount, string $variableSymbol): ?string
    {
        $iban = self::normalizeIban($bankAccount);
        if (!$iban || $amount <= 0) return null;
        $vs = preg_replace('/\D+/', '', $variableSymbol);
        return 'SPD*1.0*ACC:' . $iban
            . '*AM:' . number_format($amount, 2, '.', '')
            . '*CC:CZK*X-VS:' . $vs;
    }

    /** @return array{mime:string,bytes:string,data_uri:string} */
    public static function png(string $spayd, int $size = 360, int $margin = 12): array
    {
        if (!class_exists(QrCode::class)) {
            throw new \RuntimeException('QR knihovna není nainstalovaná.');
        }
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('Na serveru není zapnuté PHP rozšíření GD potřebné pro QR obrázky.');
        }

        $qrCode = new QrCode(
            data: $spayd,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: $margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = (new PngWriter())->write($qrCode);
        return [
            'mime' => $result->getMimeType(),
            'bytes' => $result->getString(),
            'data_uri' => $result->getDataUri(),
        ];
    }
}
