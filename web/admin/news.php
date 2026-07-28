<?php
declare(strict_types=1);
$pageTitle = 'Noticias';
$activeAdminNav = 'news';
require __DIR__ . '/includes/admin_header.php';

$news = db()->query(
    'SELECT id, title, slug, is_published, published_at FROM news ORDER BY published_at DESC'
)->fetchAll();
?>

<div class="admin-topbar">
  <h1>Noticias</h1>
  <a class="btn btn-primary" href="news_form.php">+ Nueva noticia</a>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Título</th>
        <th>Publicada</th>
        <th>Fecha</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($news)): ?>
        <tr><td colspan="4">Todavía no hay noticias cargadas.</td></tr>
      <?php endif; ?>
      <?php foreach ($news as $n): ?>
        <tr>
          <td><?= h($n['title']) ?></td>
          <td><?= $n['is_published'] ? '<span class="pill pill-on">Sí</span>' : '<span class="pill pill-off">No</span>' ?></td>
          <td><?= h(date('d/m/Y H:i', strtotime($n['published_at']))) ?></td>
          <td class="actions">
            <a class="btn btn-ghost btn-sm" href="/noticia.php?slug=<?= h($n['slug']) ?>" target="_blank" rel="noopener">Ver</a>
            <a class="btn btn-ghost btn-sm" href="news_form.php?id=<?= (int) $n['id'] ?>">Editar</a>
            <form method="post" action="news_delete.php" onsubmit="return confirm('¿Eliminar la noticia \'<?= h($n['title']) ?>\'?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
