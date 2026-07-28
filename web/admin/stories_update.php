<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stories.php');
    exit;
}

verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$status = in_array($_POST['status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['status'] : 'pendiente';

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM community_stories WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $story = $stmt->fetch();

    if ($story && $story['status'] !== $status) {
        $stmt = db()->prepare('UPDATE community_stories SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        if ($status === 'aprobado') {
            $body = "Hola {$story['author_nick']},\n\n"
                . "Tu historia \"{$story['title']}\" fue publicada:\n"
                . "https://chateanos.com/historia-ver.php?id={$id}\n\nSaludos,\n" . setting('site_name');
            send_mail($story['author_email'], 'Tu historia fue publicada — ' . setting('site_name'), $body);
        } elseif ($status === 'rechazado') {
            $body = "Hola {$story['author_nick']},\n\n"
                . "Tu historia \"{$story['title']}\" no fue aprobada en esta oportunidad.\n"
                . "Si tenés dudas, escribinos a " . setting('staff_email') . ".\n\nSaludos,\n" . setting('site_name');
            send_mail($story['author_email'], 'Sobre tu historia — ' . setting('site_name'), $body);
        }
        audit_log('Historia actualizada', $story['title'] . ' -> ' . $status);
        flash_set('Historia actualizada.');
    }
}

header('Location: stories.php');
exit;
