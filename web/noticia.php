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

$shareUrl = 'https://chateanos.com/noticia.php?slug=' . rawurlencode($article['slug']);
$shareText = $article['title'];

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
    <?php if (!empty($article['cover_image'])): ?>
      <img class="news-cover" src="<?= h($article['cover_image']) ?>" alt="">
    <?php endif; ?>

    <div class="news-body">
      <?= sanitize_html_content($article['body']) ?>
    </div>

    <div class="share-row">
      <span class="share-label">Compartir:</span>
      <button type="button" class="share-btn" id="native-share-btn" data-url="<?= h($shareUrl) ?>" data-text="<?= h($shareText) ?>" hidden>🔗 Compartir</button>
      <a class="share-btn" href="https://wa.me/?text=<?= rawurlencode($shareText . ' ' . $shareUrl) ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a class="share-btn" href="https://twitter.com/intent/tweet?text=<?= rawurlencode($shareText) ?>&url=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener">X / Twitter</a>
      <a class="share-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener">Facebook</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
