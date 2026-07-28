</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <a href="/index.php" class="brand">
        <span class="brand-mark">#</span>
        <span class="brand-name"><?= h(setting('site_name')) ?></span>
      </a>
      <p><?= h(setting('tagline')) ?></p>

      <form class="newsletter-form" method="post" action="/newsletter-suscribir.php">
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <label for="newsletter-email" style="font-size:0.82rem; color:var(--text-dimmer); display:block; margin-bottom:6px;"><?= h(t('footer.newsletter_label')) ?></label>
        <div style="display:flex; gap:8px;">
          <input type="email" id="newsletter-email" name="email" placeholder="tu@email.com" required style="flex:1;">
          <button class="btn btn-primary btn-sm" type="submit"><?= h(t('footer.newsletter_btn')) ?></button>
        </div>
      </form>
    </div>

    <div class="footer-links">
      <div class="footer-col">
        <h4><?= h(t('footer.red')) ?></h4>
        <a href="/index.php"><?= h(t('nav.inicio')) ?></a>
        <a href="/historia.php"><?= h(t('footer.historia')) ?></a>
        <a href="/salas.php"><?= h(t('nav.salas')) ?></a>
        <a href="/ranking.php"><?= h(t('footer.ranking')) ?></a>
        <a href="/servicios.php"><?= h(t('nav.servicios')) ?></a>
        <a href="/staff.php"><?= h(t('nav.staff')) ?></a>
      </div>
      <div class="footer-col">
        <h4><?= h(t('footer.comunidad')) ?></h4>
        <a href="/noticias.php"><?= h(t('nav.noticias')) ?></a>
        <a href="/blog.php"><?= h(t('footer.blog')) ?></a>
        <a href="/foro.php"><?= h(t('footer.foro')) ?></a>
        <a href="/historias-comunidad.php"><?= h(t('footer.historias')) ?></a>
        <a href="/perfiles.php"><?= h(t('footer.perfiles')) ?></a>
        <a href="/eventos.php"><?= h(t('footer.eventos')) ?></a>
        <a href="/encuestas.php"><?= h(t('footer.encuestas')) ?></a>
      </div>
      <div class="footer-col">
        <h4><?= h(t('footer.gestiones')) ?></h4>
        <a href="/normas.php"><?= h(t('footer.normas')) ?></a>
        <a href="/faq.php"><?= h(t('footer.faq')) ?></a>
        <a href="/gestiones.php"><?= h(t('nav.gestiones')) ?></a>
        <a href="/colaborar.php"><?= h(t('footer.colaborar')) ?></a>
        <a href="mailto:<?= h(setting('staff_email')) ?>"><?= h(t('footer.contacto')) ?></a>
      </div>
      <div class="footer-col">
        <h4><?= h(t('footer.acceso')) ?></h4>
        <a href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener"><?= h(t('nav.webchat')) ?></a>
        <a href="/conectar.php"><?= h(setting('irc_server')) ?>:<?= h(setting('irc_port_tls')) ?></a>
        <a href="/clientes.php"><?= h(t('footer.clientes')) ?></a>
        <a href="/natasha/"><?= h(t('footer.natasha')) ?></a>
        <a href="/estado.php"><?= h(t('footer.estado')) ?></a>
      </div>
      <div class="footer-col">
        <h4><?= h(t('footer.mas')) ?></h4>
        <a href="/creditos.php"><?= h(t('footer.creditos')) ?></a>
        <a href="/donaciones.php"><?= h(t('footer.donar')) ?></a>
        <a href="/primeros-pasos.php"><?= h(t('footer.primeros_pasos')) ?></a>
        <a href="/privacidad.php"><?= h(t('footer.privacidad')) ?></a>
        <a href="/terminos.php"><?= h(t('footer.terminos')) ?></a>
        <a href="/mis-datos.php"><?= h(t('footer.mis_datos')) ?></a>
        <a href="/legal-abuso.php"><?= h(t('footer.reportar_abuso')) ?></a>
      </div>
    </div>
  </div>
  <div class="container footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= h(setting('site_name')) ?> IRC Network. <?= h(t('footer.rights')) ?></p>
  </div>
</footer>

<?php require __DIR__ . '/radio_player.php'; ?>
<?php require __DIR__ . '/cookie_banner.php'; ?>

<script src="/js/main.js"></script>
</body>
</html>
