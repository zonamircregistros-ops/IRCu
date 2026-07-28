<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$token = trim((string) ($_GET['token'] ?? ''));
$success = false;

if ($token !== '') {
    $stmt = db()->prepare('UPDATE newsletter_subscribers SET confirmed = 1, confirm_token = NULL WHERE confirm_token = :token');
    $stmt->execute(['token' => $token]);
    $success = $stmt->rowCount() > 0;
}

$pageTitle = 'Confirmar newsletter';
$activeNav = '';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1><?= $success ? 'Suscripción confirmada' : 'No pudimos confirmar' ?></h1>
    <p>
      <?php if ($success): ?>
        Gracias, ya estás suscripto al newsletter de <?= h(setting('site_name')) ?>.
      <?php else: ?>
        El link no es válido o ya fue usado.
      <?php endif; ?>
    </p>
    <a class="btn btn-ghost" href="/index.php">Volver al inicio</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
