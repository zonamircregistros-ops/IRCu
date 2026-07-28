<?php
declare(strict_types=1);
$pageTitle = 'Redes BNC';
$activeAdminNav = 'bnc_networks';
require __DIR__ . '/includes/admin_header.php';

$networks = db()->query(
    'SELECT id, network_name, host, port, use_ssl, status, is_active, sort_order FROM bnc_networks ORDER BY sort_order, network_name'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Redes donde está Natasha</h1>
  <a class="btn btn-primary" href="bnc_networks_form.php">+ Nueva red</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Red</th>
        <th>Host</th>
        <th>Puerto</th>
        <th>SSL</th>
        <th>Estado</th>
        <th>Visible</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($networks)): ?>
        <tr><td colspan="7">Todavía no hay redes cargadas.</td></tr>
      <?php endif; ?>
      <?php foreach ($networks as $n): ?>
        <tr>
          <td><?= h($n['network_name']) ?></td>
          <td><code><?= h($n['host']) ?></code></td>
          <td><?= (int) $n['port'] ?></td>
          <td><?= $n['use_ssl'] ? 'Sí' : 'No' ?></td>
          <td><?= $n['status'] === 'operativo' ? '<span class="pill pill-on">Operativo</span>' : '<span class="pill pill-off">No operativo</span>' ?></td>
          <td><?= $n['is_active'] ? '<span class="pill pill-on">Sí</span>' : '<span class="pill pill-off">No</span>' ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="bnc_networks_form.php?id=<?= (int) $n['id'] ?>">Editar</a>
            <form method="post" action="bnc_networks_delete.php" onsubmit="return confirm('¿Eliminar la red <?= h($n['network_name']) ?>?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
