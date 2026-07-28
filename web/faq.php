<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Preguntas frecuentes';
$activeNav = '';
$pageDescription = 'Todo lo que necesitás saber sobre IRC y ' . setting('site_name') . '.';

$faqs = [
    [
        '¿Qué es el IRC?',
        'IRC (Internet Relay Chat) es un protocolo de chat en tiempo real que existe desde 1988. Funciona por salas llamadas canales (que empiezan con #) donde mucha gente puede charlar a la vez, además de mensajes privados entre usuarios.',
    ],
    [
        '¿Qué es un canal?',
        'Un canal es una sala de chat grupal, identificada con un nombre que arranca con #, por ejemplo #Chateanos. Cada canal puede tener sus propias reglas, moderadores y tema de charla.',
    ],
    [
        '¿Qué es un nick?',
        'Es el apodo con el que te identificás en la red. Podés usar cualquiera que esté libre; si querés asegurarte de que nadie más lo use, podés registrarlo con NickServ.',
    ],
    [
        '¿Qué son NickServ y ChanServ?',
        'Son los servicios de la red: NickServ te deja registrar y proteger tu nick, y ChanServ hace lo mismo con canales (fundador, moderadores, modos automáticos, etc). En Chateanos estos servicios vienen integrados directamente en Natasha IRCd.',
    ],
    [
        '¿Qué es un IRCop?',
        'Un IRCop (IRC Operator) es parte del staff técnico de la red: tiene permisos especiales para moderar, aplicar sanciones y mantener la infraestructura funcionando. Podés ver quiénes son en la sección Staff, o postularte vos mismo desde Gestiones.',
    ],
    [
        '¿Qué es un bouncer (BNC)?',
        'Es un servicio que mantiene tu conexión al IRC activa todo el tiempo, aunque cierres el cliente o se corte tu internet. Nuestro bouncer se llama Natasha; podés pedirlo gratis desde /natasha.',
    ],
    [
        '¿Necesito instalar algo para chatear?',
        'No. Podés entrar directo desde el navegador con el webchat, sin registrarte ni instalar nada. Si preferís un cliente de escritorio, en Conectar tenés los datos del servidor para mIRC, HexChat, Irssi, etc.',
    ],
    [
        '¿Qué es TLS/SSL y por qué me conviene usarlo?',
        'Es cifrado para tu conexión: evita que alguien en el medio pueda leer lo que mandás. Recomendamos siempre usar el puerto TLS cuando te conectes con un cliente IRC.',
    ],
    [
        '¿Qué es un G-Line?',
        'Es una expulsión de la red aplicada por IP o rango de IPs, generalmente por incumplir las normas. Si creés que te banearon por error, podés apelar desde Gestiones.',
    ],
    [
        '¿Qué es Natasha IRCd?',
        'Es el software que corre toda la red: un IRCd escrito en Go, con servicios (NickServ/ChanServ) y bouncer integrados en un solo daemon, con soporte del 97,5% de IRCv3. Podés leer su historia completa en la sección Quiénes somos.',
    ],
];

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Preguntas frecuentes</h1>
    <p>Lo básico del IRC y de cómo funciona <?= h(setting('site_name')) ?>, explicado rápido.</p>
  </div>
</section>

<section class="section">
  <div class="container container-narrow">
    <div class="faq-list">
      <?php foreach ($faqs as [$question, $answer]): ?>
        <details class="faq-item">
          <summary><?= h($question) ?></summary>
          <p><?= h($answer) ?></p>
        </details>
      <?php endforeach; ?>
    </div>

    <p class="rules-footnote">
      ¿No encontraste lo que buscabas? Escribinos a
      <a href="mailto:<?= h(setting('staff_email')) ?>"><?= h(setting('staff_email')) ?></a>
      o abrí un ticket desde <a href="/gestiones.php">Gestiones</a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
