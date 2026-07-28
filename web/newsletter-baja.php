<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$token = trim((string) ($_GET['token'] ?? ''));
$success = false;

if ($token !== '') {
    $stmt = db()->prepare('DELETE FROM newsletter_subscribers WHERE unsubscribe_token = :token');
    $stmt->execute(['token' => $token]);
    $success = $stmt->rowCount() > 0;
}

$pageTitle = 'Baja del newsletter';
$activeNav = '';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1><?= $success ? 'Listo, te diste de baja' : 'No pudimos procesar la baja' ?></h1>
    <p>
      <?php if ($success): ?>
        Ya no vas a recibir el newsletter de <?= h(setting('site_name')) ?>. Podés volver a suscribirte cuando quieras.
      <?php else: ?>
        El link no es válido o ya fue usado.
      <?php endif; ?>
    </p>
    <a class="btn btn-ghost" href="/index.php">Volver al inicio</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
