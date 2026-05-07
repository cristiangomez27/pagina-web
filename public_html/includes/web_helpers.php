<?php
/**
 * Suave Urban - Web pública limpia
 * Lee datos reales del módulo ventas/configuracion_web sin depender del build/parches anteriores.
 */
declare(strict_types=1);

if (!defined('SUAVE_WEB_VERSION')) {
    define('SUAVE_WEB_VERSION', 'clean-web-config-v7-contacto-pro');
}

function sw_e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function sw_money($value): string {
    $n = is_numeric($value) ? (float)$value : 0.0;
    return '$' . number_format($n, 0, '.', ',') . ' MXN';
}

function sw_slug(string $txt): string {
    if (function_exists('cw_slug')) {
        return cw_slug($txt);
    }
    $txt = trim(strtolower($txt));
    $txt = strtr($txt, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
        'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ñ'=>'n','Ü'=>'u'
    ]);
    $txt = preg_replace('/[^a-z0-9]+/u', '-', $txt);
    return trim((string)$txt, '-') ?: 'item';
}

function sw_abs_url(?string $path): string {
    return sw_public_asset_url($path);
}

function sw_public_asset_url(?string $path): string {
    $p = trim((string)$path);
    if ($p === '') return '';
    $p = str_replace('\\', '/', $p);

    $query = '';
    $hash = '';
    if (str_contains($p, '#')) {
        [$p, $hashPart] = explode('#', $p, 2);
        $hash = '#' . $hashPart;
    }
    if (str_contains($p, '?')) {
        [$p, $queryPart] = explode('?', $p, 2);
        $query = '?' . $queryPart;
    }

    if (preg_match('~^https?://~i', $p)) {
        $parts = parse_url($p);
        $host = strtolower((string)($parts['host'] ?? ''));
        $urlPath = (string)($parts['path'] ?? '');
        if ($host === 'ventas.suaveurbanstudio.com.mx') {
            $p = $urlPath;
        } else {
            return $p . $query . $hash;
        }
    }

    $p = '/' . ltrim($p, '/');

    if (preg_match('~^/public_html/(.+)$~i', $p, $m)) {
        $p = '/' . ltrim($m[1], '/');
    }

    if (preg_match('~^/ventas/uploads/(.+)$~i', $p, $m)) {
        return '/ventas/uploads/' . ltrim($m[1], '/') . $query . $hash;
    }

    if (preg_match('~^/uploads/(.+)$~i', $p, $m)) {
        return '/ventas/uploads/' . ltrim($m[1], '/') . $query . $hash;
    }

    if (preg_match('~^/ventas/(.+)$~i', $p, $m)) {
        return '/ventas/' . ltrim($m[1], '/') . $query . $hash;
    }

    if (preg_match('~^/assets/(.+)$~i', $p, $m)) {
        return '/assets/' . ltrim($m[1], '/') . $query . $hash;
    }

    return '/ventas/' . ltrim($p, '/') . $query . $hash;
}


function sw_best_image_url(array $row, string $primary = 'imagen'): string {
    $candidates = [
        $row[$primary] ?? '',
        $row['imagen_portada'] ?? '',
        $row['portada'] ?? '',
        $row['portada_url'] ?? '',
        $row['imagen'] ?? '',
        $row['imagen_principal'] ?? '',
        $row['imagen_url'] ?? '',
        $row['imagen_thumb_url'] ?? '',
        $row['imagen_drive_url'] ?? '',
        $row['image'] ?? '',
        $row['ruta_imagen'] ?? '',
    ];
    foreach ($candidates as $candidate) {
        $url = sw_public_asset_url((string)$candidate);
        if ($url !== '') return $url;
    }
    return '';
}

function sw_public_asset_file(?string $url): string {
    $u = trim((string)$url);
    if ($u === '' || preg_match('~^https?://~i', $u)) return '';
    $path = parse_url($u, PHP_URL_PATH);
    if (!is_string($path) || $path === '') return '';
    $root = realpath(__DIR__ . '/..');
    if (!$root) return '';
    if (str_starts_with($path, '/ventas/')) {
        return $root . '/' . ltrim($path, '/');
    }
    if (str_starts_with($path, '/assets/')) {
        return $root . '/' . ltrim($path, '/');
    }
    return '';
}

function sw_asset_cache_bust(?string $url): string {
    $u = trim((string)$url);
    if ($u === '') return '';
    $file = sw_public_asset_file($u);
    $version = ($file !== '' && is_file($file)) ? (string)filemtime($file) : date('YmdHi');
    $sep = str_contains($u, '?') ? '&' : '?';
    return $u . $sep . 'v=' . rawurlencode($version);
}

function sw_non_empty_path(?string $path): string {
    $p = trim((string)$path);
    if ($p === '') return '';
    return $p;
}


function sw_path_has_http(?string $path): bool {
    return (bool)preg_match('~^https?://~i', trim((string)$path));
}

function sw_asset_local_exists(?string $url): bool {
    $u = trim((string)$url);
    if ($u === '') return false;
    if (sw_path_has_http($u)) return true;
    $file = sw_public_asset_file($u);
    return $file !== '' && is_file($file);
}

function sw_usable_logo_url(?string $path): string {
    $raw = trim((string)$path);
    if ($raw === '') return '';

    // Valores de fábrica que no representan un logo subido desde Configuración Web.
    $plain = strtolower(trim(str_replace('\\', '/', $raw), '/'));
    if (in_array($plain, ['logo.png', 'assets/logo.png', 'img/logo.png'], true)) {
        return '';
    }

    $url = sw_public_asset_url($raw);
    if ($url === '') return '';

    // Si es archivo local, solo lo usamos si existe. Así no se queda apuntando a rutas rotas.
    if (!sw_path_has_http($url) && !sw_asset_local_exists($url)) {
        return '';
    }
    return $url;
}

function sw_public_json_logo(): string {
    $candidates = [
        __DIR__ . '/../ventas/uploads/web/public_config.json',
        __DIR__ . '/../ventas/uploads/web/diseno/public_config.json',
    ];
    foreach ($candidates as $file) {
        if (!is_file($file)) continue;
        $json = json_decode((string)@file_get_contents($file), true);
        if (!is_array($json)) continue;
        $logo = sw_usable_logo_url($json['logo'] ?? '');
        if ($logo !== '') return $logo;
    }
    return '';
}

function sw_latest_disk_logo_url(): string {
    $root = realpath(__DIR__ . '/..');
    if (!$root) return '';
    $dirs = [
        $root . '/ventas/uploads/web/diseno',
        $root . '/ventas/uploads/web',
        $root . '/ventas/uploads',
    ];
    $files = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        foreach (glob($dir . '/*.{webp,png,jpg,jpeg,gif}', GLOB_BRACE) ?: [] as $file) {
            $base = strtolower(basename($file));
            if (!is_file($file)) continue;
            // Solo tomamos archivos claramente de logo para no confundirlo con banner/fondos/modelos.
            if (!str_contains($base, 'logo')) continue;
            $files[] = $file;
        }
    }
    if (!$files) return '';
    usort($files, fn($a, $b) => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
    $rel = ltrim(str_replace(str_replace('\\', '/', $root), '', str_replace('\\', '/', $files[0])), '/');
    return sw_usable_logo_url('/' . $rel);
}

function sw_public_logo_fallback(): string {
    $json = sw_public_json_logo();
    if ($json !== '') return $json;
    return sw_latest_disk_logo_url();
}

function sw_whatsapp_url(array $negocio, string $mensaje = 'Hola, quiero información'): string {
    $w = trim((string)($negocio['whatsapp'] ?? ''));
    if ($w === '') return '#';
    if (preg_match('~^https?://~i', $w)) {
        return $w . (str_contains($w, '?') ? '&' : '?') . 'text=' . rawurlencode($mensaje);
    }
    $w = preg_replace('/\D+/', '', $w);
    return 'https://wa.me/' . $w . '?text=' . rawurlencode($mensaje);
}

function sw_parse_list(?string $value): array {
    $value = trim((string)$value);
    if ($value === '') return [];
    $parts = preg_split('/[,|\n\r]+/', $value);
    return array_values(array_filter(array_map('trim', $parts), fn($x) => $x !== ''));
}

function sw_config_data_file(): string {
    return __DIR__ . '/../ventas/configuracion_web/data/web_data.json';
}

function sw_default_web_data(): array {
    return [
        'ok' => true,
        'config' => [
            'logo' => sw_public_logo_fallback(),
            'banner_principal' => '/assets/img/hero-suave-modelos.svg',
            'fondo_principal' => '',
            'titulo_home' => 'Suave Urban Studio',
            'subtitulo_home' => 'Ropa, modelos y accesorios de la marca Suave Urban Studio.',
            'color_primario' => '#d4af37',
            'color_secundario' => '#ffe08a',
            'web_activa' => 1,
        ],
        'negocio' => [
            'nombre' => 'Suave Urban Studio',
            'descripcion' => 'Ropa urbana, modelos y accesorios para tu estilo.',
            'footer_descripcion' => 'Suave Urban Studio.',
            'correo' => '',
            'telefono' => '',
            'whatsapp' => '',
            'direccion' => '',
            'redes' => ['tiktok'=>'', 'facebook'=>'', 'instagram'=>''],
            'copyright' => '© ' . date('Y') . ' Suave Urban Studio. Todos los derechos reservados.',
        ],
        'categorias' => [
            ['id'=>1,'nombre'=>'Novedades','slug'=>'novedades','descripcion'=>'','imagen'=>'','activa'=>1,'menu'=>1,'footer'=>1,'orden'=>1],
            ['id'=>2,'nombre'=>'Hombre','slug'=>'hombre','descripcion'=>'','imagen'=>'','activa'=>1,'menu'=>1,'footer'=>1,'orden'=>2],
            ['id'=>3,'nombre'=>'Mujer','slug'=>'mujer','descripcion'=>'','imagen'=>'','activa'=>1,'menu'=>1,'footer'=>1,'orden'=>3],
            ['id'=>4,'nombre'=>'Accesorios','slug'=>'accesorios','descripcion'=>'','imagen'=>'','activa'=>1,'menu'=>1,'footer'=>1,'orden'=>4],
        ],
        'productos' => [],
        'paginas' => [
            ['titulo'=>'Contacto','url'=>'/contacto','footer'=>1,'menu'=>1,'orden'=>1],
            ['titulo'=>'Envíos y devoluciones','url'=>'/envios-devoluciones','footer'=>1,'menu'=>0,'orden'=>2],
            ['titulo'=>'Aviso de privacidad','url'=>'/aviso-privacidad','footer'=>1,'menu'=>0,'orden'=>3],
            ['titulo'=>'Términos de servicio','url'=>'/terminos-servicio','footer'=>1,'menu'=>0,'orden'=>4],
        ],
        'secciones' => [],
        'error' => '',
    ];
}

function sw_normalize_web_data(array $data): array {
    $base = sw_default_web_data();
    $data['config'] = array_replace($base['config'], $data['config'] ?? []);
    $data['negocio'] = array_replace_recursive($base['negocio'], $data['negocio'] ?? []);
    $data['categorias'] = array_values($data['categorias'] ?? $base['categorias']);
    $data['productos'] = array_values($data['productos'] ?? []);
    $data['paginas'] = array_values($data['paginas'] ?? $base['paginas']);
    foreach ($data['categorias'] as $i => &$c) {
        $c['id'] = (int)($c['id'] ?? ($i + 1));
        $c['nombre'] = (string)($c['nombre'] ?? 'Categoría');
        $c['slug'] = sw_slug((string)($c['slug'] ?? $c['nombre']));
        $c['descripcion'] = (string)($c['descripcion'] ?? '');
        // Imagen principal de la categoría: debe ser la portada configurada, no una imagen de producto.
        $imgRaw = (string)($c['imagen_portada'] ?? $c['portada'] ?? $c['imagen'] ?? $c['imagen_categoria'] ?? $c['imagen_principal'] ?? $c['image'] ?? $c['ruta_imagen'] ?? '');
        if ($imgRaw !== '' && !preg_match('~^https?://~i', $imgRaw) && !str_contains($imgRaw, '/')) { $imgRaw = '/ventas/uploads/web/categorias/' . ltrim($imgRaw, '/'); }
        $c['imagen_portada'] = $imgRaw;
        $c['imagen'] = $imgRaw;
        $c['imagen_url'] = sw_best_image_url($c, 'imagen_portada');
        $c['url'] = '/' . ltrim($c['slug'], '/');
        $c['activa'] = (int)($c['activa'] ?? 1);
        $c['menu'] = (int)($c['menu'] ?? 1);
        $c['footer'] = (int)($c['footer'] ?? 1);
        $c['orden'] = (int)($c['orden'] ?? ($i + 1));
    }
    unset($c);
    $data['categorias'] = array_values(array_filter($data['categorias'], fn($c) => (int)($c['activa'] ?? 1) === 1));
    usort($data['categorias'], fn($a,$b) => ((int)$a['orden'] <=> (int)$b['orden']) ?: ((int)$a['id'] <=> (int)$b['id']));
    $catById = [];
    $catBySlug = [];
    foreach ($data['categorias'] as $c) {
        $catById[(int)$c['id']] = $c;
        $catBySlug[(string)($c['slug'] ?? '')] = $c;
    }
    $visibleProducts = [];
    $seenProducts = [];
    foreach ($data['productos'] as $i => &$p) {
        $p['id'] = (int)($p['id'] ?? ($i + 1));
        $p['nombre'] = (string)($p['nombre'] ?? 'Producto');
        $p['slug'] = sw_slug((string)($p['slug'] ?? $p['nombre']));
        $p['categoria_id'] = (int)($p['categoria_id'] ?? 0);
        $cat = $catById[$p['categoria_id']] ?? null;
        if (!$cat && !empty($p['categoria_slug'])) { foreach ($data['categorias'] as $catRow) { if (($catRow['slug'] ?? '') === (string)$p['categoria_slug']) { $cat = $catRow; break; } } }
        $p['categoria'] = $cat['nombre'] ?? '';
        $p['categoria_slug'] = $cat['slug'] ?? (string)($p['categoria_slug'] ?? '');
        $p['imagen_principal'] = (string)($p['imagen_principal'] ?? $p['imagen'] ?? '');
        $p['imagen'] = (string)($p['imagen'] ?? $p['imagen_principal'] ?? '');
        $p['imagen_url'] = sw_best_image_url($p, 'imagen_principal');
        $p['url'] = '/producto/' . $p['id'] . '-' . $p['slug'];
        $p['descripcion'] = (string)($p['descripcion'] ?? '');
        $p['descripcion_larga'] = (string)($p['descripcion_larga'] ?? '');
        $p['precio'] = (float)($p['precio'] ?? 0);
        $p['precio_oferta'] = (float)($p['precio_oferta'] ?? 0);
        $p['tallas'] = (string)($p['tallas'] ?? '');
        $p['colores'] = (string)($p['colores'] ?? '');
        $p['tallas_array'] = sw_parse_list($p['tallas']);
        $p['colores_array'] = sw_parse_list($p['colores']);
        $gallery = [];
        if (!empty($p['galeria']) && is_array($p['galeria'])) {
            foreach ($p['galeria'] as $g) {
                if (!is_array($g)) continue;
                $gUrl = sw_best_image_url($g, 'imagen');
                if ($gUrl === '') continue;
                $gallery[] = [
                    'imagen' => (string)($g['imagen'] ?? $g['imagen_principal'] ?? $gUrl),
                    'imagen_url' => $gUrl,
                    'alt' => (string)($g['alt'] ?? $p['nombre']),
                    'orden' => (int)($g['orden'] ?? count($gallery)),
                ];
            }
        }
        foreach (['imagen_2','imagen_3','imagen_4','imagen_extra','imagen_extra_1','imagen_extra_2'] as $extraKey) {
            if (empty($p[$extraKey])) continue;
            $gUrl = sw_public_asset_url((string)$p[$extraKey]);
            if ($gUrl !== '') $gallery[] = ['imagen' => (string)$p[$extraKey], 'imagen_url' => $gUrl, 'alt' => $p['nombre'], 'orden' => count($gallery) + 1];
        }
        if (!empty($p['imagen_url'])) {
            array_unshift($gallery, ['imagen'=>$p['imagen_principal'], 'imagen_url'=>$p['imagen_url'], 'alt'=>$p['nombre'], 'orden'=>0]);
        }
        $seenGallery = [];
        $p['galeria'] = array_values(array_filter($gallery, function($g) use (&$seenGallery) {
            $url = (string)($g['imagen_url'] ?? '');
            if ($url === '' || isset($seenGallery[$url])) return false;
            $seenGallery[$url] = true;
            return true;
        }));
        $p['destacado'] = (int)($p['destacado'] ?? 0);
        $p['activo'] = (int)($p['activo'] ?? 1);
        $p['stock'] = (string)($p['stock'] ?? '');
        $p['orden'] = (int)($p['orden'] ?? ($i + 1));

        // Limpieza pública: solo se publican productos activos y vinculados a una categoría activa.
        $productKey = (int)($p['id'] ?? 0) > 0 ? 'id:' . (int)$p['id'] : 'slug:' . (string)$p['slug'];
        $hasActiveCategory = ($p['categoria_slug'] !== '' && isset($catBySlug[$p['categoria_slug']])) || ($p['categoria_id'] > 0 && isset($catById[$p['categoria_id']]));
        if ((int)$p['activo'] === 1 && $hasActiveCategory && !isset($seenProducts[$productKey])) {
            $seenProducts[$productKey] = true;
            $visibleProducts[] = $p;
        }
    }
    unset($p);
    $data['productos'] = array_values($visibleProducts);
    usort($data['productos'], fn($a,$b) => ((int)$a['orden'] <=> (int)$b['orden']) ?: ((int)$b['destacado'] <=> (int)$a['destacado']) ?: ((int)$b['id'] <=> (int)$a['id']));
    $data['config']['logo'] = sw_usable_logo_url($data['config']['logo'] ?? '') ?: sw_public_logo_fallback();
    $data['config']['banner_principal'] = sw_abs_url($data['config']['banner_principal'] ?? '') ?: '/assets/img/hero-suave-modelos.svg';
    $data['config']['fondo_principal'] = sw_abs_url($data['config']['fondo_principal'] ?? '');
    $data['ok'] = true;
    $data['error'] = '';
    return $data;
}


function sw_web_db_pdo(): ?PDO {
    static $pdo = false;
    if ($pdo !== false) return $pdo;
    $file = __DIR__ . '/web_db.php';
    if (!is_file($file)) { $pdo = null; return $pdo; }
    require_once $file;
    if (!function_exists('web_db')) { $pdo = null; return $pdo; }
    try {
        $conn = web_db();
        $pdo = $conn instanceof PDO ? $conn : null;
    } catch (Throwable $e) {
        error_log('[suave-web] DB no disponible: ' . $e->getMessage());
        $pdo = null;
    }
    return $pdo;
}

function sw_load_data_from_db(): ?array {
    $pdo = sw_web_db_pdo();
    if (!$pdo) return null;
    try {
        $base = sw_default_web_data();
        $cfgRows = $pdo->query("SELECT clave, valor_texto FROM web_configuracion")?->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($cfgRows as $r) $map[(string)$r['clave']] = (string)($r['valor_texto'] ?? '');
        $base['config']['titulo_home'] = $map['site_name'] ?? $base['config']['titulo_home'];
        $base['negocio']['nombre'] = $map['brand_name'] ?? $base['negocio']['nombre'];
        $base['negocio']['copyright'] = $map['footer_rights'] ?? $base['negocio']['copyright'];
        $base['negocio']['whatsapp'] = $map['whatsapp'] ?? $base['negocio']['whatsapp'];
        if (!empty($map['logo'])) $base['config']['logo'] = (string)$map['logo'];

        $cats = $pdo->query("SELECT id,nombre,slug,descripcion,imagen_portada,activa,menu,footer,orden FROM web_categorias WHERE activa=1 ORDER BY orden ASC,id ASC")?->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($cats) $base['categorias'] = $cats;

        $products = $pdo->query("SELECT id,categoria_id,nombre,slug,sku,descripcion_corta,descripcion_larga,precio,precio_oferta,stock,tallas,colores,imagen_principal,destacado,activo,orden FROM web_productos WHERE activo=1 ORDER BY orden ASC,id DESC")?->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($products) {
            $pids = array_map(fn($x)=>(int)$x['id'], $products);
            $imgBy=[];
            if ($pids) {
                $in = implode(',', array_fill(0, count($pids), '?'));
                $st = $pdo->prepare("SELECT producto_id,imagen_url,alt_text,orden FROM web_producto_imagenes WHERE producto_id IN ($in) ORDER BY orden ASC,id ASC");
                $st->execute($pids);
                foreach (($st->fetchAll(PDO::FETCH_ASSOC) ?: []) as $g) {
                    $pid=(int)$g['producto_id'];
                    $imgBy[$pid][]=['imagen'=>$g['imagen_url'],'imagen_url'=>$g['imagen_url'],'alt'=>$g['alt_text']?:'','orden'=>(int)$g['orden']];
                }
            }
            foreach ($products as &$pr) {
                $pr['descripcion'] = $pr['descripcion_corta'] ?? '';
                $pr['galeria'] = $imgBy[(int)$pr['id']] ?? [];
            }
            unset($pr);
            $base['productos'] = $products;
        }

        $banners = $pdo->query("SELECT nombre,posicion,titulo,subtitulo,imagen_url,enlace_url,texto_boton,activo,orden FROM web_banners WHERE activo=1 AND (fecha_inicio IS NULL OR fecha_inicio<=NOW()) AND (fecha_fin IS NULL OR fecha_fin>=NOW()) ORDER BY orden ASC,id ASC")?->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $base['banners'] = $banners;

        return sw_normalize_web_data($base);
    } catch (Throwable $e) {
        error_log('[suave-web] Error cargando datos desde DB: ' . $e->getMessage());
        return null;
    }
}

function sw_load_data(): array {
    $dbData = sw_load_data_from_db();
    if (is_array($dbData)) return $dbData;
    $file = sw_config_data_file();
    if (!is_file($file)) return sw_default_web_data();
    $json = json_decode((string)@file_get_contents($file), true);
    if (!is_array($json)) return sw_default_web_data();
    return sw_normalize_web_data($json);
}

function sw_products_by_category(array $data, string $slug, int $limit = 999): array {
    $out = [];
    foreach (($data['productos'] ?? []) as $p) {
        if (($p['categoria_slug'] ?? '') === $slug) {
            $out[] = $p;
            if (count($out) >= $limit) break;
        }
    }
    return $out;
}

function sw_featured_products(array $data, int $limit = 8): array {
    // La portada solo debe mostrar productos marcados manualmente como destacados.
    // No hacemos fallback a todos los productos para evitar duplicados con categorías.
    $featured = [];
    $seen = [];
    foreach (($data['productos'] ?? []) as $p) {
        if ((int)($p['destacado'] ?? 0) !== 1) continue;
        $id = (int)($p['id'] ?? 0);
        $key = $id > 0 ? 'id:' . $id : 'slug:' . (string)($p['slug'] ?? count($featured));
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $featured[] = $p;
        if (count($featured) >= $limit) break;
    }
    return $featured;
}

function sw_find_product(array $data, int $id, string $slug = ''): ?array {
    foreach (($data['productos'] ?? []) as $p) {
        if ($id > 0 && (int)($p['id'] ?? 0) === $id) return $p;
        if ($slug !== '' && (string)($p['slug'] ?? '') === $slug) return $p;
    }
    return null;
}

function sw_find_category(array $data, string $slug): ?array {
    foreach (($data['categorias'] ?? []) as $c) {
        if (($c['slug'] ?? '') === $slug) return $c;
    }
    return null;
}

function sw_page_title(array $data, string $page = ''): string {
    $name = $data['negocio']['nombre'] ?? 'Suave Urban Studio';
    return $page ? ($page . ' | ' . $name) : $name;
}

function sw_product_card(array $p): string {
    $id = (int)($p['id'] ?? 0);
    $name = (string)($p['nombre'] ?? 'Producto');
    $img = (string)($p['imagen_url'] ?? '');
    $url = (string)($p['url'] ?? ('/producto/' . $id));
    $price = (float)($p['precio_oferta'] ?: $p['precio'] ?: 0);
    $cat = (string)($p['categoria'] ?? '');
    $stockRaw = trim((string)($p['stock'] ?? ''));
    $stockNum = is_numeric($stockRaw) ? (int)$stockRaw : null;
    $isSoldOut = $stockNum !== null && $stockNum <= 0;
    ob_start();
    ?>
    <article class="product-card" data-product-id="<?= $id ?>">
        <a class="product-card__media" href="<?= sw_e($url) ?>" aria-label="<?= sw_e($name) ?>">
            <?php if ($img): ?>
                <img src="<?= sw_e($img) ?>" alt="<?= sw_e($name) ?>" loading="lazy">
            <?php else: ?>
                <span class="image-placeholder">SU</span>
            <?php endif; ?>
            <?php if ($isSoldOut): ?><em>Agotado</em><?php elseif ((int)($p['destacado'] ?? 0) === 1): ?><em>Destacado</em><?php endif; ?>
        </a>
        <div class="product-card__body">
            <?php if ($cat): ?><small><?= sw_e($cat) ?></small><?php endif; ?>
            <h3><a href="<?= sw_e($url) ?>"><?= sw_e($name) ?></a></h3>
            <?php if ($price > 0): ?><strong><?= sw_money($price) ?></strong><?php endif; ?>
            <div class="product-card__actions">
                <a href="<?= sw_e($url) ?>"><?= $isSoldOut ? 'Ver detalle' : 'Comprar' ?></a>
            </div>
        </div>
    </article>
    <?php
    return trim((string)ob_get_clean());
}



function sw_home_model_card(array $p): string {
    $id = (int)($p['id'] ?? 0);
    $name = (string)($p['nombre'] ?? 'Modelo');
    $img = (string)($p['imagen_url'] ?? '');
    $url = (string)($p['url'] ?? ('/producto/' . $id));
    $price = (float)($p['precio_oferta'] ?: $p['precio'] ?: 0);
    $cat = (string)($p['categoria'] ?? '');
    ob_start();
    ?>
    <article class="lookbook-card" data-product-id="<?= $id ?>">
        <a class="lookbook-card__media" href="<?= sw_e($url) ?>" aria-label="<?= sw_e($name) ?>">
            <?php if ($img): ?>
                <img src="<?= sw_e($img) ?>" alt="<?= sw_e($name) ?>" loading="lazy">
            <?php else: ?>
                <span class="image-placeholder">SU</span>
            <?php endif; ?>
            <span class="lookbook-card__overlay"></span>
        </a>
        <div class="lookbook-card__content">
            <?php if ($cat): ?><small><?= sw_e($cat) ?></small><?php endif; ?>
            <h3><a href="<?= sw_e($url) ?>"><?= sw_e($name) ?></a></h3>
            <div class="lookbook-card__meta">
                <?php if ($price > 0): ?><strong><?= sw_money($price) ?></strong><?php endif; ?>
                <div class="lookbook-card__actions">
                    <a href="<?= sw_e($url) ?>">Comprar</a>
                </div>
            </div>
        </div>
    </article>
    <?php
    return trim((string)ob_get_clean());
}

function sw_category_card(array $c): string {
    $name = (string)($c['nombre'] ?? 'Categoría');
    $slug = (string)($c['slug'] ?? sw_slug($name));
    $img = (string)($c['imagen_url'] ?? '');
    $desc = (string)($c['descripcion'] ?? '');
    ob_start();
    ?>
    <a class="category-card" href="/<?= sw_e($slug) ?>">
        <span class="category-card__image">
            <?php if ($img): ?>
                <img src="<?= sw_e($img) ?>" alt="<?= sw_e($name) ?>" loading="lazy">
            <?php else: ?>
                <span class="image-placeholder"><?= sw_e(substr($name, 0, 2)) ?></span>
            <?php endif; ?>
        </span>
        <span class="category-card__content">
            <b><?= sw_e($name) ?></b>
            <?php if ($desc): ?><small><?= sw_e($desc) ?></small><?php endif; ?>
            <em>Ver colección</em>
        </span>
    </a>
    <?php
    return trim((string)ob_get_clean());
}


/* ===== CLIENTES WEB - REGISTRO / LOGIN / MI CUENTA ===== */
function sw_client_session_start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
}

function sw_client_data_file(): string {
    return __DIR__ . '/../ventas/configuracion_web/data/clientes_web.json';
}

function sw_client_orders_file(): string {
    return __DIR__ . '/../ventas/configuracion_web/data/pedidos_web.json';
}

function sw_client_data_dir(): string {
    return dirname(sw_client_data_file());
}

function sw_client_make_dir(): void {
    $dir = sw_client_data_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

function sw_client_email(string $email): string {
    return strtolower(trim($email));
}

function sw_client_phone(string $phone): string {
    return preg_replace('/\D+/', '', $phone) ?: '';
}

function sw_client_load_all(): array {
    $file = sw_client_data_file();
    if (!is_file($file)) return [];
    $json = json_decode((string)@file_get_contents($file), true);
    if (!is_array($json)) return [];
    return array_values(array_filter($json, 'is_array'));
}

function sw_client_save_all(array $clientes): bool {
    sw_client_make_dir();
    $file = sw_client_data_file();
    $payload = json_encode(array_values($clientes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($payload)) return false;
    return @file_put_contents($file, $payload . PHP_EOL, LOCK_EX) !== false;
}

function sw_client_find_by_email(string $email): ?array {
    $email = sw_client_email($email);
    if ($email === '') return null;
    foreach (sw_client_load_all() as $cliente) {
        if (sw_client_email((string)($cliente['correo'] ?? '')) === $email) return $cliente;
    }
    return null;
}

function sw_client_find_by_id(string $id): ?array {
    $id = trim($id);
    if ($id === '') return null;
    foreach (sw_client_load_all() as $cliente) {
        if ((string)($cliente['id'] ?? '') === $id) return $cliente;
    }
    return null;
}

function sw_client_public(array $cliente): array {
    return [
        'id' => (string)($cliente['id'] ?? ''),
        'nombre' => (string)($cliente['nombre'] ?? ''),
        'correo' => (string)($cliente['correo'] ?? ''),
        'whatsapp' => (string)($cliente['whatsapp'] ?? ''),
        'telefono' => (string)($cliente['whatsapp'] ?? $cliente['telefono'] ?? ''),
        'direccion' => (string)($cliente['direccion'] ?? ''),
        'fecha_registro' => (string)($cliente['fecha_registro'] ?? ''),
        'origen' => (string)($cliente['origen'] ?? 'web_publica'),
        'estado' => (string)($cliente['estado'] ?? 'activo'),
    ];
}

function sw_client_current(): ?array {
    sw_client_session_start();
    $id = (string)($_SESSION['suave_cliente_id'] ?? '');
    if ($id === '') return null;
    $cliente = sw_client_find_by_id($id);
    return $cliente ? sw_client_public($cliente) : null;
}

function sw_client_login(array $cliente): void {
    sw_client_session_start();
    session_regenerate_id(true);
    $_SESSION['suave_cliente_id'] = (string)($cliente['id'] ?? '');
}

function sw_client_logout(): void {
    sw_client_session_start();
    unset($_SESSION['suave_cliente_id']);
}

function sw_client_register(array $input): array {
    $nombre = trim((string)($input['nombre'] ?? ''));
    $correo = sw_client_email((string)($input['correo'] ?? ''));
    $whatsapp = sw_client_phone((string)($input['whatsapp'] ?? $input['telefono'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $direccion = trim((string)($input['direccion'] ?? ''));

    $errores = [];
    if ($nombre === '') $errores['nombre'] = 'Escribe tu nombre.';
    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores['correo'] = 'Escribe un correo válido.';
    if ($whatsapp === '' || strlen($whatsapp) < 10) $errores['whatsapp'] = 'Escribe un WhatsApp válido.';
    if (strlen($password) < 6) $errores['password'] = 'La contraseña debe tener mínimo 6 caracteres.';
    if ($correo !== '' && sw_client_find_by_email($correo)) $errores['correo'] = 'Ese correo ya está registrado.';
    if ($errores) return ['ok' => false, 'errores' => $errores];

    $clientes = sw_client_load_all();
    $cliente = [
        'id' => 'cli_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)),
        'nombre' => $nombre,
        'correo' => $correo,
        'whatsapp' => $whatsapp,
        'direccion' => $direccion,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'estado' => 'activo',
        'origen' => 'web_publica',
        'fecha_registro' => date('Y-m-d H:i:s'),
        'ultima_actualizacion' => date('Y-m-d H:i:s'),
    ];
    $clientes[] = $cliente;
    if (!sw_client_save_all($clientes)) {
        return ['ok' => false, 'errores' => ['general' => 'No se pudo guardar el registro. Revisa permisos de la carpeta data.']];
    }
    sw_client_login($cliente);
    return ['ok' => true, 'cliente' => sw_client_public($cliente)];
}

function sw_client_authenticate(string $correo, string $password): array {
    $cliente = sw_client_find_by_email($correo);
    if (!$cliente || (string)($cliente['estado'] ?? 'activo') !== 'activo') {
        return ['ok' => false, 'error' => 'Correo o contraseña incorrectos.'];
    }
    if (!password_verify($password, (string)($cliente['password_hash'] ?? ''))) {
        return ['ok' => false, 'error' => 'Correo o contraseña incorrectos.'];
    }
    sw_client_login($cliente);
    return ['ok' => true, 'cliente' => sw_client_public($cliente)];
}

function sw_client_update_profile(string $id, array $input): array {
    $clientes = sw_client_load_all();
    $found = false;
    $errores = [];
    foreach ($clientes as &$cliente) {
        if ((string)($cliente['id'] ?? '') !== $id) continue;
        $found = true;
        $nombre = trim((string)($input['nombre'] ?? $cliente['nombre'] ?? ''));
        $whatsapp = sw_client_phone((string)($input['whatsapp'] ?? $cliente['whatsapp'] ?? ''));
        $direccion = trim((string)($input['direccion'] ?? $cliente['direccion'] ?? ''));
        if ($nombre === '') $errores['nombre'] = 'Escribe tu nombre.';
        if ($whatsapp === '' || strlen($whatsapp) < 10) $errores['whatsapp'] = 'Escribe un WhatsApp válido.';
        if ($errores) break;
        $cliente['nombre'] = $nombre;
        $cliente['whatsapp'] = $whatsapp;
        $cliente['direccion'] = $direccion;
        $cliente['ultima_actualizacion'] = date('Y-m-d H:i:s');
        break;
    }
    unset($cliente);
    if (!$found) return ['ok' => false, 'errores' => ['general' => 'Sesión no encontrada.']];
    if ($errores) return ['ok' => false, 'errores' => $errores];
    if (!sw_client_save_all($clientes)) return ['ok' => false, 'errores' => ['general' => 'No se pudieron guardar los cambios.']];
    return ['ok' => true, 'cliente' => sw_client_current()];
}

function sw_client_load_orders(array $cliente): array {
    $file = sw_client_orders_file();
    if (!is_file($file)) return [];
    $json = json_decode((string)@file_get_contents($file), true);
    if (!is_array($json)) return [];
    $email = sw_client_email((string)($cliente['correo'] ?? ''));
    $phone = sw_client_phone((string)($cliente['whatsapp'] ?? ''));
    $id = (string)($cliente['id'] ?? '');
    $orders = [];
    foreach ($json as $row) {
        if (!is_array($row)) continue;
        $matchId = $id !== '' && (string)($row['cliente_id'] ?? '') === $id;
        $matchEmail = $email !== '' && sw_client_email((string)($row['correo'] ?? $row['cliente_correo'] ?? '')) === $email;
        $matchPhone = $phone !== '' && sw_client_phone((string)($row['whatsapp'] ?? $row['telefono'] ?? $row['cliente_whatsapp'] ?? '')) === $phone;
        if ($matchId || $matchEmail || $matchPhone) $orders[] = $row;
    }
    usort($orders, fn($a, $b) => strcmp((string)($b['fecha'] ?? $b['creado_en'] ?? ''), (string)($a['fecha'] ?? $a['creado_en'] ?? '')));
    return $orders;
}

function sw_client_prefill_script(?array $cliente = null): string {
    $cliente = $cliente ?: sw_client_current();
    if (!$cliente) return '';
    $payload = [
        'nombre' => (string)($cliente['nombre'] ?? ''),
        'telefono' => (string)($cliente['whatsapp'] ?? ''),
        'correo' => (string)($cliente['correo'] ?? ''),
        'direccion' => (string)($cliente['direccion'] ?? ''),
    ];
    return '<script>try{localStorage.setItem("suaveurban_customer_clean_v1", ' . json_encode(json_encode($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ');}catch(e){}</script>';
}
