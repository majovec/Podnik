<?php
namespace App\Services;
use PDO;
final class DocumentService {
 public static function publicToken(PDO $db): string { do{$token=bin2hex(random_bytes(32));$s=$db->prepare('SELECT COUNT(*) FROM documents WHERE public_token=?');$s->execute([$token]);}while((int)$s->fetchColumn()>0); return $token; }
 public static function nextNumber(PDO $db,int $wid,string $type): string {
   $defaults=['invoice'=>'FV','offer'=>'CN','order'=>'OBJ','proforma'=>'ZAL','credit'=>'DOB','delivery'=>'DL'];$default=$defaults[$type]??'DOC';$year=(int)date('Y');$ownTx=false;
   if(!$db->inTransaction()){ $db->exec('BEGIN IMMEDIATE');$ownTx=true; }
   try{
     $q=$db->prepare('SELECT * FROM document_series WHERE workspace_id=? AND doc_type=?');$q->execute([$wid,$type]);$row=$q->fetch();
     if(!$row){$prefix=$default;$n=1;$db->prepare('INSERT INTO document_series(workspace_id,doc_type,prefix,next_number,series_year,year_prefix) VALUES(?,?,?,?,?,?)')->execute([$wid,$type,$prefix,2,$year,$prefix]);}
     else{
       $prefix=(string)($row['prefix']?:$default);$n=(int)($row['next_number']?:1);$storedYear=(int)($row['series_year']??0);
       if($storedYear!==$year){$n=1;$db->prepare('UPDATE document_series SET next_number=2,series_year=?,year_prefix=? WHERE workspace_id=? AND doc_type=?')->execute([$year,$prefix,$wid,$type]);}
       else $db->prepare('UPDATE document_series SET next_number=next_number+1 WHERE workspace_id=? AND doc_type=?')->execute([$wid,$type]);
     }
     if($ownTx)$db->commit();return $prefix.'-'.$year.'-'.str_pad((string)$n,5,'0',STR_PAD_LEFT);
   }catch(\Throwable $e){if($ownTx&&$db->inTransaction())$db->rollBack();throw $e;}
 }
 public static function paid(PDO $db,int $wid,int $docId):float{$s=$db->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE workspace_id=? AND document_id=?');$s->execute([$wid,$docId]);return (float)$s->fetchColumn();}
 public static function refreshPaymentStatus(PDO $db,int $wid,int $docId):void{$s=$db->prepare('SELECT total_with_vat FROM documents WHERE id=? AND workspace_id=?');$s->execute([$docId,$wid]);$total=(float)$s->fetchColumn();$paid=self::paid($db,$wid,$docId);$status=$paid<=0?'unpaid':($paid+0.01>=$total?'paid':'partially_paid');$db->prepare('UPDATE documents SET payment_status=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND workspace_id=?')->execute([$status,$docId,$wid]);}
 public static function createFromTemplate(PDO $db,array $r):?int { $t=json_decode($r['template_json']??'',true);if(!$t)return null;$wid=(int)$r['workspace_id'];$cid=(int)$r['customer_id'];$type='invoice';$n=self::nextNumber($db,$wid,$type);$items=$t['items']??[];$sub=$vat=0;foreach($items as $it){$line=round((float)$it['quantity']*(float)$it['unit_price'],2);$sub+=$line;$vat+=round($line*(float)($it['vat_rate']??0)/100,2);} $db->prepare('INSERT INTO documents(workspace_id,doc_type,doc_number,variable_symbol,customer_id,status,payment_status,issue_date,due_date,total_without_vat,total_vat,total_with_vat,created_by,public_token) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$wid,$type,$n,preg_replace('/\D/','',$n),$cid,'issued','unpaid',date('Y-m-d'),date('Y-m-d',strtotime('+14 days')),$sub,$vat,$sub+$vat,null,self::publicToken($db)]);$id=(int)$db->lastInsertId();$st=$db->prepare('INSERT INTO document_items(document_id,name,quantity,unit,unit_price,vat_rate,line_total) VALUES(?,?,?,?,?,?,?)');foreach($items as $it)$st->execute([$id,$it['name'],(float)$it['quantity'],$it['unit']??'ks',(float)$it['unit_price'],(float)($it['vat_rate']??0),round((float)$it['quantity']*(float)$it['unit_price'],2)]);return $id;}
}
