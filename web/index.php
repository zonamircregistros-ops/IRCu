<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Inicio';
$activeNav = 'inicio';
$pageDescription = setting('tagline');

$grouped = get_channels_grouped();
$latestNews = get_news_list(true, 5);
$testimonials = get_testimonials();

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
    <h1>Chatea en español,<br><span class="gradient-text-animated">sin vueltas.</span></h1>
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

<?php if (!empty($latestNews)): ?>
<section class="section-tight">
  <div class="container">
    <div class="section-head">
      <h2>Lo último en la red</h2>
      <p>Novedades, lanzamientos y anuncios recientes.</p>
    </div>

    <div class="news-carousel" id="news-carousel">
      <div class="news-carousel-track">
        <?php foreach ($latestNews as $i => $item): ?>
          <a class="news-slide" href="/noticia.php?slug=<?= h($item['slug']) ?>" <?= $i === 0 ? '' : 'aria-hidden="true" tabindex="-1"' ?>>
            <span class="news-date"><?= h(date('d/m/Y', strtotime($item['published_at']))) ?></span>
            <h3><?= h($item['title']) ?></h3>
            <?php if (!empty($item['excerpt'])): ?>
              <p><?= h($item['excerpt']) ?></p>
            <?php endif; ?>
            <span class="news-readmore">Leer más →</span>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (count($latestNews) > 1): ?>
        <button class="carousel-arrow carousel-arrow-prev" id="news-prev" aria-label="Noticia anterior">‹</button>
        <button class="carousel-arrow carousel-arrow-next" id="news-next" aria-label="Noticia siguiente">›</button>
        <div class="carousel-dots" id="news-dots">
          <?php foreach ($latestNews as $i => $item): ?>
            <button class="carousel-dot <?= $i === 0 ? 'is-active' : '' ?>" data-index="<?= $i ?>" aria-label="Ir a la noticia <?= $i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <p style="text-align:center; margin-top: 20px;"><a class="back-link" style="display:inline-block; margin:0;" href="/noticias.php">Ver todas las noticias →</a></p>
  </div>
</section>
<?php endif; ?>

<section class="section section-alt">
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

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>¿Por qué <?= h(setting('site_name')) ?>?</h2>
      <p>No somos solo un chat. Es todo un ecosistema, gratis, para vos.</p>
    </div>

    <div class="feature-grid">
      <div class="feature-card">
        <div class="feature-icon">🤖</div>
        <h3>Bouncer gratis</h3>
        <p>Con Natasha quedás conectado 24/7 aunque cierres el cliente. Gratis hasta 5 redes.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🧑‍💻</div>
        <h3>Servicios propios</h3>
        <p>Git, Wiki, Nube y Webmail para la comunidad, no solo el chat.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📻</div>
        <h3>Radio en vivo</h3>
        <p>Sonando en toda la red mientras charlás, las 24 horas.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🌎</div>
        <h3>Comunidad histórica</h3>
        <p>Diez años de historia latinoamericana en el IRC, desde 2016.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🛡️</div>
        <h3>Moderación activa</h3>
        <p>IRCops reales, presentes, con canales para pedir ayuda o apelar.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📲</div>
        <h3>Multiplataforma</h3>
        <p>Webchat, mIRC, HexChat, Irssi o tu bouncer: entrás como quieras.</p>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($testimonials)): ?>
<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Lo que dice la comunidad</h2>
      <p>Gente real, de toda Latinoamérica.</p>
    </div>

    <div class="testimonial-grid">
      <?php foreach ($testimonials as $t): ?>
        <div class="testimonial-card">
          <p class="testimonial-quote">&ldquo;<?= h($t['quote']) ?>&rdquo;</p>
          <div class="testimonial-author">
            <div class="staff-avatar" style="width:40px;height:40px;font-size:0.9rem;">
              <?php if (!empty($t['avatar_url'])): ?>
                <img src="<?= h($t['avatar_url']) ?>" alt="<?= h($t['author_nick']) ?>">
              <?php else: ?>
                <?= h(mb_strtoupper(mb_substr($t['author_nick'], 0, 2))) ?>
              <?php endif; ?>
            </div>
            <div>
              <strong><?= h($t['author_nick']) ?></strong>
              <?php if (!empty($t['years_in_network'])): ?>
                <span class="testimonial-years"><?= (int) $t['years_in_network'] ?> años en la red</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

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

<section class="section history-teaser">
  <div class="container history-teaser-inner">
    <div>
      <span class="badge">Desde 2016</span>
      <h2>De BuenChat a Chateanos</h2>
      <p>
        Diez años de historia, un rebranding, y un IRCd propio escrito en Go con bouncer y
        servicios integrados. Natasha IRCd nació de un experimento de fin de semana entre dos
        IRCops y hoy es el corazón de toda la red.
      </p>
      <a class="btn btn-ghost" href="/historia.php">Conocé toda la historia →</a>
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
