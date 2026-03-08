<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "Hosting check\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "PDO available: " . (class_exists('PDO') ? 'yes' : 'no') . "\n";
echo "pdo_mysql loaded: " . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . "\n";
echo "DB host: " . DB_HOST . "\n";
echo "DB name: " . DB_NAME . "\n";

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $stmt = $pdo->query('SELECT NOW() AS server_time');
    $row = $stmt->fetch();

    echo "DB connection: OK\n";
    echo "DB time: " . ($row['server_time'] ?? 'n/a') . "\n";
} catch (Throwable $exception) {
    echo "DB connection: FAIL\n";
    echo "Error: " . $exception->getMessage() . "\n";
}
