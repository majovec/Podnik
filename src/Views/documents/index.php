<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
  <div><h1 class="h">Doklady</h1><div class="sub">Nabídky, objednávky, zálohy, faktury i dobropisy.</div></div>
  <a class="btn primary" href="/documents/new">+ Nový doklad</a>
</div>
<div class="actions"><a class="btn" href="/documents">Vše</a><a class="btn" href="/documents?type=offer">Nabídky</a><a class="btn" href="/documents?type=order">Objednávky</a><a class="btn" href="/documents?type=invoice">Faktury</a><a class="btn" href="/documents?type=proforma">Zálohy</a></div>

<div class="card tablewrap">
<table class="table">
  <tr><th>Číslo</th><th>Zákazník</th><th>Typ</th><th>Datum</th><th>Splatnost</th><th>Stav</th><th>Celkem</th><th></th></tr>
  <?php foreach($documents as $d): ?>
  <tr>
    <td><b><?=\App\Core\View::e($d['doc_number'])?></b></td>
    <td><?=\App\Core\View::e($d['company_name']?:trim(($d['first_name']??'').' '.($d['last_name']??'')))?></td>
    <td><?=\App\Core\View::e($d['doc_type'])?></td>
    <td><?=\App\Core\View::e($d['issue_date'])?></td>
    <td><?=\App\Core\View::e($d['due_date'])?></td>
    <td><span class="badge <?=$d['payment_status']==='paid'?'ok':($d['payment_status']==='unpaid'?'warn':'')?>"><?=\App\Core\View::e($d['payment_status'])?></span></td>
    <td><?=\App\Core\View::money($d['total_with_vat'])?></td>
    <td class="doc-actions">
      <a class="btn" href="/documents/<?=$d['id']?>/pdf">PDF</a>
      <a class="btn" href="/documents/<?=$d['id']?>/isdoc">ISDOC</a>
      <?php if($d['doc_type']=='invoice' && $d['status']!=='cancelled'): ?>
        <form method="post" action="/documents/<?=$d['id']?>/cancel" style="display:inline"><input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>"><button class="btn" onclick="return confirm('Opravdu stornovat doklad?')">Storno</button></form>
      <?php endif; ?>
      <?php if($d['doc_type']=='invoice'): ?>
        <button type="button" class="btn qr-open" data-qr-url="/documents/<?=$d['id']?>/qr" data-download-url="/documents/<?=$d['id']?>/qr?download=1" data-doc="<?=\App\Core\View::e($d['doc_number'])?>">QR</button>
        <?php if(\App\Services\GoPayService::isConnected(App\Core\Auth::workspaceId())): ?>
          <form method="post" action="/bank/gopay/<?=$d['id']?>" style="display:inline"><input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>"><button class="btn">GoPay</button></form>
        <?php endif; ?>
      <?php endif; ?>
      <?php if($d['doc_type']==='offer'): ?>
        <form method="post" action="/documents/<?=$d['id']?>/offer-status" style="display:inline"><input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>"><select name="status" onchange="this.form.submit()"><option value="pending" <?=($d['status']==='pending')?'selected':''?>>Čeká</option><option value="accepted" <?=($d['status']==='accepted')?'selected':''?>>Schváleno</option><option value="rejected" <?=($d['status']==='rejected')?'selected':''?>>Zamítnuto</option></select></form>
        <form method="post" action="/documents/<?=$d['id']?>/convert-job" style="display:inline"><input type="hidden" name="_csrf" value="<?=App\Core\Auth::csrf()?>"><button class="btn">→ Zakázka</button></form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>

<div class="qr-modal" id="qrModal" aria-hidden="true">
  <div class="qr-modal-backdrop" data-qr-close></div>
  <div class="qr-modal-card" role="dialog" aria-modal="true" aria-labelledby="qrModalTitle">
    <button type="button" class="qr-modal-close" data-qr-close aria-label="Zavřít">×</button>
    <div class="qr-modal-kicker">ÚHRADA FAKTURY</div>
    <h2 id="qrModalTitle">QR platba</h2>
    <p id="qrModalDoc" class="sub">Načítám QR kód…</p>
    <div class="qr-modal-image-wrap"><img id="qrModalImage" alt="QR platba" /></div>
    <div class="qr-modal-actions">
      <a id="qrDownload" class="btn primary" href="#">Uložit QR</a>
      <a id="qrOpen" class="btn" href="#" target="_blank" rel="noopener">Otevřít samostatně</a>
    </div>
    <div class="qr-modal-hint">QR obsahuje údaje pro úhradu faktury. Pokud se QR nezobrazí, zkontrolujte bankovní účet firmy v Nastavení.</div>
  </div>
</div>

<script>
(function(){
  const modal=document.getElementById('qrModal');
  const image=document.getElementById('qrModalImage');
  const doc=document.getElementById('qrModalDoc');
  const download=document.getElementById('qrDownload');
  const open=document.getElementById('qrOpen');
  if(!modal)return;
  function close(){modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');image.removeAttribute('src');}
  document.querySelectorAll('.qr-open').forEach(btn=>btn.addEventListener('click',()=>{
    const url=btn.dataset.qrUrl, dl=btn.dataset.downloadUrl;
    doc.textContent='Faktura '+(btn.dataset.doc||'');
    image.src=url;
    download.href=dl;
    open.href=url;
    modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');
  }));
  modal.querySelectorAll('[data-qr-close]').forEach(el=>el.addEventListener('click',close));
  document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
})();
</script>
