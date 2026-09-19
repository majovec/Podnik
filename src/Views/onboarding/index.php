<?php
$step=max(1,min(7,(int)($step??1)));
$steps=[
  1=>['title'=>'Nejdřív nastavíme firmu','text'=>'Začneme údaji firmy. Když zadáš IČO, můžu údaje zkusit doplnit z ARES.','target'=>null],
  2=>['title'=>'Tady jsou tvoji zákazníci','text'=>'V CRM najdeš kontakty, komunikaci, doklady i zakázky každého zákazníka.','target'=>'customers'],
  3=>['title'=>'Tady vznikají doklady','text'=>'Faktury, nabídky, objednávky, dobropisy i veřejné odkazy pro zákazníky máš na jednom místě.','target'=>'documents'],
  4=>['title'=>'Zakázky drží práci pohromadě','text'=>'U zakázky uvidíš rozpočet, práci, materiál, náklady a výsledek.','target'=>'jobs'],
  5=>['title'=>'Banka a párování plateb','text'=>'Tady se načtou transakce a Byznio může pomáhat s párováním plateb k fakturám.','target'=>'bank'],
  6=>['title'=>'Kalendář a úkoly','text'=>'Termíny a úkoly máš přímo v aplikaci, takže důležité věci nezůstanou jen v hlavě.','target'=>'calendar'],
  7=>['title'=>'A kdykoli mě zavoláš','text'=>'Jsem tady vpravo dole. Klikni na mě, napiš mi co potřebuješ a provedu tě dál.','target'=>'nia'],
];
$current=$steps[$step];
?>
<style>
.nia-onboard{max-width:1080px;margin:0 auto}.nia-welcome{display:grid;grid-template-columns:1.1fr .9fr;gap:22px;align-items:stretch}.nia-card{background:linear-gradient(145deg,#eef7ff,#f7f1ff);border:1px solid #d7e5f7;border-radius:26px;padding:25px;box-shadow:var(--shadow)}.nia-character{height:170px;display:flex;align-items:center;justify-content:center}.nia-character svg{height:165px;overflow:visible}.nia-speech{position:relative;background:#fff;border:1px solid #dce6f2;border-radius:21px 21px 21px 7px;padding:18px 19px;box-shadow:0 12px 30px rgba(16,33,63,.08);line-height:1.6}.nia-speech:before{content:"";position:absolute;left:25px;bottom:-9px;width:17px;height:17px;background:#fff;border-right:1px solid #dce6f2;border-bottom:1px solid #dce6f2;transform:rotate(45deg)}.nia-speech strong{display:block;font-size:18px;margin-bottom:5px}.nia-steps{background:#fff;border:1px solid var(--line);border-radius:26px;padding:25px;box-shadow:var(--shadow)}.progress{height:8px;background:#e8eef6;border-radius:99px;overflow:hidden;margin:12px 0 22px}.progress span{display:block;height:100%;width:calc((<?=($step)?> / 7) * 100%);background:linear-gradient(90deg,#1677ee,#5946e8);border-radius:99px}.step-kicker{font-size:11px;text-transform:uppercase;letter-spacing:.1em;font-weight:850;color:#6d7c92}.nia-steps h1{font-size:30px;letter-spacing:-.035em;margin:7px 0 9px}.nia-steps p{color:var(--muted);line-height:1.6}.company-form{margin-top:20px}.guide-tip{margin-top:18px;padding:13px 14px;border-radius:14px;background:#f5f8ff;border:1px solid #dde8f8;color:#425674;font-size:13px}.tour-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}.tour-actions .btn{width:auto}.tour-actions form{display:inline}.tour-skip{border:0;background:none;color:var(--muted);font-weight:750;cursor:pointer;padding:9px}.feature-row{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:18px}.feature{border:1px solid var(--line);border-radius:15px;padding:14px;background:#fbfdff}.feature b{display:block;margin-bottom:4px}.feature span{font-size:13px;color:var(--muted);line-height:1.45}.tour-banner{margin-top:20px;border:1px solid #dce6f3;border-radius:17px;padding:14px 16px;background:#f8fbff;display:flex;justify-content:space-between;gap:14px;align-items:center}.tour-banner strong{display:block}.tour-banner span{font-size:13px;color:var(--muted)}@media(max-width:820px){.nia-welcome{grid-template-columns:1fr}.nia-character{height:125px}.nia-character svg{height:125px}.feature-row{grid-template-columns:1fr}.nia-steps h1{font-size:26px}.tour-actions .btn{width:100%}}
</style>
<div class="nia-onboard">
  <div class="tour-banner"><div><strong>Průvodce Byzniem · krok <?=$step?> ze 7</strong><span>Nia ti postupně ukáže skutečné části aplikace. Nic nemusíš studovat dopředu.</span></div><form method="post" action="/uvod/skip"><input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>"><button class="tour-skip" type="submit">Přeskočit průvodce</button></form></div>
  <div class="progress"><span></span></div>
  <div class="nia-welcome">
    <div class="nia-card">
      <div class="nia-character"><svg viewBox="0 0 120 190" fill="none" aria-hidden="true"><g class="nia-head"><circle cx="60" cy="48" r="30" fill="#FFD8C2"/><path d="M30 47c2-27 49-38 61-4-10-8-22-10-34-5-10 4-16 8-27 9Z" fill="#20395F"/><circle cx="49" cy="51" r="3" fill="#17345A"/><circle cx="71" cy="51" r="3" fill="#17345A"/><path d="M51 65c5 5 13 5 18 0" stroke="#B85B65" stroke-width="3" stroke-linecap="round"/></g><path d="M31 91c8-15 50-15 58 0l12 58H19l12-58Z" fill="#5B46E8"/><path d="M34 103 10 126" stroke="#FFD8C2" stroke-width="12" stroke-linecap="round"/><path d="m86 103 24 23" stroke="#FFD8C2" stroke-width="12" stroke-linecap="round"/><circle cx="10" cy="126" r="7" fill="#FFD8C2"/><circle cx="110" cy="126" r="7" fill="#FFD8C2"/><path d="M47 149 39 179M73 149l8 30" stroke="#20395F" stroke-width="12" stroke-linecap="round"/></svg></div>
      <div class="nia-speech"><strong>Ahoj <?=View::e(Auth::user()['name']??'') ?>, já jsem Nia.</strong><?=View::e($current['text'])?></div>
    </div>
    <div class="nia-steps">
      <div class="step-kicker">Krok <?=$step?> · <?=$step===1?'nastavení firmy':'rychlá orientace'?></div>
      <h1><?=View::e($current['title'])?></h1>
      <?php if($step===1): ?>
        <p>Stačí zadat IČO. Údaje můžeš později kdykoli změnit v Nastavení.</p>
        <form class="form company-form" method="post" action="/uvod/company"><input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>"><div class="field"><label>IČO</label><input name="ico" value="<?=View::e($workspace['ico']??'')?>" inputmode="numeric" placeholder="12345678"></div><label><input type="checkbox" name="ares" value="1" checked> Doplnit údaje z ARES</label><div class="row"><div class="field"><label>DIČ</label><input name="dic" value="<?=View::e($workspace['dic']??'')?>"></div><div class="field"><label>PSČ</label><input name="zip" value="<?=View::e($workspace['zip']??'')?>"></div></div><div class="row"><div class="field"><label>Ulice</label><input name="street" value="<?=View::e($workspace['street']??'')?>"></div><div class="field"><label>Město</label><input name="city" value="<?=View::e($workspace['city']??'')?>"></div></div><button class="btn primary" type="submit">Uložit a pokračovat</button></form>
      <?php elseif($step===7): ?>
        <p>Teď už znáš základní mapu Byznia. Nia zůstane s tebou i po dokončení průvodce.</p><div class="guide-tip">💬 Kliknutím na panáčka vpravo dole kdykoli otevřeš bublinu a můžeš mu napsat vlastní požadavek.</div><div class="tour-actions"><form method="post" action="/uvod/complete"><input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>"><button class="btn primary" type="submit">Dokončit a otevřít Byznio</button></form><a class="btn" href="/uvod?step=6">← Zpět</a></div>
      <?php else: ?>
        <p><?=View::e($current['text'])?></p><div class="feature-row"><div class="feature"><b>Propojené workflow</b><span>Zákazník → nabídka → zakázka → faktura → banka → platba.</span></div><div class="feature"><b>Automatizace</b><span>Upomínky, opakované faktury, párování plateb a přehledy.</span></div><div class="feature"><b>Finance</b><span>Výdaje, cashflow, sklad a daňové podklady na jednom místě.</span></div><div class="feature"><b>Nia</b><span>Pomoc s přehledem, návrhy a akcemi, které vždy nejdřív potvrdíš.</span></div></div><div class="tour-actions"><a class="btn primary" href="/uvod?step=<?=$step+1?>">Ukázat další část →</a><a class="btn" href="/uvod?step=<?=$step-1?>">← Zpět</a></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
(function(){
 const target=<?=json_encode($current['target'],JSON_UNESCAPED_UNICODE)?>;
 if(!target)return;
 const sel=target==='nia'?'#niaFab':'[data-guide="'+target+'"]';
 const el=document.querySelector(sel);if(!el)return;
 const guide=document.createElement('div');guide.className='nia-guide';guide.id='onboardSpot';document.body.appendChild(guide);
 const note=document.createElement('div');note.className='nia-guide-note';note.innerHTML='<strong><?=htmlspecialchars($current['title'],ENT_QUOTES,'UTF-8')?></strong><?=htmlspecialchars($current['text'],ENT_QUOTES,'UTF-8')?>';document.body.appendChild(note);
 function place(){const r=el.getBoundingClientRect();guide.style.left=(r.left-5)+'px';guide.style.top=(r.top-5)+'px';guide.style.width=(r.width+10)+'px';guide.style.height=(r.height+10)+'px';note.style.left=Math.min(Math.max(14,r.left),window.innerWidth-314)+'px';note.style.top=Math.min(window.innerHeight-150,Math.max(80,r.bottom+12))+'px';guide.classList.add('show');note.classList.add('show');}
 place();window.addEventListener('resize',place);window.addEventListener('scroll',place,{passive:true});
})();
</script>
