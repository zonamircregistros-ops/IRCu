<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Eventos';
$activeNav = '';
$pageDescription = 'Torneos, aniversarios y eventos de ' . setting('site_name') . '.';

$upcoming = get_upcoming_events();
$past = get_past_events(10);

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Eventos</h1>
    <p>Torneos, trivias, aniversarios y otros eventos de la comunidad.</p>
  </div>
</section>

<section class="section">
  <div class="container container-narrow">
    <h2 class="search-group-title">Próximos</h2>
    <?php if (empty($upcoming)): ?>
      <p class="empty-note">No hay eventos programados por ahora.</p>
    <?php else: ?>
      <div class="status-list">
        <?php foreach ($upcoming as $e): ?>
          <div class="status-row">
            <div>
              <strong><?= h($e['title']) ?></strong>
              <?php if (!empty($e['description'])): ?><p class="table-note"><?= nl2br(h($e['description'])) ?></p><?php endif; ?>
            </div>
            <div style="text-align:right;">
              <span class="pill pill-on"><?= h(date('d/m/Y H:i', strtotime($e['starts_at']))) ?></span>
              <?php if (!empty($e['ends_at'])): ?><div class="table-note">Hasta <?= h(date('d/m/Y H:i', strtotime($e['ends_at']))) ?></div><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($past)): ?>
      <h2 class="search-group-title" style="margin-top:40px;">Ya pasaron</h2>
      <div class="status-list">
        <?php foreach ($past as $e): ?>
          <div class="status-row">
            <div><strong><?= h($e['title']) ?></strong></div>
            <div style="text-align:right;"><span class="pill">Finalizado</span><div class="table-note"><?= h(date('d/m/Y', strtotime($e['starts_at']))) ?></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
