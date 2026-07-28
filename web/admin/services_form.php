<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$service = [
    'name' => '',
    'url' => '',
    'description' => '',
    'icon' => '',
    'sort_order' => 0,
    'is_active' => 1,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró ese servicio.', 'error');
        header('Location: services.php');
        exit;
    }
    $service = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $service['name'] = trim((string) ($_POST['name'] ?? ''));
    $service['url'] = trim((string) ($_POST['url'] ?? ''));
    $service['description'] = trim((string) ($_POST['description'] ?? ''));
    $service['icon'] = trim((string) ($_POST['icon'] ?? ''));
    $service['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $service['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($service['name'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if ($service['url'] === '') {
        $errors[] = 'La URL es obligatoria (puede ser relativa, ej: natasha/).';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE services SET name = :name, url = :url, description = :description, icon = :icon,
                 sort_order = :sort_order, is_active = :is_active WHERE id = :id'
            );
            $stmt->execute([
                'name' => $service['name'],
                'url' => $service['url'],
                'description' => $service['description'] ?: null,
                'icon' => $service['icon'] ?: null,
                'sort_order' => $service['sort_order'],
                'is_active' => $service['is_active'],
                'id' => $id,
            ]);
            flash_set('Servicio actualizado.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO services (name, url, description, icon, sort_order, is_active)
                 VALUES (:name, :url, :description, :icon, :sort_order, :is_active)'
            );
            $stmt->execute([
                'name' => $service['name'],
                'url' => $service['url'],
                'description' => $service['description'] ?: null,
                'icon' => $service['icon'] ?: null,
                'sort_order' => $service['sort_order'],
                'is_active' => $service['is_active'],
            ]);
            flash_set('Servicio creado.');
        }
        header('Location: services.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Editar servicio' : 'Nuevo servicio';
$activeAdminNav = 'services';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="services.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="services_form.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="name">Nombre</label>
      <input type="text" id="name" name="name" value="<?= h($service['name']) ?>" required maxlength="60">
    </div>

    <div class="form-group">
      <label for="url">URL (completa https://... o relativa como natasha/)</label>
      <input type="text" id="url" name="url" value="<?= h($service['url']) ?>" required maxlength="255">
    </div>

    <div class="form-group">
      <label for="description">Descripción breve</label>
      <textarea id="description" name="description" maxlength="160"><?= h($service['description']) ?></textarea>
    </div>

    <div class="form-group">
      <label for="icon">Ícono (un emoji)</label>
      <input type="text" id="icon" name="icon" value="<?= h($service['icon']) ?>" maxlength="8">
    </div>

    <div class="form-group">
      <label for="sort_order">Orden (menor primero)</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= (int) $service['sort_order'] ?>">
    </div>

    <label class="form-check">
      <input type="checkbox" name="is_active" <?= $service['is_active'] ? 'checked' : '' ?>>
      Activo (visible en el sitio)
    </label>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear servicio' ?></button>
      <a class="btn btn-ghost" href="services.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
