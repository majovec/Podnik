<?php
namespace App\Services;
use PDO;
final class CnbRateService{
 public static function get(PDO $db,string $currency,string $date):?float{
  $currency=strtoupper(trim($currency));if($currency==='CZK')return 1.0;$q=$db->prepare('SELECT rate FROM currency_rates WHERE currency=? AND rate_date=?');$q->execute([$currency,$date]);$cached=$q->fetchColumn();if($cached!==false)return (float)$cached;
  $url='https://www.cnb.cz/en/financial-markets/foreign-exchange-market/exchange-rate-fixing/daily.txt?date='.rawurlencode($date);$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_USERAGENT=>'Byznio/1.0']);$body=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);if($body===false||$http!==200)return null;
  foreach(preg_split('/\r\n|\n|\r/',$body) as $line){$parts=explode('|',$line);if(count($parts)>=5&&strtoupper(trim($parts[3]))===$currency){$rate=(float)str_replace(',','.',$parts[4]);$amount=max(1,(float)$parts[2]);$rate=$rate/$amount;$db->prepare('INSERT OR REPLACE INTO currency_rates(currency,rate_date,rate,source) VALUES(?,?,?,?)')->execute([$currency,$date,$rate,'CNB']);return $rate;}}
  return null;
 }
}
