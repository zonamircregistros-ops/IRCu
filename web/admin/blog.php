<?php
declare(strict_types=1);
$pageTitle = 'Blog comunitario';
$activeAdminNav = 'blog';
require __DIR__ . '/includes/admin_header.php';

$statusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalRows = (int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = db()->prepare(
    'SELECT * FROM blog_posts ORDER BY (status = "pendiente") DESC, created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Blog comunitario</h1>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Título</th>
        <th>Autor</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($posts)): ?>
        <tr><td colspan="5">Todavía no llegaron artículos.</td></tr>
      <?php endif; ?>
      <?php foreach ($posts as $p): ?>
        <tr>
          <td><?= h($p['title']) ?></td>
          <td><?= h($p['author_nick']) ?> · <?= h($p['author_email']) ?> <?= $p['email_verified'] ? '<span class="pill pill-on" title="Email verificado">✓</span>' : '<span class="pill pill-off" title="Email sin verificar">?</span>' ?></td>
          <td><?= h(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td>
          <td>
            <form method="post" action="blog_update.php" style="display:flex; gap:6px; align-items:center;">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <select name="status" onchange="this.form.submit()">
                <?php foreach ($statusLabels as $value => $label): ?>
                  <option value="<?= h($value) ?>" <?= $p['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
              <noscript><button class="btn btn-ghost btn-sm" type="submit">Guardar</button></noscript>
            </form>
          </td>
          <td class="actions">
            <?php if ($p['status'] === 'aprobado'): ?>
              <a class="btn btn-ghost btn-sm" href="/blog-post.php?slug=<?= h($p['slug']) ?>" target="_blank" rel="noopener">Ver</a>
            <?php endif; ?>
            <form method="post" action="blog_delete.php" onsubmit="return confirm('¿Eliminar este artículo?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
