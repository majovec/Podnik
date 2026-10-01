<?php
namespace App\Controllers;
use PDO;use App\Core\{Auth,Response,View,Env};use Dompdf\Dompdf;use Dompdf\Options;

final class ContractsController {
    public function __construct(private PDO $db){}

    private function customersList():array{$s=$this->db->prepare('SELECT id,company_name,first_name,last_name,ico,dic,street,city,zip FROM customers WHERE workspace_id=? AND active=1 ORDER BY company_name,last_name');$s->execute([Auth::workspaceId()]);return $s->fetchAll();}

    private function customerLabel(array $c):string{
        $n = trim((string)($c['company_name'] ?: trim(($c['first_name']??'').' '.($c['last_name']??''))));
        return $n !== '' ? $n : ('Zákazník #'.$c['id']);
    }

    private function customerAddress(array $c):string{
        $parts=array_filter([$c['street']??null,trim(($c['zip']??'').' '.($c['city']??''))]);
        return implode(', ',$parts);
    }

    public function index():void{
        Auth::requirePermission('invoicing');
        $wid=Auth::workspaceId();
        $tpl=$this->db->prepare('SELECT * FROM contract_templates WHERE is_system=1 OR workspace_id=? ORDER BY is_system DESC,name');
        $tpl->execute([$wid]);
        $templates=$tpl->fetchAll();
        $c=$this->db->prepare('SELECT c.*,cu.company_name,cu.first_name,cu.last_name FROM contracts c LEFT JOIN customers cu ON cu.id=c.customer_id WHERE c.workspace_id=? ORDER BY c.created_at DESC');
        $c->execute([$wid]);
        $contracts=$c->fetchAll();
        View::render('contracts/index',['title'=>'Smlouvy','templates'=>$templates,'contracts'=>$contracts]);
    }

    public function newFromTemplate(int $id):void{
        Auth::requirePermission('invoicing');
        $wid=Auth::workspaceId();
        $t=$this->db->prepare('SELECT * FROM contract_templates WHERE id=? AND (is_system=1 OR workspace_id=?)');
        $t->execute([$id,$wid]);
        $template=$t->fetch();
        if(!$template)Response::abort(404,'Vzor smlouvy nenalezen.');
        View::render('contracts/form',['title'=>'Nová smlouva – '.$template['name'],'template'=>$template,'customers'=>$this->customersList()]);
    }

    public function generate():void{
        Auth::requirePermission('invoicing');
        Auth::verifyCsrf();
        $wid=Auth::workspaceId();
        $templateId=(int)($_POST['template_id']??0);
        $customerId=(int)($_POST['customer_id']??0);
        $t=$this->db->prepare('SELECT * FROM contract_templates WHERE id=? AND (is_system=1 OR workspace_id=?)');
        $t->execute([$templateId,$wid]);
        $template=$t->fetch();
        if(!$template)Response::abort(404,'Vzor smlouvy nenalezen.');
        $cu=$this->db->prepare('SELECT * FROM customers WHERE id=? AND workspace_id=?');
        $cu->execute([$customerId,$wid]);
        $customer=$cu->fetch();
        if(!$customer)Response::abort(404,'Zákazník nenalezen.');
        $w=$this->db->prepare('SELECT * FROM workspaces WHERE id=?');
        $w->execute([$wid]);
        $company=$w->fetch()?:[];

        $predmet=trim((string)($_POST['predmet']??''));
        $cena=trim((string)($_POST['cena']??''));
        $misto=trim((string)($_POST['misto']??($company['city']??'')));
        $datum=trim((string)($_POST['datum']??date('d.m.Y')));

        $dodavatelNazev=trim((string)($company['name']??''));
        $dodavatelAdresa=trim(implode(', ',array_filter([$company['street']??null,trim(($company['zip']??'').' '.($company['city']??''))])));
        $dodavatelIco=trim((string)($company['ico']??''));
        $dodavatelDicRadek=!empty($company['dic'])?(', DIČ: '.$company['dic']):'';

        $zakaznikNazev=$this->customerLabel($customer);
        $zakaznikAdresa=$this->customerAddress($customer);
        $zakaznikIcoRadek='';
        if(!empty($customer['ico'])){$zakaznikIcoRadek=', IČO: '.$customer['ico'];if(!empty($customer['dic']))$zakaznikIcoRadek.=', DIČ: '.$customer['dic'];}

        $map=[
            '{{dodavatel_nazev}}'=>$dodavatelNazev?:'[doplňte název firmy v Nastavení]',
            '{{dodavatel_adresa}}'=>$dodavatelAdresa?:'[doplňte adresu v Nastavení]',
            '{{dodavatel_ico}}'=>$dodavatelIco?:'[doplňte IČO v Nastavení]',
            '{{dodavatel_dic_radek}}'=>$dodavatelDicRadek,
            '{{zakaznik_nazev}}'=>$zakaznikNazev,
            '{{zakaznik_adresa}}'=>$zakaznikAdresa?:'[adresa zákazníka není vyplněna]',
            '{{zakaznik_ico_radek}}'=>$zakaznikIcoRadek,
            '{{predmet}}'=>$predmet?:'[doplňte předmět smlouvy]',
            '{{cena}}'=>$cena?:'[doplňte částku]',
            '{{misto}}'=>$misto?:'[doplňte místo]',
            '{{datum}}'=>$datum,
        ];
        $body=strtr((string)$template['body'],$map);
        $name=$zakaznikNazev.' – '.$template['name'];

        $this->db->beginTransaction();
        try{
            $this->db->prepare('INSERT INTO contracts(workspace_id,customer_id,template_id,name,body,status,created_by) VALUES(?,?,?,?,?,?,?)')
                ->execute([$wid,$customerId,$templateId,$name,$body,'final',Auth::id()]);
            $contractId=(int)$this->db->lastInsertId();

            $o=new Options();$o->set('defaultFont','DejaVu Sans');$o->set('isRemoteEnabled',false);$o->set('isHtml5ParserEnabled',true);
            $d=new Dompdf($o);
            $html='<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:12px;white-space:pre-wrap;line-height:1.5}</style></head><body>'.nl2br(htmlspecialchars($body,ENT_QUOTES,'UTF-8')).'</body></html>';
            $d->loadHtml($html,'UTF-8');$d->setPaper('A4');$d->render();
            $pdfData=$d->output();

            $fileName=bin2hex(random_bytes(20));
            $storage=dirname(__DIR__,2).'/storage/uploads/'.$fileName;
            file_put_contents($storage,$pdfData);
            $dest='storage/uploads/'.$fileName;
            $safeDisplayName=preg_replace('/[\/\\\\:*?"<>|]/','-',$name).'.pdf';
            $this->db->prepare('INSERT INTO documents_files(workspace_id,entity_type,entity_id,file_name,storage_path,mime_type,size) VALUES(?,?,?,?,?,?,?)')
                ->execute([$wid,'contract',$contractId,$safeDisplayName,$dest,'application/pdf',strlen($pdfData)]);
            $fileId=(int)$this->db->lastInsertId();

            $this->db->prepare('UPDATE contracts SET file_id=? WHERE id=? AND workspace_id=?')->execute([$fileId,$contractId,$wid]);
            $this->db->commit();
        }catch(\Throwable $e){
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
        Response::redirect('/contracts/'.$contractId);
    }

    public function show(int $id):void{
        Auth::requirePermission('invoicing');
        $s=$this->db->prepare('SELECT c.*,cu.company_name,cu.first_name,cu.last_name FROM contracts c LEFT JOIN customers cu ON cu.id=c.customer_id WHERE c.id=? AND c.workspace_id=?');
        $s->execute([$id,Auth::workspaceId()]);
        $contract=$s->fetch();
        if(!$contract)Response::abort(404,'Smlouva nenalezena.');
        View::render('contracts/show',['title'=>$contract['name'],'contract'=>$contract]);
    }

    public function pdf(int $id):void{
        Auth::requirePermission('invoicing');
        $s=$this->db->prepare('SELECT f.* FROM contracts c JOIN documents_files f ON f.id=c.file_id WHERE c.id=? AND c.workspace_id=?');
        $s->execute([$id,Auth::workspaceId()]);
        $f=$s->fetch();
        if(!$f)Response::abort(404,'Soubor smlouvy nenalezen.');
        $path=dirname(__DIR__,2).'/'.$f['storage_path'];
        if(!is_file($path))Response::abort(404,'Soubor chybí.');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.basename($f['file_name']).'"');
        readfile($path);
        exit;
    }

    public function destroy(int $id):void{
        Auth::requirePermission('invoicing');
        Auth::verifyCsrf();
        $wid=Auth::workspaceId();
        $s=$this->db->prepare('SELECT c.*,f.storage_path FROM contracts c LEFT JOIN documents_files f ON f.id=c.file_id WHERE c.id=? AND c.workspace_id=?');
        $s->execute([$id,$wid]);
        $row=$s->fetch();
        if(!$row)Response::abort(404,'Smlouva nenalezena.');
        $this->db->beginTransaction();
        try{
            if(!empty($row['file_id']))$this->db->prepare('DELETE FROM documents_files WHERE id=? AND workspace_id=?')->execute([(int)$row['file_id'],$wid]);
            $this->db->prepare('DELETE FROM contracts WHERE id=? AND workspace_id=?')->execute([$id,$wid]);
            $this->db->commit();
        }catch(\Throwable $e){ if($this->db->inTransaction())$this->db->rollBack(); throw $e; }
        if(!empty($row['storage_path'])){$p=dirname(__DIR__,2).'/'.$row['storage_path'];if(is_file($p))@unlink($p);}
        Response::redirect('/contracts');
    }
}
