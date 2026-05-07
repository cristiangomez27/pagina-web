<?php
require_once __DIR__ . '/includes/web_helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

$data = sw_load_data();
$title = 'Contacto | Suave Urban Studio - Atención y pedidos personalizados';
$negocio = $data['negocio'] ?? [];
$redes = is_array($negocio['redes'] ?? null) ? $negocio['redes'] : [];

function sw_contact_clean_text($value, int $max = 500): string {
    $txt = trim((string)($value ?? ''));
    $txt = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $txt) ?? '';
    if (function_exists('mb_substr')) {
        return mb_substr($txt, 0, $max, 'UTF-8');
    }
    return substr($txt, 0, $max);
}

function sw_contact_phone_digits(string $phone): string {
    return preg_replace('/\D+/', '', $phone) ?? '';
}

function sw_contact_storage_file(): string {
    return __DIR__ . '/data/contactos_web.json';
}

function sw_contact_save_lead(array $lead, ?string &$error = null): bool {
    $file = sw_contact_storage_file();
    $dir = dirname($file);

    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        $error = 'No se pudo crear la carpeta de contactos web.';
        return false;
    }

    $fp = @fopen($file, 'c+');
    if (!$fp) {
        $error = 'No se pudo abrir el archivo de contactos web.';
        return false;
    }

    $ok = false;
    if (@flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        $payload = json_decode((string)$raw, true);
        if (!is_array($payload)) {
            $payload = ['version' => 1, 'leads' => []];
        }
        if (!isset($payload['leads']) || !is_array($payload['leads'])) {
            $payload['leads'] = [];
        }

        array_unshift($payload['leads'], $lead);
        $payload['leads'] = array_slice($payload['leads'], 0, 2000);
        $payload['updated_at'] = date('c');

        rewind($fp);
        ftruncate($fp, 0);
        $ok = fwrite($fp, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)) !== false;
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        $error = 'No se pudo bloquear el archivo de contactos web.';
    }

    fclose($fp);
    if (!$ok && $error === null) {
        $error = 'No se pudo guardar el contacto web.';
    }
    return $ok;
}


function sw_contact_db_pdo(): ?PDO {
    $dbFile = __DIR__ . '/includes/web_db.php';
    if (!is_file($dbFile)) return null;
    require_once $dbFile;
    if (!function_exists('web_db')) return null;
    try {
        $pdo = web_db();
        return $pdo instanceof PDO ? $pdo : null;
    } catch (Throwable $e) {
        error_log('[suave-contacto] DB no disponible: ' . $e->getMessage());
        return null;
    }
}

function sw_contact_save_lead_db(array $lead, ?string &$error = null): bool {
    $pdo = sw_contact_db_pdo();
    if (!$pdo) return false;
    try {
        $sql = 'INSERT INTO web_contactos (nombre, whatsapp, correo, tipo_consulta, mensaje, estado, created_at, updated_at) VALUES (:nombre, :whatsapp, :correo, :tipo, :mensaje, :estado, NOW(), NOW())';
        $st = $pdo->prepare($sql);
        return $st->execute([
            ':nombre' => (string)($lead['nombre'] ?? ''),
            ':whatsapp' => (string)($lead['telefono'] ?? ''),
            ':correo' => (string)($lead['email'] ?? ''),
            ':tipo' => (string)($lead['tipo'] ?? 'Contacto web'),
            ':mensaje' => (string)($lead['mensaje'] ?? ''),
            ':estado' => 'nuevo',
        ]);
    } catch (Throwable $e) {
        $error = 'No se pudo guardar en base de datos web.';
        error_log('[suave-contacto] Error guardando contacto: ' . $e->getMessage());
        return false;
    }
}

$tipos = [
    'Pedido personalizado',
    'Playeras / colecciones',
    'Cotización por mayoreo',
    'Seguimiento de pedido',
    'Otro',
];

$form = [
    'nombre' => '',
    'whatsapp' => '',
    'correo' => '',
    'tipo' => 'Pedido personalizado',
    'mensaje' => '',
];
$errors = [];
$success = false;
$saveWarning = '';
$whatsappMsg = 'Hola, quiero información de Suave Urban Studio.';
if (empty($_SESSION['sw_contact_csrf'])) { $_SESSION['sw_contact_csrf'] = bin2hex(random_bytes(32)); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nombre'] = sw_contact_clean_text($_POST['nombre'] ?? '', 90);
    $form['whatsapp'] = sw_contact_clean_text($_POST['whatsapp'] ?? '', 30);
    $form['correo'] = sw_contact_clean_text($_POST['correo'] ?? '', 120);
    $form['tipo'] = sw_contact_clean_text($_POST['tipo'] ?? 'Pedido personalizado', 60);
    $form['mensaje'] = sw_contact_clean_text($_POST['mensaje'] ?? '', 1200);
    $honeypot = sw_contact_clean_text($_POST['website'] ?? '', 80);
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if ($csrf === '' || !hash_equals((string)($_SESSION['sw_contact_csrf'] ?? ''), $csrf)) {
        $errors['general'] = 'La sesión del formulario expiró. Intenta nuevamente.';
    }

    if (!in_array($form['tipo'], $tipos, true)) {
        $form['tipo'] = 'Otro';
    }

    $phoneDigits = sw_contact_phone_digits($form['whatsapp']);

    if ($form['nombre'] === '' || strlen($form['nombre']) < 2) {
        $errors['nombre'] = 'Escribe tu nombre.';
    }
    if ($phoneDigits !== '' && (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 20)) {
        $errors['whatsapp'] = 'Escribe un teléfono válido o déjalo vacío.';
    }
    if ($form['correo'] !== '' && !filter_var($form['correo'], FILTER_VALIDATE_EMAIL)) {
        $errors['correo'] = 'Escribe un correo válido o déjalo vacío.';
    }
    if ($form['mensaje'] === '' || strlen($form['mensaje']) < 8) {
        $errors['mensaje'] = 'Cuéntanos un poco más de lo que necesitas.';
    }

    $whatsappMsg = "Hola Suave Urban Studio, soy {$form['nombre']}.\n";
    $whatsappMsg .= "Tipo de solicitud: {$form['tipo']}.\n";
    $whatsappMsg .= "WhatsApp: {$form['whatsapp']}.";
    if ($form['correo'] !== '') {
        $whatsappMsg .= "\nCorreo: {$form['correo']}.";
    }
    $whatsappMsg .= "\nMensaje: {$form['mensaje']}";

    if (!$errors) {
        // Campo oculto antispam: si viene lleno, no guardamos, pero no damos pistas al bot.
        if ($honeypot === '') {
            $lead = [
                'id' => 'web_' . date('Ymd_His') . '_' . random_int(1000, 9999),
                'fecha' => date('c'),
                'nombre' => $form['nombre'],
                'whatsapp' => $phoneDigits,
                'whatsapp_visible' => $form['whatsapp'],
                'correo' => $form['correo'],
                'tipo' => $form['tipo'],
                'mensaje' => $form['mensaje'],
                'origen' => 'web_publica_contacto',
                'estado' => 'nuevo',
            ];
            $saveError = null;
            $saved = sw_contact_save_lead_db([
                'nombre' => $form['nombre'],
                'telefono' => $phoneDigits,
                'email' => $form['correo'],
                'tipo' => $form['tipo'],
                'mensaje' => $form['mensaje'],
            ], $saveError);
            if (!$saved && !sw_contact_save_lead($lead, $saveError)) {
                $saveWarning = 'Recibimos tu solicitud, pero hubo un retraso al guardarla.';
            }
        }
        $success = true;
        $form = [
            'nombre' => '',
            'whatsapp' => '',
            'correo' => '',
            'tipo' => 'Pedido personalizado',
            'mensaje' => '',
        ];
    }
}

$waUrl = !empty($negocio['whatsapp']) ? sw_whatsapp_url($negocio, $whatsappMsg) : '#';
$mapsUrl = !empty($negocio['direccion']) ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string)$negocio['direccion']) : '';

require __DIR__ . '/includes/web_header.php';
?>

<section class="contact-pro-hero">
    <div class="contact-pro-hero__copy">
        <p class="eyebrow">Atención Suave Urban</p>
        <h1>Cuéntanos tu idea.</h1>
        <p>Escríbenos.</p>

    </div>

    <aside class="contact-pro-hero__panel" aria-label="Información rápida">
        <div>
            <span>Respuesta</span>
            <b>Atención personalizada</b>
        </div>
</section>

<section class="contact-pro-grid" id="formulario-contacto">
    <article class="contact-form-card">
        <div class="contact-form-card__head">
            <p class="eyebrow">Formulario</p>
            <h2>Solicita información</h2>
        </div>

        <?php if ($success): ?>
            <div class="contact-alert contact-alert--success">
                <b>Solicitud recibida.</b>
                <span>Ya quedó registrada. También puedes enviarla por WhatsApp para respuesta más rápida.</span>
                <?php if (!empty($negocio['whatsapp'])): ?>
                    <a href="<?= sw_e($waUrl) ?>" target="_blank" rel="noopener">Enviar por WhatsApp</a>
                <?php endif; ?>
                <?php if ($saveWarning !== ''): ?>
                    <small><?= sw_e($saveWarning) ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="contact-alert contact-alert--error">
                <b>Revisa los datos.</b>
                <span>Faltan algunos campos para poder enviar tu solicitud.</span>
            </div>
        <?php endif; ?>

        <form class="contact-pro-form" method="post" action="/contacto#formulario-contacto" novalidate>
            <input type="hidden" name="csrf_token" value="<?= sw_e((string)($_SESSION['sw_contact_csrf'] ?? '')) ?>">
            <div class="contact-hidden-field" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <label>
                <span>Nombre completo</span>
                <input type="text" name="nombre" value="<?= sw_e($form['nombre']) ?>" placeholder="Tu nombre" autocomplete="name" required>
                <?php if (!empty($errors['nombre'])): ?><small><?= sw_e($errors['nombre']) ?></small><?php endif; ?>
            </label>

            <label>
                <span>WhatsApp</span>
                <input type="tel" name="whatsapp" value="<?= sw_e($form['whatsapp']) ?>" placeholder="Ej. 871 000 0000" autocomplete="tel" required>
                <?php if (!empty($errors['whatsapp'])): ?><small><?= sw_e($errors['whatsapp']) ?></small><?php endif; ?>
            </label>

            <label>
                <span>Correo opcional</span>
                <input type="email" name="correo" value="<?= sw_e($form['correo']) ?>" placeholder="correo@ejemplo.com" autocomplete="email">
                <?php if (!empty($errors['correo'])): ?><small><?= sw_e($errors['correo']) ?></small><?php endif; ?>
            </label>

            <label>
                <span>Tipo de solicitud</span>
                <select name="tipo" required>
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= sw_e($tipo) ?>" <?= $form['tipo'] === $tipo ? 'selected' : '' ?>><?= sw_e($tipo) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="contact-pro-form__full">
                <span>Mensaje</span>
                <textarea name="mensaje" rows="6" placeholder="Cuéntanos ." required><?= sw_e($form['mensaje']) ?></textarea>
                <?php if (!empty($errors['mensaje'])): ?><small><?= sw_e($errors['mensaje']) ?></small><?php endif; ?>
            </label>

            <div class="contact-pro-form__actions">
                <button class="btn btn--gold" type="submit">Enviar formulario</button>
            </div>
        </form>
    </article>

    <aside class="contact-info-stack">
        <article class="contact-info-card contact-info-card--gold">
            <p class="eyebrow">Contacto</p>
            <h2><?= sw_e($negocio['nombre'] ?? 'Suave Urban Studio') ?></h2>
        </article>

        <?php if (!empty($negocio['whatsapp']) || !empty($negocio['telefono']) || !empty($negocio['correo'])): ?>
            <article class="contact-info-card">
                <h3>Atención</h3>
                <?php if (!empty($negocio['whatsapp'])): ?><a href="<?= sw_e(sw_whatsapp_url($negocio)) ?>" target="_blank" rel="noopener">WhatsApp: <?= sw_e($negocio['whatsapp']) ?></a><?php endif; ?>
                <?php if (!empty($negocio['telefono'])): ?><p>Teléfono: <?= sw_e($negocio['telefono']) ?></p><?php endif; ?>
                <?php if (!empty($negocio['correo'])): ?><a href="mailto:<?= sw_e($negocio['correo']) ?>">Correo: <?= sw_e($negocio['correo']) ?></a><?php endif; ?>
            </article>
        <?php endif; ?>

        <?php if (!empty($negocio['direccion'])): ?>
            <article class="contact-info-card">
                <h3>Ubicación</h3>
                <p><?= nl2br(sw_e($negocio['direccion'])) ?></p>
                <?php if ($mapsUrl): ?><a href="<?= sw_e($mapsUrl) ?>" target="_blank" rel="noopener">Abrir en Google Maps</a><?php endif; ?>
            </article>
        <?php endif; ?>

        <?php if (!empty($redes['instagram']) || !empty($redes['facebook']) || !empty($redes['tiktok'])): ?>
            <article class="contact-info-card">
                <h3>Redes</h3>
                <div class="contact-socials">
                    <?php if (!empty($redes['instagram'])): ?><a href="<?= sw_e($redes['instagram']) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
                    <?php if (!empty($redes['facebook'])): ?><a href="<?= sw_e($redes['facebook']) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
                    <?php if (!empty($redes['tiktok'])): ?><a href="<?= sw_e($redes['tiktok']) ?>" target="_blank" rel="noopener">TikTok</a><?php endif; ?>
                </div>
            </article>
        <?php endif; ?>
    </aside>
</section>

<?php require __DIR__ . '/includes/web_footer.php'; ?>
