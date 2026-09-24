<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/src/bootstrap.php';
use App\Core\{Database,Env};use App\Services\{MatcherService,DocumentService,MailerService,FioService,BankTokenService,BackupService};
$pdo=Database::pdo();
// Bank sync: only explicitly connected accounts; Fio token is read-only by design.
$accounts=$pdo->query("SELECT * FROM bank_accounts WHERE active=1 AND provider='fio'")->fetchAll();
foreach($accounts as $a){try{$token=BankTokenService::decrypt((string)$a['token_encrypted']);$data=FioService::movementsFromLast($token);foreach(FioService::normalize($data) as $r){$st=$pdo->prepare('INSERT OR IGNORE INTO bank_transactions(workspace_id,bank_account_id,booked_at,amount,currency,counterparty,account_number,variable_symbol,constant_symbol,specific_symbol,reference,message,status,external_id,raw_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$st->execute([$a['workspace_id'],$a['id'],$r['booked_at'],$r['amount'],$r['currency'],$r['counterparty'],$r['account_number'],$r['variable_symbol'],$r['constant_symbol'],$r['specific_symbol'],$r['reference'],$r['message'],'unmatched',$r['external_id'],$r['raw_json']]);if($st->rowCount())MatcherService::match($pdo,(int)$a['workspace_id'],(int)$pdo->lastInsertId());}$pdo->prepare('UPDATE bank_accounts SET last_sync_at=CURRENT_TIMESTAMP,last_error=NULL WHERE id=?')->execute([$a['id']]);}catch(Throwable $e){$pdo->prepare('UPDATE bank_accounts SET last_error=? WHERE id=?')->execute([$e->getMessage(),$a['id']]);}}
// Recurring invoices and basic reminders.
$rows=$pdo->query("SELECT * FROM recurring_invoices WHERE active=1 AND next_run<=date('now') LIMIT 100")->fetchAll();
foreach($rows as $r){try{$id=DocumentService::createFromTemplate($pdo,$r);$intervalType=(string)$r['interval_type'];
        $intervalValue=max(1,(int)$r['interval_value']);
        $currentDate=new DateTimeImmutable((string)$r['next_run']);
        $lastDayOfMonth=$currentDate->modify('last day of this month')->format('Y-m-d');
        $isMonthEnd=$currentDate->format('Y-m-d')===$lastDayOfMonth;
        if($intervalType==='year'){
            $months=$intervalValue*12;
        }elseif($intervalType==='quarter'){
            $months=$intervalValue*3;
        }else{
            $months=$intervalValue;
        }
        if($isMonthEnd){
            $nextDate=$currentDate->modify('first day of this month')->modify('+'.$months.' months')->modify('last day of this month');
        }else{
            $nextDate=$intervalType==='year'
                ? $currentDate->modify('+'.$intervalValue.' years')
                : $currentDate->modify('+'.$months.' months');
        }
        $pdo->prepare('UPDATE recurring_invoices SET next_run=? WHERE id=?')->execute([$nextDate->format('Y-m-d'),$r['id']]);if($id&&$r['send_email']){ $s=$pdo->prepare('SELECT email FROM customers WHERE id=? AND workspace_id=?');$s->execute([$r['customer_id'],$r['workspace_id']]);$email=$s->fetchColumn();if($email){$tk=$pdo->prepare('SELECT public_token,doc_number FROM documents WHERE id=? AND workspace_id=?');$tk->execute([$id,$r['workspace_id']]);$doc=$tk->fetch();if($doc)$pdo->prepare('INSERT INTO email_queue(workspace_id,to_email,subject,body,action_url,status) VALUES(?,?,?,?,?,?)')->execute([$r['workspace_id'],$email,'Nová faktura','Byla vytvořena nová pravidelná faktura č. '.$doc['doc_number'],rtrim(Env::get('APP_URL',''),'/').'/d/'.$doc['public_token'],'queued']);}}}catch(Throwable $e){error_log($e->getMessage());}}
// Payment statuses.
$pdo->exec("UPDATE documents SET payment_status='overdue' WHERE doc_type IN ('invoice','proforma') AND payment_status='unpaid' AND due_date<date('now')");
// Scheduled invoice reminders with deduplication.
$docs=$pdo->query("SELECT d.*,c.email,w.mail_enabled,w.mail_reminders FROM documents d LEFT JOIN customers c ON c.id=d.customer_id JOIN workspaces w ON w.id=d.workspace_id WHERE d.doc_type='invoice' AND d.payment_status!='paid' AND c.email IS NOT NULL")->fetchAll();
foreach($docs as $d){
    $days=(int)floor((strtotime(date('Y-m-d'))-strtotime($d['due_date']))/86400);
    if(!in_array($days,[-3,0,3,7],true)||empty($d['mail_enabled'])||empty($d['mail_reminders'])) continue;
    $q=$pdo->prepare('INSERT OR IGNORE INTO reminder_log(workspace_id,document_id,days_offset) VALUES(?,?,?)');
    $q->execute([$d['workspace_id'],$d['id'],$days]);
    if(!$q->rowCount()) continue;
    $subject=$days<0?'Blíží se splatnost '.$d['doc_number']:'Upomínka '.$d['doc_number'];
    $text=$days<0?'před splatností':'po splatnosti '.$days.' dní';
    $body='Faktura '.$d['doc_number'].' ve výši '.number_format((float)$d['total_with_vat'],2,',',' ').' Kč je '.$text.'.';
    $pdo->prepare('INSERT INTO email_queue(workspace_id,to_email,subject,body,action_url,status) VALUES(?,?,?,?,?,?)')->execute([$d['workspace_id'],$d['email'],$subject,$body,rtrim(Env::get('APP_URL',''),'/').'/d/'.$d['public_token'],'queued']);
}
// Rule-based automations.
$rules=$pdo->query("SELECT * FROM automation_rules WHERE active=1")->fetchAll();
foreach($rules as $rule){
    try {
        $wid=(int)$rule['workspace_id']; $action=$rule['action_type']; $trigger=$rule['trigger_type'];
        if($trigger==='payment_received' && $action==='match_payment'){
            $tx=$pdo->prepare('SELECT id FROM bank_transactions WHERE workspace_id=? AND status IN ("unmatched","suggested") AND amount>0 ORDER BY id DESC LIMIT 100');$tx->execute([$wid]);$txRows=$tx->fetchAll();$matched=0;
            foreach($txRows as $row){if(MatcherService::match($pdo,$wid,(int)$row['id']))$matched++;}
            $message='Zkontrolováno '.count($txRows).' příchozích plateb; nově spárováno '.$matched.'.';
        } elseif($trigger==='stock_low' && $action==='create_task'){
            $items=$pdo->prepare('SELECT id,name FROM products WHERE workspace_id=? AND active=1 AND stock<=min_stock');$items->execute([$wid]);$created=0;
            foreach($items as $item){$q=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE workspace_id=? AND title=? AND status="open"');$q->execute([$wid,'Doplnit sklad: '.$item['name']]);if(!(int)$q->fetchColumn()){$pdo->prepare('INSERT INTO tasks(workspace_id,title,priority,status) VALUES(?,?,?,?)')->execute([$wid,'Doplnit sklad: '.$item['name'],'high','open']);$created++;}}
            $message='Nízká zásoba zkontrolována; vytvořeno '.$created.' úkolů.';
        } elseif($trigger==='job_over_budget' && $action==='create_task'){
            $jobs=$pdo->prepare('SELECT id,name FROM jobs WHERE workspace_id=? AND budget>0 AND actual_cost>budget AND status NOT IN ("done","cancelled")');$jobs->execute([$wid]);$created=0;
            foreach($jobs as $job){$title='Zakázka překročila rozpočet: '.$job['name'];$q=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE workspace_id=? AND title=? AND status="open"');$q->execute([$wid,$title]);if(!(int)$q->fetchColumn()){$pdo->prepare('INSERT INTO tasks(workspace_id,title,priority,status,job_id) VALUES(?,?,?,?,?)')->execute([$wid,$title,'urgent','open',$job['id']]);$created++;}}
            $message='Rozpočty zkontrolovány; vytvořeno '.$created.' úkolů.';
        } elseif($trigger==='invoice_due' && $action==='send_reminder'){
            $cfg=json_decode((string)$rule['config_json'],true)?:[];$days=(int)($cfg['days']??0);$docs=$pdo->prepare('SELECT d.*,c.email FROM documents d LEFT JOIN customers c ON c.id=d.customer_id WHERE d.workspace_id=? AND d.doc_type="invoice" AND d.payment_status!="paid" AND c.email IS NOT NULL AND d.due_date IS NOT NULL');$docs->execute([$wid]);$queued=0;
            foreach($docs as $d){$diff=(int)floor((strtotime(date('Y-m-d'))-strtotime($d['due_date']))/86400);if(abs($diff)!==$days && !($days===0&&$diff===0))continue;$ded=$pdo->prepare('SELECT COUNT(*) FROM reminder_log WHERE workspace_id=? AND document_id=? AND days_offset=?');$ded->execute([$wid,$d['id'],$diff]);if((int)$ded->fetchColumn())continue;$pdo->prepare('INSERT INTO reminder_log(workspace_id,document_id,days_offset) VALUES(?,?,?)')->execute([$wid,$d['id'],$diff]);$pdo->prepare('INSERT INTO email_queue(workspace_id,to_email,subject,body,action_url,status) VALUES(?,?,?,?,?,?)')->execute([$wid,$d['email'],'Upomínka k faktuře '.$d['doc_number'],'Faktura '.$d['doc_number'].' je '.$diff.' dní po splatnosti.',rtrim(Env::get('APP_URL',''),'/').'/d/'.$d['public_token'],'queued']);$queued++;}
            $message='Připraveno '.$queued.' upomínek.';
        } else { $message='Pravidlo je uloženo; pro tuto kombinaci zatím není automatická akce implementována.'; }
        $pdo->prepare('INSERT INTO automation_runs(workspace_id,rule_id,status,message) VALUES(?,?,?,?)')->execute([$wid,$rule['id'],'ok',$message]);
    } catch(Throwable $e){$pdo->prepare('INSERT INTO automation_runs(workspace_id,rule_id,status,message) VALUES(?,?,?,?)')->execute([(int)$rule['workspace_id'],$rule['id'],'error',$e->getMessage()]);}
}

// Trial expiry notifications: queue exactly once for 3 days before and the expiry day.
$trialRows=$pdo->query("SELECT w.id workspace_id,w.name,w.email,w.logo_path,s.trial_ends_at FROM workspaces w JOIN subscriptions s ON s.workspace_id=w.id WHERE s.status='trial' AND w.email IS NOT NULL AND trim(w.email)!=''")->fetchAll();
foreach($trialRows as $t){
    $days=(int)floor((strtotime(date('Y-m-d',strtotime((string)$t['trial_ends_at'])))-strtotime(date('Y-m-d')))/86400);
    if(!in_array($days,[3,0],true)) continue;
    $subject=$days===0?'Zkušební doba končí dnes':'Zkušební doba končí za 3 dny';
    $exists=$pdo->prepare('SELECT id,status FROM email_queue WHERE workspace_id=? AND to_email=? AND subject=? AND status IN ("queued","sent") LIMIT 1');
    $exists->execute([(int)$t['workspace_id'],$t['email'],$subject]);
    if($exists->fetch()) continue;
    $body=$days===0?'Vaše zkušební doba končí dnes. Aktivujte předplatné, aby vaše firma nepřišla o přístup k Byzniu.':'Vaše zkušební doba skončí za 3 dny. Aktivujte předplatné, aby vaše firma mohla v používání Byznia pokračovat bez přerušení.';
    $pdo->prepare('INSERT INTO email_queue(workspace_id,to_email,subject,body,action_url,status) VALUES(?,?,?,?,?,?)')->execute([(int)$t['workspace_id'],$t['email'],$subject,$body,rtrim(Env::get('APP_URL',''),'/').'/admin/plans','queued']);
}


// Weekly and monthly management reports.
function reportMoney(float $v): string { return number_format($v,2,',',' ').' Kč'; }
function reportPct(float $current,float $previous): string {
    if(abs($previous)<0.005) return abs($current)<0.005 ? '0 %' : 'nově';
    $change=(($current-$previous)/abs($previous))*100;
    return ($change>=0?'+':'').number_format($change,0,',',' ').' %';
}
function reportBar(float $value,float $max,string $label,string $valueText,string $fill): string {
    $width=$max>0?max(2,min(100,($value/$max)*100)):0;
    return '<div style="margin:12px 0"><div style="display:flex;justify-content:space-between;gap:12px;font-size:13px"><span>'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</span><strong>'.htmlspecialchars($valueText,ENT_QUOTES,'UTF-8').'</strong></div><div style="height:10px;background:#edf1f7;border-radius:999px;overflow:hidden;margin-top:6px"><div style="height:10px;width:'.number_format($width,2,'.','').'% ;background:'.$fill.';border-radius:999px"></div></div></div>';
}
function buildReport(PDO $pdo,int $wid,string $periodStart,string $periodEnd,string $previousStart,string $previousEnd,string $label): array {
    $one=function(string $sql,array $params=[])use($pdo){$s=$pdo->prepare($sql);$s->execute($params);return $s->fetchColumn();};
    $row=function(string $sql,array $params=[])use($pdo){$s=$pdo->prepare($sql);$s->execute($params);return $s->fetch()?:[];};
    $all=function(string $sql,array $params=[])use($pdo){$s=$pdo->prepare($sql);$s->execute($params);return $s->fetchAll();};
    $revenue=(float)$one("SELECT COALESCE(SUM(total_with_vat),0) FROM documents WHERE workspace_id=? AND doc_type='invoice' AND issue_date BETWEEN ? AND ?",[$wid,$periodStart,$periodEnd]);
    $prevRevenue=(float)$one("SELECT COALESCE(SUM(total_with_vat),0) FROM documents WHERE workspace_id=? AND doc_type='invoice' AND issue_date BETWEEN ? AND ?",[$wid,$previousStart,$previousEnd]);
    $expenses=(float)$one("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE workspace_id=? AND expense_date BETWEEN ? AND ?",[$wid,$periodStart,$periodEnd]);
    $prevExpenses=(float)$one("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE workspace_id=? AND expense_date BETWEEN ? AND ?",[$wid,$previousStart,$previousEnd]);
    $paid=(float)$one("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN documents d ON d.id=p.document_id AND d.workspace_id=p.workspace_id WHERE p.workspace_id=? AND d.doc_type='invoice' AND p.paid_at BETWEEN ? AND ?",[$wid,$periodStart,$periodEnd]);
    $prevPaid=(float)$one("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN documents d ON d.id=p.document_id AND d.workspace_id=p.workspace_id WHERE p.workspace_id=? AND d.doc_type='invoice' AND p.paid_at BETWEEN ? AND ?",[$wid,$previousStart,$previousEnd]);
    $issuedCount=(int)$one("SELECT COUNT(*) FROM documents WHERE workspace_id=? AND doc_type='invoice' AND issue_date BETWEEN ? AND ?",[$wid,$periodStart,$periodEnd]);
    $prevIssuedCount=(int)$one("SELECT COUNT(*) FROM documents WHERE workspace_id=? AND doc_type='invoice' AND issue_date BETWEEN ? AND ?",[$wid,$previousStart,$previousEnd]);
    $paidCount=(int)$one("SELECT COUNT(DISTINCT p.document_id) FROM payments p JOIN documents d ON d.id=p.document_id AND d.workspace_id=p.workspace_id WHERE p.workspace_id=? AND d.doc_type='invoice' AND p.paid_at BETWEEN ? AND ?",[$wid,$periodStart,$periodEnd]);
    $prevPaidCount=(int)$one("SELECT COUNT(DISTINCT p.document_id) FROM payments p JOIN documents d ON d.id=p.document_id AND d.workspace_id=p.workspace_id WHERE p.workspace_id=? AND d.doc_type='invoice' AND p.paid_at BETWEEN ? AND ?",[$wid,$previousStart,$previousEnd]);
    $overdueCount=(int)$one("SELECT COUNT(*) FROM documents WHERE workspace_id=? AND doc_type='invoice' AND payment_status!='paid' AND payment_status!='cancelled' AND due_date<date('now')",[$wid]);
    $overdueAmount=(float)$one("SELECT COALESCE(SUM(total_with_vat),0) FROM documents WHERE workspace_id=? AND doc_type='invoice' AND payment_status!='paid' AND payment_status!='cancelled' AND due_date<date('now')",[$wid]);
    $overdue=$all("SELECT doc_number,total_with_vat,due_date FROM documents WHERE workspace_id=? AND doc_type='invoice' AND payment_status!='paid' AND payment_status!='cancelled' AND due_date<date('now') ORDER BY due_date ASC,total_with_vat DESC LIMIT 5",[$wid]);
    $newCustomers=(int)$one("SELECT COUNT(*) FROM customers WHERE workspace_id=? AND active=1 AND date(created_at) BETWEEN ? AND ?",[$wid,$periodStart,$periodEnd]);
    $prevNewCustomers=(int)$one("SELECT COUNT(*) FROM customers WHERE workspace_id=? AND active=1 AND date(created_at) BETWEEN ? AND ?",[$wid,$previousStart,$previousEnd]);
    $lowStock=$all("SELECT name,stock,min_stock FROM products WHERE workspace_id=? AND active=1 AND stock<=min_stock ORDER BY name LIMIT 8",[$wid]);
    $overBudget=$all("SELECT name,budget,actual_cost,(actual_cost-budget) over_amount FROM jobs WHERE workspace_id=? AND budget>0 AND actual_cost>budget AND status NOT IN ('done','cancelled') ORDER BY over_amount DESC LIMIT 8",[$wid]);
    $profit=$revenue-$expenses;$prevProfit=$prevRevenue-$prevExpenses;$cashflow=$paid-$expenses;$prevCashflow=$prevPaid-$prevExpenses;
    $maxMoney=max($revenue,$expenses,$paid,$overdueAmount,0.01);
    $body="Souhrnný report za období {$periodStart}–{$periodEnd}.\n\n".
        "Příjmy: ".reportMoney($revenue).' ('.reportPct($revenue,$prevRevenue).")\n".
        "Výdaje: ".reportMoney($expenses).' ('.reportPct($expenses,$prevExpenses).")\n".
        "Zisk: ".reportMoney($profit).' ('.reportPct($profit,$prevProfit).")\n".
        "Cashflow: ".reportMoney($cashflow).' ('.reportPct($cashflow,$prevCashflow).")\n\n".
        "Faktury vystavené: {$issuedCount} / ".reportMoney($revenue).' (minulé období '.$prevIssuedCount.')'."\n".
        "Faktury zaplacené: {$paidCount} / ".reportMoney($paid).' (minulé období '.$prevPaidCount.')'."\n".
        "Faktury po splatnosti: {$overdueCount} / ".reportMoney($overdueAmount)."\n".
        "Noví zákazníci: {$newCustomers} (minulé období {$prevNewCustomers})\n".
        "Položky na minimální zásobě: ".count($lowStock)."\n".
        "Zakázky nad rozpočtem: ".count($overBudget)."\n";
    if($overdue){$body.="\nNejstarší / nejvyšší faktury po splatnosti:\n";foreach($overdue as $d)$body.='• '.$d['doc_number'].' · splatnost '.$d['due_date'].' · '.reportMoney((float)$d['total_with_vat'])."\n";}
    $html='<h2 style="margin:0 0 8px;font-size:22px">'.$label.'</h2><p style="color:#6c7890;margin-top:0">'.$periodStart.' – '.$periodEnd.'</p>';
    $html.='<div style="padding:16px;border:1px solid #e5eaf2;border-radius:14px;background:#fbfcfe"><strong>Příjmy vs. výdaje</strong>'.reportBar($revenue,$maxMoney,'Příjmy',reportMoney($revenue),'#1677ee').reportBar($expenses,$maxMoney,'Výdaje',reportMoney($expenses),'#8b5cf6').'<div style="margin-top:12px;font-size:14px"><b>Zisk:</b> '.reportMoney($profit).' · <b>Cashflow:</b> '.reportMoney($cashflow).'</div></div>';
    $html.='<div style="padding:16px;border:1px solid #e5eaf2;border-radius:14px;background:#fbfcfe;margin-top:14px"><strong>Zaplaceno vs. po splatnosti</strong>'.reportBar($paid,$maxMoney,'Zaplaceno v období',reportMoney($paid),'#16a34a').reportBar($overdueAmount,$maxMoney,'Po splatnosti nyní',reportMoney($overdueAmount),'#dc5a5a').'</div>';
    $html.='<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px"><div style="padding:14px;border:1px solid #e5eaf2;border-radius:14px"><b>Faktury</b><br>Vystavené: '.$issuedCount.' / '.reportMoney($revenue).'<br>Zaplacené: '.$paidCount.' / '.reportMoney($paid).'<br>Po splatnosti: '.$overdueCount.' / '.reportMoney($overdueAmount).'</div><div style="padding:14px;border:1px solid #e5eaf2;border-radius:14px"><b>Noví zákazníci</b><br>'.$newCustomers.' <span style="color:#6c7890">('.reportPct($newCustomers,$prevNewCustomers).' oproti minulému období)</span></div></div>';
    $html.='<h3 style="margin:22px 0 8px">Po splatnosti</h3>';
    if($overdue){$html.='<ul style="padding-left:20px">';foreach($overdue as $d)$html.='<li style="margin:5px 0">'.htmlspecialchars((string)$d['doc_number'],ENT_QUOTES,'UTF-8').' · splatnost '.htmlspecialchars((string)$d['due_date'],ENT_QUOTES,'UTF-8').' · <b>'.reportMoney((float)$d['total_with_vat']).'</b></li>';$html.='</ul>';}else $html.='<p>Žádné faktury po splatnosti.</p>';
    $html.='<h3 style="margin:22px 0 8px">Sklad</h3>';if($lowStock){$html.='<ul style="padding-left:20px">';foreach($lowStock as $x)$html.='<li style="margin:5px 0">'.htmlspecialchars((string)$x['name'],ENT_QUOTES,'UTF-8').' · sklad '.htmlspecialchars((string)$x['stock'],ENT_QUOTES,'UTF-8').' / minimum '.htmlspecialchars((string)$x['min_stock'],ENT_QUOTES,'UTF-8').'</li>';$html.='</ul>';}else $html.='<p>Žádná položka není na minimální zásobě.</p>';
    $html.='<h3 style="margin:22px 0 8px">Zakázky nad rozpočtem</h3>';if($overBudget){$html.='<ul style="padding-left:20px">';foreach($overBudget as $j)$html.='<li style="margin:5px 0">'.htmlspecialchars((string)$j['name'],ENT_QUOTES,'UTF-8').' · rozpočet '.reportMoney((float)$j['budget']).' · skutečnost '.reportMoney((float)$j['actual_cost']).' · překročení '.reportMoney((float)$j['over_amount']).'</li>';$html.='</ul>';}else $html.='<p>Žádná aktivní zakázka nepřekračuje rozpočet.</p>';
    return [$body,$html];
}
function queuePeriodicReport(PDO $pdo,array $w,string $kind,string $start,string $end,string $prevStart,string $prevEnd,string $label,string $column): void {
    if(empty($w['email']) || empty($w['mail_enabled']) || empty($w[$kind.'_report_enabled'])) return;
    $last=$w[$column]??null;
    if($last && (string)$last >= $end) return;
    [$body,$html]=buildReport($pdo,(int)$w['id'],$start,$end,$prevStart,$prevEnd,$label);
    $subject=$kind==='weekly'?'Týdenní report Byznio · '.$end:'Měsíční report Byznio · '.substr($end,0,7);
    $dup=$pdo->prepare('SELECT id FROM email_queue WHERE workspace_id=? AND subject=? AND status IN ("queued","sent") LIMIT 1');$dup->execute([(int)$w['id'],$subject]);if($dup->fetch())return;
    $url=rtrim(Env::get('APP_URL',''),'/').'/';
    $pdo->prepare('INSERT INTO email_queue(workspace_id,to_email,subject,body,action_url,status,html_body) VALUES(?,?,?,?,?,?,?)')->execute([(int)$w['id'],$w['email'],$subject,$body,$url,'queued',$html]);
}
$today=new DateTimeImmutable(date('Y-m-d'));
if((int)$today->format('N')===1){
    $end=$today->modify('-1 day')->format('Y-m-d');$start=$today->modify('-7 days')->format('Y-m-d');$prevEnd=$today->modify('-8 days')->format('Y-m-d');$prevStart=$today->modify('-14 days')->format('Y-m-d');
    $ws=$pdo->query("SELECT id,email,mail_enabled,weekly_report_enabled,last_weekly_report_at FROM workspaces WHERE email IS NOT NULL AND trim(email)!=''")->fetchAll();
    foreach($ws as $w) queuePeriodicReport($pdo,$w,'weekly',$start,$end,$prevStart,$prevEnd,'Týdenní souhrnný report','last_weekly_report_at');
}
if((int)$today->format('d')===1){
    $first=$today->modify('-1 month')->modify('first day of this month');$end=$first->modify('last day of this month');$prevEnd=$first->modify('-1 day');$prevStart=$prevEnd->modify('first day of this month');
    $ws=$pdo->query("SELECT id,email,mail_enabled,monthly_report_enabled,last_monthly_report_at FROM workspaces WHERE email IS NOT NULL AND trim(email)!=''")->fetchAll();
    foreach($ws as $w) queuePeriodicReport($pdo,$w,'monthly',$first->format('Y-m-d'),$end->format('Y-m-d'),$prevStart->format('Y-m-d'),$prevEnd->format('Y-m-d'),'Měsíční souhrnný report','last_monthly_report_at');
}

// Queue mail through authenticated Postmark API.

$emails=$pdo->query("SELECT q.*,w.name workspace_name,w.email_localpart,w.mail_enabled,w.logo_path,ss.mail_domain FROM email_queue q JOIN workspaces w ON w.id=q.workspace_id CROSS JOIN saas_settings ss WHERE q.status='queued' AND (q.scheduled_at IS NULL OR q.scheduled_at<=datetime('now')) ORDER BY q.id LIMIT 50")->fetchAll();foreach($emails as $e){$from=((int)$e['mail_enabled']&&$e['email_localpart'])?$e['email_localpart'].'@'.$e['mail_domain']:Env::get('MAIL_FROM');$ok=!empty($e['html_body'])?MailerService::sendReport($e['to_email'],$e['subject'],$e['body'],$e['html_body'],$from,$e['workspace_name'],$e['action_url']??null,$e['logo_path']??null,(int)$e['workspace_id'],$e['reply_to']??$from):MailerService::send($e['to_email'],$e['subject'],$e['body'],$e['attachment_path']??null,$from,$e['workspace_name'],$e['action_url']??null,$e['logo_path']??null,(int)$e['workspace_id'],$e['reply_to']??$from);$pdo->prepare('UPDATE email_queue SET status=?,attempts=attempts+1,last_error=? WHERE id=?')->execute([$ok?'sent':'failed',$ok?null:MailerService::lastError(),$e['id']]);if($ok && !empty($e['html_body'])){if(str_starts_with((string)$e['subject'],'Týdenní report Byznio'))$pdo->prepare('UPDATE workspaces SET last_weekly_report_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int)$e['workspace_id']]);elseif(str_starts_with((string)$e['subject'],'Měsíční report Byznio'))$pdo->prepare('UPDATE workspaces SET last_monthly_report_at=CURRENT_TIMESTAMP WHERE id=?')->execute([(int)$e['workspace_id']]);}}
// Automatic daily backup: execute at most once every 24 hours.
try{
    $st=$pdo->query('SELECT last_automatic_backup_at FROM saas_settings WHERE id=1');
    $last=$st->fetchColumn();
    $due=empty($last) || strtotime((string)$last) <= time()-86400;
    if($due){
        $result=BackupService::run($pdo);
        $pdo->exec('UPDATE saas_settings SET last_automatic_backup_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=1');
    }
}catch(Throwable $e){error_log('Automatic backup failed: '.$e->getMessage());}

echo "worker ok\n";
