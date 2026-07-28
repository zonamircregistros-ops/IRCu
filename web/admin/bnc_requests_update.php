<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bnc_requests.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$status = in_array($_POST['status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['status'] : 'pendiente';

if ($id > 0) {
    $stmt = db()->prepare('UPDATE bnc_requests SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $id]);
    flash_set('Solicitud actualizada.');
}

header('Location: bnc_requests.php');
exit;
