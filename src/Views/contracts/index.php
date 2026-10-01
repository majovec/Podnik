<div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px"><div><h1 class="h">Smlouvy</h1><div class="sub">Vyberte vzor, doplňte zákazníka a smlouva se uloží i do Dokumentů.</div></div></div>

<h3 style="margin:22px 0 10px">Vzory smluv</h3>
<div class="grid" style="grid-template-columns:repeat(4,1fr)">
<?php foreach($templates as $t):?>
<div class="card"><div class="label"><?=$t['is_system']?'Vzor Byznio':'Vlastní vzor'?></div><h3 style="margin:8px 0 14px"><?=\App\Core\View::e($t['name'])?></h3><a class="btn primary" style="width:100%" href="/contracts/new/<?=$t['id']?>">Vyplnit a vygenerovat</a></div>
<?php endforeach;?>
<?php if(!$templates):?><div class="card"><p class="sub">Zatím nejsou k dispozici žádné vzory.</p></div><?php endif;?>
</div>

<h3 style="margin:30px 0 10px">Uložené smlouvy</h3>
<div class="card">
<div class="tablewrap"><table class="table">
<thead><tr><th>Název</th><th>Zákazník</th><th>Vytvořeno</th><th></th></tr></thead>
<tbody>
<?php foreach($contracts as $c):?>
<tr>
<td><a href="/contracts/<?=$c['id']?>"><?=\App\Core\View::e($c['name'])?></a></td>
<td><?=\App\Core\View::e($c['company_name'] ?: trim(($c['first_name']??'').' '.($c['last_name']??'')))?></td>
<td><?=\App\Core\View::e(substr((string)$c['created_at'],0,10))?></td>
<td style="text-align:right"><a class="btn" href="/contracts/<?=$c['id']?>/pdf" target="_blank">PDF</a></td>
</tr>
<?php endforeach;?>
<?php if(!$contracts):?><tr><td colspan="4" class="sub">Zatím žádné vygenerované smlouvy.</td></tr><?php endif;?>
</tbody>
</table></div>
</div>
