<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Nuestra historia';
$activeNav = '';
$pageDescription = 'De BuenChat a Chateanos: diez años de historia y el nacimiento de Natasha IRCd.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Nuestra historia</h1>
    <p>Diez años de historia no se resumen en un eslogan, pero lo intentamos.</p>
  </div>
</section>

<section class="section">
  <div class="container">
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
    <h2>¿Listo para ser parte de esta historia?</h2>
    <p>Diez años después, la puerta sigue abierta.</p>
    <a class="btn btn-primary btn-lg" href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">Entrar al webchat de <?= h(setting('site_name')) ?></a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
