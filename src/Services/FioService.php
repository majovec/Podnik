<?php
namespace App\Services;
use App\Core\Env;
use PDO;
final class FioService {
    public static function movements(string $token, string $from, string $to): array {
        $url='https://fioapi.fio.cz/v1/rest/periods/'.$from.'/'.$to.'/transactions.json?'.$token;
        return self::getJson($url);
    }
    public static function movementsFromLast(string $token): array {
        return self::getJson('https://fioapi.fio.cz/v1/rest/last/'.$token.'/transactions.json');
    }
    private static function getJson(string $url): array {
        $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Accept: application/json']]);
        $body=curl_exec($ch); $err=curl_error($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
        if($body===false||$code>=400) throw new \RuntimeException('Fio API chyba HTTP '.$code.': '.($err?:$body));
        $data=json_decode($body,true); if(!is_array($data)) throw new \RuntimeException('Fio API vrátilo neplatný JSON.'); return $data;
    }
    public static function normalize(array $data): array {
        $rows=$data['accountStatement']['transactionList']['transaction']??$data['accountStatement']['transactionList']??[]; if(isset($rows['column22'])) $rows=[$rows];
        $out=[]; foreach($rows as $r){
            $out[]=['booked_at'=>$r['column0']['value']??null,'amount'=>(float)($r['column1']['value']??0),'currency'=>$r['column14']['value']??'CZK','counterparty'=>$r['column10']['value']??($r['column16']['value']??''),'account_number'=>$r['column2']['value']??null,'variable_symbol'=>preg_replace('/\D/','',(string)($r['column5']['value']??'')),'constant_symbol'=>preg_replace('/\D/','',(string)($r['column4']['value']??'')),'specific_symbol'=>preg_replace('/\D/','',(string)($r['column6']['value']??'')),'reference'=>$r['column22']['value']??null,'message'=>$r['column16']['value']??($r['column25']['value']??''),'external_id'=>(string)($r['column17']['value']??($r['column22']['value']??sha1(json_encode($r))) ),'raw_json'=>json_encode($r,JSON_UNESCAPED_UNICODE)];
        } return $out;
    }
}
