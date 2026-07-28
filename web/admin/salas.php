<?php
declare(strict_types=1);
$pageTitle = 'Salas';
$activeAdminNav = 'salas';
require __DIR__ . '/includes/admin_header.php';

$channels = db()->query(
    'SELECT id, name, category, description, is_nsfw, is_active, sort_order
     FROM channels
     ORDER BY category, sort_order, name'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Salas</h1>
  <a class="btn btn-primary" href="salas_form.php">+ Nueva sala</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>NSFW</th>
        <th>Estado</th>
        <th>Orden</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($channels)): ?>
        <tr><td colspan="6">Todavía no hay salas cargadas.</td></tr>
      <?php endif; ?>
      <?php foreach ($channels as $c): ?>
        <tr>
          <td>#<?= h($c['name']) ?></td>
          <td><?= h(CHANNEL_CATEGORIES[$c['category']] ?? $c['category']) ?></td>
          <td><?= $c['is_nsfw'] ? '<span class="pill pill-nsfw">18+</span>' : '—' ?></td>
          <td><?= $c['is_active'] ? '<span class="pill pill-on">Activa</span>' : '<span class="pill pill-off">Inactiva</span>' ?></td>
          <td><?= (int) $c['sort_order'] ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="salas_form.php?id=<?= (int) $c['id'] ?>">Editar</a>
            <form method="post" action="salas_delete.php" onsubmit="return confirm('¿Eliminar la sala #<?= h($c['name']) ?>? Esta acción no se puede deshacer.');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
