<?php
declare(strict_types=1);
$pageTitle = 'Errores';
$activeAdminNav = 'errors';
$requiredRole = ['superadmin'];
require __DIR__ . '/includes/admin_header.php';

$perPage = 30;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM error_log')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare('SELECT * FROM error_log ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$errorRows = $stmt->fetchAll();

$severityPill = [
    'exception'  => 'pill-off',
    'error'      => 'pill-off',
    'warning'    => 'pill-warn',
    'notice'     => 'pill-on',
    'deprecated' => 'pill-on',
];
?>

<div class="admin-topbar">
  <h1>Log de errores</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Severidad</th>
        <th>Mensaje</th>
        <th>Archivo</th>
        <th>URL</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($errorRows)): ?>
        <tr><td colspan="5">Sin errores registrados. 🎉</td></tr>
      <?php endif; ?>
      <?php foreach ($errorRows as $err): ?>
        <tr>
          <td><span class="pill <?= h($severityPill[$err['severity']] ?? 'pill-off') ?>"><?= h($err['severity']) ?></span></td>
          <td><?= h($err['message']) ?></td>
          <td><?= h($err['file'] ? $err['file'] . ':' . $err['line'] : '—') ?></td>
          <td><?= h($err['request_uri'] ?? '—') ?></td>
          <td><?= h(date('d/m/Y H:i', strtotime($err['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/pagination.php'; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
