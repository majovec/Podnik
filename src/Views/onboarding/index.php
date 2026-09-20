<?php
$step=max(1,min(14,(int)($step??1)));
$steps=[
1=>['title'=>'Začneme přehledem','text'=>'Ahoj, jsem Nia. Provedu tě celým Byzniem a ukážu ti, kde co najdeš. Kdykoli můžeš průvodce přeskočit.','route'=>'/?tour=1&next=2'],
2=>['title'=>'Přehled firmy','text'=>'Tady vidíš příjmy, výdaje, cashflow, faktury, zakázky, úkoly a důležitá upozornění.','route'=>'/?tour=2&next=3'],
3=>['title'=>'CRM a zákazníci','text'=>'Tady vedeš zákazníky, kontakty, historii, doklady a zakázky.','route'=>'/customers?tour=3&next=4'],
4=>['title'=>'Doklady a faktury','text'=>'Tady vytváříš nabídky, faktury a další doklady. Nia tě následně může provést i jednotlivými poli formuláře.','route'=>'/documents?tour=4&next=5'],
5=>['title'=>'Zakázky','text'=>'Zakázka propojuje zákazníka, rozpočet, práci, materiál a následnou fakturaci.','route'=>'/jobs?tour=5&next=6'],
6=>['title'=>'Banka a platby','text'=>'Tady sleduješ bankovní transakce a páruješ skutečné platby s vystavenými doklady.','route'=>'/bank?tour=6&next=7'],
7=>['title'=>'Výdaje','text'=>'Tady zapisuješ firemní náklady a doklady k výdajům.','route'=>'/expenses?tour=7&next=8'],
8=>['title'=>'Sklad','text'=>'Tady spravuješ produkty, zásoby, pohyby a upozornění na nízký stav.','route'=>'/products?tour=8&next=9'],
9=>['title'=>'Kalendář','text'=>'Tady plánuješ termíny, schůzky a práci spojenou se zákazníky a zakázkami.','route'=>'/calendar?tour=9&next=10'],
10=>['title'=>'Úkoly','text'=>'Tady držíš úkoly, termíny a priority na jednom místě.','route'=>'/tasks?tour=10&next=11'],
11=>['title'=>'Automatizace','text'=>'Tady nastavíš opakované faktury, upomínky, reporty a další automatické činnosti.','route'=>'/automation?tour=11&next=12'],
12=>['title'=>'Daně a exporty','text'=>'Tady najdeš daňové údaje, podklady a exporty pro další zpracování.','route'=>'/tax?tour=12&next=13'],
13=>['title'=>'Nia a AI','text'=>'Tady můžeš Niě zadat úkol. U důležitých nebo finančních akcí ti nejdřív ukáže návrh a vyžádá potvrzení.','route'=>'/ai?tour=13&next=14'],
14=>['title'=>'Hotovo','text'=>'Průvodce je dokončený. Nia zůstává v aplikaci jako malý pomocník, kterého můžeš kdykoli otevřít.','route'=>null],
];
$current=$steps[$step];
?>
<style>
/* v32: isolated Nia chat layer. Deliberately uses unique classes so app-wide CSS cannot hide the message. */
.nia-onboard-dialog{position:fixed!important;z-index:1203!important;right:20px!important;bottom:238px!important;width:min(410px,calc(100vw - 40px))!important;box-sizing:border-box!important;display:block!important;visibility:visible!important;opacity:1!important;overflow:visible!important;height:auto!important;min-height:0!important;max-height:none!important;background:#fff!important;color:#182c50!important;border:1px solid #dce6f3!important;border-radius:22px 22px 10px 22px!important;padding:17px!important;box-shadow:0 22px 60px rgba(7,26,58,.27)!important;pointer-events:auto!important}
.nia-onboard-dialog:after{content:"";position:absolute;right:38px;bottom:-10px;width:19px;height:19px;background:#fff;border-right:1px solid #dce6f3;border-bottom:1px solid #dce6f3;transform:rotate(45deg)}
.nia-onboard-kicker{display:block!important;visibility:visible!important;opacity:1!important;font-size:11px!important;text-transform:uppercase!important;letter-spacing:.11em!important;color:#1677ee!important;font-weight:900!important;margin:0 0 6px!important;line-height:1.2!important}
.nia-onboard-title{display:block!important;visibility:visible!important;opacity:1!important;font-size:19px!important;line-height:1.25!important;letter-spacing:-.02em!important;margin:0 0 7px!important;color:#182c50!important;font-weight:850!important}
.nia-onboard-text{display:block!important;visibility:visible!important;opacity:1!important;color:#526783!important;line-height:1.5!important;margin:0!important;font-size:14px!important;white-space:normal!important;overflow:visible!important;height:auto!important;max-height:none!important}
.nia-onboard-actions{display:flex!important;gap:7px!important;flex-wrap:nowrap!important;margin:13px 0 0!important;visibility:visible!important;opacity:1!important}
.nia-onboard-actions a,.nia-onboard-actions button{cursor:pointer!important;flex:1 1 0!important;text-align:center!important;min-height:40px!important;padding:9px 8px!important;border-radius:12px!important;text-decoration:none!important;box-sizing:border-box!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;border:1px solid #dce6f3!important;background:#fff!important;color:#182c50!important;font:inherit!important;font-size:12px!important;font-weight:800!important}
.nia-onboard-actions .primary{background:#1677ee!important;color:#fff!important;border-color:#1677ee!important}
.nia-onboard-actions form{display:flex!important;flex:1 1 0!important;margin:0!important;padding:0!important}
.nia-onboard-actions form button{width:100%!important}
.nia-onboard-progress{display:flex!important;gap:3px!important;margin:11px 0 0!important;flex-wrap:wrap!important}
.nia-onboard-progress i{display:block!important;width:17px!important;height:4px!important;border-radius:99px!important;background:#dfe6ef!important}
.nia-onboard-progress i.active{background:linear-gradient(90deg,#1677ee,#5946e8)!important}
@media(max-width:760px){
 .nia-onboard-dialog{left:14px!important;right:14px!important;bottom:208px!important;width:auto!important;padding:16px!important;border-radius:20px 20px 10px 20px!important}
 .nia-onboard-dialog:after{right:31px!important;bottom:-9px!important}
 .nia-onboard-title{font-size:17px!important}
 .nia-onboard-text{font-size:13.5px!important;line-height:1.48!important}
 .nia-onboard-actions{gap:6px!important;margin-top:11px!important}
 .nia-onboard-actions a,.nia-onboard-actions button{font-size:12px!important;padding:9px 5px!important;min-height:38px!important}
 .nia-onboard-progress{margin-top:9px!important}
 .nia-onboard-progress i{width:14px!important;height:4px!important}
}
</style>
<div class="guide-intro">
  <div class="guide-preview" aria-hidden="true">
    <div class="guide-preview-head"><div><div class="guide-kicker">Byznio · skutečná aplikace</div><h1>Přehled firmy</h1><p>Průvodce ti ukáže, kde co najdeš a jak jednotlivé části spolupracují.</p></div></div>
    <div class="guide-preview-grid"><div class="guide-preview-card"><div class="guide-preview-label">Příjmy</div><div class="guide-preview-value">128 450 Kč</div></div><div class="guide-preview-card"><div class="guide-preview-label">Výdaje</div><div class="guide-preview-value">42 800 Kč</div></div><div class="guide-preview-card"><div class="guide-preview-label">Po splatnosti</div><div class="guide-preview-value">3</div></div><div class="guide-preview-card"><div class="guide-preview-label">Zakázky</div><div class="guide-preview-value">12</div></div></div>
    <div class="guide-preview-lower"><div class="guide-preview-card wide"><div class="guide-preview-label">AI doporučení</div><p style="margin-top:15px;color:#52647d;line-height:1.6">Nia ti během průvodce ukáže, kde sledovat peníze, zákazníky, zakázky a úkoly.</p></div><div class="guide-preview-card wide"><div class="guide-preview-label">Úkoly</div><p style="margin-top:15px;color:#52647d;line-height:1.6">Po dokončení průvodce bude Nia dál dostupná v celé aplikaci.</p></div></div>
  </div>
  <div class="guide-overlay"></div>
  <button class="guide-skip" id="guideSkip" type="button">Přeskočit průvodce</button>
  <div class="guide-robot" aria-hidden="true"><span class="antenna"></span><div class="head"><div class="screen"><span class="eye"></span><span class="eye"></span></div></div><span class="arm left"></span><span class="arm right"></span><div class="body"><span class="core"></span></div><span class="foot l"></span><span class="foot r"></span></div>
  <section class="nia-onboard-dialog" role="dialog" aria-modal="true" aria-labelledby="niaOnboardTitle">
    <div class="nia-onboard-kicker">Nia · <?=$step?> / 14</div>
    <h2 class="nia-onboard-title" id="niaOnboardTitle"><?=View::e($current['title'])?></h2>
    <p class="nia-onboard-text"><?=View::e($current['text'])?></p>
    <div class="nia-onboard-actions">
      <?php if($step>1): ?><a class="btn" href="/uvod?step=<?=$step-1?>">← Zpět</a><?php endif; ?>
      <?php if($step===14): ?>
        <form method="post" action="/uvod/complete"><input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>"><button class="btn primary" type="submit">Dokončit →</button></form>
      <?php else: ?>
        <a class="btn" href="<?=View::e($current['route'])?>">Ukázat v aplikaci</a>
        <a class="btn primary" href="/uvod?step=<?=$step+1?>">Další →</a>
      <?php endif; ?>
    </div>
    <div class="nia-onboard-progress"><?php for($i=1;$i<=14;$i++): ?><i class="<?=$i===$step?'active':''?>"></i><?php endfor; ?></div>
  </section>
</div>
<script>(function(){const b=document.getElementById('guideSkip');if(!b)return;b.addEventListener('click',async()=>{const f=document.createElement('form');f.method='post';f.action='/uvod/skip';const i=document.createElement('input');i.type='hidden';i.name='_csrf';i.value=<?=json_encode(App\Core\Auth::csrf(),JSON_UNESCAPED_UNICODE)?>;f.appendChild(i);document.body.appendChild(f);f.submit()});})();</script>
