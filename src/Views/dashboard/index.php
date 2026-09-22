<?php
$monthRevenue=(float)($k['revenue']??0); $monthExpenses=(float)($k['expenses']??0); $monthPaid=(float)($k['paid']??0);
$receivables=max(0,$monthRevenue-$monthPaid); $cashflow=$monthPaid-$monthExpenses;
$invoiceCount=(int)($k['invoice_count']??0); $overdueCount=(int)($k['overdue']??0); $jobsCount=(int)($k['jobs']??0); $customersCount=(int)($k['customers']??0);
$maxChart=1; foreach(($monthly??[]) as $m){$maxChart=max($maxChart,(float)$m['revenue'],(float)$m['expenses']);}
?>
<div class="dashboard-v3" data-dashboard-build="2026.09.22-r33">
  <div class="dash3-head">
    <div>
      <div class="dash3-eyebrow">CENTRÁLNÍ PŘEHLED</div>
      <h1>Přehled</h1>
      <p>Vše důležité o firmě na jednom místě.</p>
    </div>
    <div class="dash3-actions">
      <a class="dash3-action primary" href="/documents/new"><span>＋</span> Faktura</a>
      <a class="dash3-action" href="/customers/new"><span>＋</span> Zákazník</a>
      <a class="dash3-action" href="/jobs/new"><span>＋</span> Zakázka</a>
    </div>
  </div>

  <section class="dash3-kpis">
    <a class="dash3-kpi blue" href="/documents"><span class="kpi-icon">▣</span><div><small>Faktury</small><strong><?=View::money($monthRevenue)?></strong><em><?=number_format($invoiceCount,0,',',' ')?> tento měsíc</em></div><b>→</b></a>
    <a class="dash3-kpi violet" href="/documents?type=invoice"><span class="kpi-icon">◔</span><div><small>Pohledávky</small><strong><?=View::money($receivables)?></strong><em><?=$overdueCount?> po splatnosti</em></div><b>→</b></a>
    <a class="dash3-kpi green" href="/jobs"><span class="kpi-icon">⌁</span><div><small>Zakázky</small><strong><?=number_format($jobsCount,0,',',' ')?></strong><em>aktivní a rozpracované</em></div><b>→</b></a>
    <a class="dash3-kpi cyan" href="/customers"><span class="kpi-icon">♙</span><div><small>Zákazníci</small><strong><?=number_format($customersCount,0,',',' ')?></strong><em>aktivní v CRM</em></div><b>→</b></a>
  </section>

  <section class="dash3-grid-main">
    <article class="dash3-panel chart-panel">
      <div class="dash3-panel-head"><div><h2>Příjmy a výdaje</h2><p>Vývoj za posledních 6 měsíců</p></div><span class="dash3-legend"><i></i>Příjmy <i></i>Výdaje</span></div>
      <div class="dash3-chart">
        <div class="chart-axis"><span><?=View::money($maxChart)?></span><span><?=View::money($maxChart/2)?></span><span>0 Kč</span></div>
        <div class="chart-stage"><div class="chart-lines"><b></b><b></b><b></b><b></b></div><div class="chart-bars">
          <?php foreach(($monthly??[]) as $m): $rev=(float)$m['revenue'];$exp=(float)$m['expenses'];$rh=min(100,($rev/$maxChart)*100);$eh=min(100,($exp/$maxChart)*100); ?>
            <div class="chart-month"><div class="bars"><i class="income" style="height:<?=max(3,$rh)?>%" title="Příjmy <?=View::money($rev)?>"></i><i class="expense" style="height:<?=max(3,$eh)?>%" title="Výdaje <?=View::money($exp)?>"></i></div><small><?=View::e($m['label'])?></small></div>
          <?php endforeach; ?>
        </div></div>
      </div>
    </article>

    <article class="dash3-panel activity-panel">
      <div class="dash3-panel-head"><div><h2>Poslední aktivita</h2><p>Nejdůležitější pohyby</p></div><a href="/bank">Banka →</a></div>
      <?php if(!$recentPayments): ?><div class="dash3-empty"><span>✓</span><div><b>Zatím bez plateb</b><small>Jakmile přijde úhrada, objeví se tady.</small></div></div><?php endif; ?>
      <?php foreach(($recentPayments??[]) as $p): $payer=trim((string)($p['company_name']?:trim(($p['first_name']??'').' '.($p['last_name']??'')))); ?>
        <div class="dash3-activity"><span>↗</span><div><b>Platba · <?=View::e($p['doc_number'])?></b><small><?=View::e($payer?:'Zákazník')?> · <?=View::e($p['paid_at'])?></small></div><strong><?=View::money($p['amount'])?></strong></div>
      <?php endforeach; ?>
    </article>
  </section>

  <section class="dash3-grid-three">
    <article class="dash3-panel">
      <div class="dash3-panel-head"><div><h2>Dnešní úkoly</h2><p>Co čeká právě dnes</p></div><a href="/tasks">Vše →</a></div>
      <?php if(!$todayTasks): ?><div class="dash3-empty"><span>✓</span><div><b>Dnes je klid</b><small>Nemáte otevřený úkol po termínu.</small></div></div><?php endif; ?>
      <?php foreach(($todayTasks??[]) as $t): ?><div class="dash3-row"><span class="check">○</span><div><b><?=View::e($t['title'])?></b><small>Priorita <?=View::e($t['priority'])?></small></div><em><?=View::e($t['due_at'])?></em></div><?php endforeach; ?>
    </article>
    <article class="dash3-panel">
      <div class="dash3-panel-head"><div><h2>Po splatnosti</h2><p>Faktury k vyřízení</p></div><a href="/reminders">Upomínky →</a></div>
      <?php if(!$alerts): ?><div class="dash3-empty"><span>✓</span><div><b>Žádné resty</b><small>Momentálně není žádná faktura po splatnosti.</small></div></div><?php endif; ?>
      <?php foreach(($alerts??[]) as $a): ?><div class="dash3-row"><span class="danger-dot">!</span><div><b><?=View::e($a['doc_number'])?></b><small>Splatnost <?=View::e($a['due_date'])?></small></div><strong><?=View::money($a['total_with_vat'])?></strong></div><?php endforeach; ?>
    </article>
    <article class="dash3-panel finance-panel">
      <div class="dash3-panel-head"><div><h2>Finance</h2><p>Rychlý stav firmy</p></div><a href="/tax">Daně →</a></div>
      <div class="finance-items"><div><small>Výdaje</small><b><?=View::money($monthExpenses)?></b></div><div><small>Cashflow</small><b class="<?=$cashflow<0?'negative':'positive'?>"><?=View::money($cashflow)?></b></div><div><small>Uhrazeno</small><b><?=View::money($monthPaid)?></b></div><div><small>Sklad</small><b><?=View::money($k['stock']??0)?></b></div></div>
      <?php if($low): ?><div class="dash3-warning">⚠ <b><?=count($low)?> položek</b> je pod minimální zásobou. <a href="/products">Doplnit →</a></div><?php endif; ?>
    </article>
  </section>

  <section class="dash3-grid-two">
    <article class="dash3-panel">
      <div class="dash3-panel-head"><div><h2>Nadcházející</h2><p>Kalendář, návštěvy a termíny</p></div><a href="/calendar">Kalendář →</a></div>
      <?php if(!$events): ?><div class="dash3-empty"><span>◷</span><div><b>Žádná událost</b><small>Naplánujte schůzku nebo návštěvu.</small></div></div><?php endif; ?>
      <?php foreach(($events??[]) as $e): ?><div class="event-row"><span><?=View::e(date('d.m.',strtotime($e['start_at'])))?></span><div><b><?=View::e($e['title'])?></b><small><?=View::e($e['type'])?> · <?=View::e(date('H:i',strtotime($e['start_at'])))?></small></div></div><?php endforeach; ?>
    </article>
    <article class="dash3-panel nia-card"><div class="nia-card-glow"></div><div class="dash3-panel-head"><div><h2>Nia doporučuje</h2><p>Co stojí za pozornost právě teď</p></div><span>✦ Nia</span></div><div class="nia-recos"><?php foreach(($recommendations??[]) as $r): ?><div><i>✦</i><?=View::e($r)?></div><?php endforeach; ?></div></article>
  </section>

  <section class="dash3-tools">
    <div><div class="dash3-eyebrow">RYCHLÝ PŘÍSTUP</div><h2>Vše, co potřebujete</h2><p>Propojené nástroje bez zbytečného hledání.</p></div>
    <div class="dash3-tool-grid">
      <?php $tools=[['/customers','CRM / Zákazníci','Správa zákazníků','users'],['/documents','Faktury a nabídky','Vystavení, odeslání a platby','file'],['/jobs','Zakázky / Projekty','Plánování, rozpočet a tým','briefcase'],['/products','Sklad / Inventura','Přehled zásob a pohybů','box'],['/bank','Banka / Párování','Automatické párování plateb','bank'],['/calendar','Kalendář / Úkoly','Termíny, schůzky a úkoly','calendar'],['/documents/files','Dokumenty','Uložení a sdílení','folder'],['/recurring','Opakované faktury','Automatické vystavování','repeat'],['/automation','Automatizace','Zjednodušení rutin','workflow'],['/tax','Daňový přehled','Podklady pro daně','percent']]; foreach($tools as $tool): ?>
        <a href="<?=View::e($tool[0])?>" class="dash3-tool"><span class="tool-icon <?=$tool[3]?>">•</span><div><b><?=View::e($tool[1])?></b><small><?=View::e($tool[2])?></small></div><strong>→</strong></a>
      <?php endforeach; ?>
    </div>
  </section>
</div>
