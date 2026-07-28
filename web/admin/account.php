<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$errors = [];
$emailErrors = [];

$stmt = db()->prepare('SELECT email FROM admins WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['admin_id']]);
$currentEmail = (string) ($stmt->fetchColumn() ?: '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'email') {
    verify_csrf();

    $newEmail = trim((string) ($_POST['email'] ?? ''));
    if ($newEmail !== '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $emailErrors[] = 'Ingresá un email válido.';
    } else {
        $stmt = db()->prepare('UPDATE admins SET email = :email WHERE id = :id');
        $stmt->execute(['email' => $newEmail ?: null, 'id' => $_SESSION['admin_id']]);
        audit_log('Email de recuperación actualizado');
        flash_set('Email actualizado.');
        header('Location: account.php');
        exit;
    }
    $currentEmail = $newEmail;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    verify_csrf();

    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        $errors[] = 'La contraseña actual no es correcta.';
    } elseif (strlen($new) < 8) {
        $errors[] = 'La nueva contraseña tiene que tener al menos 8 caracteres.';
    } elseif ($new !== $confirm) {
        $errors[] = 'La confirmación no coincide con la nueva contraseña.';
    }

    if (empty($errors)) {
        $stmt = db()->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $stmt->execute([
            'hash' => password_hash($new, PASSWORD_DEFAULT),
            'id' => $_SESSION['admin_id'],
        ]);
        audit_log('Contraseña propia actualizada');
        flash_set('Contraseña actualizada.');
        header('Location: account.php');
        exit;
    }
}

$pageTitle = 'Mi cuenta';
$activeAdminNav = 'account';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1>Mi cuenta</h1>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>
<?php if (!empty($emailErrors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $emailErrors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Email de recuperación</h2>
  <p style="color: var(--text-dim); font-size:0.88rem;">Se usa solo para poder mandarte un link si olvidás tu contraseña.</p>
  <form class="admin-form" method="post" action="account.php">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="email">
    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= h($currentEmail) ?>" maxlength="160">
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar email</button>
    </div>
  </form>
</div>

<div class="admin-card">
  <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Cambiar contraseña</h2>
  <form class="admin-form" method="post" action="account.php">
    <?= csrf_field() ?>
    <input type="hidden" name="form" value="password">
    <div class="form-group">
      <label for="current_password">Contraseña actual</label>
      <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
    </div>
    <div class="form-group">
      <label for="new_password">Nueva contraseña</label>
      <input type="password" id="new_password" name="new_password" autocomplete="new-password" required minlength="8">
    </div>
    <div class="form-group">
      <label for="confirm_password">Confirmar nueva contraseña</label>
      <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required minlength="8">
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
