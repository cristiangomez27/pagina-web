<?php
/**
 * Herramienta temporal de instalación para la BD web pública.
 * IMPORTANTE: eliminar este archivo después de la instalación.
 *
 * Uso:
 *   /tools/install_web_db.php?token=CAMBIAR_TOKEN_SEGURO
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

$dbFile = dirname(__DIR__) . '/includes/web_db.php';
$schemaFile = dirname(__DIR__, 2) . '/database/web_schema.sql';
$seedFile = dirname(__DIR__, 2) . '/database/web_seed.sql';

if (!is_file($dbFile)) {
    http_response_code(500);
    echo "Error: no existe includes/web_db.php\n";
    exit;
}
if (!is_file($schemaFile)) {
    http_response_code(500);
    echo "Error: no existe database/web_schema.sql\n";
    exit;
}
if (!is_file($seedFile)) {
    http_response_code(500);
    echo "Error: no existe database/web_seed.sql\n";
    exit;
}

require_once $dbFile;
if (!function_exists('web_db')) {
    http_response_code(500);
    echo "Error: includes/web_db.php no define la función web_db().\n";
    exit;
}

$requiredTables = [
    'web_configuracion', 'web_categorias', 'web_productos', 'web_producto_imagenes',
    'web_clientes', 'web_direcciones', 'web_favoritos', 'web_carritos',
    'web_carrito_items', 'web_pedidos', 'web_pedido_items', 'web_pagos',
    'web_contactos', 'web_cupones', 'web_banners', 'web_reset_tokens',
];

function runSqlStatements(PDO $pdo, string $sql): void {
    $chunks = preg_split('/;\s*(\r?\n|$)/', $sql);
    if (!$chunks) return;
    foreach ($chunks as $chunk) {
        $stmt = trim($chunk);
        if ($stmt === '') continue;
        $pdo->exec($stmt);
    }
}

try {
    $pdo = web_db();
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('web_db() no devolvió una instancia de PDO.');
    }

    echo "BD conectada\n";

    $schemaSql = (string)file_get_contents($schemaFile);
    $seedSql = (string)file_get_contents($seedFile);

    $pdo->beginTransaction();
    runSqlStatements($pdo, $schemaSql);
    runSqlStatements($pdo, $seedSql);
    $pdo->commit();

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

    echo "\nTablas encontradas:\n";
    foreach ($requiredTables as $table) {
        if (isset($foundSet[$table])) {
            echo " - {$table}\n";
        }
    }

    echo "\nTablas faltantes:\n";
    if (!$missing) {
        echo " - Ninguna\n";
    } else {
        foreach ($missing as $table) {
            echo " - {$table}\n";
        }
    }

    echo "\nInstalación finalizada.\n";
    echo "IMPORTANTE: eliminar /public_html/tools/check_web_db.php e /public_html/tools/install_web_db.php tras validar.\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "Error durante instalación/verificación de BD.\n";
    echo "Detalle técnico resumido: " . $e->getMessage() . "\n";
}
