<div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px">
<div><h1 class="h"><?=\App\Core\View::e($contract['name'])?></h1><div class="sub">Vytvořeno <?=\App\Core\View::e(substr((string)$contract['created_at'],0,10))?></div></div>
<div class="actions contract-show-actions">
<a class="btn primary" href="/contracts/<?=$contract['id']?>/pdf" target="_blank">Stáhnout PDF</a>
<form method="post" action="/contracts/<?=$contract['id']?>/delete" onsubmit="return confirm('Opravdu smazat tuto smlouvu?')"><input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>"><button class="btn danger">Smazat</button></form>
</div>
</div>

<div class="card" style="margin-top:18px"><pre style="white-space:pre-wrap;font-family:inherit;font-size:14px;line-height:1.65"><?=\App\Core\View::e($contract['body'])?></pre></div>

<style>
.contract-show-actions{flex-wrap:wrap}
.contract-show-actions form{margin:0}
@media(max-width:760px){
  .contract-show-actions{display:grid;grid-template-columns:1fr;width:100%}
  .contract-show-actions .btn,.contract-show-actions form,.contract-show-actions form .btn{width:100%}
}
</style>
