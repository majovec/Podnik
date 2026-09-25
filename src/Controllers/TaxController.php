<?php
namespace App\Controllers;
use PDO;use App\Core\{Auth,View};use App\Services\TaxService;
final class TaxController{public function __construct(private PDO $db){}public function index():void{Auth::requirePermission('tax');$wid=Auth::workspaceId();$s=$this->db->prepare('SELECT * FROM tax_profiles WHERE workspace_id=?');$s->execute([$wid]);$p=$s->fetch()?:['year'=>date('Y'),'income_tax_method'=>'actual'];$year=max(2000,(int)($_GET['year']??$p['year']??date('Y')));View::render('tax/index',['title'=>'Daně a odvody','estimate'=>TaxService::calculate($this->db,$wid,$year,$p),'profile'=>$p,'year'=>$year]);}}
