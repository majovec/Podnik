<h1 class="h">Nová smlouva – <?=\App\Core\View::e($template['name'])?></h1>
<div class="sub" style="margin-bottom:18px">Údaje vaší firmy se doplní automaticky z Nastavení. Vyberte zákazníka a doplňte zbylé údaje.</div>

<form class="form" method="post" action="/contracts/generate">
<input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>">
<input type="hidden" name="template_id" value="<?=$template['id']?>">

<div class="field">
<label>Zákazník</label>
<select name="customer_id" required>
<option value="">— vyberte zákazníka —</option>
<?php foreach($customers as $c):?>
<option value="<?=$c['id']?>"><?=\App\Core\View::e($c['company_name'] ?: trim(($c['first_name']??'').' '.($c['last_name']??'')))?></option>
<?php endforeach;?>
</select>
<?php if(!$customers):?><div class="sub" style="margin-top:6px">Zatím nemáte žádné zákazníky – <a href="/customers/new">založte prvního</a>.</div><?php endif;?>
</div>

<div class="field"><label>Předmět smlouvy</label><input name="predmet" placeholder="Např. dodávka a montáž kuchyňské linky" required></div>

<div class="row">
<div class="field"><label>Cena (Kč)</label><input name="cena" type="text" placeholder="např. 45 000"></div>
<div class="field"><label>Místo</label><input name="misto" placeholder="např. Praha"></div>
</div>

<div class="field"><label>Datum</label><input name="datum" type="text" value="<?=date('d.m.Y')?>"></div>

<button class="btn primary">Vygenerovat smlouvu</button>
</form>

<div class="card" style="margin-top:22px"><h3>Náhled vzoru</h3><pre style="white-space:pre-wrap;font-family:inherit;font-size:13px;line-height:1.6;color:var(--muted)"><?=\App\Core\View::e($template['body'])?></pre></div>
