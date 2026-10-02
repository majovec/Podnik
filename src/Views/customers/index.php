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
  <div class="customer-count"><?= (int)($customerCount ?? count($customers)) ?> <?=($customerCount ?? count($customers))===1?'zákazník':(($customerCount ?? count($customers))<5?'zákazníci':'zákazníků')?></div>
</div>

<?php if(!$customers): ?>
  <div class="card customer-empty">
    <div class="customer-empty-icon">👤</div>
    <b><?= $q!=='' ? 'Žádný zákazník neodpovídá hledání.' : 'Zatím nemáš žádné zákazníky.' ?></b>
    <div class="sub">Zákazníky vytvořené v CRM najdeš právě tady. U každého pak uvidíš doklady, zakázky a historii.</div>
    <a class="btn primary" href="/customers/new">+ Nový zákazník</a>
  </div>
<?php else: ?>
  <div class="customer-list" aria-label="Seznam zákazníků">
    <?php foreach($customers as $c):
      $name=trim((string)($c['company_name']??''));
      if($name==='') $name=trim((string)($c['first_name']??'').' '.(string)($c['last_name']??''));
      if($name==='') $name='Bez názvu';
      $active=(int)($c['crm_active']??$c['active']??1)===1;
      $contact=trim((string)($c['email']??''));
      $phone=trim((string)($c['phone']??''));
      $city=trim((string)($c['city']??''));
    ?>
      <article class="card customer-card">
        <a class="customer-main" href="/customers/<?=$c['id']?>">
          <div class="customer-avatar" aria-hidden="true">👤</div>
          <div class="customer-main-copy">
            <div class="customer-name-row">
              <b class="customer-name"><?=\App\Core\View::e($name)?></b>
              <?php if(!$active): ?><span class="customer-archived">Archivovaný</span><?php endif; ?>
            </div>
            <div class="customer-meta">
              <?php if(!empty($c['ico'])): ?><span>IČO <?=\App\Core\View::e($c['ico'])?></span><?php endif; ?>
              <?php if($city!==''): ?><span><?=\App\Core\View::e($city)?></span><?php endif; ?>
              <?php if($contact!==''): ?><span><?=\App\Core\View::e($contact)?></span><?php endif; ?>
              <?php if($phone!==''): ?><span><?=\App\Core\View::e($phone)?></span><?php endif; ?>
            </div>
          </div>
          <span class="customer-chevron" aria-hidden="true">›</span>
        </a>
        <a class="btn customer-edit" href="/customers/<?=$c['id']?>/edit">Upravit</a>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<style>
.customer-page-head{align-items:center;margin-bottom:18px}
.customer-page-head .page-title{font-size:32px;line-height:1.12;letter-spacing:-.035em;margin:0 0 7px;font-weight:850}
.customer-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;margin:0 0 16px;flex-wrap:wrap}
.customer-search{display:flex;align-items:center;gap:9px;flex:1;min-width:0}
.customer-search-input{width:min(420px,100%);min-height:46px;padding:11px 13px;border:1px solid #d6deea;border-radius:12px;background:#fff;color:var(--text);outline:none}
.customer-search-input:focus{border-color:#7db0f4;box-shadow:0 0 0 4px rgba(22,119,238,.1)}
.customer-count{color:var(--muted);font-size:13px;font-weight:700;white-space:nowrap}
.customer-list{display:grid;gap:12px}
.customer-card{display:flex;align-items:center;gap:14px;padding:14px 16px}
.customer-main{display:flex;align-items:center;gap:13px;min-width:0;flex:1}
.customer-avatar{width:46px;height:46px;flex:0 0 46px;display:grid;place-items:center;border-radius:14px;background:#eef5ff;font-size:22px}
.customer-main-copy{min-width:0;flex:1}
.customer-name-row{display:flex;align-items:center;gap:8px;min-width:0}
.customer-name{color:var(--text);font-size:16px;line-height:1.25;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.customer-archived{display:inline-block;flex:0 0 auto;padding:3px 7px;border-radius:999px;background:#fff4e5;color:#9a5b00;font-size:10px;font-weight:800}
.customer-name:hover{color:var(--blue)}
.customer-meta{display:flex;align-items:center;gap:12px;flex-wrap:wrap;color:var(--muted);font-size:12px;margin-top:5px}
.customer-meta span{overflow:hidden;text-overflow:ellipsis}
.customer-chevron{font-size:30px;line-height:1;color:#8fa0b8;padding:0 3px}
.customer-edit{flex:0 0 auto}
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
  .customer-card{padding:13px 14px;gap:8px}
  .customer-main{gap:10px}
  .customer-avatar{width:42px;height:42px;flex-basis:42px;border-radius:12px;font-size:19px}
  .customer-name{font-size:15px}
  .customer-meta{display:grid;grid-template-columns:1fr;gap:3px;margin-top:4px;font-size:11px}
  .customer-meta span{max-width:100%}
  .customer-edit{display:none}
  .customer-chevron{font-size:28px}
}
</style>
