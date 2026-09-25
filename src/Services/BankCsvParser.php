<?php
namespace App\Services;

final class BankCsvParser
{
    public static function parse(string $path): array
    {
        $raw=(string)@file_get_contents($path);
        if($raw==='') return [];
        $raw=mb_convert_encoding($raw,'UTF-8','UTF-8,Windows-1250,ISO-8859-2');
        $sample=preg_split('/\r\n|\n|\r/',$raw,3)[0]??'';
        $delimiter=substr_count($sample,';')>=substr_count($sample,',')?';':',';
        $fh=fopen('php://memory','r+');fwrite($fh,$raw);rewind($fh);
        $header=fgetcsv($fh,0,$delimiter); if(!$header) return [];
        $norm=fn($v)=>mb_strtolower(trim((string)$v));
        $h=array_map($norm,$header);
        $map=[];
        foreach($h as $i=>$v){
            if($v===''||isset($map[$v])) continue; $map[$v]=$i;
        }
        $find=function(array $aliases)use($map,$h){foreach($aliases as $a){$a=mb_strtolower($a);if(isset($map[$a]))return $map[$a];}foreach($map as $k=>$i){foreach($aliases as $a){if($a!==''&&mb_stripos($k,$a)!==false)return $i;}}return null;};
        $idx=[
            'date'=>$find(['datum','datum zaúčtování','booked at','booking date','date']),
            'amount'=>$find(['částka','částka v měně účtu','amount','transaction amount','sum']),
            'currency'=>$find(['měna','currency']),
            'counterparty'=>$find(['název protiúčtu','protistrana','název','counterparty','name']),
            'account_number'=>$find(['protiúčet','číslo účtu','account number','account']),
            'variable_symbol'=>$find(['variabilní symbol','vs','variable symbol']),
            'constant_symbol'=>$find(['konstantní symbol','ks','constant symbol']),
            'specific_symbol'=>$find(['specifický symbol','ss','specific symbol']),
            'message'=>$find(['zpráva pro příjemce','poznámka','message','description','reference']),
            'external_id'=>$find(['id transakce','id','transaction id','external id','identifikátor']),
        ];
        $out=[];
        while(($row=fgetcsv($fh,0,$delimiter))!==false){
            if(count($row)<2)continue;
            $get=fn($k)=>$idx[$k]!==null&&isset($row[$idx[$k]])?trim((string)$row[$idx[$k]]):'';
            $amount=self::amount($get('amount')); if($amount===null)continue;
            $date=self::date($get('date')); if(!$date)continue;
            $external=$get('external_id');
            $data=[
                'booked_at'=>$date,'amount'=>$amount,'currency'=>$get('currency')?:'CZK','counterparty'=>$get('counterparty'),
                'account_number'=>$get('account_number'),'variable_symbol'=>preg_replace('/\D/','',$get('variable_symbol')),
                'constant_symbol'=>preg_replace('/\D/','',$get('constant_symbol')),'specific_symbol'=>preg_replace('/\D/','',$get('specific_symbol')),
                'message'=>$get('message'),'external_id'=>$external,'raw'=>$row
            ];
            if($external===''){$data['external_id']=hash('sha256',implode('|',[$date,number_format($amount,2,'.',''),$data['variable_symbol'],$data['counterparty'],$data['account_number'],$data['message']]));}
            $out[]=$data;
        }
        fclose($fh); return $out;
    }
    private static function amount(string $v):?float{
        $v=trim(str_replace(["\xC2\xA0",' '],'',$v));if($v==='')return null;
        if(str_contains($v,',')&&str_contains($v,'.')){$v=strrpos($v,',')>strrpos($v,'.')?str_replace('.','',str_replace(',','.',$v)):str_replace(',','',$v);}else{$v=str_replace(',','.',$v);}
        $v=preg_replace('/[^0-9.\-+]/','',$v);return is_numeric($v)?(float)$v:null;
    }
    private static function date(string $v):?string{
        $v=trim($v);if($v==='')return null;
        foreach(['Y-m-d','d.m.Y','d/m/Y','d-m-Y','Y/m/d'] as $f){$d=\DateTimeImmutable::createFromFormat($f,$v);if($d&&$d->format($f)===$v)return $d->format('Y-m-d');}
        $t=strtotime($v);return $t!==false?date('Y-m-d',$t):null;
    }
}
