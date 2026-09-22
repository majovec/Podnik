<?php
$steps=[
 1=>['Vítej v Byzniu','Ahoj! Jsem Nia. Provedu tě během několika krátkých kroků tím nejdůležitějším a nastavíme základ firmy.','welcome'],
 2=>['Nastavme tvoji firmu','Stačí základní údaje. IČO může Byznio použít pro načtení údajů z ARES. Ostatní můžeš kdykoli doplnit v Nastavení.','company'],
 3=>['Tvůj hlavní přehled','Po registraci tě čeká dashboard s financemi, fakturami, zakázkami, úkoly, kalendářem, skladem a upozorněními. Nic důležitého nemusíš hledat po jednotlivých modulech.','dashboard'],
 4=>['Zákazníci a CRM','U každého zákazníka budeš mít na jednom místě kontakty, nabídky, zakázky, faktury, dokumenty a historii.','crm'],
 5=>['Zakázka → práce → faktura','Nabídku můžeš převést na zakázku, přidávat práci a materiál a z ní rovnou vystavit fakturu. Při vystavování faktury můžeš nového zákazníka založit přímo ve formuláři.','flow'],
 6=>['Banka, sklad a automatizace','Byznio hlídá platby, párování, nízké zásoby, termíny a opakované procesy. Dashboard tě upozorní na to, co vyžaduje pozornost.','automation'],
 7=>['Nia je součástí Byznia','Robot Nia bude létat po obrazovce, může tě sám upozornit na důležité věci a po kliknutí jí zadáš úkol. U citlivých finančních akcí nejdřív připraví návrh a čeká na potvrzení.','nia'],
];
[$headline,$copy,$type]=$steps[$step];
$next=$step<7?'/uvod?step='.($step+1):null;
$prev=$step>1?'/uvod?step='.($step-1):null;
?>
<div class="onboarding-shell">
  <div class="onboarding-progress" aria-label="Průběh průvodce"><?php for($i=1;$i<=7;$i++): ?><i class="<?=$i<=$step?'done':''?>"></i><?php endfor; ?></div>
  <div class="onboarding-card">
    <div class="onboarding-visual">
      <div class="onboarding-glow"></div>
      <div class="onboarding-robot" aria-hidden="true"><span class="ob-antenna"></span><span class="ob-head"><b></b><b></b></span><span class="ob-body"><i></i></span><span class="ob-arm left"></span><span class="ob-arm right"></span><span class="ob-leg left"></span><span class="ob-leg right"></span></div>
      <div class="onboarding-spark s1">✦</div><div class="onboarding-spark s2">•</div><div class="onboarding-spark s3">✦</div>
      <div class="visual-caption"><b>Nia</b><span>tvůj průvodce Byzniem</span></div>
    </div>
    <div class="onboarding-content">
      <div class="ob-step">NIA · KROK <?=$step?> Z 7</div>
      <h1><?=$headline?></h1>
      <p><?=$copy?></p>

      <?php if((int)$step===2): ?>
        <form class="ob-company-form ob-company-form-r25" method="post" action="/uvod/company" autocomplete="on">
          <input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>">
          <div class="ob-form-title"><b>Údaje o firmě</b><span>Vyplň jen to, co máš po ruce.</span></div>
          <div class="ob-field"><label>Název firmy</label><input name="company_name" autocomplete="organization" placeholder="např. Novák servis s.r.o." value="<?=View::e($company['name']??'')?>"></div>
          <div class="ob-fields">
            <div class="ob-field"><label>IČO</label><input name="ico" inputmode="numeric" autocomplete="off" placeholder="12345678" value="<?=View::e($company['ico']??'')?>"></div>
            <div class="ob-field"><label>DIČ</label><input name="dic" placeholder="CZ12345678" value="<?=View::e($company['dic']??'')?>"></div>
          </div>
          <label class="ob-check"><input type="checkbox" name="ares" value="1" checked> <span><b>Načíst údaje z ARES</b><small>Po zadání IČO doplníme dostupné údaje automaticky.</small></span></label>
          <div class="ob-fields">
            <div class="ob-field"><label>Ulice a číslo</label><input name="street" autocomplete="street-address" placeholder="Hlavní 123" value="<?=View::e($company['street']??'')?>"></div>
            <div class="ob-field"><label>Město</label><input name="city" autocomplete="address-level2" placeholder="Ostrava" value="<?=View::e($company['city']??'')?>"></div>
          </div>
          <div class="ob-fields ob-fields-last">
            <div class="ob-field"><label>PSČ</label><input name="zip" inputmode="numeric" autocomplete="postal-code" placeholder="702 00" value="<?=View::e($company['zip']??'')?>"></div>
            <div class="ob-field"><label>Telefon</label><input name="phone" type="tel" autocomplete="tel" placeholder="+420 777 123 456" value="<?=View::e($company['phone']??'')?>"></div>
          </div>
          <div class="ob-actions"><a class="ob-btn" href="/uvod?step=1">← Zpět</a><button class="ob-btn primary" type="submit">Uložit a pokračovat →</button></div>
        </form>
      <?php elseif($type==='welcome'): ?>
        <div class="ob-preview welcome-preview"><div class="preview-icon">✦</div><div><b>Začneme jednoduše</b><small>Nastavení firmy → přehled → zákazníci → zakázky a faktury → automatizace → Nia.</small></div></div>
      <?php elseif($type==='dashboard'): ?>
        <div class="ob-preview dashboard-preview"><div class="mini-kpis"><span>Faktury<strong>48 500 Kč</strong></span><span>Pohledávky<strong>12 000 Kč</strong></span><span>Zakázky<strong>6</strong></span></div><div class="mini-chart"><i style="height:42%"></i><i style="height:68%"></i><i style="height:55%"></i><i style="height:82%"></i><i style="height:63%"></i><i style="height:91%"></i></div><div class="mini-lines"><b>Dnešní úkoly</b><span>3 úkoly · 2 faktury po splatnosti</span></div></div>
      <?php elseif($type==='crm'): ?>
        <div class="ob-preview crm-preview"><div class="preview-title"><b>CRM / Zákazníci</b><span>+ Nový</span></div><div class="crm-row"><span class="avatar-dot">N</span><div><b>Novák servis s.r.o.</b><small>IČO 12345678 · zákazník</small></div><em>3 faktury · 1 zakázka</em></div><div class="crm-row"><span class="avatar-dot second">K</span><div><b>Karel Nový</b><small>karel@example.cz</small></div><em>2 dokumenty</em></div></div>
      <?php elseif($type==='flow'): ?>
        <div class="ob-preview flow-preview"><div class="flow-step active"><b>1</b><span>Nabídka</span></div><div class="flow-line"></div><div class="flow-step active"><b>2</b><span>Zakázka</span></div><div class="flow-line"></div><div class="flow-step active"><b>3</b><span>Práce + materiál</span></div><div class="flow-line"></div><div class="flow-step"><b>4</b><span>Faktura</span></div><div class="inline-hint">＋ Nový zákazník lze založit přímo při tvorbě faktury.</div></div>
      <?php elseif($type==='automation'): ?>
        <div class="ob-preview automation-preview"><div class="auto-card"><b>Platba přijde</b><small>→ spárujeme s fakturou</small></div><div class="auto-card"><b>Faktura po splatnosti</b><small>→ připravíme upomínku</small></div><div class="auto-card"><b>Sklad pod minimem</b><small>→ upozorníme na doplnění</small></div></div>
      <?php else: ?>
        <div class="ob-preview nia-preview"><div class="nia-mini-orb"><span>✦</span></div><div><b>„Dnes máš 3 úkoly.“</b><small>Nia může sama říct, co je potřeba řešit, a po kliknutí jí můžeš zadat vlastní úkol.</small></div><div class="nia-input-demo">Napiš Nii, co potřebuješ… <strong>Poslat</strong></div></div>
      <?php endif; ?>

      <?php if((int)$step!==2): ?>
        <div class="ob-actions"><div><?php if($prev): ?><a class="ob-btn" href="<?=$prev?>">← Zpět</a><?php endif; ?></div><?php if($next): ?><a class="ob-btn primary" href="<?=$next?>">Pokračovat →</a><?php else: ?><form method="post" action="/uvod/complete" class="ob-finish-form"><input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>"><button class="ob-btn primary" type="submit">Dokončit a otevřít Byznio →</button></form><?php endif; ?></div>
      <?php endif; ?>
      <div class="ob-skip"><form method="post" action="/uvod/skip"><input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>"><button type="submit">Přeskočit průvodce</button></form></div>
    </div>
  </div>
</div>

