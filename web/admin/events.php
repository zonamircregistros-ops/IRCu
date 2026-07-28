<?php
declare(strict_types=1);
$pageTitle = 'Eventos';
$activeAdminNav = 'events';
require __DIR__ . '/includes/admin_header.php';

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare('SELECT * FROM events ORDER BY starts_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$events = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Eventos</h1>
  <a class="btn btn-primary" href="events_form.php">+ Nuevo evento</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr><th>Título</th><th>Inicio</th><th>Fin</th><th>Activo</th><th></th></tr>
    </thead>
    <tbody>
      <?php if (empty($events)): ?>
        <tr><td colspan="5">Todavía no hay eventos cargados.</td></tr>
      <?php endif; ?>
      <?php foreach ($events as $e): ?>
        <tr>
          <td><?= h($e['title']) ?></td>
          <td><?= h(date('d/m/Y H:i', strtotime($e['starts_at']))) ?></td>
          <td><?= $e['ends_at'] ? h(date('d/m/Y H:i', strtotime($e['ends_at']))) : '—' ?></td>
          <td><?= $e['is_active'] ? '<span class="pill pill-on">Sí</span>' : '<span class="pill pill-off">No</span>' ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="events_form.php?id=<?= (int) $e['id'] ?>">Editar</a>
            <form method="post" action="events_delete.php" onsubmit="return confirm('¿Eliminar el evento \'<?= h($e['title']) ?>\'?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
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
