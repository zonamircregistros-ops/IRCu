<?php
declare(strict_types=1);
$pageTitle = 'Foro';
$activeAdminNav = 'forum';
require __DIR__ . '/includes/admin_header.php';

$statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM forum_topics')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare(
    'SELECT ft.*, (SELECT COUNT(*) FROM forum_replies fr WHERE fr.topic_id = ft.id AND fr.status = "pendiente") AS pending_replies
     FROM forum_topics ft ORDER BY (ft.status = "pendiente") DESC, ft.created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$topics = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Foro</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Tema</th>
        <th>Autor</th>
        <th>Respuestas pendientes</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($topics)): ?>
        <tr><td colspan="5">Todavía no hay temas.</td></tr>
      <?php endif; ?>
      <?php foreach ($topics as $t): ?>
        <tr>
          <td><a href="forum_topic_view.php?id=<?= (int) $t['id'] ?>"><?= h($t['title']) ?></a></td>
          <td><?= h($t['author_nick']) ?> · <?= h($t['author_email']) ?> <?= $t['email_verified'] ? '<span class="pill pill-on" title="Email verificado">✓</span>' : '<span class="pill pill-off" title="Email sin verificar">?</span>' ?></td>
          <td><?= (int) $t['pending_replies'] ?></td>
          <td>
            <form method="post" action="forum_update.php" style="display:flex; gap:6px; align-items:center;">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
              <select name="status" onchange="this.form.submit()">
                <?php foreach ($statusLabels as $value => $label): ?>
                  <option value="<?= h($value) ?>" <?= $t['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
              <noscript><button class="btn btn-ghost btn-sm" type="submit">Guardar</button></noscript>
            </form>
          </td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="forum_topic_view.php?id=<?= (int) $t['id'] ?>">Ver</a>
            <form method="post" action="forum_delete.php" onsubmit="return confirm('¿Eliminar este tema y sus respuestas?');">
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

<?php require __DIR__ . '/includes/pagination.php'; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
