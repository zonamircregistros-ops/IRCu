<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$query = trim((string) ($_GET['q'] ?? ''));

$pageTitle = 'Buscar';
$activeNav = '';
$pageDescription = 'Buscá en noticias, salas y preguntas frecuentes de ' . setting('site_name') . '.';

$newsResults = [];
$channelResults = [];
$faqResults = [];

if ($query !== '') {
    $like = '%' . $query . '%';

    $stmt = db()->prepare(
        "SELECT title, slug, excerpt FROM news
         WHERE is_published = 1 AND published_at <= NOW()
           AND (title LIKE :q1 OR excerpt LIKE :q2 OR body LIKE :q3)
         ORDER BY published_at DESC LIMIT 15"
    );
    $stmt->execute(['q1' => $like, 'q2' => $like, 'q3' => $like]);
    $newsResults = $stmt->fetchAll();

    $stmt = db()->prepare(
        "SELECT name, category, description FROM channels
         WHERE is_active = 1 AND (name LIKE :q1 OR description LIKE :q2)
         ORDER BY category, sort_order LIMIT 15"
    );
    $stmt->execute(['q1' => $like, 'q2' => $like]);
    $channelResults = $stmt->fetchAll();

    foreach (get_faq_items() as $faq) {
        if (stripos($faq['question'], $query) !== false || stripos($faq['answer'], $query) !== false) {
            $faqResults[] = $faq;
        }
    }
}

$totalResults = count($newsResults) + count($channelResults) + count($faqResults);

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1>Buscar</h1>
    <form class="search-form" method="get" action="/buscar.php">
      <input type="search" name="q" value="<?= h($query) ?>" placeholder="Buscá salas, noticias, preguntas frecuentes..." autofocus>
      <button class="btn btn-primary" type="submit">Buscar</button>
    </form>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($query === ''): ?>
      <p class="empty-note">Escribí algo para buscar en todo el sitio.</p>
    <?php elseif ($totalResults === 0): ?>
      <p class="empty-note">No encontramos resultados para "<?= h($query) ?>". Probá con otra palabra o mirá <a href="/faq.php">las preguntas frecuentes</a>.</p>
    <?php else: ?>

      <?php if (!empty($channelResults)): ?>
        <h2 class="search-group-title">Salas (<?= count($channelResults) ?>)</h2>
        <div class="channel-grid" style="margin-bottom: 32px;">
          <?php foreach ($channelResults as $c): ?>
            <a class="channel-chip" href="/salas.php#<?= h($c['category']) ?>">#<?= h($c['name']) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($newsResults)): ?>
        <h2 class="search-group-title">Noticias (<?= count($newsResults) ?>)</h2>
        <div class="news-list" style="margin-bottom: 32px;">
          <?php foreach ($newsResults as $item): ?>
            <a class="news-card" href="/noticia.php?slug=<?= h($item['slug']) ?>">
              <h2><?= h($item['title']) ?></h2>
              <?php if (!empty($item['excerpt'])): ?><p><?= h($item['excerpt']) ?></p><?php endif; ?>
              <span class="news-readmore">Leer más →</span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($faqResults)): ?>
        <h2 class="search-group-title">Preguntas frecuentes (<?= count($faqResults) ?>)</h2>
        <div class="faq-list">
          <?php foreach ($faqResults as $faq): ?>
            <details class="faq-item">
              <summary><?= h($faq['question']) ?></summary>
              <p><?= h($faq['answer']) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
