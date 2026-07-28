<?php
declare(strict_types=1);
$pageTitle = 'Auditoría';
$activeAdminNav = 'audit_log';
require __DIR__ . '/includes/admin_header.php';

$perPage = 30;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM admin_audit_log')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare('SELECT * FROM admin_audit_log ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Auditoría del panel</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Admin</th>
        <th>Acción</th>
        <th>Detalle</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="4">Todavía no hay acciones registradas.</td></tr>
      <?php endif; ?>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= h($log['admin_user']) ?></td>
          <td><?= h($log['action']) ?></td>
          <td><?= h($log['details'] ?? '') ?></td>
          <td><?= h(date('d/m/Y H:i', strtotime($log['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/pagination.php'; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
