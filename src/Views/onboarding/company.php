<?php
$prev='/uvod?step=1';
?>
<div class="onboarding-shell onboarding-r29-page" data-onboarding-build="2026.09.22-r29">
  <div class="onboarding-progress" aria-label="Průběh průvodce"><?php for($i=1;$i<=7;$i++): ?><i class="<?=$i<=2?'done':''?>"></i><?php endfor; ?></div>
  <div class="onboarding-card r29-card">
    <div class="onboarding-visual r29-visual">
      <div class="onboarding-glow"></div>
      <div class="onboarding-robot" aria-hidden="true"><span class="ob-antenna"></span><span class="ob-head"><b></b><b></b></span><span class="ob-body"><i></i></span><span class="ob-arm left"></span><span class="ob-arm right"></span><span class="ob-leg left"></span><span class="ob-leg right"></span></div>
      <div class="onboarding-spark s1">✦</div><div class="onboarding-spark s2">•</div><div class="onboarding-spark s3">✦</div>
      <div class="visual-caption"><b>Nia</b><span>tvůj průvodce Byzniem</span></div>
    </div>
    <div class="onboarding-content r29-content">
      <div class="ob-step">NIA · KROK 2 Z 7</div>
      <h1>Nastavme tvoji firmu</h1>
      <p>Stačí základní údaje. IČO může Byznio použít pro načtení údajů z ARES. Ostatní můžeš kdykoli doplnit v Nastavení.</p>

      <form class="r29-company-form" method="post" action="/uvod/company" autocomplete="on">
        <input type="hidden" name="_csrf" value="<?=\App\Core\View::e(App\Core\Auth::csrf())?>">
        <div class="r29-form-heading"><strong>Údaje o firmě</strong><span>Vyplň jen to, co máš po ruce.</span></div>
        <div class="r29-field">
          <label for="r29-company-name">Název firmy</label>
          <input id="r29-company-name" name="company_name" autocomplete="organization" placeholder="např. Novák servis s.r.o." value="<?=\App\Core\View::e($company['name']??'')?>">
        </div>
        <div class="r29-grid">
          <div class="r29-field"><label for="r29-ico">IČO</label><input id="r29-ico" name="ico" inputmode="numeric" autocomplete="off" placeholder="12345678" value="<?=\App\Core\View::e($company['ico']??'')?>"></div>
          <div class="r29-field"><label for="r29-dic">DIČ</label><input id="r29-dic" name="dic" placeholder="CZ12345678" value="<?=\App\Core\View::e($company['dic']??'')?>"></div>
        </div>
        <label class="r29-check"><input type="checkbox" name="ares" value="1" checked><span><b>Načíst údaje z ARES</b><small>Po zadání IČO doplníme dostupné údaje automaticky.</small></span></label>
        <div class="r29-grid">
          <div class="r29-field"><label for="r29-street">Ulice a číslo</label><input id="r29-street" name="street" autocomplete="street-address" placeholder="Hlavní 123" value="<?=\App\Core\View::e($company['street']??'')?>"></div>
          <div class="r29-field"><label for="r29-city">Město</label><input id="r29-city" name="city" autocomplete="address-level2" placeholder="Ostrava" value="<?=\App\Core\View::e($company['city']??'')?>"></div>
        </div>
        <div class="r29-grid">
          <div class="r29-field"><label for="r29-zip">PSČ</label><input id="r29-zip" name="zip" inputmode="numeric" autocomplete="postal-code" placeholder="702 00" value="<?=\App\Core\View::e($company['zip']??'')?>"></div>
          <div class="r29-field"><label for="r29-phone">Telefon</label><input id="r29-phone" name="phone" type="tel" autocomplete="tel" placeholder="+420 777 123 456" value="<?=\App\Core\View::e($company['phone']??'')?>"></div>
        </div>
        <div class="r29-actions">
          <a class="r29-btn secondary" href="<?=$prev?>">← Zpět</a>
          <button class="r29-btn primary" type="submit">Uložit a pokračovat →</button>
        </div>
      </form>
      <div class="r29-skip"><form method="post" action="/uvod/skip"><input type="hidden" name="_csrf" value="<?=\App\Core\View::e(App\Core\Auth::csrf())?>"><button type="submit">Přeskočit průvodce</button></form></div>
    </div>
  </div>
</div>
<style>
.onboarding-r29-page .r29-card{overflow:hidden}.onboarding-r29-page .r29-content{justify-content:flex-start}.r29-company-form{display:block!important;visibility:visible!important;opacity:1!important;width:100%!important;height:auto!important;max-height:none!important;overflow:visible!important;position:relative!important;z-index:5!important;margin:8px 0 0!important}.r29-form-heading{display:flex!important;align-items:baseline;justify-content:space-between;gap:12px;margin:0 0 10px;color:#172b4d}.r29-form-heading strong{font-size:13px}.r29-form-heading span{font-size:10px;color:#8391a5}.r29-field{display:block!important;visibility:visible!important;opacity:1!important;margin:0 0 10px}.r29-field label{display:block!important;color:#4f617b;font-size:11px;font-weight:850;margin:0 0 5px}.r29-field input{display:block!important;visibility:visible!important;opacity:1!important;width:100%!important;min-height:44px!important;height:44px!important;padding:9px 12px!important;border:1px solid #d7e0ec!important;border-radius:11px!important;background:#fff!important;color:#172b4d!important;box-sizing:border-box!important}.r29-grid{display:grid!important;grid-template-columns:1fr 1fr;gap:10px}.r29-check{display:flex!important;align-items:center;gap:8px;margin:2px 0 10px;color:#63758d;font-size:11px}.r29-check input{width:17px;height:17px;flex:0 0 17px}.r29-check span{display:flex;flex-direction:column;gap:2px}.r29-check b{color:#51647e;font-size:11px}.r29-check small{font-size:9px;color:#8a98aa}.r29-actions{display:flex!important;align-items:center;justify-content:space-between;gap:10px;margin-top:8px!important}.r29-btn{display:inline-flex!important;visibility:visible!important;opacity:1!important;align-items:center;justify-content:center;min-height:46px;padding:10px 14px;border-radius:12px;border:1px solid #dce5ef;background:#fff;color:#223655;font-weight:850;cursor:pointer;white-space:nowrap;text-decoration:none}.r29-btn.primary{background:#1677ee!important;color:#fff!important;border-color:#1677ee!important;box-shadow:0 10px 22px rgba(22,119,238,.18)}.r29-skip{margin-top:9px;text-align:right}.r29-skip button{border:0;background:transparent;color:#8492a5;font-size:10px;text-decoration:underline;cursor:pointer;padding:0}
@media(max-width:760px){.onboarding-r29-page{padding:10px 10px 24px!important;display:block!important;min-height:auto!important;height:auto!important;overflow:visible!important}.onboarding-r29-page .onboarding-progress{top:76px}.onboarding-r29-page .r29-card{display:block!important;margin:8px auto 0!important;height:auto!important;max-height:none!important;overflow:visible!important;border-radius:20px!important}.onboarding-r29-page .r29-visual{min-height:104px!important;height:104px!important}.onboarding-r29-page .r29-content{display:block!important;height:auto!important;max-height:none!important;overflow:visible!important;padding:18px 16px 16px!important}.onboarding-r29-page .r29-content h1{font-size:25px!important;line-height:1.05!important;margin:7px 0 8px!important}.onboarding-r29-page .r29-content>p{font-size:13px!important;line-height:1.42!important;margin:0 0 12px!important}.r29-company-form{margin-top:4px!important}.r29-grid{grid-template-columns:1fr!important;gap:0!important}.r29-form-heading span{display:none}.r29-field{margin-bottom:8px}.r29-field label{font-size:9px;margin-bottom:3px}.r29-field input{height:38px!important;min-height:38px!important;border-radius:9px!important;font-size:13px!important;padding:7px 10px!important}.r29-check{font-size:10px;margin:1px 0 8px;line-height:1.2}.r29-check b{font-size:10px}.r29-check small{font-size:9px}.r29-actions{position:sticky!important;bottom:0!important;z-index:20!important;margin:6px -16px -16px!important;padding:12px 16px 10px!important;background:linear-gradient(180deg,rgba(255,255,255,.2),#fff 24%)!important}.r29-btn{min-height:42px;font-size:11px;padding:9px 10px}.r29-btn.primary{flex:1}.r29-skip{text-align:center;margin-top:7px;padding-bottom:2px}}
</style>
