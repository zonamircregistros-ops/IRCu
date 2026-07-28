<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profiles.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$status = in_array($_POST['status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['status'] : 'pendiente';

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM user_profiles WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $profile = $stmt->fetch();

    if ($profile && $profile['status'] !== $status) {
        $stmt = db()->prepare('UPDATE user_profiles SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
        audit_log('Perfil actualizado', $profile['nick'] . ' -> ' . $status);
        flash_set('Perfil actualizado.');
    }
}

header('Location: profiles.php');
exit;
