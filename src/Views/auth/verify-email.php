<div class="login"><div class="loginbox">
<img src="/assets/byznio-logo.svg" alt="Byznio" style="width:150px;height:auto;margin-bottom:10px">
<h1 style="margin:8px 0 6px">Potvrďte e-mail</h1>
<p class="sub">Ještě jeden krok a můžete začít používat Byznio.</p>
<?php if(!empty($error)):?><div class="alert"><?=\App\Core\View::e($error)?></div><?php endif;?>
<?php if(!empty($sent)):?>
<div style="padding:18px;border:1px solid #dbe5f2;background:#f7faff;border-radius:14px;margin:18px 0;color:#243b5a;line-height:1.6">
<strong>Aktivační e-mail byl odeslán.</strong><br>
<?php if(!empty($email)):?>Zkontrolujte schránku <strong><?=\App\Core\View::e($email)?></strong> a klikněte na aktivační tlačítko.<?php else:?>Zkontrolujte svou e-mailovou schránku.<?php endif;?>
<br><span style="font-size:13px;color:#667085">Odkaz platí 24 hodin. Nezapomeňte zkontrolovat také Spam.</span>
</div>
<?php elseif(isset($sent)&&!$sent):?>
<div class="alert">Aktivační e-mail se nepodařilo odeslat. Zkontrolujte nastavení e-mailu na serveru a zkuste odeslání znovu.</div>
<?php endif;?>
<form class="form" method="post" action="/verify-email/resend">
<input type="hidden" name="_csrf" value="<?=\App\Core\Auth::csrf()?>">
<div class="field"><label>E-mail</label><input type="email" name="email" value="<?=\App\Core\View::e($email??($_SESSION['byznio_pending_verification_email']??''))?>" required></div>
<button class="btn primary">Poslat aktivační e-mail znovu</button>
</form>
<p style="margin-top:20px;color:#667085"><a href="/login">← Zpět na přihlášení</a></p>
</div></div>
