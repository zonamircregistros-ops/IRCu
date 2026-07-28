<?php
declare(strict_types=1);
$pageTitle = 'Apelaciones G-Line';
$activeAdminNav = 'gline_appeals';
require __DIR__ . '/includes/admin_header.php';

$statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

$appeals = db()->query(
    'SELECT * FROM gline_appeals ORDER BY (status = "pendiente") DESC, created_at DESC'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Apelaciones de G-Line</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Usuario</th>
        <th>IP / rango</th>
        <th>Email</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($appeals)): ?>
        <tr><td colspan="6">Todavía no hay apelaciones.</td></tr>
      <?php endif; ?>
      <?php foreach ($appeals as $a): ?>
        <tr>
          <td><?= h($a['username']) ?></td>
          <td><code><?= h($a['ip_or_range']) ?></code></td>
          <td><?= h($a['email']) ?></td>
          <td><?= h(date('d/m/Y', strtotime($a['created_at']))) ?></td>
          <td><span class="pill <?= $a['status'] === 'aprobado' ? 'pill-on' : ($a['status'] === 'rechazado' ? 'pill-off' : '') ?>"><?= h($statusLabels[$a['status']]) ?></span></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="gline_appeal_view.php?id=<?= (int) $a['id'] ?>">Ver / responder</a>
            <form method="post" action="gline_appeals_delete.php" onsubmit="return confirm('¿Eliminar esta apelación?');">
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

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
