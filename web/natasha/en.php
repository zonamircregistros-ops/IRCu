<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Natasha Bouncer';
$activeNav = 'servicios';
$pageDescription = 'Natasha Bouncer: keep your IRC connection always on. Free for up to 5 networks, Premium with unlimited networks.';

$networks = get_bnc_networks();
$serviceStatus = setting('bnc_service_status');
$isOperational = $serviceStatus === 'operativo';

require __DIR__ . '/../includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <div class="lang-switch">
      <a href="/natasha/index.php">Español</a>
      <span class="is-active">English</span>
    </div>
    <h1>Natasha Bouncer</h1>
    <p>Your IRC connection, always on. Close your client whenever you want — Natasha stays connected for you.</p>
    <span class="status-pill <?= $isOperational ? 'status-ok' : 'status-down' ?>">
      <?= $isOperational ? '● Service operational' : '● Service disrupted' ?>
    </span>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>What is a bouncer?</h2>
      <p>A BNC (bouncer) is a permanent bridge between you and IRC: it stays connected 24/7, keeps your history, and reconnects you automatically even if you close your client, lose internet, or shut down your computer.</p>
    </div>

    <div class="plan-grid">
      <div class="plan-card">
        <span class="badge">Free</span>
        <h3>Free</h3>
        <p class="plan-price">$0<span>/month</span></p>
        <ul class="plan-features">
          <li>Up to <?= h(setting('bnc_free_limit')) ?> networks total (including Chateanos)</li>
          <li>You connect mainly to Chateanos</li>
          <li>Pick <?= h(setting('bnc_free_own_choice')) ?> more networks of your choice</li>
          <li>Available over IPv4</li>
        </ul>
        <a class="btn btn-ghost btn-block" href="/natasha/solicitar.php?lang=en&plan=free">Request the Free plan</a>
      </div>

      <div class="plan-card plan-card-premium">
        <span class="badge">Premium</span>
        <h3>Premium</h3>
        <p class="plan-price">$<?= h(setting('bnc_premium_price')) ?><span>/month</span></p>
        <ul class="plan-features">
          <li>Unlimited networks</li>
          <li>No need to connect to Chateanos</li>
          <li>IPv4 and IPv6</li>
          <li>+$<?= h(setting('bnc_premium_extra_ip_price')) ?> extra: dedicated, non-shared IP</li>
          <li>Choose your datacenter</li>
        </ul>
        <a class="btn btn-primary btn-block" href="/natasha/solicitar.php?lang=en&plan=premium">Request Premium</a>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <h2>Available datacenters</h2>
      <p>Pick the datacenter closest to you (availability depends on your plan).</p>
    </div>
    <div class="datacenter-grid">
      <div class="datacenter-card">
        <span class="dc-flag">🇦🇺</span>
        <h3>Oceania</h3>
        <p>Sydney, Australia</p>
      </div>
      <div class="datacenter-card">
        <span class="dc-flag">🇱🇹</span>
        <h3>Europe</h3>
        <p>Vilnius, Lithuania</p>
      </div>
      <div class="datacenter-card">
        <span class="dc-flag">🇦🇷</span>
        <h3>South America</h3>
        <p>Buenos Aires, Argentina</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Networks where Natasha is present</h2>
      <p>Current status of Natasha's presence on each network.</p>
    </div>

    <?php if (empty($networks)): ?>
      <p class="empty-note">No networks listed yet.</p>
    <?php else: ?>
      <div class="table-scroll">
        <table class="public-table">
          <thead>
            <tr>
              <th>Network</th>
              <th>Host</th>
              <th>IP</th>
              <th>Port</th>
              <th>SSL</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($networks as $net): ?>
              <tr>
                <td><?= h($net['network_name']) ?></td>
                <td><code><?= h($net['host']) ?></code></td>
                <td><?= h($net['ip_address'] ?: '—') ?></td>
                <td><?= (int) $net['port'] ?></td>
                <td><?= $net['use_ssl'] ? 'Yes' : 'No' ?></td>
                <td>
                  <?php if ($net['status'] === 'operativo'): ?>
                    <span class="pill pill-on">Operational</span>
                  <?php else: ?>
                    <span class="pill pill-off" title="<?= h($net['banned_reason'] ?? '') ?>">Not operational</span>
                  <?php endif; ?>
                  <?php if ($net['status'] !== 'operativo' && !empty($net['banned_reason'])): ?>
                    <div class="table-note"><?= h($net['banned_reason']) ?></div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <h2>Service rules</h2>
    </div>
    <ol class="rules-list">
      <li><strong>Personal use.</strong> Each Natasha account is for a single user; credentials are not shared.</li>
      <li><strong>No abuse.</strong> No flooding, spamming, or attacks launched through the bouncer.</li>
      <li><strong>Respect each network's rules.</strong> Natasha doesn't exempt you from following the rules of the networks you connect to.</li>
      <li><strong>Fair use.</strong> Staff may suspend accounts that compromise service stability for everyone else.</li>
    </ol>
  </div>
</section>

<section class="cta-banner">
  <div class="container cta-inner">
    <h2>Ready to request your account?</h2>
    <p>Fill out the form and staff will reach out to activate it.</p>
    <a class="btn btn-primary btn-lg" href="/natasha/solicitar.php?lang=en">Request Natasha Bouncer</a>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
