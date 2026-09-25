<?php
namespace App\Services;

use App\Core\Env;
use PDO;

final class InboundMailService {

    public static function handleRawMessage(PDO $db, string $raw, string $recipient): array {
        $domain=strtolower(trim((string)Env::get('MAIL_DOMAIN','byznio.cz')));
        $recipient=strtolower(trim($recipient));
        $parts=explode('@',$recipient,2);
        if(count($parts)!==2 || strtolower($parts[1])!==$domain) throw new \RuntimeException('Neplatný příjemce.');
        $local=$parts[0];
        $q=$db->prepare('SELECT id FROM workspaces WHERE lower(email_localpart)=? AND COALESCE(mail_enabled,1)=1 LIMIT 1');$q->execute([$local]);$wid=(int)($q->fetchColumn()?:0);
        if(!$wid) throw new \RuntimeException('Neznámá Byznio adresa: '.$recipient);
        $parsed=self::parseRaw($raw);
        $from=strtolower(trim($parsed['from']));$subject=trim($parsed['subject']);$messageId=trim($parsed['message_id']);
        if($from==='') $from='unknown@invalid.local';
        $exists=$messageId!==''?$db->prepare("SELECT id FROM email_messages WHERE workspace_id=? AND direction='inbound' AND message_id=? LIMIT 1"):null;
        if($exists){$exists->execute([$wid,$messageId]);if($exists->fetchColumn())return ['workspace_id'=>$wid,'message_id'=>$messageId,'attachments'=>0,'duplicate'=>true];}
        $cq=$db->prepare('SELECT id FROM customers WHERE workspace_id=? AND lower(email)=? LIMIT 1');$cq->execute([$wid,$from]);$customerId=(int)($cq->fetchColumn()?:0);
        $db->prepare('INSERT INTO email_messages(workspace_id,customer_id,direction,from_email,to_email,subject,body,html_body,message_id) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,$customerId?:null,'inbound',$from,$recipient,$subject,$parsed['text'],$parsed['html'],$messageId?:null]);
        if($customerId)$db->prepare('INSERT INTO customer_communications(workspace_id,customer_id,channel,direction,subject,message,created_by) VALUES(?,?,?,?,?,?,NULL)')->execute([$wid,$customerId,'email','inbound',$subject,$parsed['text']]);
        $count=0;
        foreach($parsed['attachments'] as $att){
            $name=(string)$att['name'];$mime=strtolower((string)$att['mime']);$data=$att['data'];
            if(!self::isInvoiceAttachment($name,$mime)||strlen($data)>15*1024*1024)continue;
            $dir=dirname(__DIR__,2).'/storage/received-invoices/'.$wid;if(!is_dir($dir))@mkdir($dir,0775,true);
            $safe=preg_replace('/[^a-zA-Z0-9._-]+/','_',basename($name))?:'invoice';$path=$dir.'/'.bin2hex(random_bytes(16)).'_'.$safe;if(file_put_contents($path,$data,LOCK_EX)===false)continue;
            $sourceId=($messageId!==''?$messageId:'local:'.hash('sha256',$from.'|'.$recipient.'|'.$subject.'|'.hash('sha256',$data))).':'.hash('sha256',$name.'|'.hash('sha256',$data));
            $dupe=$db->prepare('SELECT id FROM received_invoices WHERE workspace_id=? AND source_email_message_id=? LIMIT 1');$dupe->execute([$wid,$sourceId]);if($dupe->fetchColumn()){unlink($path);continue;}
            $db->prepare('INSERT INTO received_invoices(workspace_id,supplier,document_number,payment_status,attachment_path,ocr_status,source_type,source_email_message_id,note) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,$from,null,'unpaid',$path,'pending','email',$sourceId,'Přijato na '. $recipient .'. Automatické načtení údajů proběhne na pozadí.']);$count++;
        }
        return ['workspace_id'=>$wid,'message_id'=>$messageId,'attachments'=>$count,'duplicate'=>false];
    }

    private static function parseRaw(string $raw): array {
        [$headerText,$body]=array_pad(preg_split("/\r?\n\r?\n/",$raw,2),2,'');
        $headers=[]; $current='';
        foreach(preg_split("/\r?\n/",$headerText) as $line){
            if($line!=='' && ($line[0]===' '||$line[0]==="\t") && $current!==''){ $headers[$current].=' '.trim($line); continue; }
            $pos=strpos($line,':'); if($pos===false)continue;$current=strtolower(trim(substr($line,0,$pos)));$headers[$current]=trim(substr($line,$pos+1));
        }
        $decodeHeader=function(string $v):string{if(function_exists('iconv_mime_decode')){$x=@iconv_mime_decode($v,ICONV_MIME_DECODE_CONTINUE_ON_ERROR,'UTF-8');if($x!==false)return $x;}return $v;};
        $from=''; if(preg_match('/<([^>]+)>/',$headers['from']??'',$m))$from=strtolower(trim($m[1]));else{$from=strtolower(trim($headers['from']??''));}
        if(preg_match('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+)$/i',$from,$m))$from=strtolower($m[1]);
        $subject=$decodeHeader($headers['subject']??'');$messageId=trim($headers['message-id']??'');
        $tree=self::parsePart($headers,$body);return ['from'=>$from,'subject'=>$subject,'message_id'=>$messageId,'text'=>$tree['text'],'html'=>$tree['html'],'attachments'=>$tree['attachments']];
    }

    private static function parsePart(array $headers,string $body):array {
        $result=['text'=>'','html'=>'','attachments'=>[]];$ctype=strtolower($headers['content-type']??'text/plain');$disp=strtolower($headers['content-disposition']??'');
        if(preg_match('/boundary\s*=\s*(?:"([^"]+)"|([^;]+))/i',$ctype,$m)){
            $boundary=$m[1]??$m[2];$chunks=explode('--'.$boundary,$body);
            foreach($chunks as $chunk){$chunk=ltrim($chunk,"\r\n");if($chunk===''||str_starts_with($chunk,'--'))continue;[$h,$b]=array_pad(preg_split("/\r?\n\r?\n/",$chunk,2),2,'');$ph=[];$cur='';foreach(preg_split("/\r?\n/",$h) as $line){if($line!==''&&($line[0]===' '||$line[0]==="\t")&&$cur!==''){$ph[$cur].=' '.trim($line);continue;}$pos=strpos($line,':');if($pos===false)continue;$cur=strtolower(trim(substr($line,0,$pos)));$ph[$cur]=trim(substr($line,$pos+1));}$sub=self::parsePart($ph,$b);$result['text'].=$sub['text'];$result['html'].=$sub['html'];$result['attachments']=array_merge($result['attachments'],$sub['attachments']);}
            return $result;
        }
        $encoding=strtolower(trim($headers['content-transfer-encoding']??''));$decoded=$encoding==='base64'?base64_decode(preg_replace('/\s+/','',$body),true):($encoding==='quoted-printable'?quoted_printable_decode($body):$body);if($decoded===false)$decoded=$body;
        $name='';if(preg_match('/filename\s*=\s*(?:"([^"]+)"|([^;\s]+))/i',$headers['content-disposition']??'',$m))$name=($m[1]??$m[2]);if($name==='')if(preg_match('/name\s*=\s*(?:"([^"]+)"|([^;\s]+))/i',$ctype,$m))$name=($m[1]??$m[2]);
        if($name!==''||str_contains($disp,'attachment')){$result['attachments'][]=['name'=>$name!==''?$name:'attachment','mime'=>trim(explode(';',$ctype,2)[0]),'data'=>$decoded];return $result;}
        if(str_starts_with($ctype,'text/html'))$result['html'].=$decoded;else $result['text'].=$decoded;return $result;
    }

    public static function handleBrevoWebhook(PDO $db, array $payload): array {
        self::authorize();
        $items = $payload['items'] ?? [];
        if (!is_array($items)) throw new \RuntimeException('Neplatný Brevo payload.');
        $result = ['processed'=>0,'invoices'=>0,'ignored'=>0];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $result['processed']++;
            $result['invoices'] += self::processItem($db, $item, $result);
        }
        return $result;
    }

    private static function authorize(): void {
        $expected = trim((string)Env::get('BREVO_INBOUND_WEBHOOK_TOKEN',''));
        if ($expected === '') throw new \RuntimeException('BREVO_INBOUND_WEBHOOK_TOKEN není nastaven.');
        $provided = (string)($_SERVER['HTTP_X_BYZNIO_INBOUND_TOKEN'] ?? '');
        if ($provided === '' && isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/^Bearer\s+(.+)$/i', (string)$_SERVER['HTTP_AUTHORIZATION'], $m)) $provided = trim($m[1]);
        if ($provided === '' || !hash_equals($expected, $provided)) {
            http_response_code(401);
            exit;
        }
    }

    private static function processItem(PDO $db, array $item, array &$result): int {
        $from = strtolower(trim((string)($item['From']['Address'] ?? '')));
        $subject = trim((string)($item['Subject'] ?? ''));
        $body = (string)($item['ExtractedMarkdownMessage'] ?? $item['RawTextBody'] ?? '');
        $html = (string)($item['RawHtmlBody'] ?? '');
        $messageId = trim((string)($item['MessageId'] ?? ''));
        $recipients = self::recipientAddresses($item);
        if ($from === '' || !$recipients) { $result['ignored']++; return 0; }
        $invoiceCount = 0;
        foreach ($recipients as $recipient) {
            $parts = explode('@', $recipient, 2);
            if (count($parts)!==2) continue;
            [$local,$domain] = $parts;
            $configuredDomain = strtolower(trim((string)Env::get('MAIL_INBOUND_DOMAIN','')));
            if ($configuredDomain !== '' && strtolower($domain) !== $configuredDomain) continue;
            $q=$db->prepare('SELECT id FROM workspaces WHERE lower(email_localpart)=? LIMIT 1');
            $q->execute([strtolower($local)]);
            $wid=(int)($q->fetchColumn()?:0);
            if (!$wid) continue;
            // Idempotency: Brevo can retry a webhook. Store the message once per workspace.
            $exists=$db->prepare("SELECT id FROM email_messages WHERE workspace_id=? AND direction='inbound' AND message_id=? LIMIT 1");
            $exists->execute([$wid,$messageId]);
            $messageExists = $messageId !== '' && (bool)$exists->fetchColumn();
            $customerId=0;
            $cq=$db->prepare('SELECT id FROM customers WHERE workspace_id=? AND lower(email)=? LIMIT 1');
            $cq->execute([$wid,$from]); $customerId=(int)($cq->fetchColumn()?:0);
            if (!$messageExists) $db->prepare('INSERT INTO email_messages(workspace_id,customer_id,direction,from_email,to_email,subject,body,html_body,message_id) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,$customerId?:null,'inbound',$from,$recipient,$subject,$body,$html,$messageId?:null]);
            if ($customerId) $db->prepare('INSERT INTO customer_communications(workspace_id,customer_id,channel,direction,subject,message,created_by) VALUES(?,?,?,?,?,?,NULL)')->execute([$wid,$customerId,'email','inbound',$subject,$body]);

            $attachments=$item['Attachments']??[];
            if (!is_array($attachments)) continue;
            foreach($attachments as $attachment){
                if(!is_array($attachment)) continue;
                $name=(string)($attachment['Name']??'invoice');
                $mime=strtolower((string)($attachment['ContentType']??''));
                $token=trim((string)($attachment['DownloadToken']??''));
                $length=(int)($attachment['ContentLength']??0);
                if($token==='' || !self::isInvoiceAttachment($name,$mime) || ($length>15*1024*1024)) continue;
                $path=self::downloadAttachment($token,$name,$wid);
                $sourceId=$messageId!=='' ? $messageId.':'.hash('sha256',$name.'|'.$token) : 'brevo:'.hash('sha256',$wid.'|'.$from.'|'.$subject.'|'.$name.'|'.$token);
                $dupe=$db->prepare('SELECT id FROM received_invoices WHERE workspace_id=? AND source_email_message_id=? LIMIT 1');
                $dupe->execute([$wid,$sourceId]);
                if($dupe->fetchColumn()){ @unlink($path); continue; }
                $db->prepare('INSERT INTO received_invoices(workspace_id,supplier,document_number,payment_status,attachment_path,ocr_status,source_type,source_email_message_id,note) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,trim((string)($item['From']['Name']??''))?:$from,null,'unpaid',$path,'pending','email',$sourceId,'Přijato e-mailem. Automatické načtení údajů proběhne na pozadí.']);
                $invoiceCount++;
            }
        }
        return $invoiceCount;
    }

    private static function recipientAddresses(array $item): array {
        $out=[];
        foreach(['To','Recipients','Cc'] as $key){
            $value=$item[$key]??[];
            if(!is_array($value)) $value=[$value];
            foreach($value as $mail){
                $address=is_array($mail)?(string)($mail['Address']??''):(string)$mail;
                $address=strtolower(trim($address));
                if(filter_var($address,FILTER_VALIDATE_EMAIL)) $out[$address]=true;
            }
        }
        return array_keys($out);
    }

    private static function isInvoiceAttachment(string $name,string $mime): bool {
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        return in_array($mime,['application/pdf','image/jpeg','image/png','image/tiff','image/webp'],true) || in_array($ext,['pdf','jpg','jpeg','png','tif','tiff','webp'],true);
    }

    private static function downloadAttachment(string $token,string $name,int $wid): string {
        $api=trim((string)Env::get('BREVO_API_KEY',''));
        if($api==='') throw new \RuntimeException('BREVO_API_KEY není nastaven.');
        $safe=preg_replace('/[^a-zA-Z0-9._-]+/','_',basename($name))?:'invoice';
        $dir=dirname(__DIR__,2).'/storage/received-invoices/'.$wid;
        if(!is_dir($dir)) @mkdir($dir,0775,true);
        $path=$dir.'/'.bin2hex(random_bytes(16)).'_'.$safe;
        $ch=curl_init('https://api.brevo.com/v3/inbound/attachments/'.rawurlencode($token));
        $fp=fopen($path,'wb');
        curl_setopt_array($ch,[CURLOPT_FILE=>$fp,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_RETURNTRANSFER=>false,CURLOPT_HTTPHEADER=>['accept: application/octet-stream','api-key: '.$api],CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>60,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $ok=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch); fclose($fp);
        if(!$ok || $code<200 || $code>=300 || !is_file($path) || filesize($path)<=0){ @unlink($path); throw new \RuntimeException('Stažení přílohy z Brevo selhalo (HTTP '.$code.'): '.$err); }
        return $path;
    }
}
