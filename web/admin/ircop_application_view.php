<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM ircop_applications WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();

if (!$app) {
    flash_set('No se encontró esa postulación.', 'error');
    header('Location: ircop_applications.php');
    exit;
}

$statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $status = in_array($_POST['status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['status'] : $app['status'];
    $notes = trim((string) ($_POST['admin_notes'] ?? ''));

    $stmt = db()->prepare('UPDATE ircop_applications SET status = :status, admin_notes = :notes WHERE id = :id');
    $stmt->execute(['status' => $status, 'notes' => $notes ?: null, 'id' => $id]);

    audit_log('Postulación IRCop actualizada', $app['username'] . ' -> ' . $status);
    flash_set('Postulación actualizada.');
    header('Location: ircop_applications.php');
    exit;
}

$pageTitle = 'Postulación de ' . $app['username'];
$activeAdminNav = 'ircop_applications';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1>Postulación de <?= h($app['username']) ?></h1>
  <a class="btn btn-ghost" href="ircop_applications.php">← Volver</a>
</div>

<div class="admin-card">
  <dl class="server-info">
    <div class="server-row"><dt>Nombre real</dt><dd><?= h($app['real_name']) ?></dd></div>
    <div class="server-row"><dt>Edad</dt><dd><?= (int) $app['age'] ?></dd></div>
    <div class="server-row"><dt>Fecha de nacimiento</dt><dd><?= h(date('d/m/Y', strtotime($app['birthdate']))) ?></dd></div>
    <div class="server-row"><dt>Email</dt><dd><?= h($app['email']) ?></dd></div>
  </dl>

  <h3 style="font-family: var(--font-display); font-size:1rem;">Historial como usuario</h3>
  <p style="white-space: pre-wrap;"><?= h($app['user_history']) ?></p>

  <?php if (!empty($app['notable_history'])): ?>
    <h3 style="font-family: var(--font-display); font-size:1rem;">Historial relevante</h3>
    <p style="white-space: pre-wrap;"><?= h($app['notable_history']) ?></p>
  <?php endif; ?>

  <h3 style="font-family: var(--font-display); font-size:1rem;">¿Por qué quiere ser IRCop?</h3>
  <p style="white-space: pre-wrap;"><?= h($app['reason']) ?></p>
</div>

<div class="admin-card">
  <form class="admin-form" method="post" action="ircop_application_view.php?id=<?= (int) $id ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="status">Estado</label>
      <select id="status" name="status">
        <?php foreach ($statusLabels as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $app['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="admin_notes">Notas internas del staff</label>
      <textarea id="admin_notes" name="admin_notes" style="min-height: 100px;"><?= h($app['admin_notes'] ?? '') ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
