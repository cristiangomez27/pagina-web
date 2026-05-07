<?php
require_once __DIR__ . '/includes/web_helpers.php';
$data = sw_load_data();
$slug = sw_slug((string)($_GET['slug'] ?? 'colecciones'));
$cat = ($slug !== 'colecciones' && $slug !== 'novedades') ? sw_find_category($data, $slug) : null;
$notFound = false;

if ($slug === 'colecciones') {
    $pageName = 'Colecciones';
    $products = $data['productos'] ?? [];
} elseif ($slug === 'novedades') {
    $pageName = 'Novedades';
    $products = array_values(array_filter($data['productos'] ?? [], fn($p) => (int)($p['destacado'] ?? 0) === 1 || ($p['categoria_slug'] ?? '') === 'novedades'));
} elseif ($cat) {
    $pageName = $cat['nombre'] ?? ucfirst($slug);
    $products = array_values(array_filter($data['productos'] ?? [], fn($p) => ($p['categoria_slug'] ?? '') === $slug));
} else {
    $pageName = 'Categoría no disponible';
    $products = [];
    $notFound = true;
}

$title = sw_page_title($data, $pageName);
$categoryHeroImage = is_array($cat) ? sw_best_image_url($cat, 'imagen_portada') : '';
require __DIR__ . '/includes/web_header.php';
?>

<section class="page-title category-page-title">
    <p>Suave Urban Studio</p>
    <h1><?= sw_e($pageName) ?></h1>
    <?php if (!empty($cat['descripcion'])): ?><span><?= sw_e($cat['descripcion']) ?></span><?php endif; ?>
    <?php if ($categoryHeroImage !== ''): ?>
        <div class="category-hero-image"><img src="<?= sw_e($categoryHeroImage) ?>" alt="<?= sw_e((string)$pageName) ?>"></div>
    <?php endif; ?>
</section>

<?php if ($notFound): ?>
    <div class="empty-state empty-state--large">
        <h2>Categoría no disponible</h2>
        <p>Esta categoría no está activa o ya no existe en la tienda.</p>
        <a class="btn btn--gold" href="/colecciones">Ver colecciones</a>
    </div>
<?php else: ?>
    <?php if ($slug === 'colecciones' && !empty($data['categorias'])): ?>
    <section class="category-grid">
        <?php foreach ($data['categorias'] as $c): ?>
            <?= sw_category_card($c) ?>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="section-head section-head--row">
        <div>
            <p><?= $slug === 'colecciones' ? 'Todos los modelos' : 'Modelos por categoría' ?></p>
            <h2><?= sw_e($slug === 'colecciones' ? 'Productos disponibles' : $pageName) ?></h2>
        </div>
    </section>

    <section class="product-grid">
        <?php if ($products): ?>
            <?php foreach ($products as $p): ?>
                <?= sw_product_card($p) ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state empty-state--large">
                <h2>Próximamente</h2>
                <p>Todavía no hay modelos activos en esta categoría. Puedes revisar otras colecciones mientras agregamos nuevos productos.</p>
                <a class="btn btn--ghost" href="/colecciones">Ver otras colecciones</a>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/web_footer.php'; ?>
