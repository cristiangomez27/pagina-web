<?php
require_once __DIR__ . '/includes/web_helpers.php';
$data = sw_load_data();
$title = sw_page_title($data);
$config = $data['config'] ?? [];
$hero = $config['banner_principal'] ?: ($config['fondo_principal'] ?: '/assets/img/hero-suave-modelos.svg');
$featured = sw_featured_products($data, 8);
require __DIR__ . '/includes/web_header.php';
?>

<section class="hero hero--background-models">
    <div class="hero__media hero__media--bg" aria-hidden="true">
        <?php if ($hero): ?><img src="<?= sw_e($hero) ?>" alt="Suave Urban Studio"><?php endif; ?>
    </div>
    <div class="hero__copy">
        <p class="eyebrow">Suave Urban Studio</p>
        <h1><?= sw_e($config['titulo_home'] ?: 'Moda urbana con identidad propia') ?></h1>
        <div class="hero__actions">
            <a class="btn btn--gold" href="/colecciones">Ver colecciones</a>
            <a class="btn btn--ghost" href="/novedades">Ver novedades</a>
        </div>
    </div>
</section>

<section class="section-head">
    <p>Categorías principales</p>
    <h2>Elige tu colección</h2>
</section>

<?php if (!empty($data['categorias'])): ?>
    <section class="category-grid">
        <?php foreach ($data['categorias'] as $cat): ?>
            <?= sw_category_card($cat) ?>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <div class="empty-state">Aún no hay categorías activas. Súbelas desde Configuración Web.</div>
<?php endif; ?>

<?php if ($featured): ?>
<section class="section-head section-head--row">
    <div>
        <p>Destacados</p>
        <h2>Modelos principales</h2>
    </div>
    <a href="/colecciones">Ver todo</a>
</section>
<section class="lookbook-grid lookbook-grid--featured">
    <?php foreach ($featured as $p): ?>
        <?= sw_home_model_card($p) ?>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/web_footer.php'; ?>
