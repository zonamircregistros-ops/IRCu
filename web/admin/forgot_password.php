<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));

    if (!rate_limit_check('admin_forgot_password', 5, 3600)) {
        // Igual mostramos el mensaje genérico: no revelamos si hubo o no rate limit.
        $sent = true;
    } else {
        $stmt = db()->prepare('SELECT id, email FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && !empty($admin['email'])) {
            $token = random_token();
            $stmt = db()->prepare('UPDATE admins SET reset_token = :token, reset_token_expires = :expires WHERE id = :id');
            $stmt->execute([
                'token' => $token,
                'expires' => date('Y-m-d H:i:s', time() + 3600),
                'id' => $admin['id'],
            ]);

            $resetUrl = 'https://chateanos.com/admin/reset_password.php?token=' . $token;
            $body = "Hola,\n\n"
                . "Pediste restablecer tu contraseña del panel de " . setting('site_name') . ".\n\n"
                . "Este link vale por 1 hora:\n{$resetUrl}\n\n"
                . "Si no fuiste vos, ignorá este mensaje.\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($admin['email'], 'Restablecer contraseña del panel — ' . setting('site_name'), $body);
        }
        // Mensaje siempre genérico, exista o no el usuario / tenga o no email cargado.
        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña — Panel · <?= h(setting('site_name')) ?></title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-body">
  <div class="admin-login-wrap">
    <div class="admin-login-card">
      <a href="../index.php" class="brand">
        <span class="brand-mark">#</span>
        <span class="brand-name"><?= h(setting('site_name')) ?></span>
      </a>
      <?php if ($sent): ?>
        <div class="flash flash-success">Si el usuario existe y tiene un email cargado, le mandamos un link para restablecer la contraseña.</div>
        <a class="btn btn-ghost btn-block" href="login.php">Volver al login</a>
      <?php else: ?>
        <p style="color: var(--text-dim); font-size:0.9rem;">Ingresá tu usuario del panel. Si tenés un email de recuperación cargado, te mandamos un link.</p>
        <form class="admin-form" method="post" action="forgot_password.php">
          <?= csrf_field() ?>
          <div class="form-group">
            <label for="username">Usuario</label>
            <input type="text" id="username" name="username" required autofocus>
          </div>
          <button class="btn btn-primary btn-lg btn-block" type="submit">Enviar link de recuperación</button>
        </form>
        <a class="back-link" href="login.php">← Volver al login</a>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
