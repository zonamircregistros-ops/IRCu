<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Blog comunitario';
$activeNav = 'noticias';
$pageDescription = 'Artículos escritos por la comunidad de ' . setting('site_name') . '.';

$posts = get_blog_posts(true);

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Blog comunitario</h1>
    <p>Artículos escritos por usuarios de la red, revisados por el staff antes de publicarse.</p>
    <p><a class="back-link" style="display:inline-block; margin:0;" href="/blog-enviar.php">✍️ Escribir un artículo</a></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (empty($posts)): ?>
      <p class="empty-note">Todavía no hay artículos publicados. ¡Sé el primero!</p>
    <?php else: ?>
      <div class="news-list">
        <?php foreach ($posts as $item): ?>
          <a class="news-card" href="/blog-post.php?slug=<?= h($item['slug']) ?>">
            <span class="news-date"><?= h(date('d/m/Y', strtotime($item['published_at']))) ?> · por <?= h($item['author_nick']) ?></span>
            <h2><?= h($item['title']) ?></h2>
            <p><?= h(mb_strimwidth(strip_tags($item['body']), 0, 160, '…')) ?></p>
            <span class="news-readmore">Leer más →</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
