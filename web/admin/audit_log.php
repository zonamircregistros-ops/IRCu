<?php
declare(strict_types=1);
$pageTitle = 'Auditoría';
$activeAdminNav = 'audit_log';
$requiredRole = ['superadmin'];
require __DIR__ . '/includes/admin_header.php';

$query = trim((string) ($_GET['q'] ?? ''));

$perPage = 30;
$page = max(1, (int) ($_GET['page'] ?? 1));

if ($query !== '') {
    $like = '%' . $query . '%';
    $countStmt = db()->prepare('SELECT COUNT(*) FROM admin_audit_log WHERE action LIKE :q OR details LIKE :q2 OR admin_user LIKE :q3');
    $countStmt->execute(['q' => $like, 'q2' => $like, 'q3' => $like]);
    $totalRows = (int) $countStmt->fetchColumn();
} else {
    $totalRows = (int) db()->query('SELECT COUNT(*) FROM admin_audit_log')->fetchColumn();
}
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

if ($query !== '') {
    $like = '%' . $query . '%';
    $stmt = db()->prepare(
        'SELECT * FROM admin_audit_log WHERE action LIKE :q OR details LIKE :q2 OR admin_user LIKE :q3
         ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue('q', $like);
    $stmt->bindValue('q2', $like);
    $stmt->bindValue('q3', $like);
} else {
    $stmt = db()->prepare('SELECT * FROM admin_audit_log ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Auditoría del panel</h1>
</div>

<div class="admin-card">
  <form class="admin-form" method="get" action="audit_log.php" style="flex-direction:row; gap:8px;">
    <input type="text" name="q" value="<?= h($query) ?>" placeholder="Buscar por admin, acción o entidad..." style="flex:1;">
    <button class="btn btn-primary btn-sm" type="submit">Buscar</button>
    <?php if ($query !== ''): ?><a class="btn btn-ghost btn-sm" href="audit_log.php">Limpiar</a><?php endif; ?>
  </form>
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
