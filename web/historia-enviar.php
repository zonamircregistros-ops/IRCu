<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contar mi historia';
$activeNav = '';
$pageDescription = 'Contá tu historia en ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$errors = [];
$success = false;

$form = ['title' => '', 'author_nick' => '', 'author_email' => '', 'body' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['title'] = trim((string) ($_POST['title'] ?? ''));
    $form['author_nick'] = trim((string) ($_POST['author_nick'] ?? ''));
    $form['author_email'] = trim((string) ($_POST['author_email'] ?? ''));
    $form['body'] = trim((string) ($_POST['body'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } elseif (!rate_limit_check('historia_enviar', 5, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        if ($form['title'] === '' || $form['author_nick'] === '' || $form['author_email'] === '' || $form['body'] === '') {
            $errors[] = 'Completá todos los campos obligatorios.';
        } elseif (!filter_var($form['author_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        } elseif (mb_strlen($form['body']) < 300) {
            $errors[] = 'La historia tiene que tener al menos 300 caracteres.';
        } elseif (contains_blocked_domain($form['body'])) {
            $errors[] = 'La historia contiene un link no permitido.';
        } elseif (!captcha_check((string) ($_POST['captcha_token'] ?? ''), (string) ($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'La respuesta de seguridad no es correcta.';
        }

        if (empty($errors)) {
            $verifyToken = random_token();
            $stmt = db()->prepare(
                'INSERT INTO community_stories (title, author_nick, author_email, body, email_verify_token)
                 VALUES (:title, :nick, :email, :body, :token)'
            );
            $stmt->execute([
                'title' => $form['title'],
                'nick' => $form['author_nick'],
                'email' => $form['author_email'],
                'body' => $form['body'],
                'token' => $verifyToken,
            ]);

            $verifyUrl = 'https://chateanos.com/verificar.php?type=story&token=' . $verifyToken;
            $body = "Hola {$form['author_nick']},\n\n"
                . "Recibimos tu historia \"{$form['title']}\". Confirmá tu email haciendo clic acá:\n{$verifyUrl}\n\n"
                . "El staff la va a revisar antes de publicarla.\n\nSaludos,\n" . setting('site_name');
            send_mail($form['author_email'], 'Recibimos tu historia — ' . setting('site_name'), $body);

            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/historias-comunidad.php">← Historias de la comunidad</a>
    <h1>Contar mi historia</h1>
    <p>Escribí con calma: cómo llegaste, qué te llevás, alguna anécdota. Mínimo 300 caracteres.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Historia recibida</h2>
        <p>Te mandamos un email de confirmación. Cuando el staff la apruebe, se va a publicar.</p>
        <a class="btn btn-ghost" href="/historias-comunidad.php">Volver</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/historia-enviar.php">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="title">Título</label>
            <input type="text" id="title" name="title" value="<?= h($form['title']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="author_nick">Tu nick</label>
            <input type="text" id="author_nick" name="author_nick" value="<?= h($form['author_nick']) ?>" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="author_email">Email de contacto</label>
            <input type="email" id="author_email" name="author_email" value="<?= h($form['author_email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="body">Tu historia</label>
            <textarea id="body" name="body" required style="min-height: 280px;"><?= h($form['body']) ?></textarea>
          </div>

          <?= captcha_field() ?>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enviar historia</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
