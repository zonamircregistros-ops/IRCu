<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Servicios';
$activeNav = 'servicios';
$pageDescription = 'Servicios de ' . setting('site_name') . ' más allá del chat: Git, Wiki, Nube, Webmail y Natasha Bouncer.';

$services = get_services();

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Servicios</h1>
    <p>Más allá del chat: herramientas para la comunidad, mantenidas por <?= h(setting('site_name')) ?>.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (empty($services)): ?>
      <p class="empty-note">Todavía no hay servicios cargados.</p>
    <?php else: ?>
      <div class="service-grid">
        <?php foreach ($services as $service):
          $isExternal = str_starts_with($service['url'], 'http://') || str_starts_with($service['url'], 'https://');
          $href = $isExternal ? $service['url'] : '/' . ltrim($service['url'], '/');
        ?>
          <a class="service-card" href="<?= h($href) ?>" <?= $isExternal ? 'target="_blank" rel="noopener"' : '' ?>>
            <div class="service-icon"><?= $service['icon'] ?: '🔗' ?></div>
            <h2><?= h($service['name']) ?></h2>
            <?php if (!empty($service['description'])): ?>
              <p><?= h($service['description']) ?></p>
            <?php endif; ?>
            <span class="service-link"><?= $isExternal ? h(parse_url($service['url'], PHP_URL_HOST)) : 'Ver más' ?> →</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
