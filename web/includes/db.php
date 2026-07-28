<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $configFile = __DIR__ . '/../config.php';

    if (!file_exists($configFile)) {
        http_response_code(500);
        die('Falta config.php. Copiá config.example.php a config.php y completá los datos de conexión a MySQL.');
    }

    $config = require $configFile;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db_host'],
        $config['db_name'],
        $config['db_charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die('No se pudo conectar a la base de datos. Revisá config.php y que MySQL esté corriendo.');
    }

    return $pdo;
}
