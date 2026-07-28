<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Historias de la comunidad';
$activeNav = '';
$pageDescription = 'Historias largas contadas por usuarios de ' . setting('site_name') . '.';

$stories = get_stories(true);

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Historias de la comunidad</h1>
    <p>Relatos largos de gente de la red: cómo llegaron, qué se llevan, anécdotas. Más allá de una cita corta.</p>
    <p><a class="back-link" style="display:inline-block; margin:0;" href="/historia-enviar.php">✍️ Contar mi historia</a></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (empty($stories)): ?>
      <p class="empty-note">Todavía no hay historias publicadas.</p>
    <?php else: ?>
      <div class="news-list">
        <?php foreach ($stories as $s): ?>
          <a class="news-card" href="/historia-ver.php?id=<?= (int) $s['id'] ?>">
            <span class="news-date"><?= h(date('d/m/Y', strtotime($s['created_at']))) ?> · por <?= h($s['author_nick']) ?></span>
            <h2><?= h($s['title']) ?></h2>
            <p><?= h(mb_strimwidth($s['body'], 0, 160, '…')) ?></p>
            <span class="news-readmore">Leer más →</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
