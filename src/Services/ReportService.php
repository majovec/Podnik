<?php
namespace App\Services;
use PDO;
use App\Config\BusinessTypes;

final class ReportService {
    public static function summarize(PDO $db,int $wid,string $start,string $end): array {
        $q=function(string $sql,array $params=[])use($db,$wid){$s=$db->prepare($sql);$s->execute(array_merge([$wid],$params));return $s->fetchColumn();};
        $money=function($v){return (float)$v;};
        $invoiceIssued=$money($q('SELECT COALESCE(SUM(total_with_vat),0) FROM documents WHERE workspace_id=? AND doc_type="invoice" AND issue_date BETWEEN ? AND ?',[$start,$end]));
        $received=$money($q('SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN documents d ON d.id=p.document_id WHERE p.workspace_id=? AND date(p.paid_at) BETWEEN ? AND ?',[$start,$end]));
        $expenses=$money($q('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE workspace_id=? AND expense_date BETWEEN ? AND ?',[$start,$end]));
        $receivedInvoices=$money($q('SELECT COALESCE(SUM(total_amount-paid_amount),0) FROM received_invoices WHERE workspace_id=? AND payment_status!="paid"'));
        $receivedInvoicesPeriod=$money($q('SELECT COALESCE(SUM(total_amount-paid_amount),0) FROM received_invoices WHERE workspace_id=? AND payment_status!="paid" AND (due_date IS NULL OR due_date<=?)',[$end]));
        $receivables=$money($q('SELECT COALESCE(SUM(total_with_vat-COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.document_id=d.id AND p.workspace_id=d.workspace_id),0)),0) FROM documents d WHERE d.workspace_id=? AND d.doc_type IN ("invoice","proforma") AND d.payment_status!="paid"'));
        $overdue=$money($q('SELECT COALESCE(SUM(total_with_vat-COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.document_id=d.id AND p.workspace_id=d.workspace_id),0)),0) FROM documents d WHERE d.workspace_id=? AND d.doc_type="invoice" AND d.payment_status!="paid" AND d.due_date<date("now")'));
        $cashBank=$money($q('SELECT COALESCE(SUM(amount),0) FROM bank_transactions WHERE workspace_id=? AND date(booked_at) BETWEEN ? AND ?',[$start,$end]));
        $bankCount=(int)$q('SELECT COUNT(*) FROM bank_transactions WHERE workspace_id=? AND date(booked_at) BETWEEN ? AND ?',[$start,$end]);
        $cashflow=$bankCount>0?$cashBank:($received-$expenses);
        $stock=$money($q('SELECT COALESCE(SUM(stock*purchase_price),0) FROM products WHERE workspace_id=? AND active=1'));
        $products=(int)$q('SELECT COUNT(*) FROM products WHERE workspace_id=? AND active=1');
        $low=(int)$q('SELECT COUNT(*) FROM products WHERE workspace_id=? AND active=1 AND stock<=min_stock');
        $customers=(int)$q('SELECT COUNT(*) FROM customers WHERE workspace_id=? AND active=1');
        $jobs=(int)$q('SELECT COUNT(*) FROM jobs WHERE workspace_id=? AND status NOT IN ("done","cancelled")');
        $invoiceCount=(int)$q('SELECT COUNT(*) FROM documents WHERE workspace_id=? AND doc_type="invoice" AND issue_date BETWEEN ? AND ?',[$start,$end]);
        $paidInvoiceCount=(int)$q('SELECT COUNT(*) FROM documents WHERE workspace_id=? AND doc_type="invoice" AND payment_status="paid" AND issue_date BETWEEN ? AND ?',[$start,$end]);
        $dueReceivedCount=(int)$q('SELECT COUNT(*) FROM received_invoices WHERE workspace_id=? AND payment_status!="paid" AND due_date BETWEEN ? AND ?',[$start,$end]);
        $dueReceivedOverdue=(int)$q('SELECT COUNT(*) FROM received_invoices WHERE workspace_id=? AND payment_status!="paid" AND due_date<date("now")');
        $topStock=[];
        $st=$db->prepare('SELECT name,stock,unit,purchase_price,stock*purchase_price AS value FROM products WHERE workspace_id=? AND active=1 ORDER BY value DESC,name LIMIT 8');$st->execute([$wid]);$topStock=$st->fetchAll();
        $lowStock=[];
        $st=$db->prepare('SELECT name,stock,min_stock,unit FROM products WHERE workspace_id=? AND active=1 AND stock<=min_stock ORDER BY name LIMIT 8');$st->execute([$wid]);$lowStock=$st->fetchAll();
        $businessType=(string)($q('SELECT business_type FROM workspaces WHERE id=?')?:'services');
        $bt=BusinessTypes::get($businessType);
        return compact('start','end','invoiceIssued','received','expenses','receivedInvoices','receivedInvoicesPeriod','receivables','overdue','cashflow','cashBank','bankCount','stock','products','low','customers','jobs','invoiceCount','paidInvoiceCount','dueReceivedCount','dueReceivedOverdue','topStock','lowStock','businessType','bt');
    }
    public static function ranges(string $kind): array {
        $today=new \DateTimeImmutable('today');
        if($kind==='month'){
            $end=$today->modify('last day of previous month');$start=$end->modify('first day of this month');
        } else {
            $end=$today->modify('monday this week')->modify('-1 day');$start=$end->modify('-6 days');
        }
        return [$start->format('Y-m-d'),$end->format('Y-m-d')];
    }
    public static function html(array $r,string $label): string {
        $m=fn($v)=>number_format((float)$v,0,',',' ').' Kč';
        $cf=$r['cashflow']>=0?'+':'';
        $stockRows='';
        foreach($r['topStock'] as $p)$stockRows.='<tr><td>'.htmlspecialchars((string)$p['name']).'</td><td>'.htmlspecialchars((string)$p['stock']).' '.htmlspecialchars((string)$p['unit']).'</td><td style="text-align:right">'.$m($p['value']).'</td></tr>';
        return '<div style="font-family:Arial,sans-serif;color:#10213f">'
            .'<p style="font-size:20px;font-weight:700;margin:0 0 8px">'.htmlspecialchars($label).'</p>'
            .'<p style="color:#6c7890;margin:0 0 22px">Období '.htmlspecialchars($r['start']).' až '.htmlspecialchars($r['end']).'</p>'
            .'<table width="100%" cellpadding="10" cellspacing="0" style="border-collapse:collapse">'
            .'<tr><td>Vystaveno</td><td align="right"><b>'.$m($r['invoiceIssued']).'</b></td></tr>'
            .'<tr><td>Přijato</td><td align="right"><b>'.$m($r['received']).'</b></td></tr>'
            .'<tr><td>Výdaje</td><td align="right"><b>'.$m($r['expenses']).'</b></td></tr>'
            .'<tr><td>Cash flow z období</td><td align="right"><b>'.$cf.$m($r['cashflow']).'</b></td></tr>'
            .'<tr><td>Neuhrazené vystavené faktury</td><td align="right"><b>'.$m($r['receivables']).'</b></td></tr>'
            .'<tr><td>Neuhrazené přijaté faktury</td><td align="right"><b>'.$m($r['receivedInvoices']).'</b></td></tr>'
            .'<tr><td>Hodnota skladu</td><td align="right"><b>'.$m($r['stock']).'</b></td></tr>'
            .'</table>'
            .'<h3 style="margin:26px 0 10px">Sklad</h3>'
            .'<p>Aktivní položky: <b>'.(int)$r['products'].'</b> · Na minimu: <b>'.(int)$r['low'].'</b></p>'
            .($stockRows?'<table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse"><tr><th align="left">Položka</th><th align="left">Množství</th><th align="right">Hodnota</th></tr>'.$stockRows.'</table>':'<p>Sklad zatím nemá evidované položky.</p>')
            .'</div>';
    }
}
