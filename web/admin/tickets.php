<?php
declare(strict_types=1);
$pageTitle = 'Tickets';
$activeAdminNav = 'tickets';
require __DIR__ . '/includes/admin_header.php';

$statusLabels = ['abierto' => 'Abierto', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'cerrado' => 'Cerrado'];
$categoryLabels = ['soporte' => 'Soporte', 'reclamo' => 'Reclamo', 'otro' => 'Otro'];

$tickets = db()->query(
    'SELECT * FROM tickets ORDER BY (status = "abierto") DESC, updated_at DESC'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Tickets</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Asunto</th>
        <th>Categoría</th>
        <th>De</th>
        <th>Última actividad</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($tickets)): ?>
        <tr><td colspan="6">Todavía no hay tickets.</td></tr>
      <?php endif; ?>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td><?= h($t['subject']) ?></td>
          <td><?= h($categoryLabels[$t['category']]) ?></td>
          <td><?= h($t['requester_name']) ?> <span style="color:var(--text-dimmer);">(<?= h($t['requester_email']) ?>)</span></td>
          <td><?= h(date('d/m/Y H:i', strtotime($t['updated_at']))) ?></td>
          <td><span class="pill <?= $t['status'] === 'abierto' ? 'pill-on' : ($t['status'] === 'rechazado' ? 'pill-off' : '') ?>"><?= h($statusLabels[$t['status']]) ?></span></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="ticket_view.php?id=<?= (int) $t['id'] ?>">Abrir</a>
            <form method="post" action="tickets_delete.php" onsubmit="return confirm('¿Eliminar este ticket y toda su conversación?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
