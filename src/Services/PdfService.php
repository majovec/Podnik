<?php
declare(strict_types=1);
namespace App\Services;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

final class PdfService {
    private static function title(string $type): string { return ['invoice'=>'FAKTURA','offer'=>'NABÍDKA','order'=>'OBJEDNÁVKA','proforma'=>'ZÁLOHOVÁ FAKTURA','credit'=>'DOBROPIS'][$type] ?? 'DOKLAD'; }
    private static function paymentLabel(string $method): string { return ['bank_transfer'=>'Bankovním převodem','cash'=>'Hotově','card'=>'Kartou','gopay'=>'GoPay'][$method] ?? 'Bankovním převodem'; }
    private static function normalizeIban(string $value):?string{
        $v=strtoupper(preg_replace('/\s+/', '', trim($value))); if($v==='')return null;
        if(preg_match('/^CZ[0-9]{22}$/',$v)){ $check=substr($v,2,2);$bban=substr($v,4);$num=$bban.'1235'.$check;$rem=0;for($i=0,$n=strlen($num);$i<$n;$i++)$rem=(($rem*10)+(int)$num[$i])%97;return $rem===1?$v:null; }
        if(preg_match('/^(?:(\d{1,6})-)?(\d{1,10})\/(\d{4})$/',$v,$m)){ $prefix=str_pad($m[1]??'',6,'0',STR_PAD_LEFT);$account=str_pad($m[2],10,'0',STR_PAD_LEFT);$bban=$m[3].$prefix.$account;$num=$bban.'123500';$rem=0;for($i=0,$n=strlen($num);$i<$n;$i++)$rem=(($rem*10)+(int)$num[$i])%97;return 'CZ'.str_pad((string)(98-$rem),2,'0',STR_PAD_LEFT).$bban; }
        return null;
    }
    private static function accountDisplay(string $value):string{
        $v=trim($value); if($v==='')return '—';
        if(preg_match('/^(\d{1,6})-(\d{1,10})\/(\d{4})$/',$v,$m))return $m[1].'-'.$m[2].'/'.$m[3];
        if(preg_match('/^(\d{1,10})\/(\d{4})$/',$v))return $v;
        return strtoupper(preg_replace('/\s+/',' ',$v));
    }
    private static function qrDataUri(string $data):?string{
        if(!class_exists(Builder::class))return null;
        try{
            $result=(new Builder(writer:new PngWriter(),writerOptions:[],validateResult:false,data:$data,encoding:new Encoding('UTF-8'),errorCorrectionLevel:ErrorCorrectionLevel::High,size:300,margin:10,roundBlockSizeMode:RoundBlockSizeMode::Margin))->build();
            return $result->getDataUri();
        }catch(\Throwable $e){ return null; }
    }
    public static function invoice(array $doc,array $items,array $company,array $customer,?string $qrUrl=null): string {
        $esc=fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
        $type=(string)($doc['doc_type']??'invoice'); $paymentMethod=(string)($doc['payment_method']??'bank_transfer'); $paymentStatus=(string)($doc['payment_status']??'unpaid');
        $rows='';
        foreach($items as $i=>$it){$rows.='<tr><td class="num">'.($i+1).'</td><td><b>'.$esc($it['name']).'</b></td><td>'.number_format((float)$it['quantity'],2,',',' ').' '.$esc($it['unit']).'</td><td class="right">'.number_format((float)$it['unit_price'],2,',',' ').' Kč</td><td class="right strong">'.number_format((float)$it['line_total'],2,',',' ').' Kč</td></tr>';}
        $logoHtml=''; if(!empty($company['logo_path'])){ $logoFile=dirname(__DIR__,2).'/'.ltrim((string)$company['logo_path'],'/'); if(is_file($logoFile)){ $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($logoFile); if(in_array($mime,['image/png','image/jpeg','image/svg+xml'],true))$logoHtml='<img src="data:'.$mime.';base64,'.base64_encode((string)file_get_contents($logoFile)).'" class="logo" alt="Logo">'; }}
        $companyName=$esc($company['name']??'Byznio'); $customerName=$esc($customer['company_name']??trim(($customer['first_name']??'').' '.($customer['last_name']??'')));
        $customerAddress=trim((string)($customer['street']??'')); if($customerAddress!=='')$customerAddress.='<br>'; $customerAddress.=$esc(trim((string)($customer['zip']??'').' '.(string)($customer['city']??'')));
        $customerIds=[]; if(!empty($customer['ico']))$customerIds[]='IČO '.$esc($customer['ico']); if(!empty($customer['dic']))$customerIds[]='DIČ '.$esc($customer['dic']);
        $supplierIds=[]; if(!empty($company['ico']))$supplierIds[]='IČO '.$esc($company['ico']); if(!empty($company['dic']))$supplierIds[]='DIČ '.$esc($company['dic']);
        $iban=self::normalizeIban((string)($company['bank_account']??'')); $account=self::accountDisplay((string)($company['bank_account']??''));
        $qrHtml='';
        if(in_array($type,['invoice','proforma'],true) && $paymentMethod==='bank_transfer' && $paymentStatus!=='paid' && $iban){
            $spayd='SPD*1.0*ACC:'.$iban.'*AM:'.number_format((float)$doc['total_with_vat'],2,'.','').'*CC:CZK*X-VS:'.preg_replace('/\D/','',(string)($doc['variable_symbol']??''));
            if($qrUrl)$spayd.='*X-URL:'.$qrUrl;
            $src=self::qrDataUri($spayd);
            if($src)$qrHtml='<div class="qrbox"><div class="qrtitle">QR PLATBA</div><img src="'.$src.'" alt="QR platba"><div class="qrhint">Naskenujte v bankovní aplikaci</div></div>';
        }
        $bankBlock='';
        if(in_array($type,['invoice','proforma'],true)){
            $bankBlock='<table class="paytable"><tr><td><span>Úhrada</span><b>'.self::paymentLabel($paymentMethod).'</b></td><td><span>Bankovní účet</span><b>'.$esc($account).'</b></td></tr><tr><td><span>Variabilní symbol</span><b>'.$esc($doc['variable_symbol']??'').'</b></td><td><span>IBAN</span><b>'.$esc($iban?:'—').'</b></td></tr></table>';
        }
        $statusHtml=$paymentStatus==='paid'?'<span class="paid">UHRAZENO</span>':'';
        $html='<!doctype html><html lang="cs"><head><meta charset="utf-8"><style>
        @page{margin:0}body{font-family:DejaVu Sans,sans-serif;color:#17233c;font-size:10.5px;margin:0;background:#fff}.page{padding:34px 38px 30px}.accent{height:6px;background:#4f46e5;margin:-34px -38px 28px}.top{width:100%;border-collapse:collapse}.top td{vertical-align:top}.brand{width:58%}.logo{max-width:180px;max-height:52px;object-fit:contain;margin-bottom:10px}.brandfallback{font-size:20px;font-weight:900;color:#102a54;margin-bottom:10px}.eyebrow{font-size:8px;letter-spacing:2px;color:#66748a;font-weight:800;text-transform:uppercase}.title{font-size:30px;line-height:1.05;font-weight:900;color:#102a54;margin:4px 0}.number{font-size:11px;color:#617089}.meta{width:42%;text-align:right}.meta-card{background:#f5f7fb;border:1px solid #e3e8f1;border-radius:14px;padding:13px 15px;display:inline-block;text-align:left;min-width:190px}.meta-card .label{font-size:8px;color:#718096;text-transform:uppercase;letter-spacing:1px;font-weight:800}.meta-card .value{font-size:12px;font-weight:800;margin-top:3px}.pill{display:inline-block;background:#eef2ff;color:#3730a3;padding:5px 8px;border-radius:20px;font-size:8px;font-weight:800;margin-top:8px}.paid{display:inline-block;background:#eaf8ef;color:#137a43;padding:5px 9px;border-radius:20px;font-size:8px;font-weight:800;margin-left:5px}.section{margin-top:22px}.twocol{width:100%;border-collapse:separate;border-spacing:10px 0;margin-left:-10px;width:calc(100% + 20px)}.box{background:#f7f9fc;border:1px solid #e2e8f0;border-radius:14px;padding:14px;vertical-align:top;width:50%;line-height:1.55}.box .cap{font-size:8px;text-transform:uppercase;letter-spacing:1px;color:#728198;font-weight:800;margin-bottom:7px}.box .name{font-size:12px;font-weight:800;color:#17233c}.ids{font-size:9px;color:#65748b;margin-top:5px}.items{margin-top:22px;border:1px solid #dfe6f0;border-radius:14px;overflow:hidden}table.items-table{width:100%;border-collapse:collapse}th{background:#102a54;color:#fff;text-align:left;padding:10px 9px;font-size:8.5px;text-transform:uppercase;letter-spacing:.5px}td{padding:10px 9px;border-bottom:1px solid #edf0f4}tr:last-child td{border-bottom:0}.num{color:#78869b;width:26px}.right{text-align:right}.strong{font-weight:800;color:#17233c}.totals{width:100%;border-collapse:collapse;margin-top:18px}.totals td{border:0;padding:4px 0}.totals .label{text-align:right;color:#718096}.totals .value{text-align:right;width:150px;font-weight:700}.grand td{padding-top:9px;border-top:2px solid #dfe5ef}.grand .value{font-size:18px;color:#102a54}.paywrap{margin-top:20px;background:#f7f9fc;border:1px solid #e1e7ef;border-radius:16px;padding:14px}.paytable{width:100%;border-collapse:collapse}.paytable td{border:0;width:50%;padding:4px 8px 7px 0}.paytable span{display:block;color:#748198;font-size:8px;text-transform:uppercase;letter-spacing:.7px;font-weight:700}.paytable b{display:block;margin-top:3px;font-size:10.5px}.payrow{width:100%;border-collapse:collapse}.payrow td{vertical-align:top;border:0}.qrbox{width:150px;text-align:center;background:#fff;border:1px solid #e0e6ef;border-radius:14px;padding:10px}.qrbox img{width:125px;height:125px}.qrtitle{font-size:9px;font-weight:900;letter-spacing:1px;color:#102a54;margin-bottom:7px}.qrhint{font-size:7.5px;color:#718096;margin-top:4px}.footer{margin-top:28px;padding-top:12px;border-top:1px solid #e5eaf1;color:#7a8798;font-size:8px}.footer strong{color:#53647a}.smallnote{color:#7a8798;font-size:8px;margin-top:6px}
        </style></head><body><div class="page"><div class="accent"></div><table class="top"><tr><td class="brand">'.($logoHtml?:'<div class="brandfallback">BYZNIO</div>').'<div class="eyebrow">Doklad</div><div class="title">'.$esc(self::title($type)).'</div><div class="number">'.$esc($doc['doc_number']??'').' '.$statusHtml.'</div></td><td class="meta"><div class="meta-card"><div class="label">Vystaveno</div><div class="value">'.$esc($doc['issue_date']??'').'</div><div class="label" style="margin-top:9px">Splatnost</div><div class="value">'.$esc($doc['due_date']??'').'</div><div class="pill">'.self::paymentLabel($paymentMethod).'</div></div></td></tr></table>
        <div class="section"><table class="twocol"><tr><td class="box"><div class="cap">Dodavatel</div><div class="name">'.$companyName.'</div>'.$esc($company['street']??'').'<br>'.$esc(trim((string)($company['zip']??'').' '.(string)($company['city']??''))).'<div class="ids">'.implode(' · ',$supplierIds).'</div></td><td class="box"><div class="cap">Odběratel</div><div class="name">'.$customerName.'</div>'.$customerAddress.'<div class="ids">'.implode(' · ',$customerIds).'</div></td></tr></table></div>
        <div class="items"><table class="items-table"><thead><tr><th>#</th><th>Položka</th><th>Množství</th><th class="right">Jedn. cena</th><th class="right">Celkem</th></tr></thead><tbody>'.$rows.'</tbody></table></div>
        <table class="totals"><tr><td class="label">Mezisoučet bez DPH</td><td class="value">'.number_format((float)$doc['total_without_vat'],2,',',' ').' Kč</td></tr><tr><td class="label">DPH</td><td class="value">'.number_format((float)$doc['total_vat'],2,',',' ').' Kč</td></tr><tr class="grand"><td class="label">K úhradě</td><td class="value">'.number_format((float)$doc['total_with_vat'],2,',',' ').' Kč</td></tr></table>
        '.($bankBlock!==''?'<div class="paywrap"><table class="payrow"><tr><td>'.$bankBlock.'</td><td style="width:170px">'.$qrHtml.'</td></tr></table></div>':'').'
        <div class="footer"><strong>'.$companyName.'</strong> · Doklad vytvořen systémem Byznio · '.$esc($company['email']??'').' '.(!empty($company['phone'])?'· '.$esc($company['phone']):'').'</div></div></body></html>';
        $o=new Options();$o->set('defaultFont','DejaVu Sans');$o->set('isRemoteEnabled',false);$o->set('isHtml5ParserEnabled',true);$d=new Dompdf($o);$d->loadHtml($html,'UTF-8');$d->setPaper('A4');$d->render();return $d->output();
    }
}
