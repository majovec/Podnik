<?php
$prev='/uvod?step=6';
?>
<div class="onboarding-shell onboarding-r28" data-onboarding-build="2026.09.22-r28">
  <div class="onboarding-progress" aria-label="Průběh průvodce"><?php for($i=1;$i<=7;$i++): ?><i class="done"></i><?php endfor; ?></div>
  <div class="onboarding-card">
    <div class="onboarding-visual">
      <div class="onboarding-glow"></div>
      <div class="onboarding-robot" aria-hidden="true"><span class="ob-antenna"></span><span class="ob-head"><b></b><b></b></span><span class="ob-body"><i></i></span><span class="ob-arm left"></span><span class="ob-arm right"></span><span class="ob-leg left"></span><span class="ob-leg right"></span></div>
      <div class="onboarding-spark s1">✦</div><div class="onboarding-spark s2">•</div><div class="onboarding-spark s3">✦</div>
      <div class="visual-caption"><b>Nia</b><span>tvůj průvodce Byzniem</span></div>
    </div>
    <div class="onboarding-content">
      <div class="ob-step">NIA · KROK 7 Z 7</div>
      <h1>Nia je součástí Byznia</h1>
      <p>Robot Nia bude létat po obrazovce, může tě sám upozornit na důležité věci a po kliknutí jí zadáš úkol. U citlivých finančních akcí nejdřív připraví návrh a čeká na potvrzení.</p>
      <div class="ob-preview nia-preview"><div class="nia-mini-orb"><span>✦</span></div><div><b>„Dnes máš 3 úkoly.“</b><small>Nia může sama říct, co je potřeba řešit, a po kliknutí jí můžeš zadat vlastní úkol.</small></div><div class="nia-input-demo">Napiš Nii, co potřebuješ… <strong>Poslat</strong></div></div>
      <div class="ob-actions"><div><a class="ob-btn" href="<?=$prev?>">← Zpět</a></div><form method="post" action="/uvod/complete" class="ob-finish-form ob-finish-form-r28"><input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>"><button class="ob-btn primary" type="submit">Dokončit a otevřít Byznio →</button></form></div>
      <div class="ob-skip"><form method="post" action="/uvod/skip"><input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>"><button type="submit">Přeskočit průvodce</button></form></div>
    </div>
  </div>
</div>
