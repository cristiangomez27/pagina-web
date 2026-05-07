<?php
require_once __DIR__ . '/includes/web_helpers.php';
$data = sw_load_data();
$title = sw_page_title($data, 'Favoritos');
require __DIR__ . '/includes/web_header.php';
?>
<section class="page-title">
    <p>Tu selección</p>
    <h1>Favoritos</h1>
</section>

<section class="favorites-page" data-favorites-page>
    <div class="empty-state">Cargando favoritos...</div>
</section>

<?php require __DIR__ . '/includes/web_footer.php'; ?>
