<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$lang = ($_GET['lang'] ?? 'es') === 'en' ? 'en' : 'es';
$host = setting('bnc_connect_host');
$port = setting('bnc_connect_port');

$t = $lang === 'en' ? [
    'title' => 'How to connect with Natasha Bouncer',
    'intro' => 'Once your account is approved you\'ll get your username and password by email. Here\'s how to plug them into the most common clients.',
    'back' => '← Back to Natasha Bouncer',
    'creds_title' => 'Your connection data',
    'creds_note' => 'Use the username and password from your approval email. Both go together in the server password field as',
    'note_title' => 'Before you start',
    'note_body' => 'Natasha listens without SSL/TLS on this port, so make sure "Use SSL" is turned off for this specific connection.',
] : [
    'title' => 'Cómo conectarte con Natasha Bouncer',
    'intro' => 'Una vez que tu cuenta esté aprobada, te llega el usuario y la contraseña por email. Acá te mostramos cómo cargarlos en los clientes más comunes.',
    'back' => '← Volver a Natasha Bouncer',
    'creds_title' => 'Tus datos de conexión',
    'creds_note' => 'Usá el usuario y la contraseña que te llegaron por email. Los dos van juntos en el campo de contraseña del servidor como',
    'note_title' => 'Antes de empezar',
    'note_body' => 'Natasha escucha sin SSL/TLS en este puerto, así que asegurate de tener "Usar SSL" desactivado para esta conexión en particular.',
];

$pageTitle = $t['title'];
$activeNav = 'servicios';
$pageDescription = $t['intro'];

require __DIR__ . '/../includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/natasha/<?= $lang === 'en' ? 'en.php' : 'index.php' ?>"><?= h($t['back']) ?></a>
    <div class="lang-switch">
      <a href="?lang=es" class="<?= $lang === 'es' ? 'is-active' : '' ?>">Español</a>
      <a href="?lang=en" class="<?= $lang === 'en' ? 'is-active' : '' ?>">English</a>
    </div>
    <h1><?= h($t['title']) ?></h1>
    <p><?= h($t['intro']) ?></p>
  </div>
</section>

<section class="section-tight">
  <div class="container container-narrow">
    <div class="connect-card connect-card-featured">
      <h3><?= h($t['creds_title']) ?></h3>
      <dl class="server-info">
        <div class="server-row">
          <dt>Host</dt>
          <dd><code><?= h($host) ?></code></dd>
        </div>
        <div class="server-row">
          <dt><?= $lang === 'en' ? 'Port' : 'Puerto' ?></dt>
          <dd><code><?= h($port) ?></code> (<?= $lang === 'en' ? 'no SSL' : 'sin SSL' ?>)</dd>
        </div>
      </dl>
      <p class="hint"><?= h($t['creds_note']) ?> <code>usuario:contraseña</code>.</p>
    </div>

    <div class="notice-box" style="margin-top:20px;">
      ⚠️ <strong><?= h($t['note_title']) ?>:</strong> <?= h($t['note_body']) ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container container-narrow">

    <div class="tutorial-step">
      <h2>mIRC</h2>
      <ol>
        <?php if ($lang === 'en'): ?>
          <li>Open <strong>Alt+E</strong> (server list) and click <strong>Add</strong>.</li>
          <li>Description: <code>Chateanos (Natasha)</code>. Server: <code><?= h($host) ?></code>. Port: <code><?= h($port) ?></code>.</li>
          <li>In the <strong>Password</strong> field enter <code>usuario:contraseña</code> (your BNC username and password, no SSL checkbox).</li>
          <li>Select the entry and click <strong>Connect</strong>.</li>
        <?php else: ?>
          <li>Abrí <strong>Alt+E</strong> (lista de servidores) y hacé clic en <strong>Add</strong>.</li>
          <li>Descripción: <code>Chateanos (Natasha)</code>. Server: <code><?= h($host) ?></code>. Port: <code><?= h($port) ?></code>.</li>
          <li>En el campo <strong>Password</strong> escribí <code>usuario:contraseña</code> (tu usuario y contraseña de Natasha, sin tildar SSL).</li>
          <li>Seleccioná la entrada y hacé clic en <strong>Connect</strong>.</li>
        <?php endif; ?>
      </ol>
    </div>

    <div class="tutorial-step">
      <h2>HexChat</h2>
      <ol>
        <?php if ($lang === 'en'): ?>
          <li>Open <strong>Network List</strong> and click <strong>Add</strong> to create "Chateanos (Natasha)".</li>
          <li>Click <strong>Edit</strong>, and under Servers add <code><?= h($host) ?>/<?= h($port) ?></code>.</li>
          <li>In the network's <strong>Password</strong> field enter <code>usuario:contraseña</code>.</li>
          <li>Make sure <strong>"Use SSL for all servers"</strong> is unchecked, then <strong>Connect</strong>.</li>
        <?php else: ?>
          <li>Abrí <strong>Network List</strong> y hacé clic en <strong>Add</strong> para crear "Chateanos (Natasha)".</li>
          <li>Hacé clic en <strong>Edit</strong> y en Servers agregá <code><?= h($host) ?>/<?= h($port) ?></code>.</li>
          <li>En el campo <strong>Password</strong> de la red escribí <code>usuario:contraseña</code>.</li>
          <li>Fijate que <strong>"Use SSL for all servers"</strong> esté destildado, y dale a <strong>Connect</strong>.</li>
        <?php endif; ?>
      </ol>
    </div>

    <div class="tutorial-step">
      <h2>Irssi</h2>
      <ol>
        <?php if ($lang === 'en'): ?>
          <li>Add the network: <code>/network add Chateanos</code></li>
          <li>Add the server: <code>/server add -auto -network Chateanos <?= h($host) ?> <?= h($port) ?> usuario:contraseña</code></li>
          <li>Connect: <code>/connect Chateanos</code></li>
        <?php else: ?>
          <li>Agregá la red: <code>/network add Chateanos</code></li>
          <li>Agregá el servidor: <code>/server add -auto -network Chateanos <?= h($host) ?> <?= h($port) ?> usuario:contraseña</code></li>
          <li>Conectate: <code>/connect Chateanos</code></li>
        <?php endif; ?>
      </ol>
    </div>

    <div class="tutorial-step">
      <h2>The Lounge / Kiwi IRC</h2>
      <ol>
        <?php if ($lang === 'en'): ?>
          <li>When adding a network, set Host to <code><?= h($host) ?></code> and Port to <code><?= h($port) ?></code>, TLS off.</li>
          <li>Set Nick/Username to your BNC username, and Password to your BNC password (not the combined format here — most web clients have separate fields).</li>
        <?php else: ?>
          <li>Al agregar una red, poné Host <code><?= h($host) ?></code> y Puerto <code><?= h($port) ?></code>, con TLS desactivado.</li>
          <li>Poné tu usuario de Natasha en Nick/Usuario, y tu contraseña de Natasha en Password (acá van en campos separados, no combinados).</li>
        <?php endif; ?>
      </ol>
    </div>

    <p class="rules-footnote">
      <?= $lang === 'en'
        ? 'Still stuck? Open a support ticket and staff will help you out.'
        : '¿Seguís trabado? Abrí un ticket de soporte y el staff te ayuda.' ?>
      <a href="/gestiones.php"><?= $lang === 'en' ? 'Open Gestiones' : 'Ir a Gestiones' ?></a>
    </p>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
