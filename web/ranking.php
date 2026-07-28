<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Ranking de salas';
$activeNav = 'salas';
$pageDescription = 'Las salas más activas de ' . setting('site_name') . '.';

$ranking = get_channel_ranking(20);

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/salas.php">← Todas las salas</a>
    <h1>Ranking de salas</h1>
    <p>Las salas más elegidas desde esta web, según los clics al webchat.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if (empty($ranking) || (int) $ranking[0]['click_count'] === 0): ?>
      <p class="empty-note">Todavía no hay suficientes datos para armar un ranking.</p>
    <?php else: ?>
      <div class="admin-card" style="padding:0; overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr><th>#</th><th>Sala</th><th>Categoría</th><th>Clics</th></tr>
          </thead>
          <tbody>
            <?php foreach ($ranking as $i => $c): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>#<?= h($c['name']) ?></td>
                <td><?= h(CHANNEL_CATEGORIES[$c['category']] ?? $c['category']) ?></td>
                <td><?= (int) $c['click_count'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
