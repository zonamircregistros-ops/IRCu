<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = $slug !== '' ? get_blog_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Artículo no encontrado';
    $activeNav = 'noticias';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
      <div class="container">
        <h1>Artículo no encontrado</h1>
        <p>Puede que haya sido movido o todavía esté en revisión. Volvé al <a href="/blog.php">blog comunitario</a>.</p>
      </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $post['title'];
$activeNav = 'noticias';
$pageDescription = 'Por ' . $post['author_nick'] . ' — ' . setting('site_name');

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/blog.php">← Blog comunitario</a>
    <span class="news-date"><?= h(date('d/m/Y', strtotime($post['published_at']))) ?> · por <?= h($post['author_nick']) ?></span>
    <h1><?= h($post['title']) ?></h1>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <div class="news-body">
      <?= nl2br(h($post['body'])) ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
