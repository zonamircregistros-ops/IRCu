<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gline_appeals.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM gline_appeals WHERE id = :id');
    $stmt->execute(['id' => $id]);
    audit_log('Apelación eliminada', 'id ' . $id);
    flash_set('Apelación eliminada.');
}

header('Location: gline_appeals.php');
exit;
