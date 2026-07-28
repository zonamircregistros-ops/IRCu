<?php
declare(strict_types=1);

/**
 * Envío de correo "best effort" vía la función mail() de PHP, que en el
 * servidor de producción se relaya por Postfix en localhost. Si el envío
 * falla (servidor sin MTA configurado, etc.) no interrumpe el flujo que
 * la llamó: solo queda registrado en el log de errores de PHP.
 */
function send_mail(string $to, string $subject, string $body, ?string $fromEmail = null, ?string $fromName = null): bool
{
    $fromEmail = $fromEmail ?: setting('staff_email');
    $fromName = $fromName ?: setting('site_name');

    $headers = [
        'From: ' . mail_encode_header($fromName) . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: ' . setting('site_name'),
    ];

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    try {
        $sent = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    } catch (\Throwable $e) {
        $sent = false;
    }

    if (!$sent) {
        error_log(sprintf('[mailer] No se pudo enviar el correo a %s ("%s")', $to, $subject));
    }

    return $sent;
}

function mail_encode_header(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function random_password(int $length = 14): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $password = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $max)];
    }
    return $password;
}

function random_token(int $bytes = 24): string
{
    return bin2hex(random_bytes($bytes));
}
