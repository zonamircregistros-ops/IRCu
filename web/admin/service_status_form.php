<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$statusLabels = ['operativo' => 'Operativo', 'degradado' => 'Degradado', 'no_operativo' => 'No operativo'];

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$item = [
    'service_name' => '',
    'url' => '',
    'status' => 'operativo',
    'note' => '',
    'sort_order' => 0,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM service_status WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró ese servicio.', 'error');
        header('Location: service_status.php');
        exit;
    }
    $item = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $item['service_name'] = trim((string) ($_POST['service_name'] ?? ''));
    $item['url'] = trim((string) ($_POST['url'] ?? ''));
    $item['status'] = array_key_exists($_POST['status'] ?? '', $statusLabels) ? $_POST['status'] : 'operativo';
    $item['note'] = trim((string) ($_POST['note'] ?? ''));
    $item['sort_order'] = (int) ($_POST['sort_order'] ?? 0);

    if ($item['service_name'] === '') {
        $errors[] = 'El nombre del servicio es obligatorio.';
    }

    if (empty($errors)) {
        $params = [
            'service_name' => $item['service_name'],
            'url' => $item['url'] ?: null,
            'status' => $item['status'],
            'note' => $item['note'] ?: null,
            'sort_order' => $item['sort_order'],
        ];

        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE service_status SET service_name = :service_name, url = :url, status = :status,
                 note = :note, sort_order = :sort_order WHERE id = :id'
            );
            $params['id'] = $id;
            $stmt->execute($params);
            flash_set('Servicio actualizado.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO service_status (service_name, url, status, note, sort_order)
                 VALUES (:service_name, :url, :status, :note, :sort_order)'
            );
            $stmt->execute($params);
            flash_set('Servicio creado.');
        }
        header('Location: service_status.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Editar servicio' : 'Nuevo servicio';
$activeAdminNav = 'service_status';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="service_status.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="service_status_form.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="service_name">Nombre del servicio</label>
      <input type="text" id="service_name" name="service_name" value="<?= h($item['service_name']) ?>" required maxlength="60">
    </div>

    <div class="form-group">
      <label for="url">URL (opcional)</label>
      <input type="text" id="url" name="url" value="<?= h($item['url']) ?>" maxlength="255">
    </div>

    <div class="form-group">
      <label for="status">Estado</label>
      <select id="status" name="status">
        <?php foreach ($statusLabels as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $item['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="note">Nota (opcional, ej. detalle de la incidencia)</label>
      <textarea id="note" name="note"><?= h($item['note']) ?></textarea>
    </div>

    <div class="form-group">
      <label for="sort_order">Orden (menor primero)</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= (int) $item['sort_order'] ?>">
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear' ?></button>
      <a class="btn btn-ghost" href="service_status.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
