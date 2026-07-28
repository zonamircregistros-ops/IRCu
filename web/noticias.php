<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Noticias';
$activeNav = 'noticias';
$pageDescription = 'Novedades y anuncios de ' . setting('site_name') . '.';

$news = get_news_list();

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Noticias</h1>
    <p>Anuncios, novedades y cambios en la red.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (empty($news)): ?>
      <p class="empty-note">Todavía no hay noticias publicadas.</p>
    <?php else: ?>
      <div class="news-list">
        <?php foreach ($news as $item): ?>
          <a class="news-card" href="/noticia.php?slug=<?= h($item['slug']) ?>">
            <span class="news-date"><?= h(date('d/m/Y', strtotime($item['published_at']))) ?></span>
            <h2><?= h($item['title']) ?></h2>
            <?php if (!empty($item['excerpt'])): ?>
              <p><?= h($item['excerpt']) ?></p>
            <?php endif; ?>
            <span class="news-readmore">Leer más →</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
