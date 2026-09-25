<?php
namespace App\Services;
final class ComgateService{
 public static function create(string $merchant,string $secret,int $amount,string $currency,string $refId,string $label,string $returnUrl,string $notificationUrl):array{
  $payload=http_build_query(['merchant'=>$merchant,'secret'=>$secret,'price'=>$amount,'curr'=>$currency,'label'=>$label,'refId'=>$refId,'method'=>'ALL','prepareOnly'=>0,'return2return'=>$returnUrl,'notifyUrl'=>$notificationUrl]);$ch=curl_init('https://payments.comgate.cz/v2.0/create');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);$body=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);if($body===false||$http<200||$http>=300)return ['ok'=>false,'error'=>$err?:'Comgate HTTP '.$http];parse_str((string)$body,$r);if(($r['code']??'')!=='0')return ['ok'=>false,'error'=>$r['message']??$r['code']??'Comgate chyba'];return ['ok'=>true,'transId'=>$r['transId']??'','redirect'=>$r['redirect']??''];
 }
}
