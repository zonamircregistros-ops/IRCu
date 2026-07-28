<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$lang = current_lang();

$copy = $lang === 'en' ? [
    'title' => 'Frequently asked questions',
    'intro' => 'The basics of IRC and how ' . setting('site_name') . ' works, explained quickly.',
    'not_found' => "Didn't find what you were looking for? Write to us at",
    'or_ticket' => 'or open a ticket from',
] : [
    'title' => 'Preguntas frecuentes',
    'intro' => 'Lo básico del IRC y de cómo funciona ' . setting('site_name') . ', explicado rápido.',
    'not_found' => '¿No encontraste lo que buscabas? Escribinos a',
    'or_ticket' => 'o abrí un ticket desde',
];

$pageTitle = $copy['title'];
$activeNav = '';
$pageDescription = $copy['intro'];

$faqs = get_faq_items();

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1><?= h($copy['title']) ?></h1>
    <p><?= h($copy['intro']) ?></p>
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
      <?= h($copy['not_found']) ?>
      <a href="mailto:<?= h(setting('staff_email')) ?>"><?= h(setting('staff_email')) ?></a>
      <?= h($copy['or_ticket']) ?> <a href="/gestiones.php">Gestiones</a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
