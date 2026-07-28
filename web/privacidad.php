<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Política de privacidad';
$activeNav = '';
$pageDescription = 'Qué datos recolecta ' . setting('site_name') . ' y para qué los usa.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1>Política de privacidad</h1>
    <p>Última actualización: julio de 2026.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow legal-content">
    <p>
      En <?= h(setting('site_name')) ?> tratamos de pedirte solo los datos que necesitamos para
      darte el servicio que estás pidiendo, y de ser claros sobre qué hacemos con ellos.
    </p>

    <h2>¿Qué datos recolectamos?</h2>
    <ul>
      <li><strong>Formularios de contacto</strong> (Natasha Bouncer, apelaciones G-Line, postulaciones a IRCop, tickets de soporte): nick, email y el contenido que nos escribas. La postulación a IRCop pide además nombre real y fecha de nacimiento, porque es información que necesita el staff para evaluarla.</li>
      <li><strong>Apelaciones de G-Line</strong>: la IP o rango que nos indiques vos mismo, para poder ubicar la sanción.</li>
      <li><strong>Analítica propia</strong>: contamos visitas por página y de dónde vienen (referrer), sin guardar tu IP ni usar cookies de rastreo de terceros. Es solo para saber qué contenido se usa.</li>
      <li><strong>Control de spam</strong>: guardamos tu IP asociada a un formulario específico por un rato corto (menos de 24&nbsp;horas) para evitar envíos masivos. Se borra sola.</li>
      <li><strong>Panel de administración</strong>: si sos staff, usamos una cookie de sesión técnica para mantenerte conectado a <code>/admin</code>. No se usa para nada más.</li>
      <li><strong>Reproductor de radio</strong>: guardamos tu preferencia de reproducción/silencio en el almacenamiento local de tu navegador (localStorage), no en un servidor.</li>
    </ul>

    <h2>¿Qué NO hacemos?</h2>
    <ul>
      <li>No vendemos ni compartimos tus datos con terceros.</li>
      <li>No usamos publicidad ni rastreo entre sitios (cross-site tracking).</li>
      <li>No usamos tus datos de contacto para nada distinto de lo que pediste (ej: no te vamos a sumar a una lista de marketing sin avisarte).</li>
    </ul>

    <h2>¿Con quién se comparte?</h2>
    <p>
      Los datos que enviás por estos formularios los ve el staff de <?= h(setting('site_name')) ?>
      para poder responderte. El envío de emails se hace a través de nuestro propio servidor de
      correo, no de un servicio de terceros.
    </p>

    <h2>¿Cuánto tiempo se guardan?</h2>
    <p>
      Las solicitudes, apelaciones, postulaciones y tickets quedan guardados mientras sean
      relevantes para el historial de la red. Si querés que eliminemos tus datos, escribinos
      (ver más abajo) y lo resolvemos.
    </p>

    <h2>Red IRC (Natasha IRCd)</h2>
    <p>
      Esta política cubre el sitio web. La red de IRC en sí (conexiones, canales, logs del
      servidor) se rige por sus propias reglas de retención, que están pensadas para operar la
      red y moderarla, no para el sitio web.
    </p>

    <h2>Tus derechos</h2>
    <p>
      Podés pedirnos en cualquier momento acceder, corregir o eliminar los datos que nos
      diste. Escribinos a <a href="mailto:<?= h(setting('staff_email')) ?>"><?= h(setting('staff_email')) ?></a>
      o abrí un <a href="/tickets.php">ticket</a>.
    </p>

    <h2>Cambios a esta política</h2>
    <p>
      Si cambiamos algo importante en cómo tratamos tus datos, lo vamos a anunciar en
      <a href="/noticias.php">Noticias</a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
