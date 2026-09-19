<?php
namespace App\Services;
use Dompdf\Dompdf;
use Dompdf\Options;
final class PdfService {
    private static function title(string $type): string { return ['invoice'=>'FAKTURA','offer'=>'NABÍDKA','order'=>'OBJEDNÁVKA','proforma'=>'ZÁLOHOVÁ FAKTURA','credit'=>'DOBROPIS'][$type] ?? 'DOKLAD'; }
    public static function invoice(array $doc,array $items,array $company,array $customer): string {
        $esc=fn($x)=>htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');
        $rows='';
        foreach($items as $i=>$it)$rows.='<tr><td>'.($i+1).'</td><td>'.$esc($it['name']).'</td><td>'.$esc($it['quantity']).' '.$esc($it['unit']).'</td><td>'.number_format((float)$it['unit_price'],2,',',' ').'</td><td>'.number_format((float)$it['line_total'],2,',',' ').'</td></tr>';
        $logoHtml='';
        if(!empty($company['logo_path'])){
            $logoFile=dirname(__DIR__,2).'/'.ltrim((string)$company['logo_path'],'/');
            if(is_file($logoFile)){
                $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($logoFile);
                if(in_array($mime,['image/png','image/jpeg','image/svg+xml'],true)) $logoHtml='<img src="data:'.$mime.';base64,'.base64_encode((string)file_get_contents($logoFile)).'" style="max-width:180px;max-height:58px;object-fit:contain;margin-bottom:8px" alt="Logo">';
            }
        }
        $html='<!doctype html><html lang="cs"><meta charset="utf-8"><style>
        body{font-family:DejaVu Sans,sans-serif;color:#172033;font-size:12px;margin:38px}
        .top{display:flex;justify-content:space-between;border-bottom:3px solid #111827;padding-bottom:24px}
        h1{font-size:30px;margin:0}.muted{color:#667085}.grid{display:flex;gap:50px;margin:30px 0}.box{flex:1}
        table{width:100%;border-collapse:collapse;margin-top:25px}th{background:#111827;color:#fff;text-align:left;padding:9px}td{border-bottom:1px solid #e5e7eb;padding:9px}
        .total{margin-left:auto;width:260px;margin-top:22px}.line{display:flex;justify-content:space-between;padding:6px}.grand{font-size:19px;font-weight:800;border-top:2px solid #111827;padding-top:12px}
        </style><div class="top"><div>'.$logoHtml.'<div class="muted">BYZNIO</div><h1>'.$esc(self::title($doc['doc_type']??'invoice')).'</h1><div>'.$esc($doc['doc_number']).'</div></div><div><b>'.$esc($company['name']??'').'</b><br>'.$esc($company['street']??'').'<br>'.$esc(($company['zip']??'').' '.($company['city']??'')).'<br>IČO '.$esc($company['ico']??'').'</div></div>
        <div class="grid"><div class="box"><b>Odběratel</b><br>'.$esc($customer['company_name']??($customer['first_name'].' '.$customer['last_name'])).'<br>'.$esc($customer['street']??'').'<br>'.$esc(($customer['zip']??'').' '.($customer['city']??'')).'</div><div class="box"><b>Vystaveno:</b> '.$esc($doc['issue_date']).'<br><b>Splatnost:</b> '.$esc($doc['due_date']).'<br><b>VS:</b> '.$esc($doc['variable_symbol']).'</div></div>
        <table><thead><tr><th>#</th><th>Položka</th><th>Množství</th><th>Jedn. cena</th><th>Celkem</th></tr></thead><tbody>'.$rows.'</tbody></table>
        <div class="total"><div class="line"><span>Bez DPH</span><b>'.number_format((float)$doc['total_without_vat'],2,',',' ').' Kč</b></div><div class="line"><span>DPH</span><b>'.number_format((float)$doc['total_vat'],2,',',' ').' Kč</b></div><div class="line grand"><span>K úhradě</span><span>'.number_format((float)$doc['total_with_vat'],2,',',' ').' Kč</span></div></div>
        <p class="muted" style="margin-top:60px">Dokument vytvořen systémem Byznio.</p></html>';
        $o=new Options();$o->set('defaultFont','DejaVu Sans');$o->set('isRemoteEnabled',false);
        $d=new Dompdf($o);$d->loadHtml($html,'UTF-8');$d->setPaper('A4');$d->render();return $d->output();
    }
}
