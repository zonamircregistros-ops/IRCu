<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$roleLabels = [
    'administrador' => 'Administrador',
    'ircop'         => 'IRCop',
    'soporte'       => 'Soporte',
];

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$member = [
    'nick' => '',
    'role' => 'soporte',
    'bio' => '',
    'avatar_url' => '',
    'sort_order' => 0,
    'is_active' => 1,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM staff WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró ese miembro del staff.', 'error');
        header('Location: staff.php');
        exit;
    }
    $member = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $member['nick'] = trim((string) ($_POST['nick'] ?? ''));
    $member['role'] = (string) ($_POST['role'] ?? 'soporte');
    $member['bio'] = trim((string) ($_POST['bio'] ?? ''));
    $member['avatar_url'] = trim((string) ($_POST['avatar_url'] ?? ''));
    $member['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $member['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($member['nick'] === '') {
        $errors[] = 'El nick es obligatorio.';
    }
    if (!array_key_exists($member['role'], $roleLabels)) {
        $errors[] = 'Rol inválido.';
    }
    if ($member['avatar_url'] !== '' && !filter_var($member['avatar_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'La URL del avatar no es válida.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE staff SET nick = :nick, role = :role, bio = :bio, avatar_url = :avatar_url,
                 sort_order = :sort_order, is_active = :is_active WHERE id = :id'
            );
            $stmt->execute([
                'nick' => $member['nick'],
                'role' => $member['role'],
                'bio' => $member['bio'] ?: null,
                'avatar_url' => $member['avatar_url'] ?: null,
                'sort_order' => $member['sort_order'],
                'is_active' => $member['is_active'],
                'id' => $id,
            ]);
            flash_set('Miembro del staff actualizado.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO staff (nick, role, bio, avatar_url, sort_order, is_active)
                 VALUES (:nick, :role, :bio, :avatar_url, :sort_order, :is_active)'
            );
            $stmt->execute([
                'nick' => $member['nick'],
                'role' => $member['role'],
                'bio' => $member['bio'] ?: null,
                'avatar_url' => $member['avatar_url'] ?: null,
                'sort_order' => $member['sort_order'],
                'is_active' => $member['is_active'],
            ]);
            flash_set('Miembro del staff creado.');
        }
        header('Location: staff.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Editar staff' : 'Nuevo staff';
$activeAdminNav = 'staff';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="staff.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="staff_form.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="nick">Nick</label>
      <input type="text" id="nick" name="nick" value="<?= h($member['nick']) ?>" required maxlength="40">
    </div>

    <div class="form-group">
      <label for="role">Rol</label>
      <select id="role" name="role">
        <?php foreach ($roleLabels as $slug => $label): ?>
          <option value="<?= h($slug) ?>" <?= $member['role'] === $slug ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="bio">Bio corta</label>
      <textarea id="bio" name="bio" maxlength="200"><?= h($member['bio']) ?></textarea>
    </div>

    <div class="form-group">
      <label for="avatar_url">URL de avatar (opcional)</label>
      <input type="url" id="avatar_url" name="avatar_url" value="<?= h($member['avatar_url']) ?>" placeholder="https://...">
    </div>

    <div class="form-group">
      <label for="sort_order">Orden (menor primero)</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= (int) $member['sort_order'] ?>">
    </div>

    <label class="form-check">
      <input type="checkbox" name="is_active" <?= $member['is_active'] ? 'checked' : '' ?>>
      Activo (visible en el sitio)
    </label>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear' ?></button>
      <a class="btn btn-ghost" href="staff.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
