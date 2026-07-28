<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Crear mi perfil';
$activeNav = 'staff';
$pageDescription = 'Creá tu perfil público en ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$errors = [];
$success = false;

$form = [
    'nick' => '',
    'email' => '',
    'bio' => '',
    'favorite_channels' => '',
    'social_links' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['nick'] = trim((string) ($_POST['nick'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['bio'] = trim((string) ($_POST['bio'] ?? ''));
    $form['favorite_channels'] = trim((string) ($_POST['favorite_channels'] ?? ''));
    $form['social_links'] = trim((string) ($_POST['social_links'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } elseif (!rate_limit_check('perfil_crear', 5, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        if ($form['nick'] === '' || $form['email'] === '') {
            $errors[] = 'El nick y el email son obligatorios.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        } elseif (contains_blocked_domain($form['bio'] . ' ' . $form['social_links'])) {
            $errors[] = 'El perfil contiene un link no permitido.';
        } elseif (!captcha_check((string) ($_POST['captcha_token'] ?? ''), (string) ($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'La respuesta de seguridad no es correcta.';
        }

        $avatarUrl = null;
        if (empty($errors)) {
            try {
                $avatarUrl = handle_uploaded_image('avatar_file', 'profiles');
            } catch (\RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            $verifyToken = random_token();
            try {
                $stmt = db()->prepare(
                    'INSERT INTO user_profiles (nick, email, bio, favorite_channels, social_links, avatar_url, email_verify_token)
                     VALUES (:nick, :email, :bio, :channels, :social, :avatar, :token)
                     ON DUPLICATE KEY UPDATE email = VALUES(email), bio = VALUES(bio),
                       favorite_channels = VALUES(favorite_channels), social_links = VALUES(social_links),
                       avatar_url = COALESCE(VALUES(avatar_url), avatar_url), status = "pendiente",
                       email_verified = 0, email_verify_token = VALUES(email_verify_token)'
                );
                $stmt->execute([
                    'nick' => $form['nick'],
                    'email' => $form['email'],
                    'bio' => $form['bio'] ?: null,
                    'channels' => $form['favorite_channels'] ?: null,
                    'social' => $form['social_links'] ?: null,
                    'avatar' => $avatarUrl,
                    'token' => $verifyToken,
                ]);

                $verifyUrl = 'https://chateanos.com/verificar.php?type=profile&token=' . $verifyToken;
                $body = "Hola {$form['nick']},\n\n"
                    . "Recibimos tu perfil público. Confirmá tu email haciendo clic acá:\n{$verifyUrl}\n\n"
                    . "El staff lo va a revisar antes de publicarlo.\n\nSaludos,\n" . setting('site_name');
                send_mail($form['email'], 'Recibimos tu perfil — ' . setting('site_name'), $body);

                $success = true;
            } catch (PDOException $e) {
                $errors[] = 'Ocurrió un error al guardar el perfil.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/perfiles.php">← Perfiles</a>
    <h1>Crear mi perfil</h1>
    <p>Compartí un perfil público con la comunidad. Es completamente opcional.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Perfil recibido</h2>
        <p>Te mandamos un email de confirmación. Cuando el staff lo apruebe, se va a publicar en <a href="/perfiles.php">Perfiles</a>.</p>
        <a class="btn btn-ghost" href="/perfiles.php">Volver a Perfiles</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/perfil-crear.php" enctype="multipart/form-data">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="nick">Tu nick</label>
            <input type="text" id="nick" name="nick" value="<?= h($form['nick']) ?>" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="email">Email de contacto</label>
            <input type="email" id="email" name="email" value="<?= h($form['email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="bio">Bio corta</label>
            <textarea id="bio" name="bio" maxlength="280"><?= h($form['bio']) ?></textarea>
          </div>

          <div class="form-group">
            <label for="favorite_channels">Salas favoritas (opcional)</label>
            <input type="text" id="favorite_channels" name="favorite_channels" value="<?= h($form['favorite_channels']) ?>" maxlength="255" placeholder="#Chateanos, #Argentina...">
          </div>

          <div class="form-group">
            <label for="social_links">Redes sociales (opcional, texto libre)</label>
            <input type="text" id="social_links" name="social_links" value="<?= h($form['social_links']) ?>" maxlength="255">
          </div>

          <div class="form-group">
            <label for="avatar_file">Avatar (opcional)</label>
            <input type="file" id="avatar_file" name="avatar_file" accept="image/png,image/jpeg,image/webp,image/gif">
          </div>

          <?= captcha_field() ?>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enviar perfil</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
