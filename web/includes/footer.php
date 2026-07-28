</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <a href="/index.php" class="brand">
        <span class="brand-mark">#</span>
        <span class="brand-name"><?= h(setting('site_name')) ?></span>
      </a>
      <p><?= h(setting('tagline')) ?></p>
    </div>

    <div class="footer-links">
      <div class="footer-col">
        <h4>Red</h4>
        <a href="/index.php">Inicio</a>
        <a href="/historia.php">Nuestra historia</a>
        <a href="/salas.php">Salas</a>
        <a href="/servicios.php">Servicios</a>
        <a href="/staff.php">Staff</a>
      </div>
      <div class="footer-col">
        <h4>Comunidad</h4>
        <a href="/noticias.php">Noticias</a>
        <a href="/normas.php">Normas</a>
        <a href="/faq.php">Preguntas frecuentes</a>
        <a href="/gestiones.php">Gestiones</a>
        <a href="mailto:<?= h(setting('staff_email')) ?>">Contacto</a>
      </div>
      <div class="footer-col">
        <h4>Acceso</h4>
        <a href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">Webchat</a>
        <a href="/conectar.php"><?= h(setting('irc_server')) ?>:<?= h(setting('irc_port_tls')) ?></a>
        <a href="/natasha/">Natasha BNC</a>
        <a href="/estado.php">Estado del servicio</a>
      </div>
      <div class="footer-col">
        <h4>Más</h4>
        <a href="/creditos.php">Créditos</a>
        <a href="/donaciones.php">Donar</a>
        <a href="/privacidad.php">Privacidad</a>
        <a href="/terminos.php">Términos</a>
      </div>
    </div>
  </div>
  <div class="container footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= h(setting('site_name')) ?> IRC Network. Todos los derechos reservados.</p>
  </div>
</footer>

<?php require __DIR__ . '/radio_player.php'; ?>
<?php require __DIR__ . '/cookie_banner.php'; ?>

<script src="/js/main.js"></script>
</body>
</html>
