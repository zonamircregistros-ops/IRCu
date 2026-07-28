<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Apelar una expulsión';
$activeNav = 'gestiones';
$pageDescription = 'Apelá una expulsión (G-Line) de ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$errors = [];
$success = false;

$form = [
    'ip_or_range' => '',
    'username' => '',
    'email' => '',
    'reason' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['ip_or_range'] = trim((string) ($_POST['ip_or_range'] ?? ''));
    $form['username'] = trim((string) ($_POST['username'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['reason'] = trim((string) ($_POST['reason'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } elseif (!rate_limit_check('apelar', 5, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        if ($form['ip_or_range'] === '' || $form['username'] === '' || $form['email'] === '' || $form['reason'] === '') {
            $errors[] = 'Todos los campos son obligatorios.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        }

        if (empty($errors)) {
            $verifyToken = random_token();
            $stmt = db()->prepare(
                'INSERT INTO gline_appeals (ip_or_range, username, email, reason, email_verify_token) VALUES (:ip, :username, :email, :reason, :token)'
            );
            $stmt->execute([
                'ip' => $form['ip_or_range'],
                'username' => $form['username'],
                'email' => $form['email'],
                'reason' => $form['reason'],
                'token' => $verifyToken,
            ]);

            $verifyUrl = 'https://chateanos.com/verificar.php?type=appeal&token=' . $verifyToken;
            $body = "Hola {$form['username']},\n\n"
                . "Recibimos tu apelación por la expulsión (G-Line) de la IP/rango: {$form['ip_or_range']}.\n\n"
                . "Confirmá tu email haciendo clic acá:\n{$verifyUrl}\n\n"
                . "Motivo que enviaste:\n{$form['reason']}\n\n"
                . "El staff la va a revisar y te va a responder a este mismo email.\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($form['email'], 'Recibimos tu apelación — ' . setting('site_name'), $body);

            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/gestiones.php">← Volver a Gestiones</a>
    <h1>Apelar una expulsión</h1>
    <p>Si creés que un G-Line te alcanzó por error, contanos y el staff lo revisa.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Apelación recibida</h2>
        <p>Te mandamos un email de confirmación. El staff va a revisar tu caso y te va a responder por ese mismo medio.</p>
        <a class="btn btn-ghost" href="/gestiones.php">Volver a Gestiones</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/apelar.php">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="ip_or_range">Tu IP o el rango que creés expulsado</label>
            <input type="text" id="ip_or_range" name="ip_or_range" value="<?= h($form['ip_or_range']) ?>" required maxlength="100" placeholder="203.0.113.0 o 203.0.113.0/24">
          </div>

          <div class="form-group">
            <label for="username">Tu nick de IRC</label>
            <input type="text" id="username" name="username" value="<?= h($form['username']) ?>" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="email">Email de contacto</label>
            <input type="email" id="email" name="email" value="<?= h($form['email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="reason">Motivo de la apelación</label>
            <textarea id="reason" name="reason" required style="min-height: 140px;"><?= h($form['reason']) ?></textarea>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enviar apelación</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
