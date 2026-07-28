<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Preguntas frecuentes';
$activeNav = '';
$pageDescription = 'Todo lo que necesitás saber sobre IRC y ' . setting('site_name') . '.';

$faqs = get_faq_items();

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Preguntas frecuentes</h1>
    <p>Lo básico del IRC y de cómo funciona <?= h(setting('site_name')) ?>, explicado rápido.</p>
  </div>
</section>

<section class="section">
  <div class="container container-narrow">
    <div class="faq-list">
      <?php foreach ($faqs as $faq): ?>
        <details class="faq-item">
          <summary><?= h($faq['question']) ?></summary>
          <p><?= h($faq['answer']) ?></p>
        </details>
      <?php endforeach; ?>
    </div>

    <p class="rules-footnote">
      ¿No encontraste lo que buscabas? Escribinos a
      <a href="mailto:<?= h(setting('staff_email')) ?>"><?= h(setting('staff_email')) ?></a>
      o abrí un ticket desde <a href="/gestiones.php">Gestiones</a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
