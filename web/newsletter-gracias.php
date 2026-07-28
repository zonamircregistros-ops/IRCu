<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Newsletter';
$activeNav = '';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1>¡Casi listo!</h1>
    <p>Si el email es válido, te mandamos un link de confirmación. Revisá tu bandeja de entrada (y spam, por las dudas).</p>
    <a class="btn btn-ghost" href="/index.php">Volver al inicio</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
