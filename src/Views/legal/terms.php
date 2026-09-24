<?php ob_start(); ?>
<section class="hero"><div class="eyebrow">Byznio · veřejné informace</div><h1>Obchodní podmínky</h1><p>Podmínky používání služby Byznio a poskytování předplatného.</p></section>
<article class="card">
<h2>1. Provozovatel</h2><p>Provozovatel služby:<br>Jakub Mai<br>IČO: 08536155<br>Komenského 17<br>563 01 Lanškroun<br>Česká republika<br>E-mail zákaznické a technické podpory: help@byznio.cz<br></p><h2>2. Služba</h2><p>Byznio je online SaaS služba pro správu podnikatelské administrativy, zákazníků, dokladů, zakázek, skladu, bankovních napojení, úkolů a souvisejících funkcí. Konkrétní dostupnost funkcí se může řídit aktuálním plánem a nastavením účtu.</p>
<h2>3. Účet</h2><p>Uživatel odpovídá za správnost údajů při registraci, zabezpečení přístupových údajů a používání účtu v souladu s právními předpisy. Účet je určen pro oprávněné používání zákazníkem a jeho pracovníky.</p>
<h2>4. Předplatné a ceny</h2><p>Aktuální ceny a délka zkušebního období jsou uvedeny na veřejné stránce Byznia a v aplikaci. Cena je uváděna v Kč. Pokud zákazník zvolí opakovanou platbu, řídí se její podmínky samostatným dokumentem <a href="/podminky-opakovanych-plateb">Podmínky opakovaných plateb</a>.</p>
<h2>5. Platby</h2><p>Platby předplatného jsou zpracovávány prostřednictvím poskytovatele platební brány GoPay. Byznio samo nezískává ani neukládá úplné údaje platební karty, pokud poskytovatel platební služby umožňuje jejich zpracování na své straně.</p>
<h2>6. Zrušení</h2><p>Zákazník může předplatné zrušit způsobem dostupným v aplikaci nebo prostřednictvím podpory. Podrobnosti o automatickém strhávání jsou uvedeny v podmínkách opakovaných plateb.</p>
<h2>7. Podpora</h2><p>Technická a zákaznická podpora je dostupná na <a class="email" href="mailto:<?=\App\Core\View::e($supportEmail)?>"><?=\App\Core\View::e($supportEmail)?></a>.</p>
<h2>8. Ochrana údajů</h2><p>Informace o zpracování osobních údajů jsou uvedeny na stránce <a href="/ochrana-osobnich-udaju">Ochrana osobních údajů</a>.</p>

</article>
<?php $content=ob_get_clean(); include __DIR__.'/_layout.php'; ?>
