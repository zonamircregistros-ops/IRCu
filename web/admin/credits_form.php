<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$item = [
    'name' => '',
    'role_label' => '',
    'category' => 'colaboradores',
    'sort_order' => 0,
    'is_active' => 1,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM credits WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró ese crédito.', 'error');
        header('Location: credits.php');
        exit;
    }
    $item = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $item['name'] = trim((string) ($_POST['name'] ?? ''));
    $item['role_label'] = trim((string) ($_POST['role_label'] ?? ''));
    $item['category'] = array_key_exists($_POST['category'] ?? '', CREDIT_CATEGORIES) ? $_POST['category'] : 'colaboradores';
    $item['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $item['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($item['name'] === '') {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (empty($errors)) {
        $params = [
            'name' => $item['name'],
            'role_label' => $item['role_label'] ?: null,
            'category' => $item['category'],
            'sort_order' => $item['sort_order'],
            'is_active' => $item['is_active'],
        ];

        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE credits SET name = :name, role_label = :role_label, category = :category,
                 sort_order = :sort_order, is_active = :is_active WHERE id = :id'
            );
            $params['id'] = $id;
            $stmt->execute($params);
            flash_set('Crédito actualizado.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO credits (name, role_label, category, sort_order, is_active)
                 VALUES (:name, :role_label, :category, :sort_order, :is_active)'
            );
            $stmt->execute($params);
            flash_set('Crédito creado.');
        }
        header('Location: credits.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Editar crédito' : 'Nuevo crédito';
$activeAdminNav = 'credits';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="credits.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="credits_form.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="name">Nombre</label>
      <input type="text" id="name" name="name" value="<?= h($item['name']) ?>" required maxlength="100">
    </div>

    <div class="form-group">
      <label for="role_label">Rol / motivo (opcional)</label>
      <input type="text" id="role_label" name="role_label" value="<?= h($item['role_label']) ?>" maxlength="120">
    </div>

    <div class="form-group">
      <label for="category">Categoría</label>
      <select id="category" name="category">
        <?php foreach (CREDIT_CATEGORIES as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $item['category'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="sort_order">Orden (menor primero)</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= (int) $item['sort_order'] ?>">
    </div>

    <label class="form-check">
      <input type="checkbox" name="is_active" <?= $item['is_active'] ? 'checked' : '' ?>>
      Activo (visible en el sitio)
    </label>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear' ?></button>
      <a class="btn btn-ghost" href="credits.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
