<?php
$prev='/uvod?step=1';
?>
<div class="onboarding-shell onboarding-r28" data-onboarding-build="2026.09.22-r28">
  <div class="onboarding-progress" aria-label="Průběh průvodce"><?php for($i=1;$i<=7;$i++): ?><i class="<?=$i<=2?'done':''?>"></i><?php endfor; ?></div>
  <div class="onboarding-card">
    <div class="onboarding-visual">
      <div class="onboarding-glow"></div>
      <div class="onboarding-robot" aria-hidden="true"><span class="ob-antenna"></span><span class="ob-head"><b></b><b></b></span><span class="ob-body"><i></i></span><span class="ob-arm left"></span><span class="ob-arm right"></span><span class="ob-leg left"></span><span class="ob-leg right"></span></div>
      <div class="onboarding-spark s1">✦</div><div class="onboarding-spark s2">•</div><div class="onboarding-spark s3">✦</div>
      <div class="visual-caption"><b>Nia</b><span>tvůj průvodce Byzniem</span></div>
    </div>
    <div class="onboarding-content">
      <div class="ob-step">NIA · KROK 2 Z 7</div>
      <h1>Nastavme tvoji firmu</h1>
      <p>Stačí základní údaje. IČO může Byznio použít pro načtení údajů z ARES. Ostatní můžeš kdykoli doplnit v Nastavení.</p>

      <form class="ob-company-form ob-company-form-r28" method="post" action="/uvod/company" autocomplete="on">
        <input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>">
        <div class="ob-form-title"><b>Údaje o firmě</b><span>Vyplň jen to, co máš po ruce.</span></div>
        <div class="ob-field"><label for="ob-company-name">Název firmy</label><input id="ob-company-name" name="company_name" autocomplete="organization" placeholder="např. Novák servis s.r.o." value="<?=View::e($company['name']??'')?>"></div>
        <div class="ob-fields">
          <div class="ob-field"><label for="ob-ico">IČO</label><input id="ob-ico" name="ico" inputmode="numeric" autocomplete="off" placeholder="12345678" value="<?=View::e($company['ico']??'')?>"></div>
          <div class="ob-field"><label for="ob-dic">DIČ</label><input id="ob-dic" name="dic" placeholder="CZ12345678" value="<?=View::e($company['dic']??'')?>"></div>
        </div>
        <label class="ob-check"><input type="checkbox" name="ares" value="1" checked> <span><b>Načíst údaje z ARES</b><small>Po zadání IČO doplníme dostupné údaje automaticky.</small></span></label>
        <div class="ob-fields">
          <div class="ob-field"><label for="ob-street">Ulice a číslo</label><input id="ob-street" name="street" autocomplete="street-address" placeholder="Hlavní 123" value="<?=View::e($company['street']??'')?>"></div>
          <div class="ob-field"><label for="ob-city">Město</label><input id="ob-city" name="city" autocomplete="address-level2" placeholder="Ostrava" value="<?=View::e($company['city']??'')?>"></div>
        </div>
        <div class="ob-fields ob-fields-last">
          <div class="ob-field"><label for="ob-zip">PSČ</label><input id="ob-zip" name="zip" inputmode="numeric" autocomplete="postal-code" placeholder="702 00" value="<?=View::e($company['zip']??'')?>"></div>
          <div class="ob-field"><label for="ob-phone">Telefon</label><input id="ob-phone" name="phone" type="tel" autocomplete="tel" placeholder="+420 777 123 456" value="<?=View::e($company['phone']??'')?>"></div>
        </div>
        <div class="ob-actions"><div><a class="ob-btn" href="<?=$prev?>">← Zpět</a></div><button class="ob-btn primary" type="submit">Uložit a pokračovat →</button></div>
      </form>
      <div class="ob-skip"><form method="post" action="/uvod/skip"><input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>"><button type="submit">Přeskočit průvodce</button></form></div>
    </div>
  </div>
</div>
