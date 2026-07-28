<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Inicio';
$activeNav = 'inicio';
$pageDescription = setting('tagline');

$grouped = get_channels_grouped();

$categoryMeta = [
    'general'  => ['icon' => '💬', 'desc' => 'El canal de siempre, apenas conectás'],
    'regional' => ['icon' => '🌎', 'desc' => 'Argentina, México, Chile, Uruguay y más'],
    'adultos'  => ['icon' => '🔥', 'desc' => 'Charla +18: sexo, porno, citas'],
    'ayuda'    => ['icon' => '🛟', 'desc' => 'Soporte y dudas de conexión'],
];

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container hero-inner">
    <p class="eyebrow"><span class="dot-live"></span> Red IRC pública · 100% gratis · Sin registro</p>
    <h1>Chatea en español,<br><span class="accent-text">sin vueltas.</span></h1>
    <p class="hero-sub">
      <?= h(setting('site_name')) ?> es una red de chat IRC en español para conocer gente,
      charlar de lo que quieras y sentirte como en casa. Entra desde el navegador en dos
      clics o conectate con tu cliente IRC favorito.
    </p>
    <div class="hero-cta">
      <a class="btn btn-primary btn-lg" href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">
        Entrar al webchat
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <a class="btn btn-ghost btn-lg" href="conectar.php">Usar mi cliente IRC</a>
    </div>
    <p class="hero-note">No hace falta correo, ni contraseña, ni instalar nada. Elegís un nick y ya estás dentro.</p>
  </div>
</section>

<section class="section-tight">
  <div class="container">
    <div class="section-head">
      <h2>Explorá las salas</h2>
      <p>Cada categoría tiene su lugar. Elegí una y sumate.</p>
    </div>

    <div class="category-grid">
      <?php foreach (CHANNEL_CATEGORIES as $slug => $label): ?>
        <a class="category-card <?= $slug === 'adultos' ? 'is-nsfw' : '' ?>" href="salas.php#<?= h($slug) ?>">
          <div class="cat-icon"><?= $categoryMeta[$slug]['icon'] ?></div>
          <h3><?= h($label) ?></h3>
          <p><?= h($categoryMeta[$slug]['desc']) ?> · <?= count($grouped[$slug]) ?> salas</p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="connect-grid">
      <div class="connect-card connect-card-featured">
        <span class="badge">Recomendado</span>
        <h3>Webchat</h3>
        <p>Directo desde el navegador, sin instalar nada. Elegís nick y canal, y ya estás adentro.</p>
        <a class="btn btn-primary btn-lg btn-block" href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">Abrir webchat</a>
      </div>

      <div class="connect-card">
        <h3>¿Preferís tu cliente IRC?</h3>
        <p>Conectate con mIRC, HexChat, Irssi o el que uses siempre.</p>
        <dl class="server-info">
          <div class="server-row">
            <dt>Servidor</dt>
            <dd><code><?= h(setting('irc_server')) ?></code></dd>
          </div>
          <div class="server-row">
            <dt>Puerto TLS</dt>
            <dd><code><?= h(setting('irc_port_tls')) ?></code></dd>
          </div>
        </dl>
        <a class="btn btn-ghost btn-block" href="conectar.php">Ver instrucciones completas</a>
      </div>
    </div>
  </div>
</section>

<section class="cta-banner">
  <div class="container cta-inner">
    <h2>¿Listo para entrar?</h2>
    <p>No necesitás nada más que un navegador. Elegí un nick y sumate a la charla.</p>
    <a class="btn btn-primary btn-lg" href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">Entrar al webchat de <?= h(setting('site_name')) ?></a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
