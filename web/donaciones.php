<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Donaciones';
$activeNav = '';
$pageDescription = 'Ayudá a sostener los servidores de ' . setting('site_name') . '.';

$cafecito = setting('donation_cafecito_url');
$paypal = setting('donation_paypal_url');
$cryptoAddress = setting('donation_crypto_address');
$cryptoNetwork = setting('donation_crypto_network');
$goalAmount = (float) setting('donation_goal_amount');
$goalRaised = (float) setting('donation_goal_raised');
$goalPercent = $goalAmount > 0 ? min(100, (int) round($goalRaised / $goalAmount * 100)) : 0;

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Ayudanos a sostener la red</h1>
    <p>
      <?= h(setting('site_name')) ?> es y va a seguir siendo gratis. Pero el servidor, el
      dominio y el ancho de banda de Natasha Bouncer cuestan de verdad, y toda ayuda suma.
    </p>
  </div>
</section>

<?php if ($goalAmount > 0): ?>
<section class="section section-tight">
  <div class="container container-narrow">
    <div class="donation-goal">
      <div class="donation-goal-head">
        <span>Meta de este mes</span>
        <span>US$<?= number_format($goalRaised, 0) ?> / US$<?= number_format($goalAmount, 0) ?></span>
      </div>
      <div class="donation-goal-track">
        <div class="donation-goal-fill" style="width: <?= $goalPercent ?>%;"></div>
      </div>
      <p class="donation-goal-percent"><?= $goalPercent ?>% alcanzado</p>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="plan-grid" style="grid-template-columns: repeat(3, 1fr);">
      <?php if (!empty($cafecito)): ?>
        <div class="plan-card">
          <h3>☕ Cafecito</h3>
          <p style="color: var(--text-dim); margin-bottom: 20px;">La forma más simple de bancar la red desde Latinoamérica.</p>
          <a class="btn btn-primary btn-block" href="<?= h($cafecito) ?>" target="_blank" rel="noopener">Invitar un cafecito</a>
        </div>
      <?php endif; ?>

      <?php if (!empty($paypal)): ?>
        <div class="plan-card">
          <h3>💳 PayPal</h3>
          <p style="color: var(--text-dim); margin-bottom: 20px;">Para donaciones desde cualquier parte del mundo.</p>
          <a class="btn btn-primary btn-block" href="<?= h($paypal) ?>" target="_blank" rel="noopener">Donar por PayPal</a>
        </div>
      <?php endif; ?>

      <?php if (!empty($cryptoAddress)): ?>
        <div class="plan-card">
          <h3>₿ Cripto</h3>
          <p style="color: var(--text-dim); margin-bottom: 12px;"><?= h($cryptoNetwork) ?></p>
          <div class="server-row" style="margin-bottom: 16px;">
            <dd style="word-break: break-all;"><code><?= h($cryptoAddress) ?></code></dd>
            <button class="copy-btn" data-copy="<?= h($cryptoAddress) ?>">Copiar</button>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (empty($cafecito) && empty($paypal) && empty($cryptoAddress)): ?>
      <p class="empty-note">Todavía no hay medios de donación configurados.</p>
    <?php endif; ?>

    <p class="rules-footnote">
      Toda donación va directo a hosting, dominio y el ancho de banda de Natasha Bouncer.
      Ninguna donación es un pago por un servicio: todo lo que ofrece <?= h(setting('site_name')) ?>
      sigue siendo gratis, con o sin tu aporte. Gracias por bancar la red.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
