<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Conectar';
$activeNav = 'conectar';
$pageDescription = 'Cómo conectarte a ' . setting('site_name') . ' desde el webchat o tu cliente IRC.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Cómo conectarte</h1>
    <p>Elegí el camino que más te guste. Los dos te llevan al mismo lugar.</p>
    <p><a class="back-link" style="display:inline-block; margin:0;" href="/clientes.php">🔍 Comparar clientes IRC</a></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="connect-grid">
      <div class="connect-card connect-card-featured">
        <span class="badge">Recomendado</span>
        <h3>Webchat</h3>
        <p>Directo desde el navegador, sin instalar nada. Elegís nick y canal, y ya estás adentro.</p>
        <a class="btn btn-primary btn-lg btn-block" href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">Abrir <?= h(parse_url(webchat_link(), PHP_URL_HOST)) ?></a>
      </div>

      <div class="connect-card">
        <h3>Cliente IRC</h3>
        <p>Si ya usás mIRC, HexChat, Irssi o cualquier cliente IRC, conectate con estos datos:</p>
        <dl class="server-info">
          <div class="server-row">
            <dt>Servidor</dt>
            <dd>
              <code><?= h(setting('irc_server')) ?></code>
              <button class="copy-btn" data-copy="<?= h(setting('irc_server')) ?>" aria-label="Copiar servidor">Copiar</button>
            </dd>
          </div>
          <div class="server-row">
            <dt>Puerto TLS</dt>
            <dd>
              <code><?= h(setting('irc_port_tls')) ?></code>
              <button class="copy-btn" data-copy="<?= h(setting('irc_port_tls')) ?>" aria-label="Copiar puerto TLS">Copiar</button>
            </dd>
          </div>
          <div class="server-row">
            <dt>Puerto sin TLS</dt>
            <dd>
              <code><?= h(setting('irc_port_plain')) ?></code>
              <button class="copy-btn" data-copy="<?= h(setting('irc_port_plain')) ?>" aria-label="Copiar puerto">Copiar</button>
            </dd>
          </div>
          <div class="server-row">
            <dt>Canal general</dt>
            <dd>
              <code><?= h(setting('general_channel')) ?></code>
              <button class="copy-btn" data-copy="<?= h(setting('general_channel')) ?>" aria-label="Copiar canal">Copiar</button>
            </dd>
          </div>
        </dl>
        <p class="hint">Recomendamos usar siempre el puerto TLS para que tu conexión vaya cifrada.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-head">
      <h2>Clientes recomendados</h2>
      <p>Cualquier cliente IRC funciona. Estos son los más usados.</p>
    </div>
    <div class="client-grid">
      <div class="client-card"><strong>mIRC</strong><span>Windows</span></div>
      <div class="client-card"><strong>HexChat</strong><span>Windows / Linux</span></div>
      <div class="client-card"><strong>Irssi</strong><span>Terminal</span></div>
      <div class="client-card"><strong>The Lounge</strong><span>Web / self-hosted</span></div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
