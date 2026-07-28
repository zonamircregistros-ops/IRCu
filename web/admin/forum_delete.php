<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forum.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM forum_topics WHERE id = :id');
    $stmt->execute(['id' => $id]);
    audit_log('Tema de foro eliminado', 'id ' . $id);
    flash_set('Tema eliminado.');
}

header('Location: forum.php');
exit;
