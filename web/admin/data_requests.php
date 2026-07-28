<?php
declare(strict_types=1);
$pageTitle = 'Solicitudes de datos personales';
$activeAdminNav = 'data_requests';
$requiredRole = ['superadmin'];
require __DIR__ . '/includes/admin_header.php';

$typeLabels = ['exportar' => 'Exportar', 'eliminar' => 'Eliminar'];
$statusLabels = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'completado' => 'Completado'];

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM data_requests')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare(
    'SELECT * FROM data_requests ORDER BY (status = "pendiente") DESC, created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Solicitudes de datos personales</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Email</th>
        <th>Tipo</th>
        <th>Estado</th>
        <th>Fecha</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($requests)): ?>
        <tr><td colspan="5">Todavía no hay solicitudes.</td></tr>
      <?php endif; ?>
      <?php foreach ($requests as $r): ?>
        <tr>
          <td><?= h($r['email']) ?></td>
          <td><?= h($typeLabels[$r['request_type']]) ?></td>
          <td><span class="pill <?= $r['status'] === 'completado' ? 'pill-on' : 'pill-off' ?>"><?= h($statusLabels[$r['status']]) ?></span></td>
          <td><?= h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="data_request_view.php?id=<?= (int) $r['id'] ?>">Ver</a>
            <form method="post" action="data_requests_delete.php" onsubmit="return confirm('¿Eliminar esta solicitud?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/pagination.php'; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
