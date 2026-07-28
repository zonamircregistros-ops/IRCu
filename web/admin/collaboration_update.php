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
$status = in_array($_POST['status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['status'] : 'pendiente';

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM collaboration_applications WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $app = $stmt->fetch();

    if ($app && $app['status'] !== $status) {
        $stmt = db()->prepare('UPDATE collaboration_applications SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        if ($status === 'aprobado') {
            $body = "Hola {$app['full_name']},\n\n"
                . "¡Buenas noticias! Tu postulación para colaborar como {$app['area']} fue aprobada. "
                . "El staff se va a poner en contacto para los próximos pasos.\n\nSaludos,\n" . setting('site_name');
            send_mail($app['email'], 'Tu postulación fue aprobada — ' . setting('site_name'), $body);
        } elseif ($status === 'rechazado') {
            $body = "Hola {$app['full_name']},\n\n"
                . "Gracias por tu interés en colaborar. Por ahora no vamos a avanzar con tu postulación, "
                . "pero podés volver a postularte más adelante.\n\nSaludos,\n" . setting('site_name');
            send_mail($app['email'], 'Sobre tu postulación — ' . setting('site_name'), $body);
        }
        audit_log('Postulación de colaboración actualizada', $app['full_name'] . ' -> ' . $status);
        flash_set('Postulación actualizada.');
    }
}

header('Location: collaboration.php');
exit;
