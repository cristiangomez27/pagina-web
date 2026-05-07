<?php
require_once __DIR__ . '/includes/web_helpers.php';

$data = sw_load_data();
$cliente = sw_client_current();
if (!$cliente) {
    header('Location: /login-clientes');
    exit;
}

$title = 'Mi cuenta | Suave Urban Studio';
$profileErrors = [];
$profileSaved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['accion'] ?? '') === 'perfil') {
    if (!sw_client_check_csrf('mi_cuenta', (string)($_POST['csrf_token'] ?? ''))) {
        $profileErrors = ['general' => 'Tu sesión expiró. Intenta nuevamente.'];
    } else {
    $result = sw_client_update_profile((string)$cliente['id'], $_POST);
    if (($result['ok'] ?? false) === true) {
        $cliente = sw_client_current() ?: $cliente;
        $profileSaved = true;
    } else {
        $profileErrors = (array)($result['errores'] ?? ['general' => 'No se pudieron guardar los cambios.']);
    }
    }
}

$orders = sw_client_load_orders($cliente);
require __DIR__ . '/includes/web_header.php';
?>
<link rel="stylesheet" href="/assets/css/clientes-web-pro.css?v=2026050302">
<section class="account-hero">
    <div>
        <p class="eyebrow">Mi cuenta</p>
        <h1>Hola, <?= sw_e($cliente['nombre'] ?: 'cliente') ?>.</h1>
        <span>Aquí quedará tu historial de pedidos, datos para compras rápidas y seguimiento cuando conectemos el carrito con ventas.</span>
        <div class="client-hero__actions">
            <a class="btn btn--gold" href="/colecciones">Ver colecciones</a>
            <a class="btn btn--ghost" href="/carrito">Ver carrito</a>
            <a class="btn btn--ghost" href="/logout-clientes">Cerrar sesión</a>
        </div>
    </div>
    <aside>
        <b>Cliente registrado</b>
        <p><?= sw_e($cliente['correo']) ?></p>
        <small>Origen: web pública</small>
    </aside>
</section>

<section class="account-grid">
    <article class="account-card">
        <div class="client-card-head">
            <p>Datos</p>
            <h2>Información del cliente</h2>
            <span>Estos datos también se podrán usar para prellenar carrito y futuras solicitudes.</span>
        </div>
        <?php if ($profileSaved): ?>
            <div class="contact-alert contact-alert--success" id="cwAutoAlert">Datos actualizados correctamente.</div>
        <?php endif; ?>
        <?php if (!empty($profileErrors['general'])): ?>
            <div class="contact-alert contact-alert--error" id="cwAutoAlert"><?= sw_e($profileErrors['general']) ?></div>
        <?php endif; ?>
        <form class="client-form" method="post" action="/mi-cuenta">
            <input type="hidden" name="accion" value="perfil">
            <input type="hidden" name="csrf_token" value="<?= sw_e(sw_client_csrf_token('mi_cuenta')) ?>">
            <label>
                <span>Nombre</span>
                <input type="text" name="nombre" value="<?= sw_e($cliente['nombre']) ?>" required>
                <?php if (!empty($profileErrors['nombre'])): ?><small><?= sw_e($profileErrors['nombre']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>WhatsApp</span>
                <input type="tel" name="whatsapp" value="<?= sw_e($cliente['whatsapp']) ?>" required>
                <?php if (!empty($profileErrors['whatsapp'])): ?><small><?= sw_e($profileErrors['whatsapp']) ?></small><?php endif; ?>
            </label>
            <label class="client-form__full">
                <span>Correo</span>
                <input type="email" value="<?= sw_e($cliente['correo']) ?>" disabled>
            </label>
            <label class="client-form__full">
                <span>Dirección / referencia</span>
                <textarea name="direccion" placeholder="Dirección, colonia o referencias para entrega"><?= sw_e($cliente['direccion']) ?></textarea>
            </label>
            <button class="btn btn--gold client-form__full" type="submit">Guardar datos</button>
        </form>
    </article>

    <aside class="account-card account-card--mini">
        <h2>Accesos rápidos</h2>
        <a href="/carrito">🛒 Continuar carrito</a>
        <a href="/favoritos">♡ Ver favoritos</a>
        <a href="/contacto">✦ Cotizar diseño</a>
        <a href="/colecciones">↗ Seguir comprando</a>
    </aside>
</section>

<section class="account-card account-orders" id="historial">
    <div class="client-card-head">
        <p>Historial</p>
        <h2>Mis pedidos</h2>
        <span>Esta sección ya queda lista para recibir pedidos cuando conectemos carrito → ventas internas.</span>
    </div>
    <?php if (!$orders): ?>
        <div class="empty-state account-empty">
            <h3>Aún no hay pedidos conectados.</h3>
            <p>Cuando conectemos el carrito con el sistema interno, tus pedidos aparecerán aquí con estado, fecha y seguimiento.</p>
            <a class="btn btn--gold" href="/colecciones">Empezar compra</a>
        </div>
    <?php else: ?>
        <div class="orders-table">
            <div class="orders-table__head">
                <span>Pedido</span><span>Fecha</span><span>Estado</span><span>Total</span>
            </div>
            <?php foreach ($orders as $order): ?>
                <article class="orders-table__row">
                    <b><?= sw_e($order['folio'] ?? $order['id'] ?? 'Pedido') ?></b>
                    <span><?= sw_e($order['fecha'] ?? $order['creado_en'] ?? '') ?></span>
                    <em><?= sw_e($order['estado'] ?? 'Nuevo') ?></em>
                    <strong><?= sw_money($order['total'] ?? 0) ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?= sw_client_prefill_script($cliente) ?>
<?php require __DIR__ . '/includes/web_footer.php'; ?>
