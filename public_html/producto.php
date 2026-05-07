<?php
require_once __DIR__ . '/includes/web_helpers.php';
$data = sw_load_data();
$id = (int)($_GET['id'] ?? 0);
$slug = sw_slug((string)($_GET['slug'] ?? ''));
$product = sw_find_product($data, $id, $slug);
$title = sw_page_title($data, $product ? (string)$product['nombre'] : 'Producto no disponible');
require __DIR__ . '/includes/web_header.php';
?>

<?php if (!$product): ?>
    <section class="page-title product-status-page">
        <p>Producto</p>
        <h1>Producto no disponible</h1>
        <span>El modelo que buscas no está disponible o fue desactivado.</span>
    </section>
    <div class="empty-state product-empty-state">
        <p>Regresa a colecciones para ver productos activos de Suave Urban Studio.</p>
        <a class="btn btn--gold" href="/colecciones">Ver colecciones</a>
    </div>
<?php else: ?>
    <?php
        $img = (string)($product['imagen_url'] ?? '');
        $regularPrice = (float)($product['precio'] ?? 0);
        $offerPrice = (float)($product['precio_oferta'] ?? 0);
        $price = $offerPrice > 0 ? $offerPrice : $regularPrice;
        $hasOffer = $offerPrice > 0 && $regularPrice > $offerPrice;
        $name = (string)($product['nombre'] ?? 'Producto');
        $categoryName = (string)($product['categoria'] ?? '');
        $categorySlug = (string)($product['categoria_slug'] ?? 'colecciones');
        $productUrl = (string)($product['url'] ?? ('/producto/' . (int)$product['id']));
        $gallery = $product['galeria'] ?? [];
        if (!$gallery && $img !== '') $gallery = [['imagen_url' => $img, 'alt' => $name]];
        $sizes = $product['tallas_array'] ?? [];
        $colors = $product['colores_array'] ?? [];
        $stockRaw = trim((string)($product['stock'] ?? ''));
        $stockNum = is_numeric($stockRaw) ? (int)$stockRaw : null;
        $isSoldOut = $stockNum !== null && $stockNum <= 0;
        $canBuy = !$isSoldOut && $price > 0;
        $related = [];
        foreach (sw_products_by_category($data, $categorySlug, 9) as $candidate) {
            if ((int)($candidate['id'] ?? 0) === (int)($product['id'] ?? 0)) continue;
            $related[] = $candidate;
            if (count($related) >= 4) break;
        }
        $shareText = 'Mira este producto de Suave Urban Studio: ' . $name;
    ?>

    <nav class="breadcrumbs product-breadcrumbs">
        <a href="/">Inicio</a><span>/</span>
        <a href="/colecciones">Colecciones</a><span>/</span>
        <?php if ($categorySlug !== ''): ?><a href="/<?= sw_e($categorySlug) ?>"><?= sw_e($categoryName ?: 'Categoría') ?></a><span>/</span><?php endif; ?>
        <b><?= sw_e($name) ?></b>
    </nav>

    <section class="product-detail product-detail--complete" data-product-detail>
        <div class="product-detail__gallery">
            <div class="main-product-image product-main-frame">
                <?php if ($img): ?>
                    <img src="<?= sw_e($img) ?>" alt="<?= sw_e($name) ?>" data-main-product-image>
                <?php else: ?>
                    <span class="image-placeholder">SU</span>
                <?php endif; ?>
                <?php if ($hasOffer): ?><span class="product-badge product-badge--offer">Oferta</span><?php endif; ?>
                <?php if ($isSoldOut): ?><span class="product-badge product-badge--soldout">Agotado</span><?php endif; ?>
            </div>
            <?php if (count($gallery) > 1): ?>
            <div class="thumb-row product-thumb-row" aria-label="Galería del producto">
                <?php foreach ($gallery as $idx => $g): if (empty($g['imagen_url'])) continue; ?>
                    <button type="button" class="<?= $idx === 0 ? 'is-active' : '' ?>" data-thumb="<?= sw_e($g['imagen_url']) ?>" aria-label="Ver imagen <?= (int)$idx + 1 ?>">
                        <img src="<?= sw_e($g['imagen_url']) ?>" alt="<?= sw_e($g['alt'] ?? $name) ?>">
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <article class="product-detail__info product-purchase-card">
            <div class="product-meta-row">
                <?php if ($categoryName): ?><a class="eyebrow" href="/<?= sw_e($categorySlug) ?>"><?= sw_e($categoryName) ?></a><?php endif; ?>
                <span class="availability <?= $isSoldOut ? 'is-off' : 'is-on' ?>"><?= $isSoldOut ? 'Agotado' : 'Disponible' ?></span>
            </div>
            <h1><?= sw_e($name) ?></h1>

            <div class="price-row price-row--detail">
                <?php if ($price > 0): ?><strong class="detail-price"><?= sw_money($price) ?></strong><?php endif; ?>
                <?php if ($hasOffer): ?><span class="old-price"><?= sw_money($regularPrice) ?></span><?php endif; ?>
            </div>

            <?php if (!empty($product['descripcion'])): ?><p class="product-short-description"><?= nl2br(sw_e($product['descripcion'])) ?></p><?php endif; ?>

            <?php if (!empty($sizes)): ?>
            <div class="option-block" data-required-option="size">
                <b>Talla</b>
                <div class="pill-row">
                    <?php foreach ($sizes as $t): ?><button type="button" data-size-option><?= sw_e($t) ?></button><?php endforeach; ?>
                </div>
                <small>Elige una talla para continuar.</small>
            </div>
            <?php else: ?>
            <div class="option-note">Talla única o no requerida para este producto.</div>
            <?php endif; ?>

            <?php if (!empty($colors)): ?>
            <div class="option-block" data-required-option="color">
                <b>Color</b>
                <div class="pill-row">
                    <?php foreach ($colors as $c): ?><button type="button" data-color-option><?= sw_e($c) ?></button><?php endforeach; ?>
                </div>
                <small>Elige un color para continuar.</small>
            </div>
            <?php else: ?>
            <div class="option-note">Color único o no requerido para este producto.</div>
            <?php endif; ?>

            <div class="quantity-block">
                <b>Cantidad</b>
                <div class="qty qty--product">
                    <button type="button" data-product-qty-minus aria-label="Menos">−</button>
                    <input type="number" value="1" min="1" max="99" data-product-qty aria-label="Cantidad">
                    <button type="button" data-product-qty-plus aria-label="Más">+</button>
                </div>
            </div>

            <?php if (!$canBuy): ?>
                <div class="product-info-box product-info-box--warning">
                    <b>Producto no disponible para compra inmediata</b>
                    <p><?= $isSoldOut ? 'Este producto aparece como agotado.' : 'Este producto aún no tiene precio configurado.' ?></p>
                </div>
            <?php endif; ?>

            <div class="detail-actions detail-actions--stacked">
                <button class="btn btn--gold" type="button"
                    data-add-cart
                    data-id="<?= (int)$product['id'] ?>"
                    data-name="<?= sw_e($name) ?>"
                    data-price="<?= sw_e((string)$price) ?>"
                    data-image="<?= sw_e($img) ?>"
                    data-url="<?= sw_e($productUrl) ?>"
                    data-requires-size="<?= !empty($sizes) ? '1' : '0' ?>"
                    data-requires-color="<?= !empty($colors) ? '1' : '0' ?>"
                    <?= $canBuy ? '' : 'disabled' ?>>Agregar al carrito</button>
                <button class="btn btn--ghost" type="button"
                    data-buy-now
                    data-id="<?= (int)$product['id'] ?>"
                    data-name="<?= sw_e($name) ?>"
                    data-price="<?= sw_e((string)$price) ?>"
                    data-image="<?= sw_e($img) ?>"
                    data-url="<?= sw_e($productUrl) ?>"
                    data-requires-size="<?= !empty($sizes) ? '1' : '0' ?>"
                    data-requires-color="<?= !empty($colors) ? '1' : '0' ?>"
                    <?= $canBuy ? '' : 'disabled' ?>>Comprar ahora</button>
            </div>

            <div class="detail-secondary-actions">
                <button type="button" data-share-product data-share-text="<?= sw_e($shareText) ?>">Compartir producto</button>
                <?php if ($categorySlug !== ''): ?><a href="/<?= sw_e($categorySlug) ?>">Ver más de <?= sw_e($categoryName ?: 'esta categoría') ?></a><?php endif; ?>
            </div>

            <div class="product-info-grid">
                <div><b>Compra segura</b><span>Revisa talla, color y cantidad antes de confirmar.</span></div>
                <div><b>Entrega</b><span>El envío se confirma al finalizar pedido.</span></div>
                <div><b>Marca</b><span>Producto de Suave Urban Studio.</span></div>
            </div>
        </article>
    </section>

    <section class="clean-panel product-description-panel product-tabs-panel">
        <div>
            <h2>Detalles del producto</h2>
            <?php if (!empty($product['descripcion_larga'])): ?>
                <p><?= nl2br(sw_e($product['descripcion_larga'])) ?></p>
            <?php elseif (!empty($product['descripcion'])): ?>
                <p><?= nl2br(sw_e($product['descripcion'])) ?></p>
            <?php else: ?>
                <p>Producto de la tienda Suave Urban Studio. Revisa las opciones disponibles antes de agregar al carrito.</p>
            <?php endif; ?>
        </div>
        <div>
            <h2>Guía rápida</h2>
            <ul class="product-care-list">
                <li>Verifica talla, color y cantidad antes de confirmar.</li>
                <li>Las imágenes pueden variar ligeramente por pantalla o iluminación.</li>
                <li>Conserva tu comprobante y datos de pedido para seguimiento.</li>
            </ul>
        </div>
    </section>

    <?php if ($related): ?>
    <section class="section-head section-head--row related-head">
        <div>
            <p>También te puede gustar</p>
            <h2>Productos relacionados</h2>
        </div>
        <a href="/<?= sw_e($categorySlug) ?>">Ver categoría</a>
    </section>
    <section class="product-grid related-product-grid">
        <?php foreach ($related as $p): ?>
            <?= sw_product_card($p) ?>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <script type="application/ld+json"><?php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'image' => $img ? [$img] : [],
            'description' => (string)($product['descripcion'] ?: $product['descripcion_larga'] ?: $name),
            'brand' => ['@type' => 'Brand', 'name' => 'Suave Urban Studio'],
        ];
        if ($price > 0) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'MXN',
                'price' => $price,
                'availability' => $isSoldOut ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
            ];
        }
        echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?></script>
<?php endif; ?>

<?php require __DIR__ . '/includes/web_footer.php'; ?>
