<h1 class="h">Daně a odvody</h1><div class="card" style="margin:12px 0;background:#fff8e1">Výpočet je pouze orientační podklad pro účetní, není to daňové přiznání. Sazby a limity ověřte pro zvolený rok.</div>
<div class="sub">Orientační přehled příjmů, výdajů, DPH, daně z příjmu a odvodů pro zvolený daňový rok.</div>

<div class="grid">
  <div class="card"><div class="label">Příjmy</div><div class="kpi"><?=number_format((float)($estimate['income']??0),2,',',' ')?> Kč</div></div>
  <div class="card"><div class="label">Výdaje</div><div class="kpi"><?=number_format((float)($estimate['expenses']??0),2,',',' ')?> Kč</div></div>
  <div class="card"><div class="label">Zisk</div><div class="kpi"><?=number_format((float)($estimate['profit']??0),2,',',' ')?> Kč</div></div>
  <div class="card"><div class="label">Doporučená rezerva</div><div class="kpi"><?=number_format((float)($estimate['reserve']??0),2,',',' ')?> Kč</div></div>
</div>

<div class="grid" style="margin-top:16px;grid-template-columns:repeat(3,minmax(0,1fr))">
  <div class="card"><div class="label">Daň z příjmu</div><div class="kpi"><?=number_format((float)($estimate['income_tax']??0),2,',',' ')?> Kč</div></div>
  <div class="card"><div class="label">Sociální</div><div class="kpi"><?=number_format((float)($estimate['social']??0),2,',',' ')?> Kč</div></div>
  <div class="card"><div class="label">Zdravotní</div><div class="kpi"><?=number_format((float)($estimate['health']??0),2,',',' ')?> Kč</div></div>
</div>

<div class="card" style="margin-top:16px">
  <h3>Nastavení výpočtu</h3>
  <form class="form" method="post" action="/tax">
    <input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>">
    <div class="row">
      <div class="field"><label>Daňový rok</label><input type="number" name="year" min="2024" max="2100" value="<?=\App\Core\View::e($profile['year']??date('Y'))?>"></div>
      <div class="field"><label>Způsob uplatnění výdajů</label><select name="income_tax_method"><option value="actual" <?=($profile['income_tax_method']??'actual')==='actual'?'selected':''?>>Skutečné výdaje</option><option value="expense_lump" <?=($profile['income_tax_method']??'actual')==='expense_lump'?'selected':''?>>Výdajový paušál</option></select></div>
    </div>
    <div class="row">
      <div class="field"><label>Hranice 23% sazby (Kč)</label><input type="number" step="1" name="tax_threshold" value="<?=\App\Core\View::e($profile['tax_threshold']??1762812)?>"></div><div class="field"><label>Sazba sociálního (%)</label><input type="number" step="0.01" name="social_rate" value="<?=\App\Core\View::e($profile['social_rate']??0)?>"></div>
      <div class="field"><label>Sazba zdravotního (%)</label><input type="number" step="0.01" name="health_rate" value="<?=\App\Core\View::e($profile['health_rate']??0)?>"></div>
    </div>
    <div class="row">
      <div class="field"><label>Výdajový paušál (%)</label><input type="number" step="0.01" min="0" max="100" name="expense_lump_rate" value="<?=\App\Core\View::e((float)($profile['expense_lump_rate']??0.6)*100)?>"></div>
      <div class="field"><label>Daňová sleva / rok (Kč)</label><input type="number" step="0.01" name="tax_credit" value="<?=\App\Core\View::e($profile['tax_credit']??0)?>"></div>
    </div>
    <div class="row">
      <div class="field"><label><input type="checkbox" name="vat_payer" value="1" <?=!empty($profile['vat_payer'])?'checked':''?>> Jsem plátce DPH</label></div>
      <div class="field"><label><input type="checkbox" name="flat_regime" value="1" <?=!empty($profile['flat_regime'])?'checked':''?>> Paušální režim</label></div>
    </div>
    <div class="card" style="background:#f8fafc">
      <h4>Paušální režim – roční vstupy</h4>
      <div class="row">
        <div class="field"><label>Měsíční paušální daň (Kč)</label><input type="number" step="0.01" name="flat_monthly_tax" value="<?=\App\Core\View::e($profile['flat_monthly_tax']??0)?>"></div>
        <div class="field"><label>Měsíční sociální (Kč)</label><input type="number" step="0.01" name="flat_social_monthly" value="<?=\App\Core\View::e($profile['flat_social_monthly']??0)?>"></div>
      </div>
      <div class="field"><label>Měsíční zdravotní (Kč)</label><input type="number" step="0.01" name="flat_health_monthly" value="<?=\App\Core\View::e($profile['flat_health_monthly']??0)?>"></div>
      <p class="sub" style="margin:8px 0 0">Hodnoty paušálního režimu jsou zadávány podle aktuálního pásma a roku. Systém je používá pro orientační rezervu; před podáním přiznání ověřte aktuální pravidla.</p>
    </div>
    <button class="btn primary" type="submit">Uložit nastavení a přepočítat</button>
  </form>
</div>

<div class="card" style="margin-top:16px">
  <h3>DPH a metodika</h3>
  <p>Odhad DPH k odvodu: <b><?=number_format((float)($estimate['vat']??0),2,',',' ')?> Kč</b>.</p>
  <p class="sub" style="margin:0"><?=\App\Core\View::e($estimate['rule_date']??'Výpočet je orientační.')?></p>
</div>
