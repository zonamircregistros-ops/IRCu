<?php
declare(strict_types=1);
$pageTitle = 'Estado del servicio';
$activeAdminNav = 'service_status';
require __DIR__ . '/includes/admin_header.php';

$statusLabels = ['operativo' => 'Operativo', 'degradado' => 'Degradado', 'no_operativo' => 'No operativo'];

$rows = db()->query('SELECT * FROM service_status ORDER BY sort_order, id')->fetchAll();
?>

<div class="admin-topbar">
  <h1>Estado del servicio</h1>
  <a class="btn btn-primary" href="service_status_form.php">+ Nuevo servicio</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Servicio</th>
        <th>Estado</th>
        <th>Actualizado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="4">Todavía no hay servicios cargados.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['service_name']) ?></td>
          <td><span class="pill <?= $r['status'] === 'operativo' ? 'pill-on' : ($r['status'] === 'no_operativo' ? 'pill-off' : '') ?>"><?= h($statusLabels[$r['status']]) ?></span></td>
          <td><?= h(date('d/m/Y H:i', strtotime($r['updated_at']))) ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="service_status_form.php?id=<?= (int) $r['id'] ?>">Editar</a>
            <form method="post" action="service_status_delete.php" onsubmit="return confirm('¿Eliminar <?= h($r['service_name']) ?>?');">
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

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
