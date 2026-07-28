<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$channel = [
    'name' => '',
    'category' => 'general',
    'description' => '',
    'is_nsfw' => 0,
    'sort_order' => 0,
    'is_active' => 1,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM channels WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró esa sala.', 'error');
        header('Location: salas.php');
        exit;
    }
    $channel = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $channel['name'] = trim((string) ($_POST['name'] ?? ''), " \t\n\r\0\x0B#");
    $channel['category'] = (string) ($_POST['category'] ?? 'general');
    $channel['description'] = trim((string) ($_POST['description'] ?? ''));
    $channel['is_nsfw'] = isset($_POST['is_nsfw']) ? 1 : 0;
    $channel['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $channel['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($channel['name'] === '') {
        $errors[] = 'El nombre de la sala es obligatorio.';
    }
    if (!array_key_exists($channel['category'], CHANNEL_CATEGORIES)) {
        $errors[] = 'Categoría inválida.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE channels SET name = :name, category = :category, description = :description,
                 is_nsfw = :is_nsfw, sort_order = :sort_order, is_active = :is_active WHERE id = :id'
            );
            $stmt->execute([
                'name' => $channel['name'],
                'category' => $channel['category'],
                'description' => $channel['description'] ?: null,
                'is_nsfw' => $channel['is_nsfw'],
                'sort_order' => $channel['sort_order'],
                'is_active' => $channel['is_active'],
                'id' => $id,
            ]);
            flash_set('Sala actualizada.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO channels (name, category, description, is_nsfw, sort_order, is_active)
                 VALUES (:name, :category, :description, :is_nsfw, :sort_order, :is_active)'
            );
            $stmt->execute([
                'name' => $channel['name'],
                'category' => $channel['category'],
                'description' => $channel['description'] ?: null,
                'is_nsfw' => $channel['is_nsfw'],
                'sort_order' => $channel['sort_order'],
                'is_active' => $channel['is_active'],
            ]);
            flash_set('Sala creada.');
        }
        header('Location: salas.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Editar sala' : 'Nueva sala';
$activeAdminNav = 'salas';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="salas.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="salas_form.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="name">Nombre del canal (sin #)</label>
      <input type="text" id="name" name="name" value="<?= h($channel['name']) ?>" required maxlength="60">
    </div>

    <div class="form-group">
      <label for="category">Categoría</label>
      <select id="category" name="category">
        <?php foreach (CHANNEL_CATEGORIES as $slug => $label): ?>
          <option value="<?= h($slug) ?>" <?= $channel['category'] === $slug ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="description">Descripción breve</label>
      <textarea id="description" name="description" maxlength="160"><?= h($channel['description']) ?></textarea>
    </div>

    <div class="form-group">
      <label for="sort_order">Orden (menor primero)</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= (int) $channel['sort_order'] ?>">
    </div>

    <label class="form-check">
      <input type="checkbox" name="is_nsfw" <?= $channel['is_nsfw'] ? 'checked' : '' ?>>
      Contenido para mayores de 18 (se muestra con badge y aviso)
    </label>

    <label class="form-check">
      <input type="checkbox" name="is_active" <?= $channel['is_active'] ? 'checked' : '' ?>>
      Sala activa (visible en el sitio)
    </label>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear sala' ?></button>
      <a class="btn btn-ghost" href="salas.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
