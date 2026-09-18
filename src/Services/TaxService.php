<?php
namespace App\Services;
final class TaxService {
 public static function estimate(array $d):array {
  $income=(float)($d['income']??0);$expenses=(float)($d['expenses']??0);$profit=max(0,$income-$expenses);$vat=(float)($d['vat']??0);$method=$d['income_tax_method']??'actual';$flat=!empty($d['flat_regime']);$year=(int)($d['year']??date('Y'));
  if($flat){$incomeTax=(float)($d['flat_monthly_tax']??0)*12;$social=(float)($d['flat_social_monthly']??0)*12;$health=(float)($d['flat_health_monthly']??0)*12;}else{$base=$method==='expense_lump'?$income*(1-(float)($d['expense_lump_rate']??0.6)):$profit;$threshold=(float)($d['threshold']??1762812);$incomeTax=0;if($base>0){$incomeTax=min($base,$threshold)*0.15+max(0,$base-$threshold)*0.23;$incomeTax=max(0,$incomeTax-(float)($d['tax_credit']??0));}$social=max(0,$profit*(float)($d['social_rate']??0));$health=max(0,$profit*(float)($d['health_rate']??0));}
  return ['year'=>$year,'income'=>$income,'expenses'=>$expenses,'profit'=>$profit,'vat'=>$vat,'income_tax'=>round($incomeTax,2),'social'=>round($social,2),'health'=>round($health,2),'reserve'=>round($incomeTax+$social+$health,2),'rule_date'=>'Výpočet pro rok '.$year.'; ověřte aktuální sazby a svůj režim s účetním/daňovým poradcem.'];
 }
}
