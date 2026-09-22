<?php $prev='/uvod?step=6'; ?>
<div class="onboarding-shell onboarding-r29-page" data-onboarding-build="2026.09.22-r29">
  <div class="onboarding-progress" aria-label="Průběh průvodce"><?php for($i=1;$i<=7;$i++): ?><i class="done"></i><?php endfor; ?></div>
  <div class="onboarding-card r29-card">
    <div class="onboarding-visual r29-visual">
      <div class="onboarding-glow"></div>
      <div class="onboarding-robot" aria-hidden="true"><span class="ob-antenna"></span><span class="ob-head"><b></b><b></b></span><span class="ob-body"><i></i></span><span class="ob-arm left"></span><span class="ob-arm right"></span><span class="ob-leg left"></span><span class="ob-leg right"></span></div>
      <div class="onboarding-spark s1">✦</div><div class="onboarding-spark s2">•</div><div class="onboarding-spark s3">✦</div>
      <div class="visual-caption"><b>Nia</b><span>tvůj průvodce Byzniem</span></div>
    </div>
    <div class="onboarding-content r29-content">
      <div class="ob-step">NIA · KROK 7 Z 7</div>
      <h1>Nia je součástí Byznia</h1>
      <p>Robot Nia bude létat po obrazovce, může tě sám upozornit na důležité věci a po kliknutí jí zadáš úkol. U citlivých finančních akcí nejdřív připraví návrh a čeká na potvrzení.</p>
      <div class="r29-preview"><div class="r29-orb">✦</div><div><strong>„Dnes máš 3 úkoly.“</strong><span>Nia může sama říct, co je potřeba řešit, a po kliknutí jí můžeš zadat vlastní úkol.</span></div><div class="r29-demo">Napiš Nii, co potřebuješ… <b>Poslat</b></div></div>
      <div class="r29-final-actions">
        <a class="r29-btn secondary" href="<?=$prev?>">← Zpět</a>
        <form method="post" action="/uvod/complete" class="r29-finish-form"><input type="hidden" name="_csrf" value="<?=\App\Core\View::e(App\Core\Auth::csrf())?>"><button class="r29-btn primary" type="submit">Dokončit a otevřít Byznio →</button></form>
      </div>
      <div class="r29-skip"><form method="post" action="/uvod/skip"><input type="hidden" name="_csrf" value="<?=\App\Core\View::e(App\Core\Auth::csrf())?>"><button type="submit">Přeskočit průvodce</button></form></div>
    </div>
  </div>
</div>
<style>
.onboarding-r29-page .r29-card{overflow:hidden}.onboarding-r29-page .r29-content{justify-content:flex-start}.r29-preview{display:grid!important;grid-template-columns:54px 1fr;gap:10px;align-items:center;border:1px solid #e1e9f3;border-radius:17px;background:#f8fbff;padding:14px;margin:4px 0 18px;min-height:140px}.r29-orb{width:54px;height:54px;border-radius:18px;background:linear-gradient(145deg,#eaf4ff,#f0ecff);display:grid;place-items:center;color:#5946e8;font-size:22px}.r29-preview strong,.r29-preview span{display:block}.r29-preview strong{color:#172b4d;font-size:18px}.r29-preview span{color:#728198;font-size:12px;line-height:1.5;margin-top:4px}.r29-demo{grid-column:1/-1;background:#fff;border:1px solid #dce5ef;border-radius:11px;padding:10px;color:#91a0b2;font-size:11px}.r29-demo b{float:right;background:#5946e8;color:#fff;border-radius:8px;padding:6px 9px;margin-top:-5px}.r29-final-actions{display:flex!important;align-items:center;justify-content:space-between;gap:10px}.r29-finish-form{display:block!important;visibility:visible!important;opacity:1!important;margin:0!important;padding:0!important}.r29-final-actions .r29-btn{display:inline-flex!important;visibility:visible!important;opacity:1!important}.r29-final-actions .r29-btn.primary{min-width:235px}.r29-skip{margin-top:9px;text-align:right}.r29-skip button{border:0;background:transparent;color:#8492a5;font-size:10px;text-decoration:underline;cursor:pointer;padding:0}
@media(max-width:760px){.onboarding-r29-page{padding:10px 10px 24px!important;display:block!important;min-height:auto!important;height:auto!important;overflow:visible!important}.onboarding-r29-page .onboarding-progress{top:76px}.onboarding-r29-page .r29-card{display:block!important;margin:8px auto 0!important;height:auto!important;max-height:none!important;overflow:visible!important;border-radius:20px!important}.onboarding-r29-page .r29-visual{min-height:104px!important;height:104px!important}.onboarding-r29-page .r29-content{display:block!important;height:auto!important;max-height:none!important;overflow:visible!important;padding:18px 16px 16px!important}.onboarding-r29-page .r29-content h1{font-size:25px!important;line-height:1.05!important;margin:7px 0 8px!important}.onboarding-r29-page .r29-content>p{font-size:13px!important;line-height:1.42!important;margin:0 0 12px!important}.r29-preview{grid-template-columns:48px 1fr;padding:12px;min-height:0;margin-bottom:12px}.r29-orb{width:48px;height:48px}.r29-preview strong{font-size:15px}.r29-preview span{font-size:10px}.r29-demo{font-size:10px}.r29-final-actions{position:sticky!important;bottom:0!important;z-index:20!important;margin:0 -16px -16px!important;padding:12px 16px 10px!important;background:linear-gradient(180deg,rgba(255,255,255,.2),#fff 24%)!important}.r29-final-actions .r29-btn{min-height:42px;font-size:11px;padding:9px 10px}.r29-final-actions .r29-btn.primary{min-width:0;flex:1}.r29-skip{text-align:center;margin-top:7px;padding-bottom:2px}}
</style>
