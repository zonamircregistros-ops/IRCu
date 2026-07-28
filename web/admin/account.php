<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

<div class="admin-card">
  <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Cambiar contraseña</h2>
  <form class="admin-form" method="post" action="account.php">
    <?= csrf_field() ?>
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
