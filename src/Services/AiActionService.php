<?php
namespace App\Services;

use PDO;
use App\Core\Auth;

/**
 * Plans and executes explicit, auditable NIU actions. The model only proposes
 * an action; execution always happens after the user confirms the proposal.
 */
final class AiActionService
{
    public static function plan(PDO $db, string $prompt, array $ctx): ?array
    {
        $p = mb_strtolower(trim($prompt));
        $wid = Auth::workspaceId();

        // Deterministic high-confidence commands first.
        $isReminderRequest = (bool)preg_match('/\bupom[ií]nku\b/iu', $prompt);
        // Reminder can be addressed directly to a customer name, e.g.
        // "Odešli upomínku Jakubu Majerovi".
        if (preg_match('/(?:odešli|odeslat|pošli|pošlem|pošlu)\s+upomínku\s+(.+?)(?:\s+k\s+(?:faktur[ue]|dokladu?)\b|\s*$)/iu', $prompt, $m)) {
            $customerRef = trim($m[1]);
            $doc = self::findInvoiceByCustomer($db, $wid, $customerRef);
            if ($doc) return ['action_type'=>'send_reminder','payload'=>self::docPayload($doc)];
        }

        if (preg_match('/(?:pošle?m|pošli|odeslat|odešli|upomínku).*?(?:faktur[ue]|doklad)\s*(?:č\.?\s*)?([a-z0-9_-]+)?/iu', $prompt, $m)
            || preg_match('/upomínku.*?([a-z]{0,4}-?\d{4}-?\d+)/iu', $prompt, $m)) {
            $ref = trim((string)($m[1] ?? ''));
            $doc = self::findInvoice($db, $wid, $ref, $prompt);
            if ($doc) return ['action_type'=>'send_reminder','payload'=>self::docPayload($doc)];
        }

        if (preg_match('/(?:pošle?m|pošli|odeslat|odešli).*?(?:faktur[ue]|doklad)\s*(?:č\.?\s*)?([a-z0-9_-]+)?/iu', $prompt, $m)) {
            $ref = trim((string)($m[1] ?? ''));
            $doc = self::findInvoice($db, $wid, $ref, $prompt);
            if ($doc) return ['action_type'=>'send_invoice','payload'=>self::docPayload($doc)];
        }

        if (preg_match('/(?:vytvoř|vytvor|přidej|pridej|založ|zaloz).*?zákazník.*?(.+)$/iu', $prompt, $m)) {
            $name = trim(preg_replace('/^(?:firma|společnost)\s+/iu','',$m[1]));
            if ($name !== '') return ['action_type'=>'create_customer','payload'=>['company_name'=>$name]];
        }

        if (preg_match('/(?:vytvoř|vytvor|připrav|priprav).*?faktur.*?(.+?)\s+za\s+([0-9]+(?:[.,][0-9]+)?)\s*(?:kč|czk)?/iu', $prompt, $m)) {
            $name = trim($m[1]); $amount=(float)str_replace(',','.',$m[2]);
            $q=$db->prepare('SELECT id,company_name,first_name,last_name,email FROM customers WHERE workspace_id=? AND active=1 AND (company_name LIKE ? OR last_name LIKE ? OR first_name LIKE ?) LIMIT 1');
            $like='%'.$name.'%'; $q->execute([$wid,$like,$like,$like]); $c=$q->fetch();
            return ['action_type'=>'create_invoice','payload'=>['customer_id'=>(int)($c['id']??0),'customer_name'=>$c?self::customerName($c):$name,'amount'=>$amount,'description'=>'AI návrh faktury']];
        }

        // Never let the generic AI planner reinterpret an explicit reminder request as invoice sending.
        if ($isReminderRequest) return null;

        // Structured fallback for the remaining business operations.
        $catalog = [
            'create_customer'=>'vytvoření zákazníka',
            'create_invoice'=>'vytvoření faktury',
            'create_offer'=>'vytvoření nabídky',
            'create_order'=>'vytvoření objednávky',
            'create_proforma'=>'vytvoření zálohové faktury',
            'create_credit'=>'vytvoření dobropisu',
            'create_delivery'=>'vytvoření dodacího listu',
            'create_task'=>'vytvoření úkolu',
            'complete_task'=>'dokončení úkolu',
            'create_event'=>'vytvoření události v kalendáři',
            'create_job'=>'vytvoření zakázky',
            'create_product'=>'vytvoření produktu/skladové položky',
            'create_expense'=>'evidence nákladu',
            'mark_paid'=>'označení faktury jako uhrazené',
            'send_invoice'=>'odeslání faktury e-mailem',
            'send_reminder'=>'odeslání upomínky',
            'cancel_document'=>'storno dokladu',
        ];
        $promptForAi = "Jsi plánovač akcí v české podnikatelské aplikaci Byznio.\n"
            ."Uživatel napsal: ".$prompt."\n\n"
            ."Vyber nejvýše jednu akci z tohoto katalogu: ".json_encode($catalog,JSON_UNESCAPED_UNICODE)."\n"
            ."Vrať POUZE JSON bez markdownu ve tvaru {\"action_type\":null,\"payload\":{}}.\n"
            ."Pokud uživatel pouze žádá o informaci/report nebo nejsou údaje dostatečné pro bezpečné provedení, vrať null.\n"
            ."Pro zákazníka používej customer_id jen pokud je jistě v dostupných datech. Pro doklad používej document_id jen pokud je jistě v dostupných datech.\n"
            ."Dostupný kontext: ".json_encode([
                'customers'=>array_slice($ctx['customers']??[],0,100),
                'documents'=>array_slice($ctx['documents']??[],0,100),
                'tasks'=>array_slice($ctx['tasks']??[],0,100),
                'jobs'=>array_slice($ctx['jobs']??[],0,100),
                'products'=>array_slice($ctx['products']??[],0,100),
            ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        try {
            $raw=AiService::ask($promptForAi,$ctx);
            $raw=trim(preg_replace('/^```(?:json)?\s*|\s*```$/i','',$raw));
            $j=json_decode($raw,true);
            if (!is_array($j) || empty($j['action_type']) || !isset($catalog[$j['action_type']])) return null;
            return ['action_type'=>(string)$j['action_type'],'payload'=>is_array($j['payload']??null)?$j['payload']:[]];
        } catch (\Throwable $e) { return null; }
    }

    public static function execute(PDO $db, array $action, int $userId): array
    {
        $wid=Auth::workspaceId(); $type=(string)($action['action_type']??''); $p=$action['payload']??[];
        switch($type){
            case 'create_customer':
                $name=trim((string)($p['company_name']??'')); if($name==='') throw new \RuntimeException('Chybí název zákazníka.');
                $db->prepare('INSERT INTO customers(workspace_id,type,company_name,first_name,last_name,ico,dic,email,phone,street,city,zip,note) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$wid,$p['type']??'company',$name,$p['first_name']??null,$p['last_name']??null,$p['ico']??null,$p['dic']??null,$p['email']??null,$p['phone']??null,$p['street']??null,$p['city']??null,$p['zip']??null,$p['note']??null]);
                return ['message'=>'Zákazník „'.$name.'“ byl vytvořen.','entity'=>'customer','id'=>(int)$db->lastInsertId()];
            case 'create_invoice': return self::createDocument($db,$wid,$userId,$p,'invoice','faktura');
            case 'create_offer': return self::createDocument($db,$wid,$userId,$p,'offer','nabídka');
            case 'create_order': return self::createDocument($db,$wid,$userId,$p,'order','objednávka');
            case 'create_proforma': return self::createDocument($db,$wid,$userId,$p,'proforma','zálohová faktura');
            case 'create_credit': return self::createDocument($db,$wid,$userId,$p,'credit','dobropis');
            case 'create_delivery': return self::createDocument($db,$wid,$userId,$p,'delivery','dodací list');
            case 'create_task':
                $title=trim((string)($p['title']??$p['name']??'')); if($title==='') throw new \RuntimeException('Chybí název úkolu.');
                $db->prepare('INSERT INTO tasks(workspace_id,title,description,due_at,priority,status,customer_id,job_id,assigned_user_id) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,$title,$p['description']??null,$p['due_at']??null,$p['priority']??'normal','open',self::optionalOwnedId($db,'customers',$p['customer_id']??null),self::optionalOwnedId($db,'jobs',$p['job_id']??null),$userId]);
                return ['message'=>'Úkol „'.$title.'“ byl vytvořen.','entity'=>'task','id'=>(int)$db->lastInsertId()];
            case 'complete_task':
                $id=self::ownedId($db,'tasks',$p['task_id']??$p['id']??0);$db->prepare('UPDATE tasks SET status="done",completed_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=? AND workspace_id=?')->execute([$id,$wid]);return ['message'=>'Úkol byl označen jako dokončený.','entity'=>'task','id'=>$id];
            case 'create_event':
                $title=trim((string)($p['title']??''));$start=trim((string)($p['start_at']??''));if($title===''||$start==='')throw new \RuntimeException('Chybí název nebo termín události.');
                $db->prepare('INSERT INTO calendar_events(workspace_id,title,start_at,end_at,type,note,customer_id,job_id,user_id) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,$title,$start,$p['end_at']??null,$p['type']??'event',$p['note']??null,self::optionalOwnedId($db,'customers',$p['customer_id']??null),self::optionalOwnedId($db,'jobs',$p['job_id']??null),$userId]);return ['message'=>'Událost „'.$title.'“ byla přidána do kalendáře.','entity'=>'calendar_event','id'=>(int)$db->lastInsertId()];
            case 'create_job':
                $cid=self::ownedId($db,'customers',$p['customer_id']??0);$name=trim((string)($p['name']??''));if($name==='')throw new \RuntimeException('Chybí název zakázky.');$db->prepare('INSERT INTO jobs(workspace_id,customer_id,name,description,location,budget,status,start_date,end_date,notes) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$wid,$cid,$name,$p['description']??null,$p['location']??null,(float)($p['budget']??0),$p['status']??'planned',$p['start_date']??null,$p['end_date']??null,$p['notes']??null]);return ['message'=>'Zakázka „'.$name.'“ byla vytvořena.','entity'=>'job','id'=>(int)$db->lastInsertId()];
            case 'create_product':
                $name=trim((string)($p['name']??''));if($name==='')throw new \RuntimeException('Chybí název produktu.');$db->prepare('INSERT INTO products(workspace_id,sku,name,unit,purchase_price,sale_price,vat_rate,stock,min_stock,supplier) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$wid,$p['sku']??null,$name,$p['unit']??'ks',(float)($p['purchase_price']??0),(float)($p['sale_price']??0),(float)($p['vat_rate']??21),(float)($p['stock']??0),(float)($p['min_stock']??0),$p['supplier']??null]);return ['message'=>'Produkt „'.$name.'“ byl vytvořen.','entity'=>'product','id'=>(int)$db->lastInsertId()];
            case 'create_expense':
                $amount=(float)($p['amount']??0);if($amount<=0)throw new \RuntimeException('Chybí kladná částka nákladu.');$db->prepare('INSERT INTO expenses(workspace_id,supplier,expense_date,amount,vat_amount,category,document_number,note,job_id) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,$p['supplier']??null,$p['expense_date']??date('Y-m-d'),$amount,(float)($p['vat_amount']??0),$p['category']??'Ostatní',$p['document_number']??null,$p['note']??null,self::optionalOwnedId($db,'jobs',$p['job_id']??null)]);return ['message'=>'Náklad byl zaevidován.','entity'=>'expense','id'=>(int)$db->lastInsertId()];
            case 'mark_paid':
                $id=self::ownedId($db,'documents',$p['document_id']??$p['id']??0);$q=$db->prepare('SELECT total_with_vat FROM documents WHERE id=? AND workspace_id=?');$q->execute([$id,$wid]);$total=(float)$q->fetchColumn();if(!$total)throw new \RuntimeException('Doklad nenalezen.');$amount=(float)($p['amount']??$total);$db->prepare('INSERT INTO payments(workspace_id,document_id,amount,paid_at,method,source) VALUES(?,?,?,?,?,?)')->execute([$wid,$id,$amount,date('Y-m-d'),'bank','ai']);DocumentService::refreshPaymentStatus($db,$wid,$id);return ['message'=>'Faktura byla označena jako uhrazená.','entity'=>'document','id'=>$id];
            case 'send_invoice':
                $doc=self::ownedDocument($db,$wid,$p);return self::sendDocument($db,$wid,$doc,false);
            case 'send_reminder':
                $doc=self::ownedDocument($db,$wid,$p);return self::sendDocument($db,$wid,$doc,true);
            case 'cancel_document':
                $id=self::ownedId($db,'documents',$p['document_id']??$p['id']??0);$q=$db->prepare('SELECT payment_status FROM documents WHERE id=? AND workspace_id=?');$q->execute([$id,$wid]);$st=$q->fetchColumn();if($st===false)throw new \RuntimeException('Doklad nenalezen.');if($st==='paid')throw new \RuntimeException('Zaplacený doklad nelze stornovat.');$db->prepare("UPDATE documents SET status='cancelled',payment_status='cancelled',updated_at=CURRENT_TIMESTAMP WHERE id=? AND workspace_id=?")->execute([$id,$wid]);return ['message'=>'Doklad byl stornován.','entity'=>'document','id'=>$id];
        }
        throw new \RuntimeException('Tuto AI akci zatím neumím bezpečně provést.');
    }

    private static function createDocument(PDO $db,int $wid,int $userId,array $p,string $type,string $label):array
    {
        $cid=self::ownedId($db,'customers',$p['customer_id']??0);$items=$p['items']??[];
        if(!$items){$amount=(float)($p['amount']??0);if($amount<=0)throw new \RuntimeException('Chybí částka nebo položky.');$items=[['name'=>$p['description']??$label,'quantity'=>1,'unit'=>'ks','unit_price'=>$amount,'vat_rate'=>(float)($p['vat_rate']??0)]];}
        $sub=$vat=0;foreach($items as &$it){$it['quantity']=(float)($it['quantity']??1);$it['unit_price']=(float)($it['unit_price']??0);$it['vat_rate']=(float)($it['vat_rate']??0);$it['line_total']=round($it['quantity']*$it['unit_price'],2);$sub+=$it['line_total'];$vat+=round($it['line_total']*$it['vat_rate']/100,2);}unset($it);
        $n=DocumentService::nextNumber($db,$wid,$type);$db->prepare('INSERT INTO documents(workspace_id,doc_type,doc_number,variable_symbol,customer_id,status,payment_status,issue_date,due_date,total_without_vat,total_vat,total_with_vat,created_by,public_token) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$wid,$type,$n,preg_replace('/\D/','',$n),$cid,'issued','unpaid',$p['issue_date']??date('Y-m-d'),$p['due_date']??date('Y-m-d',strtotime('+14 days')),$sub,$vat,$sub+$vat,$userId,DocumentService::publicToken($db)]);$id=(int)$db->lastInsertId();$st=$db->prepare('INSERT INTO document_items(document_id,name,description,quantity,unit,unit_price,vat_rate,line_total) VALUES(?,?,?,?,?,?,?,?)');foreach($items as $it)$st->execute([$id,$it['name'],$it['description']??null,$it['quantity'],$it['unit']??'ks',$it['unit_price'],$it['vat_rate'],$it['line_total']]);return ['message'=>ucfirst($label).' '.$n.' byla vytvořena.','entity'=>'document','id'=>$id,'doc_number'=>$n];
    }
    private static function sendDocument(PDO $db,int $wid,array $doc,bool $reminder):array
    {
        $q=$db->prepare('SELECT d.*,c.email,c.company_name,c.first_name,c.last_name,w.name workspace_name,w.logo_path,w.email_localpart,w.mail_enabled FROM documents d LEFT JOIN customers c ON c.id=d.customer_id JOIN workspaces w ON w.id=d.workspace_id WHERE d.id=? AND d.workspace_id=?');$q->execute([(int)$doc['id'],$wid]);$d=$q->fetch();if(!$d)throw new \RuntimeException('Doklad nenalezen.');$email=trim((string)$d['email']);if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Zákazník nemá platnou e-mailovou adresu.');
        $name=self::customerName($d);$base=rtrim((string)\App\Core\Env::get('APP_URL',''),'/');$url=$base.'/d/'.$d['public_token'];
        $domain=trim((string)\App\Core\Env::get('MAIL_DOMAIN','')); if($domain===''){ $ss=$db->query('SELECT mail_domain FROM saas_settings WHERE id=1')->fetchColumn(); $domain=trim((string)$ss); }
        if($reminder){
            if($d['payment_status']==='paid')throw new \RuntimeException('Tato faktura je již uhrazená.');
            $reminder=ReminderService::content($d);
            $subject=$reminder['subject'];
            $body=$reminder['body'];
            $days=(int)$reminder['days'];
            $from=((int)($d['mail_enabled']??1) && !empty($d['email_localpart']) && $domain!=='') ? trim((string)$d['email_localpart']).'@'.$domain : null;
            $iq=$db->prepare('SELECT * FROM document_items WHERE document_id=? ORDER BY id');$iq->execute([(int)$d['id']]);$items=$iq->fetchAll();
            $pdf=PdfService::invoice($d,$items,['name'=>$d['workspace_name'],'logo_path'=>$d['logo_path']??null],$d,$d['doc_type']==='invoice'?$url.'/qr':null);
            $dir=dirname(__DIR__,2).'/storage/mail';if(!is_dir($dir))@mkdir($dir,0775,true);$attachment=$dir.'/'.$wid.'_'.(int)$d['id'].'_reminder_'.date('YmdHis').'.pdf';if(file_put_contents($attachment,$pdf)===false)throw new \RuntimeException('Přílohu faktury se nepodařilo připravit.');
            $reminderData=['company'=>$d['workspace_name'],'title'=>$subject,'intro'=>"Dobrý den, ".$name.",\n\n".$body,'action_url'=>$url,'action_text'=>'Otevřít doklad','details'=>['Doklad'=>$d['doc_number'],'Částka'=>number_format((float)$d['total_with_vat'],2,',',' ').' Kč','Splatnost'=>(string)$d['due_date']]];
            $render=['html'=>EmailTemplateService::fragment('reminder',$reminderData),'text'=>"Dobrý den, ".$name.",\n\n".$body."\n\nDoklad: ".$d['doc_number']."\nČástka: ".number_format((float)$d['total_with_vat'],2,',',' ')." Kč\nSplatnost: ".$d['due_date']."\n\nOtevřít doklad: ".$url."\n\nS pozdravem,\n".$d['workspace_name']];
            $ok=MailerService::sendReport($email,$subject,$render['text'],$render['html'],$from,$d['workspace_name'],null,$d['logo_path']??null,$wid,$from,$attachment);
            if(!$ok)throw new \RuntimeException('E-mail se nepodařilo odeslat: '.MailerService::lastError());
            $db->prepare('INSERT INTO email_messages(workspace_id,customer_id,document_id,direction,from_email,to_email,subject,body,html_body) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,$d['customer_id']?:null,$d['id'],'outbound',$from,$email,$subject,$body,null]);
            $db->prepare('INSERT OR IGNORE INTO reminder_log(workspace_id,document_id,days_offset) VALUES(?,?,?)')->execute([$wid,$d['id'],$days]);
            return ['message'=>'Upomínka k '.$d['doc_number'].' byla skutečně odeslána na '.$email.'.','entity'=>'document','id'=>(int)$d['id']];
        } else {$subject='Faktura '.$d['doc_number'];$intro='v příloze / odkazu zasíláme fakturu '.$d['doc_number'].' ve výši '.number_format((float)$d['total_with_vat'],2,',',' ').' Kč.';$type='invoice';$title='Faktura '.$d['doc_number'];
        $render=['html'=>EmailTemplateService::fragment($type,['company'=>$d['workspace_name'],'title'=>$title,'intro'=>"Dobrý den, ".$name.",\n\n".$intro,'action_url'=>$url,'action_text'=>'Otevřít doklad','details'=>['Doklad'=>$d['doc_number'],'Částka'=>number_format((float)$d['total_with_vat'],2,',',' ').' Kč','Splatnost'=>(string)$d['due_date']]]), 'text'=>"Dobrý den, ".$name.",\n\n".$intro."\n\nDoklad: ".$d['doc_number']."\nČástka: ".number_format((float)$d['total_with_vat'],2,',',' ')." Kč\nSplatnost: ".$d['due_date']."\n\nOtevřít doklad: ".$url."\n\nS pozdravem,\n".$d['workspace_name']];
        $from=((int)($d['mail_enabled']??1) && !empty($d['email_localpart']) && $domain!=='') ? trim((string)$d['email_localpart']).'@'.$domain : null;
        $attachment=null;
        if(!$reminder){
            $iq=$db->prepare('SELECT * FROM document_items WHERE document_id=? ORDER BY id');$iq->execute([(int)$d['id']]);$items=$iq->fetchAll();
            $pdf=PdfService::invoice($d,$items,['name'=>$d['workspace_name'],'logo_path'=>$d['logo_path']??null],$d,$d['doc_type']==='invoice'?$url.'/qr':null);
            $dir=dirname(__DIR__,2).'/storage/mail';if(!is_dir($dir))@mkdir($dir,0775,true);$attachment=$dir.'/'.$wid.'_'.(int)$d['id'].'_'.date('YmdHis').'.pdf';if(file_put_contents($attachment,$pdf)===false)throw new \RuntimeException('PDF dokladu se nepodařilo připravit.');
        }
        $ok=MailerService::sendReport($email,$subject,$render['text'],$render['html'],$from,$d['workspace_name'],null,$d['logo_path']??null,$wid,$from,$attachment);
        if(!$ok)throw new \RuntimeException('E-mail se nepodařilo odeslat: '.MailerService::lastError());
        $db->prepare('INSERT INTO email_messages(workspace_id,customer_id,document_id,direction,from_email,to_email,subject,body,html_body) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$wid,$d['customer_id']?:null,$d['id'],'outbound',$from,$email,$subject,$render['text'],MailerService::htmlForLog($render['text'],$d['workspace_name'],null,$d['logo_path']??null,$wid)]);
        if($reminder)$db->prepare('INSERT OR IGNORE INTO reminder_log(workspace_id,document_id,days_offset) VALUES(?,?,?)')->execute([$wid,$d['id'],(int)floor((strtotime(date('Y-m-d'))-strtotime($d['due_date']))/86400)]);
        return ['message'=>($reminder?'Upomínka':'Faktura').' k '.$d['doc_number'].' byla skutečně odeslána na '.$email.'.','entity'=>'document','id'=>(int)$d['id']];
        }
    }
    private static function findInvoice(PDO $db,int $wid,string $ref,string $prompt):?array
    {
        if($ref!==''){$q=$db->prepare('SELECT d.*,c.company_name,c.first_name,c.last_name,c.email FROM documents d LEFT JOIN customers c ON c.id=d.customer_id WHERE d.workspace_id=? AND d.doc_type="invoice" AND (d.doc_number=? OR d.variable_symbol=?) LIMIT 1');$q->execute([$wid,$ref,$ref]);if($d=$q->fetch())return $d;}
        $q=$db->prepare('SELECT d.*,c.company_name,c.first_name,c.last_name,c.email FROM documents d LEFT JOIN customers c ON c.id=d.customer_id WHERE d.workspace_id=? AND d.doc_type="invoice" AND d.payment_status!="paid" ORDER BY d.due_date ASC,d.id DESC LIMIT 1');$q->execute([$wid]);return $q->fetch()?:null;
    }
    private static function findInvoiceByCustomer(PDO $db,int $wid,string $name):?array
    {
        $name=trim(preg_replace('/\s+/u',' ',$name));
        if($name==='') return null;
        $tokens=preg_split('/\s+/u',$name,-1,PREG_SPLIT_NO_EMPTY);
        $where=['d.workspace_id=?','d.doc_type="invoice"','d.payment_status!="paid"'];$args=[$wid];
        foreach($tokens as $token){
            if(mb_strlen($token)<2) continue;
            $variants=[$token];
            // Czech declensions: "Jakubu Majerovi" should still find "Jakub Majer".
            $len=mb_strlen($token);
            foreach([1,2] as $cut){
                if($len>$cut+2){$variants[]=mb_substr($token,0,$len-$cut);}
            }
            $variants=array_values(array_unique($variants));
            $parts=[];
            foreach($variants as $variant){$like='%'.$variant.'%';$parts[]='(c.company_name LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)';array_push($args,$like,$like,$like);}
            $where[]='('.implode(' OR ',$parts).')';
        }
        if(count($where)<=3) return null;
        $sql='SELECT d.*,c.company_name,c.first_name,c.last_name,c.email FROM documents d LEFT JOIN customers c ON c.id=d.customer_id WHERE '.implode(' AND ',$where).' ORDER BY d.due_date ASC,d.id DESC LIMIT 1';
        $q=$db->prepare($sql);$q->execute($args);return $q->fetch()?:null;
    }
    private static function docPayload(array $d):array{return ['document_id'=>(int)$d['id'],'doc_number'=>$d['doc_number'],'customer_id'=>(int)$d['customer_id'],'customer_name'=>self::customerName($d),'email'=>$d['email']??null,'amount'=>(float)$d['total_with_vat'],'due_date'=>$d['due_date']??null];}
    private static function customerName(array $c):string{return trim((string)($c['company_name']??'')) ?: trim((string)($c['first_name']??'').' '.(string)($c['last_name']??''));}
    private static function ownedId(PDO $db,string $table,$id):int{$id=(int)$id;if($id<1)throw new \RuntimeException('Chybí nebo je neplatné ID.');$allowed=['customers','jobs','tasks','documents','products'];if(!in_array($table,$allowed,true))throw new \RuntimeException('Neplatný typ záznamu.');$q=$db->prepare("SELECT id FROM {$table} WHERE id=? AND workspace_id=?");$q->execute([$id,Auth::workspaceId()]);if(!$q->fetchColumn())throw new \RuntimeException('Záznam nebyl nalezen.');return $id;}
    private static function optionalOwnedId(PDO $db,string $table,$id):?int{return empty($id)?null:self::ownedId($db,$table,$id);}
    private static function ownedDocument(PDO $db,int $wid,array $p):array{$id=(int)($p['document_id']??$p['id']??0);if($id<1 && !empty($p['doc_number'])){$q=$db->prepare('SELECT id FROM documents WHERE workspace_id=? AND doc_number=? LIMIT 1');$q->execute([$wid,$p['doc_number']]);$id=(int)$q->fetchColumn();}if($id<1)throw new \RuntimeException('Chybí konkrétní doklad.');$q=$db->prepare('SELECT * FROM documents WHERE id=? AND workspace_id=?');$q->execute([$id,$wid]);$d=$q->fetch();if(!$d)throw new \RuntimeException('Doklad nebyl nalezen.');return $d;}
}
