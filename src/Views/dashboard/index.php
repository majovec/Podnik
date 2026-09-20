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

  <section class="dash-tools">
    <div class="dash-tools-head"><div><div class="dash-eyebrow">RYCHLÝ PŘÍSTUP</div><h2>Vše, co potřebujete</h2><p>Propojené nástroje bez zbytečného hledání.</p></div></div>
    <div class="tool-grid">
      <?php $tools=[['/customers','CRM / Zákazníci','Kontakty, historie, komunikace','users'],['/documents','Faktury a nabídky','PDF, QR, odesílání, nabídky','file'],['/jobs','Zakázky / Projekty','Rozpočty, práce, materiál','briefcase'],['/products','Sklad / Inventura','Příjem, výdej, inventura','box'],['/bank','Banka / Párování','Transakce a úhrady','bank'],['/calendar','Kalendář / Úkoly','Termíny, schůzky, úkoly','calendar'],['/documents/files','Dokumenty','Centrální úložiště','folder'],['/recurring','Opakované faktury','Pravidelné vystavování','repeat'],['/automation','Automatizace','Upomínky a rutiny','workflow'],['/tax','Daňový přehled','Průběžné daňové podklady','percent'],['/settings','Tým / Nastavení','Uživatelé, role, firma','settings'],['/export?type=invoices','Exporty','Data pro další zpracování','download']]; foreach($tools as $tool): ?>
        <a class="tool-tile" href="<?=View::e($tool[0])?>"><span class="tool-icon <?=View::e($tool[3])?>">•</span><span><b><?=View::e($tool[1])?></b><small><?=View::e($tool[2])?></small></span><strong>→</strong></a>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<style>
.dashboard-v2{--d-blue:#1677ee;--d-violet:#5946e8;--d-ink:#10213f;--d-muted:#718098;--d-line:#e4eaf2}.dash-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:22px}.dash-eyebrow{font-size:11px;font-weight:900;letter-spacing:.12em;color:var(--d-blue)}.dash-hero h1{font-size:34px;letter-spacing:-.045em;margin:6px 0 5px}.dash-hero p{margin:0;color:var(--d-muted);font-size:14px}.dash-actions{display:flex;gap:9px;flex-wrap:wrap}.dash-btn{min-height:42px;padding:10px 14px;border:1px solid var(--d-line);border-radius:11px;background:#fff;font-size:13px;font-weight:800;box-shadow:0 4px 12px rgba(16,33,63,.04)}.dash-btn.primary{background:var(--d-blue);border-color:var(--d-blue);color:#fff}.dash-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px}.dash-kpi{position:relative;overflow:hidden;background:#fff;border:1px solid var(--d-line);border-radius:16px;padding:18px;box-shadow:0 8px 24px rgba(16,33,63,.055)}.dash-kpi:after{content:"";position:absolute;width:85px;height:85px;right:-28px;top:-32px;border-radius:50%;background:#edf5ff}.dash-kpi span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--d-muted);font-weight:850}.dash-kpi strong{display:block;font-size:25px;letter-spacing:-.03em;margin-top:8px;position:relative;z-index:1}.dash-kpi small{display:block;color:#73829a;margin-top:6px;font-size:11px;font-weight:700}.dash-kpi i{position:absolute;right:15px;bottom:15px;font-style:normal;color:var(--d-blue);font-weight:900}.dash-main-grid{display:grid;grid-template-columns:1.55fr .9fr;gap:16px}.dash-three-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:16px}.dash-two-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}.dash-card{background:#fff;border:1px solid var(--d-line);border-radius:16px;padding:18px;box-shadow:0 8px 24px rgba(16,33,63,.045);min-width:0}.dash-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.dash-card-head h2{margin:0;font-size:16px;letter-spacing:-.025em}.dash-card-head p{margin:4px 0 0;color:var(--d-muted);font-size:11px}.dash-card-head a{color:var(--d-blue);font-size:11px;font-weight:850;white-space:nowrap}.dash-chart{display:grid;grid-template-columns:55px 1fr;height:255px}.chart-y{display:flex;flex-direction:column;justify-content:space-between;padding:4px 8px 27px 0;text-align:right;color:#8b98aa;font-size:9px}.chart-area{position:relative}.chart-grid-lines{position:absolute;inset:4px 0 27px;display:flex;flex-direction:column;justify-content:space-between}.chart-grid-lines b{height:1px;background:#edf1f6}.chart-bars{height:100%;display:grid;grid-template-columns:repeat(6,1fr);gap:10px;align-items:end;position:relative;padding:0 7px}.chart-col{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:8px}.bar-pair{height:205px;width:42px;display:flex;gap:5px;align-items:flex-end}.bar-income,.bar-expense{display:block;width:18px;min-height:2px;border-radius:6px 6px 2px 2px}.bar-income{background:linear-gradient(180deg,#1677ee,#5a55e8)}.bar-expense{background:#c6d2e2}.chart-col small{font-size:9px;color:#77859a}.chart-legend{display:flex;gap:16px;color:#728198;font-size:10px}.chart-legend span{display:flex;align-items:center;gap:6px}.income-dot,.expense-dot{width:7px;height:7px;border-radius:50%;display:inline-block}.income-dot{background:#1677ee}.expense-dot{background:#c6d2e2}.activity-list{display:grid}.activity-item{display:grid;grid-template-columns:30px 1fr auto;gap:9px;align-items:center;padding:11px 0;border-bottom:1px solid #edf1f5}.activity-item:last-child{border-bottom:0}.activity-icon{width:30px;height:30px;border-radius:9px;background:#edf5ff;color:var(--d-blue);display:grid;place-items:center;font-weight:900}.activity-item b,.task-row b,.invoice-row b,.event-row b,.stock-row b{display:block;font-size:12px}.activity-item small,.task-row small,.invoice-row small,.event-row small,.stock-row small{display:block;color:var(--d-muted);font-size:10px;margin-top:3px}.activity-item strong{font-size:11px;white-space:nowrap}.task-row,.invoice-row,.event-row,.stock-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #edf1f5}.task-row:last-child,.invoice-row:last-child,.event-row:last-child,.stock-row:last-child{border-bottom:0}.task-row>div,.invoice-row>div,.event-row>div,.stock-row>div{min-width:0;flex:1}.task-check{width:25px;height:25px;border:1px solid #cdd8e6;border-radius:8px;display:grid;place-items:center;color:#9aa9ba}.task-row em{font-style:normal;font-size:10px;color:#64748a;white-space:nowrap}.invoice-row strong{font-size:11px;color:#c2413b;white-space:nowrap}.finance-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}.finance-grid>div{border:1px solid #e8edf4;border-radius:11px;padding:11px}.finance-grid small{display:block;color:var(--d-muted);font-size:9px;margin-bottom:5px}.finance-grid b{font-size:13px}.finance-grid .positive{color:#0a9b64}.finance-grid .negative{color:#c2413b}.low-stock{margin-top:11px;padding:9px 10px;border-radius:10px;background:#fff7e7;color:#8a5b0c;font-size:10px}.low-stock a{float:right;color:#8a5b0c;font-weight:900}.event-time{width:42px;height:42px;border-radius:11px;background:#edf5ff;color:var(--d-blue);display:grid;place-items:center;font-size:10px;font-weight:900}.stock-ok{display:flex;align-items:center;gap:10px;padding:13px;border:1px solid #dcefe5;background:#f5fbf7;border-radius:12px}.stock-ok span{width:31px;height:31px;border-radius:9px;background:#dff5e8;color:#0a9b64;display:grid;place-items:center;font-weight:900}.stock-ok b,.stock-ok small{display:block}.stock-ok small{font-size:10px;color:var(--d-muted);margin-top:3px}.stock-row span{font-size:9px;font-weight:850;color:#a26a06;background:#fff5df;border-radius:999px;padding:5px 7px;white-space:nowrap}.empty-state{display:flex;align-items:center;gap:10px;padding:24px 5px;color:var(--d-muted)}.empty-state>span{width:34px;height:34px;border-radius:10px;background:#eef6ff;color:var(--d-blue);display:grid;place-items:center;font-weight:900}.empty-state b,.empty-state small{display:block}.empty-state small{font-size:10px;margin-top:3px}.empty-state.compact{padding:15px 5px}.dash-tools{margin-top:16px;background:#fff;border:1px solid var(--d-line);border-radius:16px;padding:19px;box-shadow:0 8px 24px rgba(16,33,63,.045)}.dash-tools-head h2{margin:5px 0 3px;font-size:19px;letter-spacing:-.03em}.dash-tools-head p{margin:0;color:var(--d-muted);font-size:11px}.tool-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:15px}.tool-tile{display:grid;grid-template-columns:36px 1fr auto;align-items:center;gap:9px;border:1px solid #e5ebf3;border-radius:13px;padding:11px 10px;background:#fff;transition:.15s}.tool-tile:hover{transform:translateY(-1px);border-color:#c9d8eb;box-shadow:0 8px 18px rgba(16,33,63,.07)}.tool-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#1683f4,#5860e8);color:#fff;display:grid;place-items:center;font-size:20px}.tool-icon.box,.tool-icon.workflow{background:linear-gradient(135deg,#5e59e8,#8d4ce8)}.tool-tile b{display:block;font-size:11px}.tool-tile small{display:block;color:var(--d-muted);font-size:9px;margin-top:3px;line-height:1.35}.tool-tile>strong{color:#90a0b5}.tool-icon.download{background:linear-gradient(135deg,#2e9c72,#4eb75a)}
@media(max-width:1100px){.dash-kpis{grid-template-columns:repeat(2,1fr)}.dash-main-grid,.dash-three-grid,.dash-two-grid{grid-template-columns:1fr}.tool-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:760px){.dash-hero{align-items:flex-start;flex-direction:column}.dash-actions{width:100%}.dash-btn{flex:1}.dash-kpis{grid-template-columns:1fr 1fr;gap:9px}.dash-kpi{padding:14px}.dash-kpi strong{font-size:19px}.dash-chart{height:220px}.bar-pair{height:175px;width:32px}.bar-income,.bar-expense{width:13px}.tool-grid{grid-template-columns:1fr 1fr}.tool-tile{grid-template-columns:32px 1fr}.tool-icon{width:32px;height:32px}.tool-tile>strong{display:none}}
@media(max-width:480px){.dash-kpis{grid-template-columns:1fr}.dash-actions{display:grid;grid-template-columns:1fr}.tool-grid{grid-template-columns:1fr}.dash-card{padding:15px}}
</style>
