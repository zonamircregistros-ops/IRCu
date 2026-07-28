<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$type = (string) ($_GET['type'] ?? '');
$token = (string) ($_GET['token'] ?? '');

$tables = [
    'bnc'    => 'bnc_requests',
    'appeal' => 'gline_appeals',
    'ircop'  => 'ircop_applications',
    'ticket' => 'tickets',
];

$pageTitle = 'Verificar email';
$activeNav = '';
$pageDescription = 'Confirmá tu email en ' . setting('site_name') . '.';

$success = false;

if (isset($tables[$type]) && $token !== '') {
    $table = $tables[$type];
    $stmt = db()->prepare("UPDATE {$table} SET email_verified = 1 WHERE email_verify_token = :token");
    $stmt->execute(['token' => $token]);
    $success = $stmt->rowCount() > 0;
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1><?= $success ? 'Email confirmado' : 'No pudimos confirmar tu email' ?></h1>
    <p>
      <?php if ($success): ?>
        Gracias, ya marcamos tu email como verificado. El staff lo va a tener en cuenta al revisar tu solicitud.
      <?php else: ?>
        El link no es válido o ya fue usado. Si el problema sigue, escribinos a
        <a href="mailto:<?= h(setting('staff_email')) ?>"><?= h(setting('staff_email')) ?></a>.
      <?php endif; ?>
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
