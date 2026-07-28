<?php
declare(strict_types=1);
$pageTitle = 'Postulaciones IRCop';
$activeAdminNav = 'ircop_applications';
require __DIR__ . '/includes/admin_header.php';

$statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM ircop_applications')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare(
    'SELECT * FROM ircop_applications ORDER BY (status = "pendiente") DESC, created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$apps = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Postulaciones a IRCop</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nick</th>
        <th>Nombre real</th>
        <th>Edad</th>
        <th>Email</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($apps)): ?>
        <tr><td colspan="7">Todavía no hay postulaciones.</td></tr>
      <?php endif; ?>
      <?php foreach ($apps as $a): ?>
        <tr>
          <td><?= h($a['username']) ?></td>
          <td><?= h($a['real_name']) ?></td>
          <td><?= (int) $a['age'] ?></td>
          <td><?= h($a['email']) ?> <?= $a['email_verified'] ? '<span class="pill pill-on" title="Email verificado">✓</span>' : '<span class="pill pill-off" title="Email sin verificar">?</span>' ?></td>
          <td><?= h(date('d/m/Y', strtotime($a['created_at']))) ?></td>
          <td><span class="pill <?= $a['status'] === 'aprobado' ? 'pill-on' : ($a['status'] === 'rechazado' ? 'pill-off' : '') ?>"><?= h($statusLabels[$a['status']]) ?></span></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="ircop_application_view.php?id=<?= (int) $a['id'] ?>">Ver</a>
            <form method="post" action="ircop_applications_delete.php" onsubmit="return confirm('¿Eliminar esta postulación?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
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
