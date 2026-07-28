<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Encuestas';
$activeNav = '';
$pageDescription = 'Participá de las encuestas de ' . setting('site_name') . '.';

$encuestasUrl = setting('encuestas_url');

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Encuestas</h1>
    <p>Ayudanos a decidir cosas de la red: nuevas salas, cambios de reglas, funciones del bouncer y más.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <div class="admin-card" style="text-align:center;">
      <p>Las encuestas de <?= h(setting('site_name')) ?> se manejan en una plataforma aparte (LimeSurvey), para que puedas votar de forma anónima y ver los resultados agregados.</p>
      <?php if (!empty($encuestasUrl)): ?>
        <a class="btn btn-primary btn-lg" href="<?= h($encuestasUrl) ?>" target="_blank" rel="noopener">Ir a Encuestas →</a>
      <?php else: ?>
        <p class="empty-note">No hay encuestas activas por ahora.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
