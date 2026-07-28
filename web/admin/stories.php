<?php
declare(strict_types=1);
$pageTitle = 'Historias';
$activeAdminNav = 'stories';
require __DIR__ . '/includes/admin_header.php';

$statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM community_stories')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare(
    'SELECT * FROM community_stories ORDER BY (status = "pendiente") DESC, created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$stories = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Historias de la comunidad</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr><th>Título</th><th>Autor</th><th>Fecha</th><th>Estado</th><th></th></tr>
    </thead>
    <tbody>
      <?php if (empty($stories)): ?>
        <tr><td colspan="5">Todavía no llegaron historias.</td></tr>
      <?php endif; ?>
      <?php foreach ($stories as $s): ?>
        <tr>
          <td><?= h($s['title']) ?></td>
          <td><?= h($s['author_nick']) ?> · <?= h($s['author_email']) ?> <?= $s['email_verified'] ? '<span class="pill pill-on" title="Email verificado">✓</span>' : '<span class="pill pill-off" title="Email sin verificar">?</span>' ?></td>
          <td><?= h(date('d/m/Y H:i', strtotime($s['created_at']))) ?></td>
          <td>
            <form method="post" action="stories_update.php" style="display:flex; gap:6px; align-items:center;">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <select name="status" onchange="this.form.submit()">
                <?php foreach ($statusLabels as $value => $label): ?>
                  <option value="<?= h($value) ?>" <?= $s['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
              <noscript><button class="btn btn-ghost btn-sm" type="submit">Guardar</button></noscript>
            </form>
          </td>
          <td class="actions">
            <?php if ($s['status'] === 'aprobado'): ?>
              <a class="btn btn-ghost btn-sm" href="/historia-ver.php?id=<?= (int) $s['id'] ?>" target="_blank" rel="noopener">Ver</a>
            <?php endif; ?>
            <form method="post" action="stories_delete.php" onsubmit="return confirm('¿Eliminar esta historia?');">
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

<?php require __DIR__ . '/includes/pagination.php'; ?>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
