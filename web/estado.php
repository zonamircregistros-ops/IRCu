<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Estado del servicio';
$activeNav = '';
$pageDescription = 'Estado actual de la red, el webchat y los servicios de ' . setting('site_name') . '.';

$statuses = get_service_statuses();
$allOperational = !empty($statuses) && !array_filter($statuses, fn ($s) => $s['status'] !== 'operativo');

$statusLabels = [
    'operativo' => 'Operativo',
    'degradado' => 'Degradado',
    'no_operativo' => 'No operativo',
];

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Estado del servicio</h1>
    <?php if (!empty($statuses)): ?>
      <span class="status-pill <?= $allOperational ? 'status-ok' : 'status-down' ?>">
        <?= $allOperational ? '● Todos los sistemas operativos' : '● Hay servicios con problemas' ?>
      </span>
    <?php endif; ?>
    <p style="margin-top:12px;">Actualizado por el staff a mano, no es un monitoreo automático en tiempo real.</p>
  </div>
</section>

<section class="section">
  <div class="container container-narrow">
    <?php if (empty($statuses)): ?>
      <p class="empty-note">Todavía no hay servicios cargados.</p>
    <?php else: ?>
      <div class="status-list">
        <?php foreach ($statuses as $s): ?>
          <div class="status-row">
            <div>
              <strong><?= h($s['service_name']) ?></strong>
              <?php if (!empty($s['note'])): ?><p class="table-note"><?= h($s['note']) ?></p><?php endif; ?>
            </div>
            <div style="text-align:right;">
              <?php
                $pillClass = $s['status'] === 'operativo' ? 'pill-on' : ($s['status'] === 'no_operativo' ? 'pill-off' : '');
              ?>
              <span class="pill <?= $pillClass ?>"><?= h($statusLabels[$s['status']]) ?></span>
              <?php $uptime = get_uptime_percent($s['service_name'], 30); ?>
              <?php if ($uptime !== null): ?>
                <div class="table-note">Uptime 30 días: <strong><?= $uptime ?>%</strong></div>
              <?php endif; ?>
              <div class="table-note">Actualizado <?= h(date('d/m/Y H:i', strtotime($s['updated_at']))) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="rules-footnote">
      ¿Algo no funciona y no está reflejado acá? Avisanos abriendo un
      <a href="/tickets.php">ticket de soporte</a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
