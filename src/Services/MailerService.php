<?php
namespace App\Services;
use App\Core\Env;
final class MailerService {
    public static function send(string $to,string $subject,string $body,?string $attachment=null,?string $from=null,?string $name=null):bool {
        $from=$from?:Env::get('MAIL_FROM'); if(!$from)return false; $name=$name?:Env::get('MAIL_FROM_NAME','Byznio');
        if(!$attachment){$headers='From: '.sprintf('"%s" <%s>',$name,$from)."\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";return mail($to,$subject,$body,$headers);}
        $file=@file_get_contents($attachment);if($file===false)return false;$boundary=bin2hex(random_bytes(12));$headers='From: '.sprintf('"%s" <%s>',$name,$from)."\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$boundary\"\r\n";$content="--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n$body\r\n--$boundary\r\nContent-Type: application/pdf; name=\"".basename($attachment)."\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"".basename($attachment)."\"\r\n\r\n".chunk_split(base64_encode($file))."\r\n--$boundary--";return mail($to,$subject,$content,$headers);
    }
}
