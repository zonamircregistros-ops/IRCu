<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
$honeypot = trim((string) ($_POST['website'] ?? ''));

if ($honeypot === '' && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && rate_limit_check('newsletter_suscribir', 5, 3600)) {
    $confirmToken = random_token();
    $unsubscribeToken = random_token();

    try {
        $stmt = db()->prepare(
            'INSERT INTO newsletter_subscribers (email, confirm_token, unsubscribe_token) VALUES (:email, :confirm, :unsub)
             ON DUPLICATE KEY UPDATE confirm_token = VALUES(confirm_token)'
        );
        $stmt->execute(['email' => $email, 'confirm' => $confirmToken, 'unsub' => $unsubscribeToken]);

        $stmt = db()->prepare('SELECT confirmed, unsubscribe_token FROM newsletter_subscribers WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!$row['confirmed']) {
            $confirmUrl = 'https://chateanos.com/newsletter-confirmar.php?token=' . $confirmToken;
            $body = "Hola,\n\n"
                . "Pediste suscribirte al newsletter de " . setting('site_name') . ". Confirmá tu email haciendo clic acá:\n{$confirmUrl}\n\n"
                . "Si no fuiste vos, ignorá este mensaje.\n\nSaludos,\n" . setting('site_name');
            send_mail($email, 'Confirmá tu suscripción — ' . setting('site_name'), $body);
        }
    } catch (PDOException $e) {
        // No revelamos nada al usuario, seguimos igual.
    }
}

header('Location: /newsletter-gracias.php');
exit;
