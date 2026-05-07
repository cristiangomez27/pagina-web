<?php
/** @var array $data */
/** @var string $title */
$config = $data['config'] ?? [];
$negocio = $data['negocio'] ?? [];
$logo = (string)($config['logo'] ?? '');
$logoSrc = $logo !== '' ? sw_asset_cache_bust($logo) : '';
$name = (string)($negocio['nombre'] ?? 'Suave Urban Studio');
$siteUrl = 'https://suaveurbanstudio.com.mx';
$seoTitle = trim((string)($title ?? ''));
if ($seoTitle === '' || (function_exists('mb_strlen') ? mb_strlen($seoTitle, 'UTF-8') : strlen($seoTitle)) < 30) {
    $seoTitle = 'Suave Urban Studio | Playeras Oversize y Streetwear Premium';
}
$seoDescription = trim((string)($negocio['descripcion'] ?? ''));
if ($seoDescription === '' || (function_exists('mb_strlen') ? mb_strlen($seoDescription, 'UTF-8') : strlen($seoDescription)) < 80) {
    $seoDescription = 'Descubre Suave Urban Studio: playeras oversize, diseños personalizados y moda urbana premium. Compra fácil, rápido y con estilo único.';
}
$ogTitle = 'Suave Urban Studio | Streetwear Premium';
$ogDescription = 'Playeras oversize, diseños personalizados y estilo urbano premium. Descubre tu estilo con Suave Urban Studio.';
$ogImage = $siteUrl . '/assets/img/preview.jpg';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$canonicalUrl = rtrim($siteUrl, '/') . ($currentPath === '/' ? '/' : $currentPath);
$primary = (string)($config['color_primario'] ?? '#d4af37');
$secondary = (string)($config['color_secundario'] ?? '#ffe08a');
$menuCategorias = array_values(array_filter($data['categorias'] ?? [], fn($c) => (int)($c['menu'] ?? 1) === 1));
$menuPaginas = array_values(array_filter($data['paginas'] ?? [], fn($p) => (int)($p['menu'] ?? 0) === 1));
$clienteActual = function_exists('sw_client_current') ? sw_client_current() : null;
?>
<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= sw_e($seoTitle) ?></title>
    <meta name="description" content="<?= sw_e($seoDescription) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= sw_e($canonicalUrl) ?>">
    <link rel="icon" href="/assets/img/preview.jpg" type="image/jpeg">

    <meta property="og:locale" content="es_MX">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= sw_e($name) ?>">
    <meta property="og:title" content="<?= sw_e($ogTitle) ?>">
    <meta property="og:description" content="<?= sw_e($ogDescription) ?>">
    <meta property="og:url" content="<?= sw_e($canonicalUrl) ?>">
    <meta property="og:image" content="<?= sw_e($ogImage) ?>">
    <meta property="og:image:secure_url" content="<?= sw_e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Suave Urban Studio - Streetwear Premium">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= sw_e($ogTitle) ?>">
    <meta name="twitter:description" content="<?= sw_e($ogDescription) ?>">
    <meta name="twitter:image" content="<?= sw_e($ogImage) ?>">

    <link rel="stylesheet" href="/assets/css/suave-web-clean.css?v=<?= sw_e(SUAVE_WEB_VERSION) ?>">
    <style>
        :root{
            --su-gold: <?= sw_e($primary) ?>;
            --su-gold-soft: <?= sw_e($secondary) ?>;
        }
    </style>
</head>
<body>
<div class="site-bg" aria-hidden="true"></div>
<header class="site-header">
    <a class="brand" href="/">
        <span class="brand__logo">
            <?php if ($logoSrc): ?>
                <img src="<?= sw_e($logoSrc) ?>" alt="<?= sw_e($name) ?>">
            <?php else: ?>
                SU
            <?php endif; ?>
        </span>
        <span class="brand__text">
            <b><?= sw_e($name) ?></b>
            <small>Studio</small>
        </span>
    </a>

    <button class="menu-btn" type="button" data-menu-toggle aria-label="Abrir menú">☰</button>

    <nav class="main-nav" data-main-nav>
        <a href="/">Inicio</a>
        <a href="/colecciones">Colecciones</a>
        <?php foreach ($menuCategorias as $cat): ?>
            <a href="/<?= sw_e($cat['slug'] ?? '') ?>"><?= sw_e($cat['nombre'] ?? 'Categoría') ?></a>
        <?php endforeach; ?>
        <?php foreach ($menuPaginas as $page): ?>
            <a href="<?= sw_e($page['url'] ?? '#') ?>"><?= sw_e($page['titulo'] ?? 'Página') ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="header-actions">
        <?php if ($clienteActual): ?>
            <a href="/mi-cuenta" class="header-link">Mi cuenta</a>
        <?php else: ?>
            <a href="/login-clientes" class="header-link">Clientes</a>
        <?php endif; ?>
        <a href="/trabajador-login" class="header-link header-link--gold">Equipo</a>
        <a href="/favoritos" class="icon-link" aria-label="Favoritos">♡ <span data-fav-count>0</span></a>
        <a href="/carrito" class="icon-link" aria-label="Carrito">🛒 <span data-cart-count>0</span></a>
    </div>
</header>
<main class="page-shell">
