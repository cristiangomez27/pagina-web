<?php
require_once __DIR__ . '/includes/web_helpers.php';

$data = sw_load_data();
$current = sw_client_current();
if ($current) {
    header('Location: /mi-cuenta');
    exit;
}

$title = 'Clientes | Suave Urban Studio - Registro y acceso';
$loginError = '';
$registerErrors = [];
$loginCorreo = '';
$registerInput = ['nombre' => '', 'correo' => '', 'whatsapp' => '', 'direccion' => ''];
$activeTab = (isset($_GET['registro']) || ($_POST['accion'] ?? '') === 'registro') ? 'registro' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sw_client_check_csrf('clientes_auth', (string)($_POST['csrf_token'] ?? ''))) {
        $loginError = 'Tu sesión expiró. Intenta nuevamente.';
    } else {
    $accion = (string)($_POST['accion'] ?? '');
    if ($accion === 'login') {
        $loginCorreo = trim((string)($_POST['correo'] ?? ''));
        $result = sw_client_authenticate($loginCorreo, (string)($_POST['password'] ?? ''));
        if (($result['ok'] ?? false) === true) {
            header('Location: /mi-cuenta?login=1');
            exit;
        }
        $loginError = (string)($result['error'] ?? 'No se pudo iniciar sesión.');
        $activeTab = 'login';
    } elseif ($accion === 'registro') {
        $registerInput = [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'correo' => trim((string)($_POST['correo'] ?? '')),
            'whatsapp' => trim((string)($_POST['whatsapp'] ?? '')),
            'direccion' => trim((string)($_POST['direccion'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
        ];
        $result = sw_client_register($registerInput);
        if (($result['ok'] ?? false) === true) {
            header('Location: /mi-cuenta?registro=1');
            exit;
        }
        $registerErrors = (array)($result['errores'] ?? ['general' => 'No se pudo crear la cuenta.']);
        $activeTab = 'registro';
    }
    }
}

require __DIR__ . '/includes/web_header.php';
?>
<link rel="stylesheet" href="/assets/css/clientes-web-pro.css?v=2026050302">
<section class="client-hero">
    <div>
        <p class="eyebrow">Clientes Suave Urban</p>
        <h1>Tu cuenta para pedidos, historial y seguimiento.</h1>
        <span>Regístrate para guardar tus datos, preparar pedidos más rápido y dejar listo el historial que se conectará con ventas internas.</span>
        <div class="client-hero__actions">
            <a class="btn btn--gold" href="#registro">Crear cuenta</a>
            <a class="btn btn--ghost" href="/colecciones">Seguir comprando</a>
        </div>
    </div>
    <aside>
        <b>Mi cuenta</b>
        <p>Registro, login, datos de entrega e historial preparado para conectar pedidos reales.</p>
    </aside>
</section>

<section class="client-auth-grid" id="clientes">
    <article class="client-auth-card <?= $activeTab === 'login' ? 'is-active' : '' ?>">
        <div class="client-card-head">
            <p>Acceso</p>
            <h2>Entrar como cliente</h2>
            <span>Usa tu correo y contraseña para entrar a tu cuenta.</span>
        </div>
        <?php if ($loginError): ?>
            <div class="contact-alert contact-alert--error" id="cwAutoAlert"><?= sw_e($loginError) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['saliste'])): ?>
            <div class="contact-alert contact-alert--success" id="cwAutoAlert">Sesión cerrada correctamente.</div>
        <?php endif; ?>
        <form class="client-form" method="post" action="/login-clientes">
            <input type="hidden" name="accion" value="login">
            <input type="hidden" name="csrf_token" value="<?= sw_e(sw_client_csrf_token('clientes_auth')) ?>">
            <label>
                <span>Correo</span>
                <input type="email" name="correo" value="<?= sw_e($loginCorreo) ?>" placeholder="correo@ejemplo.com" required autocomplete="email">
            </label>
            <label>
                <span>Contraseña</span>
                <input type="password" name="password" placeholder="Tu contraseña" required autocomplete="current-password">
            </label>
            <button class="btn btn--gold btn--wide" type="submit">Entrar</button>
            <a href="/recuperar-password">¿Olvidaste tu contraseña?</a>
        </form>
    </article>

    <article class="client-auth-card <?= $activeTab === 'registro' ? 'is-active' : '' ?>" id="registro">
        <div class="client-card-head">
            <p>Registro</p>
            <h2>Crear cuenta</h2>
            <span>Estos datos se guardan para usar después en pedidos, carrito y seguimiento.</span>
        </div>
        <?php if (!empty($registerErrors['general'])): ?>
            <div class="contact-alert contact-alert--error" id="cwAutoAlert"><?= sw_e($registerErrors['general']) ?></div>
        <?php endif; ?>
        <form class="client-form" method="post" action="/login-clientes?registro=1">
            <input type="hidden" name="accion" value="registro">
            <input type="hidden" name="csrf_token" value="<?= sw_e(sw_client_csrf_token('clientes_auth')) ?>">
            <label>
                <span>Nombre completo</span>
                <input type="text" name="nombre" value="<?= sw_e($registerInput['nombre']) ?>" placeholder="Tu nombre" required autocomplete="name">
                <?php if (!empty($registerErrors['nombre'])): ?><small><?= sw_e($registerErrors['nombre']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>WhatsApp</span>
                <input type="tel" name="whatsapp" value="<?= sw_e($registerInput['whatsapp']) ?>" placeholder="871 000 0000" required autocomplete="tel">
                <?php if (!empty($registerErrors['whatsapp'])): ?><small><?= sw_e($registerErrors['whatsapp']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Correo</span>
                <input type="email" name="correo" value="<?= sw_e($registerInput['correo']) ?>" placeholder="correo@ejemplo.com" required autocomplete="email">
                <?php if (!empty($registerErrors['correo'])): ?><small><?= sw_e($registerErrors['correo']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Contraseña</span>
                <input type="password" name="password" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                <?php if (!empty($registerErrors['password'])): ?><small><?= sw_e($registerErrors['password']) ?></small><?php endif; ?>
            </label>
            <label class="client-form__full">
                <span>Dirección / referencia opcional</span>
                <textarea name="direccion" placeholder="Calle, colonia, referencias o notas para entrega"><?= sw_e($registerInput['direccion']) ?></textarea>
            </label>
            <button class="btn btn--gold btn--wide client-form__full" type="submit">Crear cuenta</button>
        </form>
    </article>
</section>
<?php require __DIR__ . '/includes/web_footer.php'; ?>
