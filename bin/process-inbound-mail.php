<?php
declare(strict_types=1);
// Postfix pipe entrypoint. Reads one raw RFC822 message from STDIN and stores
// invoice attachments + communication metadata directly in Byznio.
$root=dirname(__DIR__);
require $root.'/vendor/autoload.php';
require $root.'/src/bootstrap.php';
use App\Core\Database;
use App\Core\Env;
use App\Services\InboundMailService;

$recipient='';
foreach($argv as $arg){ if(str_starts_with($arg,'--recipient=')){ $recipient=trim(substr($arg,11)); } }
if($recipient==='') $recipient=trim((string)(getenv('ORIGINAL_RECIPIENT')?:getenv('RECIPIENT')?:''));
$raw=file_get_contents('php://stdin');
if($raw===false || strlen($raw)===0) exit(75);
$domain=strtolower(trim((string)Env::get('MAIL_DOMAIN','byznio.cz')));
if(!filter_var($recipient,FILTER_VALIDATE_EMAIL) || !str_ends_with(strtolower($recipient),'@'.$domain)) exit(0);
try {
    $result=InboundMailService::handleRawMessage(Database::pdo(),$raw,$recipient);
    error_log('Byznio inbound mail: '.json_encode($result,JSON_UNESCAPED_UNICODE));
    exit(0);
} catch(Throwable $e){
    error_log('Byznio inbound mail failed: '.$e->getMessage());
    exit(75); // temporary failure -> Postfix can retry instead of silently losing mail
}
