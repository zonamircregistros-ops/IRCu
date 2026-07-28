<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM gline_appeals WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$appeal = $stmt->fetch();

if (!$appeal) {
    flash_set('No se encontró esa apelación.', 'error');
    header('Location: gline_appeals.php');
    exit;
}

$statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $status = in_array($_POST['status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['status'] : $appeal['status'];
    $response = trim((string) ($_POST['admin_response'] ?? ''));

    $stmt = db()->prepare('UPDATE gline_appeals SET status = :status, admin_response = :response WHERE id = :id');
    $stmt->execute(['status' => $status, 'response' => $response ?: null, 'id' => $id]);

    if ($status !== 'pendiente' && $response !== '' && ($status !== $appeal['status'] || $response !== $appeal['admin_response'])) {
        $subjectLine = $status === 'aprobado' ? 'Tu apelación fue aprobada' : 'Tu apelación fue rechazada';
        $body = "Hola {$appeal['username']},\n\n"
            . "Respuesta del staff sobre tu apelación de {$appeal['ip_or_range']}:\n\n"
            . "{$response}\n\n"
            . "Saludos,\n" . setting('site_name');
        send_mail($appeal['email'], $subjectLine . ' — ' . setting('site_name'), $body);
    }

    audit_log('Apelación G-Line actualizada', $appeal['username'] . ' -> ' . $status);
    flash_set('Apelación actualizada.');
    header('Location: gline_appeals.php');
    exit;
}

$pageTitle = 'Apelación de ' . $appeal['username'];
$activeAdminNav = 'gline_appeals';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1>Apelación de <?= h($appeal['username']) ?></h1>
  <a class="btn btn-ghost" href="gline_appeals.php">← Volver</a>
</div>

<div class="admin-card">
  <dl class="server-info">
    <div class="server-row"><dt>IP / rango</dt><dd><code><?= h($appeal['ip_or_range']) ?></code></dd></div>
    <div class="server-row"><dt>Email</dt><dd><?= h($appeal['email']) ?></dd></div>
    <div class="server-row"><dt>Fecha</dt><dd><?= h(date('d/m/Y H:i', strtotime($appeal['created_at']))) ?></dd></div>
  </dl>
  <p style="white-space: pre-wrap;"><?= h($appeal['reason']) ?></p>
</div>

<div class="admin-card">
  <form class="admin-form" method="post" action="gline_appeal_view.php?id=<?= (int) $id ?>">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="status">Estado</label>
      <select id="status" name="status">
        <?php foreach ($statusLabels as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $appeal['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="admin_response">Respuesta (se envía por email si el estado no es "Pendiente")</label>
      <textarea id="admin_response" name="admin_response" style="min-height: 140px;"><?= h($appeal['admin_response'] ?? '') ?></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar y notificar</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
