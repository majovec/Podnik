<?php
use App\Core\View;

$rows = is_array($rows ?? null) ? $rows : [];
$total = 0.0;
$unpaid = 0.0;
$overdue = 0.0;
$today = date('Y-m-d');
foreach ($rows as $row) {
    $amount = (float)($row['total_amount'] ?? 0);
    $total += $amount;
    if (($row['payment_status'] ?? 'unpaid') !== 'paid') {
        $unpaid += $amount;
        if (!empty($row['due_date']) && $row['due_date'] < $today) {
            $overdue += $amount;
        }
    }
}
?>
<div class="page-head">
    <div>
        <h1 class="h">Přijaté faktury</h1>
        <div class="sub">Přehled faktur od dodavatelů, jejich splatnosti a stavu úhrady.</div>
    </div>
</div>

<div class="grid" style="margin-bottom:18px">
    <div class="card kpi-card"><div class="label">Celkem</div><div class="kpi"><?=View::money($total)?></div></div>
    <div class="card kpi-card"><div class="label">Nezaplaceno</div><div class="kpi"><?=View::money($unpaid)?></div></div>
    <div class="card kpi-card"><div class="label">Po splatnosti</div><div class="kpi"><?=View::money($overdue)?></div></div>
    <div class="card kpi-card"><div class="label">Počet faktur</div><div class="kpi"><?=count($rows)?></div></div>
</div>

<div class="card">
    <div class="page-head" style="margin-bottom:18px">
        <div>
            <h2 style="margin:0;font-size:20px">Nová přijatá faktura</h2>
            <div class="sub">Zapište údaje faktury od dodavatele.</div>
        </div>
    </div>
    <form class="form" method="post" action="/received-invoices/save">
        <input type="hidden" name="_csrf" value="<?=View::e(App\Core\Auth::csrf())?>">
        <div class="row">
            <div class="field"><label>Dodavatel</label><input name="supplier" required></div>
            <div class="field"><label>Číslo faktury</label><input name="document_number"></div>
        </div>
        <div class="row">
            <div class="field"><label>IČO</label><input name="ico" inputmode="numeric"></div>
            <div class="field"><label>DIČ</label><input name="dic"></div>
        </div>
        <div class="row">
            <div class="field"><label>Variabilní symbol</label><input name="variable_symbol" inputmode="numeric"></div>
            <div class="field"><label>Měna</label><select name="currency"><option value="CZK">CZK</option><option value="EUR">EUR</option><option value="USD">USD</option><option value="GBP">GBP</option></select></div>
        </div>
        <div class="row">
            <div class="field"><label>Datum vystavení</label><input type="date" name="issue_date" value="<?=date('Y-m-d')?>"></div>
            <div class="field"><label>Datum splatnosti</label><input type="date" name="due_date"></div>
        </div>
        <div class="row">
            <div class="field"><label>Částka bez DPH</label><input name="amount_without_vat" type="number" step="0.01" min="0" value="0"></div>
            <div class="field"><label>DPH</label><input name="vat_amount" type="number" step="0.01" min="0" value="0"></div>
            <div class="field"><label>Celkem</label><input name="total_amount" type="number" step="0.01" min="0" required value="0"></div>
        </div>
        <div class="row">
            <div class="field"><label>Kurz ČNB</label><input name="cnb_rate" type="number" step="0.0001" min="0" value="1"></div>
            <div class="field"><label>Stav úhrady</label><select name="payment_status"><option value="unpaid">Nezaplaceno</option><option value="paid">Zaplaceno</option><option value="partial">Částečně zaplaceno</option></select></div>
        </div>
        <div class="field"><label>Poznámka</label><textarea name="note" rows="3" placeholder="Volitelná poznámka"></textarea></div>
        <button class="btn primary" type="submit">Uložit přijatou fakturu</button>
    </form>
</div>

<div style="height:18px"></div>
<div class="card tablewrap">
    <div class="page-head" style="margin-bottom:14px">
        <div>
            <h2 style="margin:0;font-size:20px">Seznam přijatých faktur</h2>
        </div>
    </div>
    <?php if (!$rows): ?>
        <div class="sub" style="padding:18px 0">Zatím zde nejsou žádné přijaté faktury.</div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Dodavatel</th><th>Číslo faktury</th><th>Vystaveno</th><th>Splatnost</th><th>Částka</th><th>Stav</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row):
                $status = (string)($row['payment_status'] ?? 'unpaid');
                $due = (string)($row['due_date'] ?? '');
                $isOverdue = $status !== 'paid' && $due !== '' && $due < $today;
                $statusLabel = match ($status) {
                    'paid' => 'Zaplaceno',
                    'partial' => 'Částečně',
                    default => $isOverdue ? 'Po splatnosti' : 'Nezaplaceno',
                };
            ?>
                <tr>
                    <td><?=View::e($row['supplier'] ?? '')?></td>
                    <td><?=View::e($row['document_number'] ?? '')?></td>
                    <td><?=View::e($row['issue_date'] ?? '')?></td>
                    <td><?=View::e($due)?></td>
                    <td><?=View::money($row['total_amount'] ?? 0)?></td>
                    <td><?=View::e($statusLabel)?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
