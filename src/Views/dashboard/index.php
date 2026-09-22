<?php
$monthRevenue=(float)($k['revenue']??0); $monthExpenses=(float)($k['expenses']??0); $monthPaid=(float)($k['paid']??0);
$receivables=(float)($k['receivables']??0); $cashflow=$monthPaid-$monthExpenses;
$invoiceCount=(int)($k['invoice_count']??0); $newInvoiceCount=(int)($k['new_invoice_count']??0); $overdueCount=(int)($k['overdue']??0); $jobsCount=(int)($k['jobs']??0); $customersCount=(int)($k['customers']??0);
$maxChart=1; foreach(($monthly??[]) as $m){$maxChart=max($maxChart,(float)$m['revenue'],(float)$m['expenses']);}
?>
<div class="dashboard-ref" data-dashboard-build="2026.09.22-r40">
  <div class="dash-ref-desktop">
    <div class="dash-ref-heading"><div><div class="dash-ref-eyebrow">PŘEHLED</div><h1>Přehled</h1></div><div class="dash-ref-date">Dnes · <?=date('j. n. Y')?></div></div>
    <section class="dash-ref-kpis">
      <a href="/documents" class="ref-kpi"><small>Faktury</small><strong><?=\App\Core\View::money($monthRevenue)?></strong><span class="up">Tento měsíc</span></a>
      <a href="/documents?type=invoice" class="ref-kpi"><small>Pohledávky</small><strong><?=\App\Core\View::money($receivables)?></strong><span class="up">Neuhrazeno</span></a>
      <a href="/jobs" class="ref-kpi"><small>Zakázky</small><strong><?=number_format($jobsCount,0,',',' ')?></strong><span class="up">Aktivní</span></a>
      <a href="/customers" class="ref-kpi"><small>Zákazníci</small><strong><?=number_format($customersCount,0,',',' ')?></strong><span class="up">V CRM</span></a>
    </section>
    <section class="dash-ref-main">
      <article class="ref-card ref-chart"><div class="ref-card-title"><b>Příjmy a výdaje</b><span><i class="legend-income"></i>Příjmy <i class="legend-expense"></i>Výdaje</span></div><div class="ref-bars"><?php foreach(($monthly??[]) as $m): $rev=(float)$m['revenue'];$exp=(float)$m['expenses'];$rh=min(100,($rev/$maxChart)*100);$eh=min(100,($exp/$maxChart)*100); ?><div class="ref-bar-month"><div class="ref-bar-pair"><i style="height:<?=max(4,$rh)?>%"></i><b style="height:<?=max(4,$eh)?>%"></b></div><small><?=\App\Core\View::e($m['label'])?></small></div><?php endforeach; ?></div></article>
      <article class="ref-card ref-activity"><div class="ref-card-title"><b>Poslední aktivita</b><a href="/bank">Zobrazit vše</a></div><?php if(!$recentPayments): ?><div class="ref-empty">Zatím bez plateb.</div><?php endif; ?><?php foreach(array_slice(($recentPayments??[]),0,5) as $p): $payer=trim((string)($p['company_name']?:trim(($p['first_name']??'').' '.($p['last_name']??'')))); ?><a class="ref-activity-row" href="/bank"><span class="ref-act-icon">↗</span><div><b><?=\App\Core\View::e($p['doc_number'])?></b><small><?=\App\Core\View::e($payer?:'Zákazník')?> · <?=\App\Core\View::e($p['paid_at'])?></small></div><strong><?=\App\Core\View::money($p['amount'])?></strong></a><?php endforeach; ?></article>
    </section>
    <section class="dash-ref-lower">
      <article class="ref-card"><div class="ref-card-title"><b>Dnešní úkoly</b><a href="/tasks">Vše</a></div><?php if(!$todayTasks): ?><div class="ref-empty">Dnes nemáte žádný otevřený úkol.</div><?php endif; ?><?php foreach(array_slice(($todayTasks??[]),0,4) as $t): ?><a class="ref-list-row" href="/tasks"><span class="ref-check"></span><div><b><?=\App\Core\View::e($t['title'])?></b><small><?=\App\Core\View::e($t['priority'])?></small></div><em><?=\App\Core\View::e($t['due_at'])?></em></a><?php endforeach; ?></article>
      <article class="ref-card"><div class="ref-card-title"><b>Faktury po splatnosti</b><a href="/documents">Zobrazit</a></div><?php if(!$alerts): ?><div class="ref-empty">Žádné faktury po splatnosti.</div><?php endif; ?><?php foreach(array_slice(($alerts??[]),0,4) as $a): ?><a class="ref-list-row" href="/documents"><span class="ref-danger">!</span><div><b><?=\App\Core\View::e($a['doc_number'])?></b><small>Splatnost <?=\App\Core\View::e($a['due_date'])?></small></div><strong><?=\App\Core\View::money($a['total_with_vat'])?></strong></a><?php endforeach; ?></article>
    </section>
  </div>
  <div class="dash-ref-mobile">
    <div class="mobile-day">Dnes</div>
    <a class="mobile-module" href="/documents"><span class="mobile-module-icon blue"><?=icon('file')?></span><div><b>Faktury</b><small><?=number_format($newInvoiceCount,0,',',' ')?> <?= $newInvoiceCount===1?'nová':'nových' ?> tento měsíc</small></div><strong><?=\App\Core\View::money($monthRevenue)?></strong><span class="arrow">›</span></a>
    <a class="mobile-module" href="/tasks"><span class="mobile-module-icon violet"><?=icon('check')?></span><div><b>Úkoly</b><small><?=count($todayTasks??[])?> otevřené</small></div><span class="arrow">›</span></a>
    <a class="mobile-module" href="/calendar"><span class="mobile-module-icon cyan"><?=icon('calendar')?></span><div><b>Kalendář</b><small><?=count($events??[])?> událostí</small></div><span class="arrow">›</span></a>
    <a class="mobile-module" href="/bank"><span class="mobile-module-icon navy"><?=icon('bank')?></span><div><b>Bankovní účty</b><small>Pohyby tento měsíc</small></div><strong><?=\App\Core\View::money($monthPaid)?></strong><span class="arrow">›</span></a>
    <a class="mobile-module" href="/customers"><span class="mobile-module-icon green"><?=icon('users')?></span><div><b>Zákazníci</b><small><?=number_format($customersCount,0,',',' ')?> v CRM</small></div><span class="arrow">›</span></a>
    <a class="mobile-module" href="/jobs"><span class="mobile-module-icon purple"><?=icon('briefcase')?></span><div><b>Zakázky</b><small><?=number_format($jobsCount,0,',',' ')?> aktivních</small></div><span class="arrow">›</span></a>
  </div>
</div>
