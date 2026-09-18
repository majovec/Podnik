<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/src/bootstrap.php';
use App\Core\Database;use App\Core\Env;use App\Services\{MatcherService,DocumentService,MailerService,FioService};
$pdo=Database::pdo();
// Bank sync: only explicitly connected accounts; Fio token is read-only by design.
$accounts=$pdo->query("SELECT * FROM bank_accounts WHERE active=1 AND provider='fio'")->fetchAll();
foreach($accounts as $a){try{$key=hash('sha256',Env::get('APP_KEY','change-me'),true);$raw=base64_decode($a['token_encrypted'],true)?:'';$token=function_exists('sodium_crypto_secretbox_open')?(sodium_crypto_secretbox_open($raw,str_repeat("\0",SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),$key)?:''):$raw;if(!$token)throw new RuntimeException('Nelze rozšifrovat token');$data=FioService::movementsFromLast($token);foreach(FioService::normalize($data) as $r){$st=$pdo->prepare('INSERT OR IGNORE INTO bank_transactions(workspace_id,bank_account_id,booked_at,amount,currency,counterparty,account_number,variable_symbol,constant_symbol,specific_symbol,reference,message,status,external_id,raw_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$st->execute([$a['workspace_id'],$a['id'],$r['booked_at'],$r['amount'],$r['currency'],$r['counterparty'],$r['account_number'],$r['variable_symbol'],$r['constant_symbol'],$r['specific_symbol'],$r['reference'],$r['message'],'unmatched',$r['external_id'],$r['raw_json']]);if($st->rowCount())MatcherService::match($pdo,(int)$a['workspace_id'],(int)$pdo->lastInsertId());}$pdo->prepare('UPDATE bank_accounts SET last_sync_at=CURRENT_TIMESTAMP,last_error=NULL WHERE id=?')->execute([$a['id']]);}catch(Throwable $e){$pdo->prepare('UPDATE bank_accounts SET last_error=? WHERE id=?')->execute([$e->getMessage(),$a['id']]);}}
// Recurring invoices and basic reminders.
$rows=$pdo->query("SELECT * FROM recurring_invoices WHERE active=1 AND next_run<=date('now') LIMIT 100")->fetchAll();
foreach($rows as $r){try{$id=DocumentService::createFromTemplate($pdo,$r);$next=$r['interval_type']==='year'?'+'.(int)$r['interval_value'].' years':($r['interval_type']==='quarter'?'+'.((int)$r['interval_value']*3).' months':'+'.(int)$r['interval_value'].' months');$pdo->prepare("UPDATE recurring_invoices SET next_run=date(next_run,?) WHERE id=?")->execute([$next,$r['id']]);if($id&&$r['send_email']){ $s=$pdo->prepare('SELECT email FROM customers WHERE id=? AND workspace_id=?');$s->execute([$r['customer_id'],$r['workspace_id']]);$email=$s->fetchColumn();if($email)$pdo->prepare('INSERT INTO email_queue(workspace_id,to_email,subject,body,status) VALUES(?,?,?,?,?)')->execute([$r['workspace_id'],$email,'Nová faktura','Byla vytvořena nová pravidelná faktura č. '.$id,'queued']);}}catch(Throwable $e){error_log($e->getMessage());}}
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
    $pdo->prepare('INSERT INTO email_queue(workspace_id,to_email,subject,body,status) VALUES(?,?,?,?,?)')->execute([$d['workspace_id'],$d['email'],$subject,$body,'queued']);
}
// Rule-based automations.
$rules=$pdo->query("SELECT * FROM automation_rules WHERE active=1")->fetchAll();
foreach($rules as $rule){
    try {
        $wid=(int)$rule['workspace_id']; $action=$rule['action_type']; $trigger=$rule['trigger_type'];
        if($trigger==='stock_low' && $action==='create_task'){
            $items=$pdo->prepare('SELECT id,name FROM products WHERE workspace_id=? AND active=1 AND stock<=min_stock');$items->execute([$wid]);
            foreach($items as $item){$q=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE workspace_id=? AND title=? AND status="open"');$q->execute([$wid,'Doplnit sklad: '.$item['name']]);if(!(int)$q->fetchColumn())$pdo->prepare('INSERT INTO tasks(workspace_id,title,priority,status) VALUES(?,?,?,?)')->execute([$wid,'Doplnit sklad: '.$item['name'],'high','open']);}
        }
        if($trigger==='job_over_budget' && $action==='create_task'){
            $jobs=$pdo->prepare('SELECT id,name FROM jobs WHERE workspace_id=? AND budget>0 AND actual_cost>budget AND status NOT IN ("done","cancelled")');$jobs->execute([$wid]);
            foreach($jobs as $job){$title='Zakázka překročila rozpočet: '.$job['name'];$q=$pdo->prepare('SELECT COUNT(*) FROM tasks WHERE workspace_id=? AND title=? AND status="open"');$q->execute([$wid,$title]);if(!(int)$q->fetchColumn())$pdo->prepare('INSERT INTO tasks(workspace_id,title,priority,status,job_id) VALUES(?,?,?,?,?)')->execute([$wid,$title,'urgent','open',$job['id']]);}
        }
        $pdo->prepare('INSERT INTO automation_runs(workspace_id,rule_id,status,message) VALUES(?,?,?,?)')->execute([$wid,$rule['id'],'ok','Automatizace provedena']);
    } catch(Throwable $e){$pdo->prepare('INSERT INTO automation_runs(workspace_id,rule_id,status,message) VALUES(?,?,?,?)')->execute([(int)$rule['workspace_id'],$rule['id'],'error',$e->getMessage()]);}
}

// Queue mail; hosting may provide sendmail/PHP mail. SMTP can be plugged in later without changing queue semantics.
$emails=$pdo->query("SELECT q.*,w.name workspace_name,w.email_localpart,w.mail_enabled,ss.mail_domain FROM email_queue q JOIN workspaces w ON w.id=q.workspace_id CROSS JOIN saas_settings ss WHERE q.status='queued' AND (q.scheduled_at IS NULL OR q.scheduled_at<=datetime('now')) ORDER BY q.id LIMIT 50")->fetchAll();foreach($emails as $e){$from=((int)$e['mail_enabled']&&$e['email_localpart'])?$e['email_localpart'].'@'.$e['mail_domain']:Env::get('MAIL_FROM');$ok=MailerService::send($e['to_email'],$e['subject'],$e['body'],$e['attachment_path']??null,$from,$e['workspace_name']);$pdo->prepare('UPDATE email_queue SET status=?,attempts=attempts+1,last_error=? WHERE id=?')->execute([$ok?'sent':'failed',$ok?null:'mail() selhal',$e['id']]);}
echo "worker ok\n";
