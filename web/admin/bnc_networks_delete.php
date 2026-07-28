<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bnc_networks.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM bnc_networks WHERE id = :id');
    $stmt->execute(['id' => $id]);
    flash_set('Red eliminada.');
}

header('Location: bnc_networks.php');
exit;
