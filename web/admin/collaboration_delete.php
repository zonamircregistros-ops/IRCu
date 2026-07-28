<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: collaboration.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM collaboration_applications WHERE id = :id');
    $stmt->execute(['id' => $id]);
    audit_log('Postulación de colaboración eliminada', 'id ' . $id);
    flash_set('Postulación eliminada.');
}

header('Location: collaboration.php');
exit;
