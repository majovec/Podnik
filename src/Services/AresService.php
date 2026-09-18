<?php
namespace App\Services;
final class AresService {
    public static function lookup(string $ico): ?array {
        $ico=preg_replace('/\D/','',$ico); if(strlen($ico)!==8)return null;
        $ch=curl_init('https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/'.$ico);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>6,CURLOPT_HTTPHEADER=>['Accept: application/json']]);
        $raw=curl_exec($ch); curl_close($ch); $d=json_decode($raw?:'',true);
        if(!$d)return null;
        $sidlo=$d['sidlo']??[];
        return ['ico'=>$d['ico']??$ico,'company_name'=>$d['obchodniJmeno']??'','dic'=>$d['dic']??'',
                'street'=>trim(($sidlo['nazevUlice']??'').' '.($sidlo['cisloDomovni']??'')),
                'city'=>$sidlo['nazevObce']??'','zip'=>(string)($sidlo['psc']??'')];
    }
}
