<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Primeros pasos';
$activeNav = '';
$pageDescription = 'Guía de primeros pasos en ' . setting('site_name') . '.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Primeros pasos en <?= h(setting('site_name')) ?></h1>
    <p>Un checklist simple para arrancar. Se guarda en tu navegador, no hace falta cuenta.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <div class="admin-card">
      <ul class="onboarding-checklist" id="onboarding-checklist">
        <li>
          <label>
            <input type="checkbox" data-step="webchat">
            <span>Entrá al <a href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">webchat</a> y elegí un nick</span>
          </label>
        </li>
        <li>
          <label>
            <input type="checkbox" data-step="canal">
            <span>Unite a un canal en <a href="/salas.php">Salas</a> (probá #Chateanos, el general)</span>
          </label>
        </li>
        <li>
          <label>
            <input type="checkbox" data-step="normas">
            <span>Leé las <a href="/normas.php">normas de la red</a></span>
          </label>
        </li>
        <li>
          <label>
            <input type="checkbox" data-step="bnc">
            <span>Pedí tu <a href="/natasha/">Natasha Bouncer</a> gratis para quedar siempre conectado</span>
          </label>
        </li>
        <li>
          <label>
            <input type="checkbox" data-step="cliente">
            <span>Si querés, instalá un <a href="/clientes.php">cliente de escritorio</a> en vez del webchat</span>
          </label>
        </li>
        <li>
          <label>
            <input type="checkbox" data-step="staff">
            <span>Mirá quién es el <a href="/staff.php">staff</a>, por si necesitás ayuda</span>
          </label>
        </li>
      </ul>
      <p class="table-note" id="onboarding-progress"></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
