<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

$status = 'ok';
$checks = ['database' => 'ok'];

try {
    db()->query('SELECT 1');
} catch (\Throwable $e) {
    $status = 'error';
    $checks['database'] = 'error';
}

http_response_code($status === 'ok' ? 200 : 503);
echo json_encode([
    'status' => $status,
    'checks' => $checks,
    'timestamp' => date(DATE_ATOM),
], JSON_PRETTY_PRINT);
