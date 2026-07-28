<?php
declare(strict_types=1);
$pageTitle = 'Créditos';
$activeAdminNav = 'credits';
require __DIR__ . '/includes/admin_header.php';

$rows = db()->query(
    'SELECT id, name, role_label, category, is_active, sort_order FROM credits ORDER BY category, sort_order, name'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Créditos</h1>
  <a class="btn btn-primary" href="credits_form.php">+ Nuevo crédito</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Rol</th>
        <th>Categoría</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5">Todavía no hay créditos cargados.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['name']) ?></td>
          <td><?= h($r['role_label'] ?: '—') ?></td>
          <td><?= h(CREDIT_CATEGORIES[$r['category']] ?? $r['category']) ?></td>
          <td><?= $r['is_active'] ? '<span class="pill pill-on">Activo</span>' : '<span class="pill pill-off">Inactivo</span>' ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="credits_form.php?id=<?= (int) $r['id'] ?>">Editar</a>
            <form method="post" action="credits_delete.php" onsubmit="return confirm('¿Eliminar a <?= h($r['name']) ?>?');">
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
