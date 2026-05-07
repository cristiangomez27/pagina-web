<?php
require_once __DIR__ . '/includes/web_helpers.php';
$data = sw_load_data();
$title = 'Restablecer contraseña | Suave Urban Studio';
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$valid = sw_client_reset_validate_token($token);
$msg=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!sw_client_check_csrf('reset_password', (string)($_POST['csrf_token']??''))) {
    $error='La sesión expiró. Intenta nuevamente.';
  } elseif (!$valid) {
    $error='El enlace no es válido o ya expiró.';
  } else {
    $p1=(string)($_POST['password']??''); $p2=(string)($_POST['password2']??'');
    if (strlen($p1)<8) $error='La contraseña debe tener mínimo 8 caracteres.';
    elseif ($p1!==$p2) $error='Las contraseñas no coinciden.';
    elseif (sw_client_reset_password($token,$p1)) { $msg='Contraseña actualizada correctamente. Ya puedes iniciar sesión.'; }
    else $error='No se pudo restablecer la contraseña.';
  }
}
require __DIR__ . '/includes/web_header.php';
?>
<link rel="stylesheet" href="/assets/css/clientes-web-pro.css?v=20260508a">
<section class="client-auth-grid" id="restablecer"><article class="client-auth-card is-active"><div class="client-card-head"><p>Seguridad</p><h2>Restablecer contraseña</h2><span>Define una nueva contraseña segura.</span></div>
<?php if($msg): ?><div class="contact-alert contact-alert--success"><?= sw_e($msg) ?></div><?php endif; ?>
<?php if($error): ?><div class="contact-alert contact-alert--error"><?= sw_e($error) ?></div><?php endif; ?>
<?php if($valid): ?><form class="client-form" method="post" action="/restablecer-password">
<input type="hidden" name="csrf_token" value="<?= sw_e(sw_client_csrf_token('reset_password')) ?>"><input type="hidden" name="token" value="<?= sw_e($token) ?>">
<label><span>Nueva contraseña</span><input type="password" name="password" required autocomplete="new-password"></label>
<label><span>Confirmar contraseña</span><input type="password" name="password2" required autocomplete="new-password"></label>
<button class="btn btn--gold client-form__full" type="submit">Actualizar contraseña</button>
</form><?php endif; ?>
</article></section>
<?php require __DIR__ . '/includes/web_footer.php'; ?>
