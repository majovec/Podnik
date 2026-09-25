<?php
namespace App\Controllers;

use PDO;
use App\Core\{Auth,Response,View};
use App\Services\OcrService;

final class ReceivedInvoicesController {
    public function __construct(private PDO $db) {}

    public function index(): void {
        Auth::requirePermission('expenses');
        $s=$this->db->prepare("SELECT * FROM received_invoices WHERE workspace_id=? ORDER BY CASE WHEN payment_status='paid' THEN 2 WHEN due_date IS NOT NULL AND due_date<date('now') THEN 0 ELSE 1 END,due_date IS NULL,due_date ASC,id DESC");
        $s->execute([Auth::workspaceId()]);
        $email=$this->db->prepare('SELECT email_localpart FROM workspaces WHERE id=?');$email->execute([Auth::workspaceId()]);
        $local=(string)($email->fetchColumn()?:'');
        $inboundDomain=trim((string)\App\Core\Env::get('MAIL_DOMAIN','byznio.cz'));
        View::render('received/index',['title'=>'Přijaté faktury','rows'=>$s->fetchAll(),'inboundEmail'=>$local&&$inboundDomain?$local.'@'.$inboundDomain:null]);
    }

    public function save(): void {
        Auth::requirePermission('expenses'); Auth::verifyCsrf();
        $d=$_POST;
        $this->db->prepare('INSERT INTO received_invoices(workspace_id,supplier,ico,dic,document_number,variable_symbol,issue_date,due_date,currency,cnb_rate,amount_without_vat,vat_amount,total_amount,payment_status,note,ocr_status,source_type) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([Auth::workspaceId(),trim($d['supplier']??''),trim($d['ico']??''),trim($d['dic']??''),trim($d['document_number']??''),trim($d['variable_symbol']??''),$d['issue_date']??null,$d['due_date']??null,strtoupper($d['currency']??'CZK'),(float)($d['cnb_rate']??1),(float)($d['amount_without_vat']??0),(float)($d['vat_amount']??0),(float)($d['total_amount']??0),$d['payment_status']??'unpaid',trim($d['note']??''),'done','manual']);
        Response::redirect('/received-invoices');
    }

    public function import(): void {
        Auth::requirePermission('expenses'); Auth::verifyCsrf();
        if(empty($_FILES['invoice']['tmp_name'])) Response::abort(422,'Vyberte PDF nebo fotografii faktury.');
        $f=$_FILES['invoice'];
        if((int)$f['error']!==UPLOAD_ERR_OK) Response::abort(422,'Nahrání faktury se nepodařilo.');
        if((int)$f['size']>15*1024*1024) Response::abort(413,'Soubor je příliš velký. Maximum je 15 MB.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $allowed=['application/pdf','image/jpeg','image/png','image/tiff','image/webp'];
        if(!in_array($mime,$allowed,true)) Response::abort(415,'Povolen je PDF nebo obrázek (JPG, PNG, TIFF, WebP).');
        $wid=Auth::workspaceId();$dir=dirname(__DIR__,2).'/storage/received-invoices/'.$wid;if(!is_dir($dir))@mkdir($dir,0775,true);
        $safe=preg_replace('/[^a-zA-Z0-9._-]+/','_',basename((string)$f['name']))?:'invoice';$path=$dir.'/'.bin2hex(random_bytes(16)).'_'.$safe;
        if(!move_uploaded_file($f['tmp_name'],$path)) Response::abort(500,'Fakturu se nepodařilo uložit.');
        $this->db->prepare('INSERT INTO received_invoices(workspace_id,payment_status,attachment_path,ocr_status,source_type,note) VALUES(?,?,?,?,?,?)')->execute([$wid,'unpaid',$path,'processing','upload','Nahraná faktura. Probíhá automatické načtení údajů.']);
        $id=(int)$this->db->lastInsertId();
        try {
            $ocr=OcrService::extract($path);
            $this->applyOcr($id,$ocr);
        } catch(\Throwable $e) {
            $this->db->prepare("UPDATE received_invoices SET ocr_status='failed',note=? WHERE id=? AND workspace_id=?")->execute(['Faktura byla uložena, ale automatické načtení se nepodařilo: '.$e->getMessage(),$id,$wid]);
        }
        Response::redirect('/received-invoices?imported='.$id);
    }

    public function edit(int $id): void {
        Auth::requirePermission('expenses');$s=$this->db->prepare('SELECT * FROM received_invoices WHERE id=? AND workspace_id=?');$s->execute([$id,Auth::workspaceId()]);$row=$s->fetch();if(!$row)Response::abort(404,'Přijatá faktura nenalezena.');View::render('received/edit',['title'=>'Upravit přijatou fakturu','row'=>$row]);
    }

    public function update(int $id): void {
        Auth::requirePermission('expenses');Auth::verifyCsrf();$d=$_POST;$this->db->prepare('UPDATE received_invoices SET supplier=?,ico=?,dic=?,document_number=?,variable_symbol=?,issue_date=?,due_date=?,currency=?,cnb_rate=?,amount_without_vat=?,vat_amount=?,total_amount=?,payment_status=?,paid_amount=?,note=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND workspace_id=?')->execute([trim($d['supplier']??''),trim($d['ico']??''),trim($d['dic']??''),trim($d['document_number']??''),trim($d['variable_symbol']??''),$d['issue_date']??null,$d['due_date']??null,strtoupper($d['currency']??'CZK'),(float)($d['cnb_rate']??1),(float)($d['amount_without_vat']??0),(float)($d['vat_amount']??0),(float)($d['total_amount']??0),$d['payment_status']??'unpaid',(float)($d['paid_amount']??0),trim($d['note']??''),$id,Auth::workspaceId()]);Response::redirect('/received-invoices');
    }

    public function download(int $id): void {
        Auth::requirePermission('expenses');$s=$this->db->prepare('SELECT attachment_path FROM received_invoices WHERE id=? AND workspace_id=?');$s->execute([$id,Auth::workspaceId()]);$path=(string)($s->fetchColumn()?:'');if($path===''||!is_file($path))Response::abort(404,'Příloha faktury nebyla nalezena.');$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);header('Content-Type: '.$mime);header('Content-Disposition: inline; filename="'.basename($path).'"');header('Content-Length: '.filesize($path));readfile($path);exit;
    }

    public function applyOcr(int $id,array $ocr): void {
        $data=is_array($ocr['data']??null)?$ocr['data']:[];
        $status=($ocr['status']??'')==='ok'?'done':'failed';
        $note=$status==='done'?'Údaje byly automaticky načteny z faktury. Zkontrolujte je před zaúčtováním.':($ocr['message']??'Automatické načtení se nepodařilo.');
        $this->db->prepare('UPDATE received_invoices SET supplier=?,ico=?,dic=?,document_number=?,variable_symbol=?,issue_date=?,due_date=?,currency=?,amount_without_vat=?,vat_amount=?,total_amount=?,ocr_json=?,ocr_status=?,note=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([trim((string)($data['supplier']??''))?:null,trim((string)($data['ico']??''))?:null,trim((string)($data['dic']??''))?:null,trim((string)($data['document_number']??''))?:null,trim((string)($data['variable_symbol']??''))?:null,$this->dateOrNull($data['issue_date']??null),$this->dateOrNull($data['due_date']??null),strtoupper(trim((string)($data['currency']??'CZK')))?:'CZK',(float)($data['amount_without_vat']??0),(float)($data['vat_amount']??0),(float)($data['total']??$data['total_amount']??0),json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$status,$note,$id]);
    }

    private function dateOrNull(mixed $value): ?string { $v=trim((string)$value); if($v==='')return null; $ts=strtotime($v); return $ts?date('Y-m-d',$ts):null; }
}
