<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Natasha Bouncer';
$activeNav = 'servicios';
$pageDescription = 'Natasha Bouncer: mantené tu conexión IRC siempre activa. Gratis hasta 5 redes, Premium con redes ilimitadas.';

$networks = get_bnc_networks();
$serviceStatus = setting('bnc_service_status');
$isOperational = $serviceStatus === 'operativo';

require __DIR__ . '/../includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <div class="lang-switch">
      <span class="is-active">Español</span>
      <a href="/natasha/en.php">English</a>
    </div>
    <h1>Natasha Bouncer</h1>
    <p>Tu conexión IRC, siempre encendida. Cerrá el cliente cuando quieras: Natasha se queda conectada por vos.</p>
    <span class="status-pill <?= $isOperational ? 'status-ok' : 'status-down' ?>">
      <?= $isOperational ? '● Servicio operativo' : '● Servicio con problemas' ?>
    </span>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>¿Qué es un bouncer?</h2>
      <p>Un BNC (bouncer) es un puente permanente entre vos y el IRC: queda conectado 24/7, guarda tu historial y te reconecta automáticamente aunque cierres el cliente, se corte tu internet o apagues la compu.</p>
      <p><a href="/natasha/tutorial.php">¿Ya tenés tu cuenta? Mirá el tutorial de conexión →</a></p>
    </div>

    <div class="plan-grid">
      <div class="plan-card">
        <span class="badge">Free</span>
        <h3>Gratis</h3>
        <p class="plan-price">$0<span>/mes</span></p>
        <ul class="plan-features">
          <li>Hasta <?= h(setting('bnc_free_limit')) ?> redes en total (incluyendo Chateanos)</li>
          <li>Te conectás principalmente a Chateanos</li>
          <li>Elegís <?= h(setting('bnc_free_own_choice')) ?> redes más a tu gusto</li>
          <li>Disponible en IPv4</li>
        </ul>
        <a class="btn btn-ghost btn-block" href="/natasha/solicitar.php?lang=es&plan=free">Solicitar plan Free</a>
      </div>

      <div class="plan-card plan-card-premium">
        <span class="badge">Premium</span>
        <h3>Premium</h3>
        <p class="plan-price">$<?= h(setting('bnc_premium_price')) ?><span>/mes</span></p>
        <ul class="plan-features">
          <li>Redes ilimitadas</li>
          <li>No hace falta conectarte a Chateanos</li>
          <li>IPv4 e IPv6</li>
          <li>+$<?= h(setting('bnc_premium_extra_ip_price')) ?> extra: IP privada, sin compartir</li>
          <li>Elegís el datacenter</li>
        </ul>
        <a class="btn btn-primary btn-block" href="/natasha/solicitar.php?lang=es&plan=premium">Solicitar Premium</a>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <h2>Datacenters disponibles</h2>
      <p>Elegí el datacenter más cercano a vos (disponibilidad sujeta al plan).</p>
    </div>
    <div class="datacenter-grid">
      <div class="datacenter-card">
        <span class="dc-flag">🇦🇺</span>
        <h3>Oceanía</h3>
        <p>Sídney, Australia</p>
      </div>
      <div class="datacenter-card">
        <span class="dc-flag">🇱🇹</span>
        <h3>Europa</h3>
        <p>Vilna, Lituania</p>
      </div>
      <div class="datacenter-card">
        <span class="dc-flag">🇦🇷</span>
        <h3>América del Sur</h3>
        <p>Buenos Aires, Argentina</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Redes donde está Natasha</h2>
      <p>Estado actual de la presencia de Natasha en cada red.</p>
    </div>

    <?php if (empty($networks)): ?>
      <p class="empty-note">Todavía no hay redes cargadas.</p>
    <?php else: ?>
      <div class="table-scroll">
        <table class="public-table">
          <thead>
            <tr>
              <th>Red</th>
              <th>Host</th>
              <th>IP</th>
              <th>Puerto</th>
              <th>SSL</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($networks as $net): ?>
              <tr>
                <td><?= h($net['network_name']) ?></td>
                <td><code><?= h($net['host']) ?></code></td>
                <td><?= h($net['ip_address'] ?: '—') ?></td>
                <td><?= (int) $net['port'] ?></td>
                <td><?= $net['use_ssl'] ? 'Sí' : 'No' ?></td>
                <td>
                  <?php if ($net['status'] === 'operativo'): ?>
                    <span class="pill pill-on">Operativo</span>
                  <?php else: ?>
                    <span class="pill pill-off" title="<?= h($net['banned_reason'] ?? '') ?>">No operativo</span>
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
      <h2>Reglas del servicio</h2>
    </div>
    <ol class="rules-list">
      <li><strong>Uso personal.</strong> Cada cuenta de Natasha es para un solo usuario; no se comparten credenciales.</li>
      <li><strong>Sin abuso.</strong> Nada de flood, spam ni ataques lanzados a través del bouncer.</li>
      <li><strong>Respetá las reglas de cada red.</strong> Natasha no te exime de cumplir las normas de las redes a las que te conectás.</li>
      <li><strong>Uso justo.</strong> El staff puede suspender cuentas que comprometan la estabilidad del servicio para el resto.</li>
    </ol>
  </div>
</section>

<section class="cta-banner">
  <div class="container cta-inner">
    <h2>¿Listo para pedir tu cuenta?</h2>
    <p>Completá el formulario y el staff te va a contactar para activarla.</p>
    <a class="btn btn-primary btn-lg" href="/natasha/solicitar.php?lang=es">Solicitar Natasha Bouncer</a>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
