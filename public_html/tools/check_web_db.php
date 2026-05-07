<?php
/**
 * Herramienta temporal de diagnóstico para la BD web pública.
 * IMPORTANTE: eliminar este archivo después de la instalación.
 */
declare(strict_types=1);

$expectedToken = 'CAMBIAR_TOKEN_SEGURO';
$token = (string)($_GET['token'] ?? '');
if ($token === '' || !hash_equals($expectedToken, $token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Acceso denegado. Token inválido o ausente.";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

$requiredTables = [
    'web_configuracion', 'web_categorias', 'web_productos', 'web_producto_imagenes',
    'web_clientes', 'web_direcciones', 'web_favoritos', 'web_carritos',
    'web_carrito_items', 'web_pedidos', 'web_pedido_items', 'web_pagos',
    'web_contactos', 'web_cupones', 'web_banners', 'web_reset_tokens',
];

$dbFile = dirname(__DIR__) . '/includes/web_db.php';
if (!is_file($dbFile)) {
    http_response_code(500);
    echo "Error: no existe includes/web_db.php\n";
    exit;
}

require_once $dbFile;

if (!function_exists('web_db')) {
    http_response_code(500);
    echo "Error: includes/web_db.php no define la función web_db().\n";
    exit;
}

try {
    $pdo = web_db();
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('web_db() no devolvió una instancia de PDO.');
    }

    echo "BD conectada\n";

    $stmt = $pdo->query('SHOW TABLES');
    $found = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $found = array_values(array_map('strval', $found));

    $foundSet = array_fill_keys($found, true);
    $missing = [];
    foreach ($requiredTables as $table) {
        if (!isset($foundSet[$table])) {
            $missing[] = $table;
        }
    }

    echo "\nTablas encontradas (" . count($found) . "):\n";
    foreach ($found as $table) {
        if (str_starts_with($table, 'web_')) {
            echo " - {$table}\n";
        }
    }

    echo "\nTablas faltantes (" . count($missing) . "):\n";
    if (!$missing) {
        echo " - Ninguna\n";
    } else {
        foreach ($missing as $table) {
            echo " - {$table}\n";
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error de conexión/verificación de BD.\n";
    echo "Detalle técnico resumido: " . $e->getMessage() . "\n";
}
