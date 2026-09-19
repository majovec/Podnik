<?php
namespace App\Services;
use App\Core\{Database,Env};
final class GoPayService {
    private static function base():string{return rtrim(Env::get('GOPAY_BASE_URL','https://gw.sandbox.gopay.com/api'),'/');}
    private static function account(int $workspaceId):array{
        $s=Database::pdo()->prepare('SELECT * FROM gopay_accounts WHERE workspace_id=? AND active=1 LIMIT 1');$s->execute([$workspaceId]);$a=$s->fetch();
        if(!$a)throw new \RuntimeException('Tato firma nemá připojený GoPay účet. Připojte jej v Nastavení.');
        return [BankTokenService::decrypt($a['goid_encrypted']),BankTokenService::decrypt($a['client_id_encrypted']),BankTokenService::decrypt($a['client_secret_encrypted'])];
    }
    private static function token(int $workspaceId):string{
        [, $id, $secret]=self::account($workspaceId);
        $ch=curl_init(self::base().'/oauth2/token');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_USERPWD=>$id.':'.$secret,CURLOPT_POSTFIELDS=>http_build_query(['grant_type'=>'client_credentials','scope'=>'payment-create','gopay-gw-url'=>self::base()]),CURLOPT_HTTPHEADER=>['Accept: application/json','Content-Type: application/x-www-form-urlencoded'],CURLOPT_TIMEOUT=>30]);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$j=json_decode((string)$raw,true);if($code>=400||empty($j['access_token']))throw new \RuntimeException('GoPay OAuth chyba.');return $j['access_token'];
    }
    public static function isConnected(int $workspaceId):bool{$s=Database::pdo()->prepare('SELECT COUNT(*) FROM gopay_accounts WHERE workspace_id=? AND active=1');$s->execute([$workspaceId]);return (int)$s->fetchColumn()>0;}
    public static function createPayment(int $workspaceId,array $data):array{
        [$goid]=self::account($workspaceId);$token=self::token($workspaceId);$ch=curl_init(self::base().'/payments/payment');$h=['Accept: application/json','Content-Type: application/json','Authorization: Bearer '.$token];$data['target']['type']='ACCOUNT';$data['target']['goid']=(int)$goid;curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>$h,CURLOPT_POSTFIELDS=>json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_TIMEOUT=>30]);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$j=json_decode((string)$raw,true);if($code>=400||!is_array($j))throw new \RuntimeException('GoPay chyba HTTP '.$code);return $j;
    }
    public static function gatewayUrl(array $payment):?string{foreach(($payment['gw_url']??[]) as $u){if(is_string($u))return $u;}return is_string($payment['gw_url']??null)?$payment['gw_url']:null;}
    public static function getPayment(int $workspaceId,int $id):array{$token=self::token($workspaceId);$ch=curl_init(self::base().'/payments/payment/'.$id);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Accept: application/json','Authorization: Bearer '.$token],CURLOPT_TIMEOUT=>30]);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$j=json_decode((string)$raw,true);if($code>=400||!is_array($j))throw new \RuntimeException('GoPay status HTTP '.$code);return $j;}
}
