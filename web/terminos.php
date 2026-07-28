<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Términos de servicio';
$activeNav = '';
$pageDescription = 'Condiciones de uso del sitio y los servicios de ' . setting('site_name') . '.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1>Términos de servicio</h1>
    <p>Última actualización: julio de 2026.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow legal-content">
    <p>
      Estos términos aplican al sitio web de <?= h(setting('site_name')) ?> y a los servicios que
      ofrecemos desde acá (webchat, Natasha Bouncer, Gestiones, Tickets). El uso de la red de
      IRC en sí se rige además por las <a href="/normas.php">Normas de la comunidad</a>.
    </p>

    <h2>Uso del sitio</h2>
    <p>
      Podés usar el sitio y sus formularios para lo que fueron pensados: pedir soporte, apelar
      una sanción, postularte a IRCop o solicitar el servicio de bouncer. No está permitido usar
      estos formularios para spam, ataques o cualquier intento de comprometer el sitio o sus
      servicios.
    </p>

    <h2>Cuentas de Natasha Bouncer</h2>
    <p>
      Cada cuenta de Natasha es personal e intransferible. Sos responsable de la actividad que
      pase con tu cuenta y tu contraseña. El staff puede suspender una cuenta que comprometa la
      estabilidad del servicio para el resto de la comunidad, según las reglas publicadas en
      <a href="/natasha/">Natasha Bouncer</a>.
    </p>

    <h2>Tickets, apelaciones y postulaciones</h2>
    <p>
      Al enviar un ticket, una apelación o una postulación a IRCop, aceptás que el staff revise
      la información y se contacte por el email que dejaste. Nos reservamos el derecho de
      rechazar cualquier solicitud sin obligación de dar más explicación que la que ya
      proveemos en la respuesta.
    </p>

    <h2>Disponibilidad del servicio</h2>
    <p>
      Hacemos lo posible por mantener la red, el webchat, el bouncer y los demás servicios
      operativos todo el tiempo (ver <a href="/estado.php">Estado del servicio</a>), pero no
      garantizamos disponibilidad del 100%. Los servicios se ofrecen "como están", sin garantías
      de ningún tipo.
    </p>

    <h2>Propiedad</h2>
    <p>
      El código, diseño y contenido del sitio pertenecen a <?= h(setting('site_name')) ?> IRC
      Network. Natasha IRCd es un proyecto propio de la red.
    </p>

    <h2>Cambios</h2>
    <p>
      Podemos actualizar estos términos cuando haga falta. Los cambios importantes se anuncian
      en <a href="/noticias.php">Noticias</a>.
    </p>

    <h2>Ley aplicable</h2>
    <p>
      Estos términos se rigen por las leyes de Argentina, sin perjuicio de las leyes de
      protección al consumidor que puedan aplicarte según tu país de residencia.
    </p>

    <h2>Contacto</h2>
    <p>
      Dudas sobre estos términos: <a href="mailto:<?= h(setting('staff_email')) ?>"><?= h(setting('staff_email')) ?></a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
