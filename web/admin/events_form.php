<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$event = [
    'title' => '',
    'description' => '',
    'starts_at' => date('Y-m-d\TH:i'),
    'ends_at' => '',
    'is_active' => 1,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró ese evento.', 'error');
        header('Location: events.php');
        exit;
    }
    $event = $found;
    $event['starts_at'] = date('Y-m-d\TH:i', strtotime($event['starts_at']));
    $event['ends_at'] = $event['ends_at'] ? date('Y-m-d\TH:i', strtotime($event['ends_at'])) : '';
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $event['title'] = trim((string) ($_POST['title'] ?? ''));
    $event['description'] = trim((string) ($_POST['description'] ?? ''));
    $event['starts_at'] = trim((string) ($_POST['starts_at'] ?? ''));
    $event['ends_at'] = trim((string) ($_POST['ends_at'] ?? ''));
    $event['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($event['title'] === '') {
        $errors[] = 'El título es obligatorio.';
    }
    if ($event['starts_at'] === '' || strtotime($event['starts_at']) === false) {
        $errors[] = 'La fecha de inicio no es válida.';
    }

    if (empty($errors)) {
        $params = [
            'title' => $event['title'],
            'description' => $event['description'] ?: null,
            'starts_at' => date('Y-m-d H:i:s', strtotime($event['starts_at'])),
            'ends_at' => $event['ends_at'] !== '' ? date('Y-m-d H:i:s', strtotime($event['ends_at'])) : null,
            'is_active' => $event['is_active'],
        ];

        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE events SET title = :title, description = :description, starts_at = :starts_at,
                 ends_at = :ends_at, is_active = :is_active WHERE id = :id'
            );
            $params['id'] = $id;
            $stmt->execute($params);
            audit_log('Evento actualizado', $event['title']);
            flash_set('Evento actualizado.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO events (title, description, starts_at, ends_at, is_active)
                 VALUES (:title, :description, :starts_at, :ends_at, :is_active)'
            );
            $stmt->execute($params);
            audit_log('Evento creado', $event['title']);
            flash_set('Evento creado.');
        }
        header('Location: events.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Editar evento' : 'Nuevo evento';
$activeAdminNav = 'events';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="events.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="events_form.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="title">Título</label>
      <input type="text" id="title" name="title" value="<?= h($event['title']) ?>" required maxlength="160">
    </div>

    <div class="form-group">
      <label for="description">Descripción</label>
      <textarea id="description" name="description"><?= h($event['description']) ?></textarea>
    </div>

    <div class="form-group">
      <label for="starts_at">Fecha y hora de inicio</label>
      <input type="datetime-local" id="starts_at" name="starts_at" value="<?= h($event['starts_at']) ?>" required>
    </div>

    <div class="form-group">
      <label for="ends_at">Fecha y hora de fin (opcional)</label>
      <input type="datetime-local" id="ends_at" name="ends_at" value="<?= h($event['ends_at']) ?>">
    </div>

    <label class="form-check">
      <input type="checkbox" name="is_active" <?= $event['is_active'] ? 'checked' : '' ?>>
      Activo (visible en el sitio)
    </label>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear evento' ?></button>
      <a class="btn btn-ghost" href="events.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
