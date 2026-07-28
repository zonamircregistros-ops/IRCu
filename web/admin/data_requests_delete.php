<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data_requests.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM data_requests WHERE id = :id');
    $stmt->execute(['id' => $id]);
    audit_log('Solicitud de datos eliminada', 'id ' . $id);
    flash_set('Solicitud eliminada.');
}

header('Location: data_requests.php');
exit;
