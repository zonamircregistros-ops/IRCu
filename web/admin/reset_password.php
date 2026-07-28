<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$errors = [];
$done = false;

$stmt = db()->prepare('SELECT id, reset_token_expires FROM admins WHERE reset_token = :token LIMIT 1');
$stmt->execute(['token' => $token]);
$admin = $stmt->fetch();

$validToken = $admin && strtotime($admin['reset_token_expires']) > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    verify_csrf();

    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($new) < 8) {
        $errors[] = 'La nueva contraseña tiene que tener al menos 8 caracteres.';
    } elseif ($new !== $confirm) {
        $errors[] = 'La confirmación no coincide con la nueva contraseña.';
    }

    if (empty($errors)) {
        $stmt = db()->prepare(
            'UPDATE admins SET password_hash = :hash, reset_token = NULL, reset_token_expires = NULL WHERE id = :id'
        );
        $stmt->execute([
            'hash' => password_hash($new, PASSWORD_DEFAULT),
            'id' => $admin['id'],
        ]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restablecer contraseña — Panel · <?= h(setting('site_name')) ?></title>
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
      <?php if ($done): ?>
        <div class="flash flash-success">Contraseña actualizada. Ya podés iniciar sesión.</div>
        <a class="btn btn-ghost btn-block" href="login.php">Ir al login</a>
      <?php elseif (!$validToken): ?>
        <div class="flash flash-error">Ese link no es válido o ya venció. Pedí uno nuevo.</div>
        <a class="btn btn-ghost btn-block" href="forgot_password.php">Pedir nuevo link</a>
      <?php else: ?>
        <?php if (!empty($errors)): ?>
          <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
        <?php endif; ?>
        <form class="admin-form" method="post" action="reset_password.php">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= h($token) ?>">
          <div class="form-group">
            <label for="new_password">Nueva contraseña</label>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password" required minlength="8" autofocus>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirmar nueva contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required minlength="8">
          </div>
          <button class="btn btn-primary btn-lg btn-block" type="submit">Restablecer contraseña</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
