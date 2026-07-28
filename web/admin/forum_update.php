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
$status = in_array($_POST['status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['status'] : 'pendiente';

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM forum_topics WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $topic = $stmt->fetch();

    if ($topic && $topic['status'] !== $status) {
        $stmt = db()->prepare('UPDATE forum_topics SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        if ($status === 'rechazado') {
            $body = "Hola {$topic['author_nick']},\n\n"
                . "Tu tema \"{$topic['title']}\" no fue aprobado para el foro en esta oportunidad.\n"
                . "Si tenés dudas, escribinos a " . setting('staff_email') . ".\n\nSaludos,\n" . setting('site_name');
            send_mail($topic['author_email'], 'Sobre tu tema del foro — ' . setting('site_name'), $body);
        }
        audit_log('Tema de foro actualizado', $topic['title'] . ' -> ' . $status);
        flash_set('Tema actualizado.');
    }
}

header('Location: forum.php');
exit;
