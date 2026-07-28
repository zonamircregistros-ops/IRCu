<?php
declare(strict_types=1);
$pageTitle = 'Staff';
$activeAdminNav = 'staff';
require __DIR__ . '/includes/admin_header.php';

$roleLabels = [
    'administrador' => 'Administrador',
    'ircop'         => 'IRCop',
    'soporte'       => 'Soporte',
];

$members = db()->query(
    'SELECT id, nick, role, bio, is_active, sort_order FROM staff ORDER BY role, sort_order, nick'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Staff</h1>
  <a class="btn btn-primary" href="staff_form.php">+ Nuevo miembro</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nick</th>
        <th>Rol</th>
        <th>Estado</th>
        <th>Orden</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($members)): ?>
        <tr><td colspan="5">Todavía no hay miembros del staff cargados.</td></tr>
      <?php endif; ?>
      <?php foreach ($members as $m): ?>
        <tr>
          <td><?= h($m['nick']) ?></td>
          <td><?= h($roleLabels[$m['role']] ?? $m['role']) ?></td>
          <td><?= $m['is_active'] ? '<span class="pill pill-on">Activo</span>' : '<span class="pill pill-off">Inactivo</span>' ?></td>
          <td><?= (int) $m['sort_order'] ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="staff_form.php?id=<?= (int) $m['id'] ?>">Editar</a>
            <form method="post" action="staff_delete.php" onsubmit="return confirm('¿Eliminar a <?= h($m['nick']) ?> del staff?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
