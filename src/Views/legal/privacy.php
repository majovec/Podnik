<?php ob_start(); ?>
<section class="hero"><div class="eyebrow">Byznio · soukromí</div><h1>Ochrana osobních údajů</h1><p>Základní informace o zpracování osobních údajů v rámci služby Byznio.</p></section>
<article class="card">
<h2>Provozovatel a kontakt</h2><p>Správcem osobních údajů v rozsahu odpovídajícím poskytování služby Byznio je Jakub Mai, IČO 08536155, Komenského 17, 563 01 Lanškroun, Česká republika. Kontakt: <a class="email" href="mailto:<?=\App\Core\View::e($supportEmail)?>"><?=\App\Core\View::e($supportEmail)?></a>.</p><h2>Jaké údaje mohou být zpracovávány</h2><p>Podle používání služby může Byznio zpracovávat registrační a kontaktní údaje uživatelů, údaje o firmě, zákaznících, dokladech, zakázkách a dalších datech, která uživatel do aplikace vloží.</p>
<h2>Účel zpracování</h2><p>Údaje jsou zpracovávány za účelem poskytování služby, správy účtu, zabezpečení, podpory, fakturace a plnění souvisejících smluvních a právních povinností.</p>
<h2>Platby</h2><p>Platby jsou zpracovávány prostřednictvím GoPay. Citlivé platební údaje jsou zpracovávány v rámci platební služby podle jejích pravidel a technické integrace.</p>
<h2>Kontakt</h2><p>Dotazy k ochraně údajů můžete poslat na <a class="email" href="mailto:<?=\App\Core\View::e($supportEmail)?>"><?=\App\Core\View::e($supportEmail)?></a>.</p>
<div class="notice"><strong>Poznámka:</strong> konkrétní právní tituly, zpracovatelé a retenční lhůty se řídí skutečným provozem služby a příslušnou právní dokumentací.</div>
</article>
<?php $content=ob_get_clean(); include __DIR__.'/_layout.php'; ?>
