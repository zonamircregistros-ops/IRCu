<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$article = $slug !== '' ? get_news_by_slug($slug) : null;

if (!$article) {
    http_response_code(404);
    $pageTitle = 'Noticia no encontrada';
    $activeNav = 'noticias';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
      <div class="container">
        <h1>Noticia no encontrada</h1>
        <p>Puede que haya sido movida o eliminada. Volvé a <a href="/noticias.php">todas las noticias</a>.</p>
      </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $article['title'];
$activeNav = 'noticias';
$pageDescription = $article['excerpt'] ?: setting('tagline');

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/noticias.php">← Todas las noticias</a>
    <span class="news-date"><?= h(date('d/m/Y', strtotime($article['published_at']))) ?></span>
    <h1><?= h($article['title']) ?></h1>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <div class="news-body">
      <?= nl2br(h($article['body'])) ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
