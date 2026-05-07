<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/web_helpers.php';
header('Content-Type: application/json; charset=utf-8');

$client = sw_client_current();
if (!$client || empty($client['id'])) {
    echo json_encode(['ok' => true, 'logged_in' => false, 'items' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$clientId = (int)$client['id'];
if ($clientId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Sesión inválida.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = [];
if ($method !== 'GET') {
    $raw = file_get_contents('php://input');
    $json = json_decode((string)$raw, true);
    if (is_array($json)) $input = $json;
    if (!$input) $input = $_POST;
}

try {
    if ($method === 'GET') {
        echo json_encode(['ok' => true, 'logged_in' => true, 'items' => sw_fav_list_for_client($clientId)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $productId = (int)($input['product_id'] ?? 0);
    if ($productId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Producto inválido.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'POST') {
        if (!sw_fav_product_exists($productId)) {
            echo json_encode(['ok' => false, 'message' => 'Producto no disponible.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $ok = sw_fav_add($clientId, $productId);
        echo json_encode(['ok' => $ok, 'logged_in' => true, 'items' => sw_fav_list_for_client($clientId)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'DELETE') {
        $ok = sw_fav_remove($clientId, $productId);
        echo json_encode(['ok' => $ok, 'logged_in' => true, 'items' => sw_fav_list_for_client($clientId)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Método no soportado.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('[suave-fav-api] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'No se pudo procesar favoritos.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
