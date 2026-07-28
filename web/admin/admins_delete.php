<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admins.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0 && $id !== (int) $_SESSION['admin_id']) {
    $stmt = db()->prepare('SELECT username FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $username = $stmt->fetchColumn();

    $stmt = db()->prepare('DELETE FROM admins WHERE id = :id');
    $stmt->execute(['id' => $id]);
    audit_log('Admin eliminado', (string) $username);
    flash_set('Administrador eliminado.');
}

header('Location: admins.php');
exit;
