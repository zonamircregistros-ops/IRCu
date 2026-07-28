<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Salas';
$activeNav = 'salas';
$pageDescription = 'Todos los canales de ' . setting('site_name') . ': generales, regionales, adultos y de ayuda.';

$grouped = get_channels_grouped();

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Salas y canales</h1>
    <p>Elegí un canal y entrá directo al webchat. Para ver la lista completa desde tu cliente usá <code>/list</code>.</p>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php foreach (CHANNEL_CATEGORIES as $slug => $label): ?>
      <div class="channel-category" id="<?= h($slug) ?>">
        <div class="channel-category-head">
          <h2><?= h($label) ?></h2>
          <?php if ($slug === 'adultos'): ?>
            <span class="badge badge-18">Solo mayores de 18 años</span>
          <?php endif; ?>
        </div>

        <?php if ($slug === 'adultos'): ?>
          <div class="notice-box">
            🔞 Estas salas son de temática adulta. Ingresá solo si sos mayor de edad en tu país de residencia.
          </div>
        <?php endif; ?>

        <?php if (empty($grouped[$slug])): ?>
          <p class="empty-note">Todavía no hay salas cargadas en esta categoría.</p>
        <?php else: ?>
          <div class="<?= $slug === 'adultos' ? 'age-gate' : '' ?>" id="<?= $slug === 'adultos' ? 'age-gate' : '' ?>">
            <?php if ($slug === 'adultos'): ?>
              <div class="age-gate-overlay">
                <p>🔞 Confirmá tu edad para ver estas salas.</p>
                <button type="button" class="btn btn-primary" id="age-gate-confirm">Soy mayor de 18 años</button>
              </div>
            <?php endif; ?>
            <div class="channel-grid<?= $slug === 'adultos' ? ' age-gate-content' : '' ?>">
              <?php foreach ($grouped[$slug] as $channel): ?>
                <a class="channel-card <?= $channel['is_nsfw'] ? 'is-nsfw' : '' ?>" href="<?= h(webchat_link($channel['name'])) ?>" target="_blank" rel="noopener">
                  <div class="channel-name">
                    #<?= h($channel['name']) ?>
                    <?php if ($channel['is_nsfw']): ?><span class="nsfw-tag">18+</span><?php endif; ?>
                  </div>
                  <?php if (!empty($channel['description'])): ?>
                    <p><?= h($channel['description']) ?></p>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
