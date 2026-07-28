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

<section class="section section-alt" id="quienes-somos">
  <div class="container">
    <div class="section-head">
      <h2>¿Quiénes somos?</h2>
      <p>Diez años de historia no se resumen en un eslogan, pero lo intentamos.</p>
    </div>

    <div class="about-story">
      <p>
        Somos <strong><?= h(setting('site_name')) ?> IRC Network</strong>, una red con más de diez años en el IRC.
        Antes fuimos <strong>BuenChat</strong>: un chat pensado por y para latinoamericanos, 100% argentino en su
        origen, nacido de la necesidad de tener un lugar propio para seguir hablando cuando las viejas redes de
        siempre — las que todos recuerdan de los 2000 — empezaron a apagarse una por una.
      </p>
      <p>
        Durante años sostuvimos la red sobre <strong>UnrealIRCd</strong> con servicios de <strong>Anope</strong>: un
        combo sólido, probado, el que usaba medio IRC hispano. Funcionó, y funcionó bien, durante mucho tiempo. Pero
        las comunidades cambian, crecen, piden cosas nuevas — bouncers, historial persistente, soporte real de
        IRCv3 — y en algún momento ese stack, tan noble como era, dejó de alcanzar. No por culpa de nadie: los
        proyectos maduran distinto, y el nuestro necesitaba otra cosa.
      </p>
      <p>
        Así que en 2026 tomamos una decisión que dio miedo: <strong>escalar de cero</strong>. Dejar de parchar y
        empezar a construir la red que queríamos tener, no la que habíamos heredado. Nos renombramos a
        <strong>Chateanos</strong> y, en paralelo, un proyecto que hasta entonces era casi un secreto entre dos
        IRCops se convirtió en el corazón de la red nueva: <strong>Natasha IRCd</strong>, un daemon de IRC escrito
        en <strong>Go</strong> desde cero, con servicios y bouncer integrados en el mismo binario, y un soporte del
        <strong>97,5% de IRCv3</strong>. Nada de piezas sueltas atadas con alambre: todo pensado para funcionar
        junto, desde el día uno.
      </p>
      <p>
        Natasha nació de <strong>Nairobi</strong> y <strong>Helsinki</strong>, dos nicks que cualquiera que haya
        estado en la vieja BuenChat va a reconocer. Lo que empezó como un experimento de fin de semana —"¿y si
        hacemos nuestro propio ircd?"— terminó siendo la base técnica de todo lo que ves hoy: la red, el bouncer,
        los servicios. Le pusieron el nombre de Natasha casi de chiste, una madrugada, y quedó para siempre.
      </p>
      <p>
        Si estás leyendo esto y usaste BuenChat alguna vez: gracias por seguir acá. Si llegaste ahora, bienvenido a
        una comunidad que decidió, después de diez años, que valía la pena empezar de nuevo para seguir otros diez.
      </p>
    </div>

    <div class="timeline">
      <div class="timeline-item">
        <span class="timeline-year">2016</span>
        <div class="timeline-body">
          <h3>Nace BuenChat</h3>
          <p>Un grupo de amigos levanta un servidor casero para no perder contacto con la comunidad de Hispano y LatinChat, que se estaba apagando de a poco.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2017</span>
        <div class="timeline-body">
          <h3>Los primeros cien</h3>
          <p>La red llega a cien usuarios simultáneos y abre su primer canal regional, #Argentina.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2018</span>
        <div class="timeline-body">
          <h3>Llega el staff</h3>
          <p>Se suman los primeros IRCops externos a los fundadores. Nace #Ayuda para sostener a los usuarios nuevos.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2019</span>
        <div class="timeline-body">
          <h3>La semana que la red cayó</h3>
          <p>Un ataque DDoS masivo deja la red caída casi una semana entera. Se migra a un proveedor más robusto y no se vuelve a caer así.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2020</span>
        <div class="timeline-body">
          <h3>La red explota</h3>
          <p>En plena pandemia, de 200 usuarios simultáneos se pasa a más de 1500. La gente necesitaba compañía y BuenChat estaba ahí.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2021</span>
        <div class="timeline-body">
          <h3>Se profesionaliza la casa</h3>
          <p>TLS obligatorio, primeros bots de moderación y la red pasa a correr sobre UnrealIRCd + Anope de forma estable.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2022</span>
        <div class="timeline-body">
          <h3>Latinoamérica entera</h3>
          <p>Se abren canales por país: México, Chile, Colombia y España se suman de forma oficial a la comunidad.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2023</span>
        <div class="timeline-body">
          <h3>El techo del stack viejo</h3>
          <p>UnrealIRCd y Anope empiezan a quedarse cortos: falta soporte moderno de IRCv3 y los servicios no escalan como se necesita.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2024</span>
        <div class="timeline-body">
          <h3>Un experimento secreto</h3>
          <p>Nairobi y Helsinki arrancan, casi en secreto y "por diversión", un IRCd propio escrito en Go. No sabían que terminaría reemplazando todo.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2025</span>
        <div class="timeline-body">
          <h3>Nace el bouncer</h3>
          <p>El prototipo, ya bautizado Natasha, suma un bouncer integrado y se prueba en un canal beta con voluntarios de la comunidad.</p>
        </div>
      </div>
      <div class="timeline-item">
        <span class="timeline-year">2026</span>
        <div class="timeline-body">
          <h3>Nace Chateanos</h3>
          <p>BuenChat se relanza oficialmente como Chateanos, corriendo 100% sobre Natasha IRCd, con servicios y bouncer propios desde el día uno.</p>
        </div>
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
