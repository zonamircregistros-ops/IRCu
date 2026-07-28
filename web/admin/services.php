<?php
declare(strict_types=1);
$pageTitle = 'Servicios';
$activeAdminNav = 'services';
require __DIR__ . '/includes/admin_header.php';

$services = db()->query(
    'SELECT id, name, url, icon, is_active, sort_order FROM services ORDER BY sort_order, name'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Servicios</h1>
  <a class="btn btn-primary" href="services_form.php">+ Nuevo servicio</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>URL</th>
        <th>Estado</th>
        <th>Orden</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($services)): ?>
        <tr><td colspan="5">Todavía no hay servicios cargados.</td></tr>
      <?php endif; ?>
      <?php foreach ($services as $s): ?>
        <tr>
          <td><?= $s['icon'] ?: '' ?> <?= h($s['name']) ?></td>
          <td><code><?= h($s['url']) ?></code></td>
          <td><?= $s['is_active'] ? '<span class="pill pill-on">Activo</span>' : '<span class="pill pill-off">Inactivo</span>' ?></td>
          <td><?= (int) $s['sort_order'] ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="services_form.php?id=<?= (int) $s['id'] ?>">Editar</a>
            <form method="post" action="services_delete.php" onsubmit="return confirm('¿Eliminar el servicio <?= h($s['name']) ?>?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
