<?php
require_once __DIR__ . '/includes/web_helpers.php';
$data = sw_load_data();
$title = sw_page_title($data, 'Carrito');
require __DIR__ . '/includes/web_header.php';
?>
<section class="page-title cart-title">
    <p>Tienda Suave Urban Studio</p>
    <h1>Carrito</h1>
    <span>Revisa tus productos, talla, color y cantidad antes de continuar. El pago se conectará después con el sistema de ventas.</span>
</section>

<section
    class="cart-page cart-page--complete"
    data-cart-page
    data-whatsapp="<?= sw_e($data['negocio']['whatsapp'] ?? '') ?>"
    data-web-order-endpoint="/api/web/crear_orden.php">
    <div class="empty-state">Cargando carrito...</div>
</section>

<?php require __DIR__ . '/includes/web_footer.php'; ?>
<script>window.SUAVE_CART_CHECKOUT_ONLY = true;</script>
<script src="/assets/js/suave-cart-checkout.js?v=20260504a" defer></script>
