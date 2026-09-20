<?php
namespace App\Services;
use App\Core\Env;
final class SaltEdgeService {
    private static function req(string $method,string $path,array $body=[]):array{
        $id=Env::get('SALTEDGE_APP_ID');$secret=Env::get('SALTEDGE_SECRET');
        if(!$id||!$secret) throw new \RuntimeException('Salt Edge není nakonfigurován.');
        $ch=curl_init('https://www.saltedge.com/api/v6'.$path);
        $h=['Accept: application/json','Content-Type: application/json','App-id: '.$id,'Secret: '.$secret];
        $o=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>60,CURLOPT_HTTPHEADER=>$h,CURLOPT_CUSTOMREQUEST=>$method];
        if($body){$o[CURLOPT_POSTFIELDS]=json_encode(['data'=>$body],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
        curl_setopt_array($ch,$o);$raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
        $j=json_decode((string)$raw,true);if($raw===false||$code>=400)throw new \RuntimeException('Salt Edge HTTP '.$code.': '.(($j['error']['message']??null) ?: ($err ?: $raw)));return is_array($j)?$j:[];
    }
    public static function connectSession(string $customerId,string $returnTo,string $country='cz'):array{
        return self::req('POST','/connections/connect',['customer_id'=>(string)$customerId,'country_code'=>strtoupper($country),'consent'=>['scopes'=>['accounts','transactions'],'from_date'=>date('Y-m-d',strtotime('-365 days'))],'attempt'=>['return_to'=>$returnTo]]);
    }
    public static function customer(string $identifier):array{return self::req('POST','/customers',['identifier'=>$identifier]);}
    public static function connections(string $customerId):array{return self::req('GET','/connections?customer_id='.rawurlencode($customerId));}
    public static function accounts(string $connectionId):array{return self::req('GET','/accounts?connection_id='.rawurlencode($connectionId));}
    public static function transactions(string $connectionId,string $accountId):array{return self::req('GET','/transactions?connection_id='.rawurlencode($connectionId).'&account_id='.rawurlencode($accountId));}
    public static function refresh(string $connectionId):array{return self::req('POST','/connections/'.rawurlencode($connectionId).'/refresh',['fetch_scopes'=>['accounts','transactions']]);}
    public static function providers(string $country='cz'):array{return self::req('GET','/providers?country_code='.rawurlencode(strtoupper($country)));}
}
