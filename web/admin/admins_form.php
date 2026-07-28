<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['superadmin']);

$roleLabels = ['superadmin' => 'Superadmin', 'moderador' => 'Moderador'];

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$admin = [
    'username' => '',
    'email' => '',
    'role' => 'moderador',
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT id, username, email, role FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró ese administrador.', 'error');
        header('Location: admins.php');
        exit;
    }
    $admin = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $admin['username'] = trim((string) ($_POST['username'] ?? ''));
    $admin['email'] = trim((string) ($_POST['email'] ?? ''));
    $admin['role'] = array_key_exists($_POST['role'] ?? '', $roleLabels) ? $_POST['role'] : 'moderador';
    $password = (string) ($_POST['password'] ?? '');

    if ($admin['username'] === '') {
        $errors[] = 'El usuario es obligatorio.';
    }
    if ($admin['email'] !== '' && !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido.';
    }
    if (!$isEdit && strlen($password) < 8) {
        $errors[] = 'La contraseña tiene que tener al menos 8 caracteres.';
    }
    if ($isEdit && $password !== '' && strlen($password) < 8) {
        $errors[] = 'La nueva contraseña tiene que tener al menos 8 caracteres.';
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                if ($password !== '') {
                    $stmt = db()->prepare(
                        'UPDATE admins SET username = :username, email = :email, role = :role, password_hash = :hash WHERE id = :id'
                    );
                    $stmt->execute([
                        'username' => $admin['username'],
                        'email' => $admin['email'] ?: null,
                        'role' => $admin['role'],
                        'hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => $id,
                    ]);
                } else {
                    $stmt = db()->prepare(
                        'UPDATE admins SET username = :username, email = :email, role = :role WHERE id = :id'
                    );
                    $stmt->execute([
                        'username' => $admin['username'],
                        'email' => $admin['email'] ?: null,
                        'role' => $admin['role'],
                        'id' => $id,
                    ]);
                }
                audit_log('Admin actualizado', $admin['username']);
                flash_set('Administrador actualizado.');
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO admins (username, email, role, password_hash) VALUES (:username, :email, :role, :hash)'
                );
                $stmt->execute([
                    'username' => $admin['username'],
                    'email' => $admin['email'] ?: null,
                    'role' => $admin['role'],
                    'hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                audit_log('Admin creado', $admin['username']);
                flash_set('Administrador creado.');
            }
            header('Location: admins.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Ya existe un administrador con ese usuario.';
        }
    }
}

$pageTitle = $isEdit ? 'Editar administrador' : 'Nuevo administrador';
$activeAdminNav = 'admins';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="admins.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="admins_form.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="username">Usuario</label>
      <input type="text" id="username" name="username" value="<?= h($admin['username']) ?>" required maxlength="50">
    </div>

    <div class="form-group">
      <label for="email">Email de recuperación (opcional)</label>
      <input type="email" id="email" name="email" value="<?= h($admin['email'] ?? '') ?>" maxlength="160">
    </div>

    <div class="form-group">
      <label for="role">Rol</label>
      <select id="role" name="role">
        <?php foreach ($roleLabels as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $admin['role'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="password"><?= $isEdit ? 'Nueva contraseña (dejar vacío para no cambiarla)' : 'Contraseña' ?></label>
      <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" <?= $isEdit ? '' : 'required' ?>>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear' ?></button>
      <a class="btn btn-ghost" href="admins.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
