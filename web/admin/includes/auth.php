<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/../../includes/functions.php';

function require_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_role(): string
{
    return $_SESSION['admin_role'] ?? 'moderador';
}

function is_superadmin(): bool
{
    return admin_role() === 'superadmin';
}

/**
 * Corta el acceso a una página si el rol del admin logueado no está en la
 * lista permitida. Llamar siempre después de require_login().
 *
 * @param array<int, string> $allowedRoles
 */
function require_role(array $allowedRoles): void
{
    if (!in_array(admin_role(), $allowedRoles, true)) {
        http_response_code(403);
        die('No tenés permiso para acceder a esta sección del panel.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF inválido o expirado. Volvé atrás y probá de nuevo.');
    }
}

function flash_set(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
