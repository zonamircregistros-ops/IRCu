<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Normas';
$activeNav = 'normas';
$pageDescription = 'Normas básicas de convivencia en ' . setting('site_name') . '.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Normas básicas</h1>
    <p>Cuatro reglas simples para que la red siga siendo un buen lugar.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <ol class="rules-list">
      <li><strong>Respeto ante todo.</strong> Nada de insultos, acoso ni discriminación hacia otros usuarios.</li>
      <li><strong>Sin spam ni publicidad</strong> no solicitada, bots de invitación ni flood de mensajes o conexiones.</li>
      <li><strong>Las salas de adultos son solo para mayores de 18 años.</strong> Nada de contenido con menores, bajo ningún concepto.</li>
      <li><strong>Nada de contenido ilegal</strong> ni material que viole las leyes de tu país o las de terceros.</li>
      <li><strong>Hacé caso al staff.</strong> Los IRCops están para ayudar y mantener el orden; sus indicaciones se respetan.</li>
    </ol>

    <p class="rules-footnote">
      Ante dudas o para reportar algo, contactá con el staff en el canal
      <code><?= h(setting('general_channel')) ?></code> o por correo a
      <a href="mailto:<?= h(setting('staff_email')) ?>"><?= h(setting('staff_email')) ?></a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
