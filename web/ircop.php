<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Postularme a IRCop';
$activeNav = 'gestiones';
$pageDescription = 'Postulate para ser IRCop en ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$errors = [];
$success = false;

$form = [
    'username' => '',
    'real_name' => '',
    'age' => '',
    'birthdate' => '',
    'email' => '',
    'user_history' => '',
    'notable_history' => '',
    'reason' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        $form[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } elseif (!rate_limit_check('ircop', 5, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        $required = ['username', 'real_name', 'age', 'birthdate', 'email', 'user_history', 'reason'];
        foreach ($required as $key) {
            if ($form[$key] === '') {
                $errors[] = 'Completá todos los campos obligatorios.';
                break;
            }
        }
        if (empty($errors) && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        }
        if (empty($errors) && (!ctype_digit($form['age']) || (int) $form['age'] < 13 || (int) $form['age'] > 120)) {
            $errors[] = 'La edad no es válida.';
        }
        if (empty($errors)) {
            $birthTimestamp = strtotime($form['birthdate']);
            if ($birthTimestamp === false) {
                $errors[] = 'La fecha de nacimiento no es válida.';
            }
        }
        if (empty($errors) && !captcha_check((string) ($_POST['captcha_token'] ?? ''), (string) ($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'La respuesta de seguridad no es correcta.';
        }

        if (empty($errors)) {
            $verifyToken = random_token();
            $stmt = db()->prepare(
                'INSERT INTO ircop_applications (username, real_name, age, birthdate, email, user_history, notable_history, reason, email_verify_token)
                 VALUES (:username, :real_name, :age, :birthdate, :email, :user_history, :notable_history, :reason, :token)'
            );
            $stmt->execute([
                'username' => $form['username'],
                'real_name' => $form['real_name'],
                'age' => (int) $form['age'],
                'birthdate' => date('Y-m-d', $birthTimestamp),
                'email' => $form['email'],
                'user_history' => $form['user_history'],
                'notable_history' => $form['notable_history'] ?: null,
                'reason' => $form['reason'],
                'token' => $verifyToken,
            ]);

            $verifyUrl = 'https://chateanos.com/verificar.php?type=ircop&token=' . $verifyToken;
            $body = "Hola {$form['username']},\n\n"
                . "Recibimos tu postulación a IRCop. Confirmá tu email haciendo clic acá:\n{$verifyUrl}\n\n"
                . "El staff la va a revisar y se va a poner en contacto por este mismo email.\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($form['email'], 'Confirmá tu postulación a IRCop — ' . setting('site_name'), $body);

            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/gestiones.php">← Volver a Gestiones</a>
    <h1>Postularme a IRCop</h1>
    <p>Contanos quién sos y por qué querés sumarte al staff técnico de <?= h(setting('site_name')) ?>.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Postulación recibida</h2>
        <p>Gracias por postularte. El staff va a revisar tu solicitud y se va a poner en contacto por email.</p>
        <a class="btn btn-ghost" href="/gestiones.php">Volver a Gestiones</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/ircop.php">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="username">Tu nick de IRC</label>
            <input type="text" id="username" name="username" value="<?= h($form['username']) ?>" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="real_name">Nombre real</label>
            <input type="text" id="real_name" name="real_name" value="<?= h($form['real_name']) ?>" required maxlength="120">
          </div>

          <div class="form-group">
            <label for="age">Edad</label>
            <input type="number" id="age" name="age" value="<?= h($form['age']) ?>" required min="13" max="120">
          </div>

          <div class="form-group">
            <label for="birthdate">Fecha de nacimiento</label>
            <input type="date" id="birthdate" name="birthdate" value="<?= h($form['birthdate']) ?>" required>
          </div>

          <div class="form-group">
            <label for="email">Email de contacto</label>
            <input type="email" id="email" name="email" value="<?= h($form['email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="user_history">Tu historial como usuario en <?= h(setting('site_name')) ?></label>
            <textarea id="user_history" name="user_history" required style="min-height: 120px;" placeholder="Hace cuánto que estás en la red, qué canales frecuentás, etc."><?= h($form['user_history']) ?></textarea>
          </div>

          <div class="form-group">
            <label for="notable_history">Historial relevante a lo largo de los años (opcional)</label>
            <textarea id="notable_history" name="notable_history" style="min-height: 100px;" placeholder="Experiencia previa como IRCop en otras redes, moderación de comunidades, etc."><?= h($form['notable_history']) ?></textarea>
          </div>

          <div class="form-group">
            <label for="reason">¿Por qué querés ser IRCop?</label>
            <textarea id="reason" name="reason" required style="min-height: 120px;"><?= h($form['reason']) ?></textarea>
          </div>

          <?= captcha_field() ?>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enviar postulación</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
