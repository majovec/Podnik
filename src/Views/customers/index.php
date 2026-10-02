<div class="page-head customer-page-head">
  <div>
    <h1 class="page-title">Zákazníci</h1>
    <div class="sub">CRM, historie dokladů a zakázek.</div>
  </div>
  <a class="btn primary" href="/customers/new">+ Nový zákazník</a>
</div>

<div class="customer-toolbar">
  <form class="customer-search" method="get" action="/customers">
    <input class="customer-search-input" name="q" value="<?=\App\Core\View::e($q)?>" placeholder="Hledat jméno, IČO…" autocomplete="off">
    <button class="btn" type="submit">Hledat</button>
    <?php if($q!==''): ?><a class="btn" href="/customers">Zrušit hledání</a><?php endif; ?>
  </form>
  <div class="customer-count"><?=count($customers)?> <?=count($customers)===1?'zákazník':(count($customers)<5?'zákazníci':'zákazníků')?></div>
</div>

<div class="card tablewrap customer-list-card">
  <?php if(!$customers): ?>
    <div class="customer-empty">
      <div class="customer-empty-icon">👤</div>
      <b><?= $q!=='' ? 'Žádný zákazník neodpovídá hledání.' : 'Zatím nemáš žádné zákazníky.' ?></b>
      <div class="sub">Zákazníky vytvořené v CRM najdeš právě tady. U každého pak uvidíš doklady, zakázky a historii.</div>
      <a class="btn primary" href="/customers/new">+ Nový zákazník</a>
    </div>
  <?php else: ?>
    <table class="table customer-table">
      <thead><tr><th>Zákazník</th><th>IČO</th><th>E-mail</th><th>Telefon</th><th>Město</th><th></th></tr></thead>
      <tbody>
      <?php foreach($customers as $c):
        $name=trim((string)($c['company_name']??''));
        if($name==='') $name=trim((string)($c['first_name']??'').' '.(string)($c['last_name']??''));
        if($name==='') $name='Bez názvu';
      ?>
        <tr>
          <td data-label="Zákazník"><a class="customer-name" href="/customers/<?=$c['id']?>"><b><?=\App\Core\View::e($name)?></b></a><?php if((int)($c['active']??1)!==1): ?><span class="customer-archived">Archivovaný</span><?php endif; ?></td>
          <td data-label="IČO"><?=\App\Core\View::e($c['ico'])?></td>
          <td data-label="E-mail"><?=\App\Core\View::e($c['email'])?></td>
          <td data-label="Telefon"><?=\App\Core\View::e($c['phone'])?></td>
          <td data-label="Město"><?=\App\Core\View::e($c['city'])?></td>
          <td class="customer-actions"><a class="btn" href="/customers/<?=$c['id']?>/edit">Upravit</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<style>
.customer-page-head{align-items:center;margin-bottom:18px}
.customer-page-head .page-title{font-size:32px;line-height:1.12;letter-spacing:-.035em;margin:0 0 7px;font-weight:850}
.customer-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;margin:0 0 16px;flex-wrap:wrap}
.customer-search{display:flex;align-items:center;gap:9px;flex:1;min-width:0}
.customer-search-input{width:min(420px,100%);min-height:46px;padding:11px 13px;border:1px solid #d6deea;border-radius:12px;background:#fff;color:var(--text);outline:none}
.customer-search-input:focus{border-color:#7db0f4;box-shadow:0 0 0 4px rgba(22,119,238,.1)}
.customer-count{color:var(--muted);font-size:13px;font-weight:700;white-space:nowrap}
.customer-list-card{padding:0;overflow:hidden}
.customer-table th:first-child,.customer-table td:first-child{padding-left:20px}
.customer-table th:last-child,.customer-table td:last-child{padding-right:20px}
.customer-name{color:var(--text)}
.customer-archived{display:inline-block;margin-left:8px;padding:3px 7px;border-radius:999px;background:#fff4e5;color:#9a5b00;font-size:10px;font-weight:800;vertical-align:middle}
.customer-name:hover{color:var(--blue)}
.customer-actions{text-align:right!important;white-space:nowrap}
.customer-empty{display:grid;justify-items:center;text-align:center;gap:10px;padding:54px 22px}
.customer-empty-icon{width:56px;height:56px;display:grid;place-items:center;border-radius:16px;background:#eef5ff;font-size:27px;margin-bottom:2px}
.customer-empty .sub{max-width:600px}
.customer-empty .btn{margin-top:5px}
@media(max-width:760px){
  .customer-page-head{align-items:stretch;gap:14px}
  .customer-page-head .page-title{font-size:29px}
  .customer-page-head>.btn{width:100%}
  .customer-toolbar{align-items:stretch}
  .customer-search{width:100%;display:grid;grid-template-columns:1fr auto}
  .customer-search-input{width:100%;grid-column:1/-1}
  .customer-search .btn{min-width:0}
  .customer-search .btn:last-child{grid-column:2}
  .customer-count{width:100%}
  .customer-list-card{background:transparent;border:0;box-shadow:none;overflow:visible}
  .customer-table,.customer-table tbody,.customer-table tr,.customer-table td{display:block;width:100%}
  .customer-table thead{display:none}
  .customer-table tr{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:12px 15px;margin-bottom:12px;box-shadow:var(--shadow)}
  .customer-table td{border:0;padding:5px 0!important;text-align:left!important;font-size:14px}
  .customer-table td:before{content:attr(data-label);display:inline-block;width:72px;margin-right:7px;color:var(--muted);font-size:11px;font-weight:750;text-transform:uppercase;letter-spacing:.04em;vertical-align:middle}
  .customer-table td:first-child{padding-top:2px!important}
  .customer-table td:first-child:before{display:none}
  .customer-table .customer-name{display:block;font-size:16px;margin-bottom:3px}
  .customer-actions{padding-top:9px!important;border-top:1px solid var(--line)!important;margin-top:6px}
  .customer-actions:before{display:none!important}
  .customer-actions .btn{width:100%}
}
</style>
