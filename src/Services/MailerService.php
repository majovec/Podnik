<?php
namespace App\Services;
use App\Core\Env;

final class MailerService {
    private static string $lastError = '';

    private static function shell(
        string $content,
        string $name,
        ?string $actionUrl = null,
        ?string $logoPath = null,
        ?int $workspaceId = null
    ): string {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $appUrl = rtrim(Env::get('APP_URL', ''), '/');

        // PNG is used for the standard email logo because SVG support varies between mail clients.
        $logo = $appUrl . '/assets/byznio-email-logo.png';
        if ($logoPath && $workspaceId) {
            $token = hash_hmac('sha256', (string)$workspaceId, (string)Env::get('APP_KEY', ''));
            $logo = $appUrl . '/mail/logo/' . (int)$workspaceId . '?token=' . $token;
        }

        $button = $actionUrl
            ? '<p style="margin:28px 0"><a href="' . htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:12px 20px;background:#1677ee;color:#fff;text-decoration:none;border-radius:10px;font-weight:700">Otevřít v Byzniu</a></p>'
            : '';

        $footerLogo = '<img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" alt="Byznio" width="105" style="display:block;width:105px;max-width:105px;height:auto;object-fit:contain">';

        return '<!doctype html><html lang="cs"><body style="margin:0;background:#f5f8fc;font-family:Arial,sans-serif;color:#10213f">'
            . '<div style="max-width:620px;margin:32px auto;padding:0 16px">'
            . '<div style="background:#071a3a;padding:22px 26px;border-radius:18px 18px 0 0">'
            . '<img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" alt="Byznio" width="150" style="display:block;width:150px;max-width:100%;height:auto">'
            . '</div>'
            . '<div style="background:#fff;padding:30px 26px;border:1px solid #e5eaf2;border-top:0;border-radius:0 0 18px 18px">'
            . $content
            . $button
            . '<div style="margin-top:34px;padding-top:20px;border-top:1px solid #edf0f5">'
            . $footerLogo
            . '<p style="color:#7a8799;font-size:11px;margin:8px 0 0">Odesláno prostřednictvím Byznio · stačí odpovědět na tento e-mail.</p>'
            . '</div></div></div></body></html>';
    }

    private static function html(
        string $body,
        string $name,
        ?string $actionUrl = null,
        ?string $logoPath = null,
        ?int $workspaceId = null
    ): string {
        // The sign-off belongs to the individual message body; the shared template must not add it again.
        return self::shell(
            '<p style="margin-top:0">Dobrý den,</p><div style="font-size:15px;line-height:1.7">'
            . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'))
            . '</div>',
            $name,
            $actionUrl,
            $logoPath,
            $workspaceId
        );
    }

    public static function reportHtml(
        string $htmlBody,
        string $name,
        ?string $actionUrl = null,
        ?string $logoPath = null,
        ?int $workspaceId = null
    ): string {
        return self::shell($htmlBody, $name, $actionUrl, $logoPath, $workspaceId);
    }

    public static function lastError(): string { return self::$lastError; }

    public static function htmlForLog(
        string $body,
        string $name,
        ?string $actionUrl = null,
        ?string $logoPath = null,
        ?int $workspaceId = null
    ): string {
        return self::html($body, $name, $actionUrl, $logoPath, $workspaceId);
    }

    private static function deliver(
        string $to,
        string $subject,
        string $body,
        string $html,
        ?string $attachment,
        ?string $from,
        ?string $name
    ): bool {
        self::$lastError = '';
        $from = $from ?: Env::get('MAIL_FROM');
        if (!$from) { self::$lastError = 'MAIL_FROM není nastaven.'; return false; }
        $name = $name ?: Env::get('MAIL_FROM_NAME', 'Byznio');
        $transport = strtolower(trim((string)Env::get('MAIL_TRANSPORT', 'local')));
        if ($transport === 'brevo') {
            return self::deliverBrevo($to,$subject,$body,$html,$attachment,$from,$name);
        }
        return self::deliverLocal($to,$subject,$body,$html,$attachment,$from,$name);
    }

    private static function deliverBrevo(string $to,string $subject,string $body,string $html,?string $attachment,string $from,string $name): bool {
        $token=trim((string)Env::get('BREVO_API_KEY',''));
        if($token===''){self::$lastError='BREVO_API_KEY není nastaven.';return false;}
        $payload=['sender'=>['email'=>$from,'name'=>$name],'to'=>[['email'=>$to]],'subject'=>$subject,'textContent'=>$body,'htmlContent'=>$html];
        if(!empty($GLOBALS['_byznio_mail_reply_to'])){$payload['replyTo']=['email'=>$GLOBALS['_byznio_mail_reply_to']];unset($GLOBALS['_byznio_mail_reply_to']);}
        if($attachment){$file=@file_get_contents($attachment);if($file===false){self::$lastError='Přílohu se nepodařilo načíst.';return false;}$payload['attachment']=[['name'=>basename($attachment),'content'=>base64_encode($file)]];}
        $ch=curl_init('https://api.brevo.com/v3/smtp/email');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['accept: application/json','content-type: application/json','api-key: '.$token],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $response=curl_exec($ch);$errno=curl_errno($ch);$error=curl_error($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if($response===false){self::$lastError='Brevo cURL chyba '.$errno.': '.$error;return false;}
        if($http<200||$http>=300){$decoded=json_decode($response,true);self::$lastError='Brevo HTTP '.$http.': '.($decoded['message']??$decoded['code']??$response);return false;}
        return true;
    }

    private static function mimeHeader(string $value): string {
        return '=?UTF-8?B?'.base64_encode($value).'?=';
    }

    private static function buildMime(string $to,string $subject,string $body,string $html,?string $attachment,string $from,string $name,?string $replyTo): string {
        $eol="\r\n";
        $headers=[];
        $headers[]='From: '.self::mimeHeader($name).' <'.$from.'>';
        $headers[]='To: <'.$to.'>';
        $headers[]='Subject: '.self::mimeHeader($subject);
        $headers[]='Date: '.date(DATE_RFC2822);
        $headers[]='Message-ID: <'.bin2hex(random_bytes(12)).'@'.(parse_url((string)Env::get('APP_URL',''),PHP_URL_HOST)?:'byznio.cz').'>';
        if($replyTo) $headers[]='Reply-To: <'.$replyTo.'>';
        $headers[]='MIME-Version: 1.0';
        $headers[]='X-Mailer: Byznio local mail server';
        if($attachment && is_file($attachment)){
            $boundary='=_Byznio_'.bin2hex(random_bytes(12));
            $headers[]='Content-Type: multipart/mixed; boundary="'.$boundary.'"';
            $out=implode($eol,$headers).$eol.$eol;
            $out.='--'.$boundary.$eol;
            $out.='Content-Type: multipart/alternative; boundary="alt_'.$boundary.'"'.$eol.$eol;
            $out.='--alt_'.$boundary.$eol.'Content-Type: text/plain; charset=UTF-8'.$eol.'Content-Transfer-Encoding: 8bit'.$eol.$eol.$body.$eol.$eol;
            $out.='--alt_'.$boundary.$eol.'Content-Type: text/html; charset=UTF-8'.$eol.'Content-Transfer-Encoding: 8bit'.$eol.$eol.$html.$eol.$eol;
            $out.='--alt_'.$boundary.'--'.$eol.$eol;
            $data=file_get_contents($attachment);
            $out.='--'.$boundary.$eol;
            $out.='Content-Type: application/octet-stream; name="'.str_replace('"','',basename($attachment)).'"'.$eol;
            $out.='Content-Disposition: attachment; filename="'.str_replace('"','',basename($attachment)).'"'.$eol;
            $out.='Content-Transfer-Encoding: base64'.$eol.$eol.chunk_split(base64_encode((string)$data),76,$eol).$eol;
            $out.='--'.$boundary.'--'.$eol;
            return $out;
        }
        $boundary='=_ByznioAlt_'.bin2hex(random_bytes(12));
        $headers[]='Content-Type: multipart/alternative; boundary="'.$boundary.'"';
        $out=implode($eol,$headers).$eol.$eol;
        $out.='--'.$boundary.$eol.'Content-Type: text/plain; charset=UTF-8'.$eol.'Content-Transfer-Encoding: 8bit'.$eol.$eol.$body.$eol.$eol;
        $out.='--'.$boundary.$eol.'Content-Type: text/html; charset=UTF-8'.$eol.'Content-Transfer-Encoding: 8bit'.$eol.$eol.$html.$eol.$eol;
        $out.='--'.$boundary.'--'.$eol;
        return $out;
    }

    private static function deliverLocal(string $to,string $subject,string $body,string $html,?string $attachment,string $from,string $name): bool {
        $sendmail=trim((string)Env::get('SENDMAIL_PATH','/usr/sbin/sendmail'));
        if(!is_executable($sendmail)){self::$lastError='Lokální sendmail není dostupný: '.$sendmail;return false;}
        if(!filter_var($to,FILTER_VALIDATE_EMAIL)||!filter_var($from,FILTER_VALIDATE_EMAIL)){self::$lastError='Neplatná e-mailová adresa odesílatele/příjemce.';return false;}
        try{$mime=self::buildMime($to,$subject,$body,$html,$attachment,$from,$name,$GLOBALS['_byznio_mail_reply_to']??null);unset($GLOBALS['_byznio_mail_reply_to']);}catch(\Throwable $e){self::$lastError='Sestavení e-mailu selhalo: '.$e->getMessage();return false;}
        $spec=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];$proc=@proc_open([$sendmail,'-t','-oi'], $spec,$pipes);
        if(!is_resource($proc)){self::$lastError='Nepodařilo se spustit sendmail.';return false;}
        fwrite($pipes[0],$mime);fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($proc);
        if($code!==0){self::$lastError='Lokální sendmail skončil kódem '.$code.($stderr!==''?': '.trim($stderr):'');return false;}
        return true;
    }

    public static function send(
        string $to,
        string $subject,
        string $body,
        ?string $attachment = null,
        ?string $from = null,
        ?string $name = null,
        ?string $actionUrl = null,
        ?string $logoPath = null,
        ?int $workspaceId = null,
        ?string $replyTo = null
    ): bool {
        if ($replyTo) {
            $GLOBALS['_byznio_mail_reply_to'] = $replyTo;
        }
        return self::deliver(
            $to,
            $subject,
            $body,
            self::html($body, $name ?: Env::get('MAIL_FROM_NAME', 'Byznio'), $actionUrl, $logoPath, $workspaceId),
            $attachment,
            $from,
            $name
        );
    }

    public static function sendReport(
        string $to,
        string $subject,
        string $body,
        string $reportHtml,
        ?string $from = null,
        ?string $name = null,
        ?string $actionUrl = null,
        ?string $logoPath = null,
        ?int $workspaceId = null,
        ?string $replyTo = null
    ): bool {
        $name = $name ?: Env::get('MAIL_FROM_NAME', 'Byznio');
        if ($replyTo) {
            $GLOBALS['_byznio_mail_reply_to'] = $replyTo;
        }
        return self::deliver(
            $to,
            $subject,
            $body,
            self::reportHtml($reportHtml, $name, $actionUrl, $logoPath, $workspaceId),
            null,
            $from,
            $name
        );
    }
}
