<?php
require_once __DIR__ . '/includes/web_helpers.php';
$data = sw_load_data();
$title = 'Recuperar contraseña | Suave Urban Studio';
$msg=''; $devLink=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!sw_client_check_csrf('recover_password', (string)($_POST['csrf_token']??''))) {
    $error='La sesión expiró. Intenta nuevamente.';
  } elseif (!sw_client_reset_rate_limit('recover_password', 20) || !sw_client_reset_ip_rate_limit('recover_password', 20)) {
    $error='Espera unos segundos e inténtalo de nuevo.';
  } else {
    $email=trim((string)($_POST['correo']??''));
    sw_client_reset_request($email, $devLink);
    $msg='Si el correo existe, se enviaron instrucciones.';
  }
}
require __DIR__ . '/includes/web_header.php';
?>
<link rel="stylesheet" href="/assets/css/clientes-web-pro.css?v=20260508a">
<section class="client-auth-grid" id="recuperar">
<article class="client-auth-card is-active"><div class="client-card-head"><p>Seguridad</p><h2>Recuperar contraseña</h2><span>Ingresa tu correo para continuar.</span></div>
<?php if($msg): ?><div class="contact-alert contact-alert--success"><?= sw_e($msg) ?></div><?php endif; ?>
<?php if($error): ?><div class="contact-alert contact-alert--error"><?= sw_e($error) ?></div><?php endif; ?>
<form class="client-form" method="post" action="/recuperar-password">
<input type="hidden" name="csrf_token" value="<?= sw_e(sw_client_csrf_token('recover_password')) ?>">
<label class="client-form__full"><span>Correo</span><input type="email" name="correo" required autocomplete="email"></label>
<button class="btn btn--gold client-form__full" type="submit">Solicitar recuperación</button>
</form>
<?php if($devLink && (getenv('APP_ENV') ?: 'dev') !== 'production'): ?><div class="contact-alert"><small>Dev link: <a href="<?= sw_e($devLink) ?>"><?= sw_e($devLink) ?></a></small></div><?php endif; ?>
</article></section>
<?php require __DIR__ . '/includes/web_footer.php'; ?>
