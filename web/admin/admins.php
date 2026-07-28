<?php
declare(strict_types=1);
$pageTitle = 'Administradores';
$activeAdminNav = 'admins';
$requiredRole = ['superadmin'];
require __DIR__ . '/includes/admin_header.php';

$roleLabels = ['superadmin' => 'Superadmin', 'moderador' => 'Moderador'];

$admins = db()->query('SELECT id, username, email, role, created_at FROM admins ORDER BY username')->fetchAll();
?>

<div class="admin-topbar">
  <h1>Administradores</h1>
  <a class="btn btn-primary" href="admins_form.php">+ Nuevo admin</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Usuario</th>
        <th>Email</th>
        <th>Rol</th>
        <th>Alta</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($admins as $a): ?>
        <tr>
          <td><?= h($a['username']) ?></td>
          <td><?= h($a['email'] ?? '—') ?></td>
          <td><span class="pill <?= $a['role'] === 'superadmin' ? 'pill-on' : 'pill-off' ?>"><?= h($roleLabels[$a['role']]) ?></span></td>
          <td><?= h(date('d/m/Y', strtotime($a['created_at']))) ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="admins_form.php?id=<?= (int) $a['id'] ?>">Editar</a>
            <?php if ((int) $a['id'] !== (int) $_SESSION['admin_id']): ?>
              <form method="post" action="admins_delete.php" onsubmit="return confirm('¿Eliminar el admin \'<?= h($a['username']) ?>\'?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
