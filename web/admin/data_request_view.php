<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$typeLabels = ['exportar' => 'Exportar', 'eliminar' => 'Eliminar'];
$statusLabels = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'completado' => 'Completado'];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM data_requests WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$request = $stmt->fetch();

if (!$request) {
    flash_set('No se encontró esa solicitud.', 'error');
    header('Location: data_requests.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $status = in_array($_POST['status'] ?? '', array_keys($statusLabels), true) ? $_POST['status'] : $request['status'];
    $notes = trim((string) ($_POST['admin_notes'] ?? ''));

    $stmt = db()->prepare('UPDATE data_requests SET status = :status, admin_notes = :notes WHERE id = :id');
    $stmt->execute(['status' => $status, 'notes' => $notes ?: null, 'id' => $id]);

    audit_log('Solicitud de datos actualizada', $request['email'] . ' -> ' . $status);
    flash_set('Solicitud actualizada.');
    header('Location: data_requests.php');
    exit;
}

$pageTitle = 'Solicitud de ' . $request['email'];
$activeAdminNav = 'data_requests';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($request['email']) ?></h1>
  <a class="btn btn-ghost" href="data_requests.php">← Volver</a>
</div>

<div class="admin-card">
  <dl class="server-info">
    <div class="server-row"><dt>Tipo</dt><dd><?= h($typeLabels[$request['request_type']]) ?></dd></div>
    <div class="server-row"><dt>Fecha</dt><dd><?= h(date('d/m/Y H:i', strtotime($request['created_at']))) ?></dd></div>
  </dl>
  <?php if (!empty($request['details'])): ?>
    <p style="white-space: pre-wrap;"><?= h($request['details']) ?></p>
  <?php endif; ?>
</div>

<div class="admin-card">
  <form class="admin-form" method="post" action="data_request_view.php?id=<?= (int) $id ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="status">Estado</label>
      <select id="status" name="status">
        <?php foreach ($statusLabels as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $request['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="admin_notes">Notas internas</label>
      <textarea id="admin_notes" name="admin_notes" style="min-height: 100px;"><?= h($request['admin_notes'] ?? '') ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
