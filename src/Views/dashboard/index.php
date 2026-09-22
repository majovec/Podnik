<?php
$monthRevenue=(float)($k['revenue']??0); $monthExpenses=(float)($k['expenses']??0); $monthPaid=(float)($k['paid']??0);
$receivables=max(0,$monthRevenue-$monthPaid); $cashflow=$monthPaid-$monthExpenses;
$invoiceCount=(int)($k['invoice_count']??0); $overdueCount=(int)($k['overdue']??0); $jobsCount=(int)($k['jobs']??0); $customersCount=(int)($k['customers']??0);
$maxChart=1; foreach(($monthly??[]) as $m){$maxChart=max($maxChart,(float)$m['revenue'],(float)$m['expenses']);}
?>
<div class="dashboard-v2">
  <section class="dash-hero">
    <div><div class="dash-eyebrow">CENTRÁLNÍ PŘEHLED</div><h1>Přehled</h1><p>Vše důležité o firmě na jednom místě.</p></div>
    <div class="dash-actions"><a class="dash-btn primary" href="/documents/new">＋ Faktura</a><a class="dash-btn" href="/customers/new">＋ Zákazník</a><a class="dash-btn" href="/jobs/new">＋ Zakázka</a></div>
  </section>

  <section class="dash-kpis">
    <a class="dash-kpi" href="/documents"><span>Faktury</span><strong><?=View::money($monthRevenue)?></strong><small>+ <?=number_format($invoiceCount,0,',',' ')?> tento měsíc</small><i>↗</i></a>
    <a class="dash-kpi" href="/documents?type=invoice"><span>Pohledávky</span><strong><?=View::money($receivables)?></strong><small><?=number_format($overdueCount,0,',',' ')?> po splatnosti</small><i>↗</i></a>
    <a class="dash-kpi" href="/jobs"><span>Zakázky</span><strong><?=number_format($jobsCount,0,',',' ')?></strong><small>aktivní a rozpracované</small><i>↗</i></a>
    <a class="dash-kpi" href="/customers"><span>Zákazníci</span><strong><?=number_format($customersCount,0,',',' ')?></strong><small>aktivní v CRM</small><i>↗</i></a>
  </section>

  <section class="dash-main-grid">
    <div class="dash-card dash-chart-card">
      <div class="dash-card-head"><div><h2>Příjmy a výdaje</h2><p>Vývoj za posledních 6 měsíců</p></div><a href="/export?type=invoices">Exportovat →</a></div>
      <div class="dash-chart">
        <div class="chart-y"><span><?=View::money($maxChart)?></span><span><?=View::money($maxChart/2)?></span><span>0 Kč</span></div>
        <div class="chart-area">
          <div class="chart-grid-lines"><b></b><b></b><b></b><b></b></div>
          <div class="chart-bars">
          <?php foreach(($monthly??[]) as $m): $rev=(float)$m['revenue'];$exp=(float)$m['expenses'];$rh=min(100,($rev/$maxChart)*100);$eh=min(100,($exp/$maxChart)*100); ?>
            <div class="chart-col"><div class="bar-pair"><span class="bar-income" style="height:<?=$rh?>%" title="Příjmy <?=View::money($rev)?>"></span><span class="bar-expense" style="height:<?=$eh?>%" title="Výdaje <?=View::money($exp)?>"></span></div><small><?=View::e($m['label'])?></small></div>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="chart-legend"><span><i class="income-dot"></i>Příjmy</span><span><i class="expense-dot"></i>Výdaje</span></div>
    </div>

    <div class="dash-card">
      <div class="dash-card-head"><div><h2>Poslední aktivita</h2><p>Nejdůležitější pohyby</p></div><a href="/bank">Banka →</a></div>
      <div class="activity-list">
      <?php if(!$recentPayments): ?><div class="empty-state"><span>✓</span><b>Zatím bez plateb</b><small>Jakmile přijde úhrada, objeví se tady.</small></div><?php endif; ?>
      <?php foreach(($recentPayments??[]) as $p): $payer=trim((string)($p['company_name']?:trim(($p['first_name']??'').' '.($p['last_name']??'')))); ?>
        <div class="activity-item"><span class="activity-icon">↗</span><div><b>Platba · <?=View::e($p['doc_number'])?></b><small><?=View::e($payer?:'Zákazník')?> · <?=View::e($p['paid_at'])?></small></div><strong><?=View::money($p['amount'])?></strong></div>
      <?php endforeach; ?></div>
    </div>
  </section>

  <section class="dash-three-grid">
    <div class="dash-card"><div class="dash-card-head"><div><h2>Dnes</h2><p>Úkoly a práce, které čekají</p></div><a href="/tasks">Vše →</a></div>
      <?php if(!$todayTasks): ?><div class="empty-state compact"><span>✓</span><b>Dnes je klid</b><small>Nemáte otevřený úkol po termínu.</small></div><?php endif; ?>
      <?php foreach(($todayTasks??[]) as $t): ?><div class="task-row"><span class="task-check">○</span><div><b><?=View::e($t['title'])?></b><small>priorita <?=View::e($t['priority'])?></small></div><em><?=View::e($t['due_at'])?></em></div><?php endforeach; ?>
    </div>
    <div class="dash-card"><div class="dash-card-head"><div><h2>Po splatnosti</h2><p>Faktury k vyřízení</p></div><a href="/reminders">Upomínky →</a></div>
      <?php if(!$alerts): ?><div class="empty-state compact"><span>✓</span><b>Žádné resty</b><small>Momentálně není žádná faktura po splatnosti.</small></div><?php endif; ?>
      <?php foreach(($alerts??[]) as $a): ?><div class="invoice-row"><div><b><?=View::e($a['doc_number'])?></b><small>splatnost <?=View::e($a['due_date'])?></small></div><strong><?=View::money($a['total_with_vat'])?></strong></div><?php endforeach; ?>
    </div>
    <div class="dash-card dash-finance-card"><div class="dash-card-head"><div><h2>Finance</h2><p>Rychlý stav firmy</p></div><a href="/tax">Daně →</a></div>
      <div class="finance-grid"><div><small>Výdaje</small><b><?=View::money($monthExpenses)?></b></div><div><small>Cashflow</small><b class="<?=($cashflow<0?'negative':'positive')?>"><?=View::money($cashflow)?></b></div><div><small>Uhrazeno</small><b><?=View::money($monthPaid)?></b></div><div><small>Sklad</small><b><?=View::money($k['stock']??0)?></b></div></div>
      <?php if($low): ?><div class="low-stock">⚠ <b><?=count($low)?> položek</b> je pod minimální zásobou. <a href="/products">Doplnit →</a></div><?php endif; ?>
    </div>
  </section>

  <section class="dash-two-grid">
    <div class="dash-card"><div class="dash-card-head"><div><h2>Nadcházející</h2><p>Kalendář, návštěvy a termíny</p></div><a href="/calendar">Kalendář →</a></div>
      <?php if(!$events): ?><div class="empty-state compact"><span>◷</span><b>Žádná událost</b><small>Naplánujte schůzku nebo návštěvu.</small></div><?php endif; ?>
      <?php foreach(($events??[]) as $e): ?><div class="event-row"><span class="event-time"><?=View::e(date('d.m.',strtotime($e['start_at'])))?></span><div><b><?=View::e($e['title'])?></b><small><?=View::e($e['type'])?> · <?=View::e(date('H:i',strtotime($e['start_at'])))?></small></div></div><?php endforeach; ?>
    </div>
    <div class="dash-card"><div class="dash-card-head"><div><h2>Sklad</h2><p>Materiál a zásoby pod kontrolou</p></div><a href="/products">Otevřít sklad →</a></div>
      <?php if(!$low): ?><div class="stock-ok"><span>✓</span><div><b>Zásoby jsou v pořádku</b><small>Žádná položka není pod minimálním stavem.</small></div></div><?php endif; ?>
      <?php foreach(array_slice(($low??[]),0,4) as $p): ?><div class="stock-row"><div><b><?=View::e($p['name'])?></b><small>minimum <?=View::e($p['min_stock'])?> · aktuálně <?=View::e($p['stock'])?></small></div><span>⚠ Nízká zásoba</span></div><?php endforeach; ?>
    </div>
  </section>

  <section class="dash-recommendations">
    <div class="dash-card dash-reco-card"><div class="dash-card-head"><div><h2>Nia doporučuje</h2><p>Co stojí za pozornost právě teď</p></div><span class="nia-chip">✦ Nia</span></div>
      <div class="reco-list">
      <?php foreach(($recommendations??[]) as $r): ?><div class="reco-item"><span>✦</span><div><?=View::e($r)?></div></div><?php endforeach; ?>
      </div>
    </div>
    <div class="dash-card quick-actions-card"><div class="dash-card-head"><div><h2>Rychlé akce</h2><p>Nejčastější práce bez hledání</p></div></div><div class="quick-actions"><a href="/documents/new">＋ Vystavit fakturu</a><a href="/customers/new">＋ Přidat zákazníka</a><a href="/jobs/new">＋ Nová zakázka</a><a href="/tasks">✓ Dnešní úkoly</a></div></div>
  </section>

  <section class="dash-tools">
    <div class="dash-tools-head"><div><div class="dash-eyebrow">RYCHLÝ PŘÍSTUP</div><h2>Vše, co potřebujete</h2><p>Propojené nástroje bez zbytečného hledání.</p></div></div>
    <div class="tool-grid">
      <?php $tools=[['/customers','CRM / Zákazníci','Kontakty, historie, komunikace','users'],['/documents','Faktury a nabídky','PDF, QR, odesílání, nabídky','file'],['/jobs','Zakázky / Projekty','Rozpočty, práce, materiál','briefcase'],['/products','Sklad / Inventura','Příjem, výdej, inventura','box'],['/bank','Banka / Párování','Transakce a úhrady','bank'],['/calendar','Kalendář / Úkoly','Termíny, schůzky, úkoly','calendar'],['/documents/files','Dokumenty','Centrální úložiště','folder'],['/recurring','Opakované faktury','Pravidelné vystavování','repeat'],['/automation','Automatizace','Upomínky a rutiny','workflow'],['/tax','Daňový přehled','Průběžné daňové podklady','percent'],['/settings','Tým / Nastavení','Uživatelé, role, firma','settings'],['/export?type=invoices','Exporty','Data pro další zpracování','download']]; foreach($tools as $tool): ?>
        <a class="tool-tile" href="<?=View::e($tool[0])?>"><span class="tool-icon <?=View::e($tool[3])?>">•</span><span><b><?=View::e($tool[1])?></b><small><?=View::e($tool[2])?></small></span><strong>→</strong></a>
      <?php endforeach; ?>
    </div>
  </section>
</div>

