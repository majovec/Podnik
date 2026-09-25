<?php
namespace App\Services;
use PDO;
final class AccountingExportService
{
    public static function csv(PDO $db,int $wid,string $from,string $to): string
    {
        $q=$db->prepare('SELECT d.*,c.company_name,c.ico,c.dic FROM documents d LEFT JOIN customers c ON c.id=d.customer_id WHERE d.workspace_id=? AND d.issue_date BETWEEN ? AND ? AND d.status<>"cancelled" ORDER BY d.issue_date,d.id');
        $q->execute([$wid,$from,$to]);$fh=fopen('php://temp','w+');fputcsv($fh,['datum','typ','číslo','IČO','DIČ','základ','DPH','celkem','stav','splatnost'],';');
        while($r=$q->fetch(PDO::FETCH_ASSOC))fputcsv($fh,[$r['issue_date'],$r['doc_type'],$r['doc_number'],$r['ico'],$r['dic'],$r['total_without_vat'],$r['total_vat'],$r['total_with_vat'],$r['payment_status'],$r['due_date']],';');
        rewind($fh);return stream_get_contents($fh);
    }
    public static function pohodaXml(PDO $db,int $wid,string $from,string $to): string
    {
        $q=$db->prepare('SELECT d.*,c.company_name,c.first_name,c.last_name,c.ico,c.dic,c.street,c.city,c.zip,c.email FROM documents d LEFT JOIN customers c ON c.id=d.customer_id WHERE d.workspace_id=? AND d.issue_date BETWEEN ? AND ? AND d.status<>"cancelled" ORDER BY d.issue_date,d.id');$q->execute([$wid,$from,$to]);
        $x=new \XMLWriter();$x->openMemory();$x->startDocument('1.0','UTF-8');$x->startElement('dat:dataPack');$x->writeAttribute('xmlns:dat','http://www.stormware.cz/schema/version_2/data.xsd');$x->writeAttribute('xmlns:typ','http://www.stormware.cz/schema/version_2/type.xsd');$x->writeAttribute('ico','');$x->writeAttribute('application','Byznio');$x->writeAttribute('version','2.0');
        while($d=$q->fetch(PDO::FETCH_ASSOC)){$x->startElement('dat:dataPackItem');$x->writeAttribute('id','byznio-'.$d['id']);$x->startElement('inv:invoice');$x->writeAttribute('xmlns:inv','http://www.stormware.cz/schema/version_2/invoice.xsd');$x->writeElement('inv:number',$d['doc_number']);$x->writeElement('inv:date',$d['issue_date']);$x->writeElement('inv:dateDue',$d['due_date']);$x->writeElement('inv:note','Export z Byznio');$x->startElement('inv:partnerIdentity');$x->writeElement('typ:company',$d['company_name']?:trim(($d['first_name']??'').' '.($d['last_name']??'')));$x->writeElement('typ:ico',$d['ico']??'');$x->writeElement('typ:dic',$d['dic']??'');$x->endElement();$x->writeElement('inv:amount',$d['total_with_vat']);$x->endElement();$x->endElement();}$x->endElement();$x->endDocument();return $x->outputMemory();
    }
    public static function package(PDO $db,int $wid,string $from,string $to,string $base): string
    {
        $dir=$base.'/storage/exports';if(!is_dir($dir))mkdir($dir,0775,true);$tmp=$dir.'/byznio_'.$from.'_'.$to.'_'.bin2hex(random_bytes(4));mkdir($tmp,0775,true);
        file_put_contents($tmp.'/doklady.csv',self::csv($db,$wid,$from,$to));file_put_contents($tmp.'/doklady.xml',self::pohodaXml($db,$wid,$from,$to));
        $q=$db->prepare('SELECT id,doc_number FROM documents WHERE workspace_id=? AND issue_date BETWEEN ? AND ? AND status<>"cancelled" ORDER BY id');
        $q->execute([$wid,$from,$to]);
        foreach($q as $d){
            $items=$db->prepare('SELECT * FROM document_items WHERE document_id=?');$items->execute([(int)$d['id']]);
            $doc=$db->prepare('SELECT * FROM documents WHERE id=?');$doc->execute([(int)$d['id']]);$row=$doc->fetch();
            if(!$row) continue;
            $cust=$db->prepare('SELECT * FROM customers WHERE id=?');$cust->execute([(int)$row['customer_id']]);$c=$cust->fetch()?:[];
            try{$pdf=PdfService::invoice($row,$items->fetchAll(),['name'=>'Byznio'],$c);file_put_contents($tmp.'/'.$d['doc_number'].'.pdf',$pdf);}catch(\Throwable $e){}
        }
        $zip=$dir.'/byznio_'.$from.'_'.$to.'_'.date('YmdHis').'.zip';$za=new \ZipArchive();$za->open($zip,\ZipArchive::CREATE|\ZipArchive::OVERWRITE);$it=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmp,\FilesystemIterator::SKIP_DOTS));foreach($it as $f)if($f->isFile())$za->addFile($f->getPathname(),$f->getFilename());$za->close();foreach(glob($tmp.'/*')?:[] as $f)@unlink($f);@rmdir($tmp);return $zip;
    }
}
