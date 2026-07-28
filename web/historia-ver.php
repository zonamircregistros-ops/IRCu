<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$story = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM community_stories WHERE id = :id AND status = "aprobado" LIMIT 1');
    $stmt->execute(['id' => $id]);
    $story = $stmt->fetch() ?: null;
}

if (!$story) {
    http_response_code(404);
    $pageTitle = 'Historia no encontrada';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
      <div class="container">
        <h1>Historia no encontrada</h1>
        <p>Puede que todavía esté en revisión. Volvé a <a href="/historias-comunidad.php">Historias de la comunidad</a>.</p>
      </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $story['title'];
$pageDescription = 'Por ' . $story['author_nick'] . ' — ' . setting('site_name');

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/historias-comunidad.php">← Historias de la comunidad</a>
    <span class="news-date"><?= h(date('d/m/Y', strtotime($story['created_at']))) ?> · por <?= h($story['author_nick']) ?></span>
    <h1><?= h($story['title']) ?></h1>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <div class="news-body">
      <?= nl2br(h($story['body'])) ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
