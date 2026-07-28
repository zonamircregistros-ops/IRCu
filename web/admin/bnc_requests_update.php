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
    $stmt = db()->prepare('SELECT * FROM bnc_requests WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $request = $stmt->fetch();

    if ($request && $request['status'] !== $status) {
        $password = $request['bnc_password'];

        if ($status === 'aprobado' && $password === null) {
            $password = random_password();
            $stmt = db()->prepare('UPDATE bnc_requests SET status = :status, bnc_password = :password WHERE id = :id');
            $stmt->execute(['status' => $status, 'password' => $password, 'id' => $id]);
        } else {
            $stmt = db()->prepare('UPDATE bnc_requests SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
        }

        if ($status === 'aprobado') {
            $body = "Hola {$request['nick']},\n\n"
                . "Tu solicitud de Natasha Bouncer (plan " . ($request['plan'] === 'premium' ? 'Premium' : 'Free') . ") fue aprobada.\n"
                . "Estos son tus datos de conexión:\n\n"
                . "Host: " . setting('bnc_connect_host') . "\n"
                . "Puerto: " . setting('bnc_connect_port') . " (sin SSL)\n"
                . "Usuario: {$request['nick']}\n"
                . "Contraseña: {$password}\n\n"
                . "Si no sabés cómo conectarte, mirá el tutorial paso a paso para mIRC, HexChat, Irssi y más en:\n"
                . "https://chateanos.com/natasha/tutorial.php\n\n"
                . "Cualquier duda, escribinos a " . setting('staff_email') . ".\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($request['contact'], 'Tu solicitud de Natasha Bouncer fue aprobada', $body, setting('natasha_mail_from'), 'Natasha Bouncer');
        } elseif ($status === 'rechazado') {
            $body = "Hola {$request['nick']},\n\n"
                . "Tu solicitud de Natasha Bouncer no fue aprobada en esta oportunidad.\n"
                . "Si tenés dudas o querés volver a intentarlo, escribinos a " . setting('staff_email') . ".\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($request['contact'], 'Tu solicitud de Natasha Bouncer fue rechazada', $body, setting('natasha_mail_from'), 'Natasha Bouncer');
        }

        audit_log('Solicitud BNC actualizada', $request['nick'] . ' -> ' . $status);
        flash_set('Solicitud actualizada' . ($status === 'aprobado' ? ' y contraseña generada.' : '.'));
    } else {
        flash_set('Solicitud actualizada.');
    }
}

header('Location: bnc_requests.php');
exit;
