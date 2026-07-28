<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Gestiones';
$activeNav = 'gestiones';
$pageDescription = 'Apelá una expulsión, postulate a IRCop o abrí un ticket de soporte.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Gestiones</h1>
    <p>Trámites con el staff de <?= h(setting('site_name')) ?>: apelaciones, postulaciones y soporte.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="category-grid">
      <a class="category-card" href="/apelar.php">
        <div class="cat-icon">⚖️</div>
        <h3>Apelar una expulsión</h3>
        <p>¿Creés que te banearon (G-Line) por error? Contanos y el staff lo revisa.</p>
      </a>
      <a class="category-card" href="/ircop.php">
        <div class="cat-icon">🛡️</div>
        <h3>Postularme a IRCop</h3>
        <p>Sumate al staff técnico de la red. Contanos tu historia en Chateanos.</p>
      </a>
      <a class="category-card" href="/tickets.php">
        <div class="cat-icon">🎫</div>
        <h3>Abrir un ticket</h3>
        <p>Soporte, reclamos o cualquier otra consulta. Seguí la respuesta con un link privado.</p>
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
