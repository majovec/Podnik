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
.guide-intro{position:relative;min-height:calc(100vh - 76px);padding:20px;background:linear-gradient(135deg,#edf5ff,#f8faff);overflow:hidden}
.guide-intro:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 78% 18%,rgba(89,70,232,.13),transparent 28%),radial-gradient(circle at 20% 72%,rgba(22,119,238,.10),transparent 30%);pointer-events:none}
.guide-preview{position:relative;z-index:1;min-height:calc(100vh - 116px);border:1px solid #dce6f3;border-radius:26px;background:rgba(255,255,255,.72);box-shadow:0 18px 55px rgba(16,33,63,.10);padding:24px}
.guide-preview-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:22px}.guide-preview h1{font-size:30px;margin:0 0 6px;letter-spacing:-.04em}.guide-preview p{margin:0;color:#687991}.guide-preview-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.guide-preview-card{background:#fff;border:1px solid #e1e8f1;border-radius:17px;padding:18px;min-height:120px}.guide-preview-label{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#71819a;font-weight:800}.guide-preview-value{font-size:25px;font-weight:850;margin-top:12px}.guide-preview-lower{display:grid;grid-template-columns:1.3fr 1fr;gap:14px;margin-top:14px}.guide-preview-card.wide{min-height:210px}
.guide-overlay{position:fixed;inset:0;z-index:1000;background:rgba(7,26,58,.30);pointer-events:none}
/* Nia is the guide dialog: one compact chat bubble attached directly to the robot. */
.guide-dialog{position:fixed;z-index:1203;right:24px;bottom:235px;width:min(390px,calc(100vw - 48px));background:rgba(255,255,255,.985);border:1px solid #dce6f3;border-radius:22px 22px 10px 22px;padding:16px 16px 13px;box-shadow:0 22px 60px rgba(7,26,58,.27);pointer-events:auto;color:#31496d}
.guide-dialog:after{content:"";position:absolute;right:38px;bottom:-10px;width:19px;height:19px;background:#fff;border-right:1px solid #dce6f3;border-bottom:1px solid #dce6f3;transform:rotate(45deg)}
.guide-kicker{font-size:11px;text-transform:uppercase;letter-spacing:.11em;color:#1677ee;font-weight:900;margin-bottom:5px}.guide-dialog h2{font-size:18px;line-height:1.25;letter-spacing:-.02em;margin:0 0 6px;color:#182c50}.guide-dialog p{color:#526783;line-height:1.48;margin:0;font-size:14px}.guide-actions{display:flex;gap:7px;flex-wrap:nowrap;margin-top:12px}.guide-actions .btn{cursor:pointer;flex:1;text-align:center;min-height:40px;padding:9px 8px;border-radius:12px;text-decoration:none}.guide-actions form{flex:1;display:flex}.guide-actions form .btn{width:100%}.guide-progress{display:flex;gap:3px;margin-top:11px;flex-wrap:wrap}.guide-progress i{width:17px;height:4px;border-radius:99px;background:#dfe6ef}.guide-progress i.active{background:linear-gradient(90deg,#1677ee,#5946e8)}
.guide-skip{position:fixed;z-index:1205;left:18px;bottom:20px;border:1px solid rgba(255,255,255,.82);background:rgba(255,255,255,.94);color:#66758b;border-radius:10px;padding:7px 10px;font-size:11px;font-weight:800;cursor:pointer;pointer-events:auto;box-shadow:0 7px 20px rgba(16,33,63,.12)}
.guide-robot{position:fixed;z-index:1202!important;right:24px;bottom:86px;width:86px;height:110px;display:block!important;visibility:visible!important;opacity:1!important;pointer-events:none;animation:guideRobotFloat 4s ease-in-out infinite;isolation:isolate;transform-origin:bottom right}
.guide-robot .head{position:absolute;left:16px;top:5px;width:54px;height:42px;border-radius:15px;background:linear-gradient(145deg,#fff,#c8daf0);border:3px solid #8da8ca;box-shadow:0 9px 18px rgba(16,33,63,.20)}.guide-robot .screen{position:absolute;inset:6px;border-radius:10px;background:#0b2147;display:flex;align-items:center;justify-content:center;gap:9px}.guide-robot .eye{width:7px;height:11px;border-radius:6px;background:#54e5ff;box-shadow:0 0 9px rgba(84,229,255,.8);animation:guideBlink 5s infinite}.guide-robot .antenna{position:absolute;width:2px;height:12px;left:43px;top:-11px;background:#8da8ca}.guide-robot .antenna:after{content:"";position:absolute;width:7px;height:7px;left:-2.5px;top:-5px;border-radius:50%;background:#8b7cff;box-shadow:0 0 10px #8b7cff}.guide-robot .body{position:absolute;left:22px;top:55px;width:43px;height:39px;border-radius:14px;background:linear-gradient(145deg,#eaf3ff,#a9c1df);border:3px solid #8da8ca;box-shadow:0 9px 18px rgba(16,33,63,.17)}.guide-robot .core{position:absolute;left:13px;top:10px;width:14px;height:14px;border-radius:50%;background:#5946e8;box-shadow:0 0 12px rgba(89,70,232,.6);animation:guideCore 2s infinite}.guide-robot .arm{position:absolute;top:58px;width:9px;height:29px;border-radius:7px;background:#b5cbe3;border:2px solid #8da8ca;transform-origin:top center}.guide-robot .left{left:12px;animation:guideArmL 4.8s infinite}.guide-robot .right{right:12px;animation:guideArmR 5.3s infinite}.guide-robot .foot{position:absolute;top:89px;width:14px;height:11px;border-radius:5px;background:#9fb8d6}.guide-robot .foot.l{left:28px}.guide-robot .foot.r{right:28px}
@keyframes guideRobotFloat{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-5px) rotate(1deg)}}@keyframes guideBlink{0%,44%,48%,100%{transform:scaleY(1)}46%{transform:scaleY(.08)}}@keyframes guideCore{0%,100%{transform:scale(1)}50%{transform:scale(1.14)}}@keyframes guideArmL{0%,100%{transform:rotate(5deg)}50%{transform:rotate(-10deg)}}@keyframes guideArmR{0%,100%{transform:rotate(-5deg)}50%{transform:rotate(10deg)}}
@media(max-width:760px){
  .guide-intro{padding:10px;min-height:calc(100vh - 68px)}.guide-preview{min-height:calc(100vh - 88px);padding:16px;border-radius:20px}.guide-preview-head{margin-bottom:16px}.guide-preview h1{font-size:24px}.guide-preview-grid{grid-template-columns:1fr 1fr}.guide-preview-card{min-height:100px;padding:14px}.guide-preview-value{font-size:20px}.guide-preview-lower{grid-template-columns:1fr}
  .guide-dialog{left:14px;right:14px;bottom:210px;width:auto;padding:15px 14px 12px;border-radius:20px 20px 10px 20px}.guide-dialog:after{right:31px;bottom:-9px}.guide-dialog h2{font-size:17px}.guide-dialog p{font-size:13.5px;line-height:1.45}.guide-actions{gap:6px;margin-top:11px}.guide-actions .btn{font-size:12px;padding:9px 5px;min-height:38px}.guide-progress{margin-top:9px}.guide-progress i{width:14px;height:4px}
  .guide-robot{right:20px;bottom:82px;width:76px;height:98px}.guide-skip{left:14px;bottom:18px;padding:6px 9px;font-size:10px}
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
  <section class="guide-dialog" role="dialog" aria-modal="true" aria-labelledby="guideTitle">
    <div class="guide-kicker">Nia · <?=$step?> / 14</div>
    <h2 id="guideTitle"><?=View::e($current['title'])?></h2>
    <p><?=View::e($current['text'])?></p>
    <div class="guide-actions">
      <?php if($step>1): ?><a class="btn" href="/uvod?step=<?=$step-1?>">← Zpět</a><?php endif; ?>
      <?php if($step===14): ?>
        <form method="post" action="/uvod/complete"><input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>"><button class="btn primary" type="submit">Dokončit →</button></form>
      <?php else: ?>
        <a class="btn" href="<?=View::e($current['route'])?>">Ukázat v aplikaci</a>
        <a class="btn primary" href="/uvod?step=<?=$step+1?>">Další →</a>
      <?php endif; ?>
    </div>
    <div class="guide-progress"><?php for($i=1;$i<=14;$i++): ?><i class="<?=$i===$step?'active':''?>"></i><?php endfor; ?></div>
  </section>
</div>
<script>(function(){const b=document.getElementById('guideSkip');if(!b)return;b.addEventListener('click',async()=>{const f=document.createElement('form');f.method='post';f.action='/uvod/skip';const i=document.createElement('input');i.type='hidden';i.name='_csrf';i.value=<?=json_encode(App\Core\Auth::csrf(),JSON_UNESCAPED_UNICODE)?>;f.appendChild(i);document.body.appendChild(f);f.submit()});})();</script>
