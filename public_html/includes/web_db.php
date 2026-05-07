<?php
declare(strict_types=1);

function web_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = getenv('WEB_DB_HOST') ?: '127.0.0.1';
    $port = getenv('WEB_DB_PORT') ?: '3306';
    $name = getenv('WEB_DB_NAME') ?: '';
    $user = getenv('WEB_DB_USER') ?: '';
    $pass = getenv('WEB_DB_PASS') ?: '';

    if ($name === '' || $user === '') {
        throw new RuntimeException('Configuración WEB_DB incompleta.');
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
