<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$path = trim((string) ($_GET['path'] ?? ''));
$count = $path !== '' ? recent_view_count($path, 5) : 0;

echo json_encode(['count' => $count]);
