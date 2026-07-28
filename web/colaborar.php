<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Colaborar';
$activeNav = 'gestiones';
$pageDescription = 'Sumate al equipo de ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$areas = ['moderacion' => 'Moderación / IRCop', 'desarrollo' => 'Desarrollo web', 'radio' => 'Radio / streaming', 'comunidad' => 'Community management', 'otro' => 'Otro'];

$errors = [];
$success = false;

$form = ['full_name' => '', 'email' => '', 'area' => 'moderacion', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['area'] = array_key_exists($_POST['area'] ?? '', $areas) ? $_POST['area'] : 'otro';
    $form['message'] = trim((string) ($_POST['message'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } elseif (!rate_limit_check('colaborar', 5, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        if ($form['full_name'] === '' || $form['email'] === '' || $form['message'] === '') {
            $errors[] = 'Completá todos los campos obligatorios.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        } elseif (!captcha_check((string) ($_POST['captcha_token'] ?? ''), (string) ($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'La respuesta de seguridad no es correcta.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare(
                'INSERT INTO collaboration_applications (full_name, email, area, message) VALUES (:name, :email, :area, :message)'
            );
            $stmt->execute([
                'name' => $form['full_name'],
                'email' => $form['email'],
                'area' => $areas[$form['area']],
                'message' => $form['message'],
            ]);

            $body = "Hola {$form['full_name']},\n\n"
                . "Recibimos tu postulación para colaborar como {$areas[$form['area']]}.\n"
                . "El staff la va a revisar y se va a poner en contacto por este mismo email.\n\nSaludos,\n" . setting('site_name');
            send_mail($form['email'], 'Recibimos tu postulación — ' . setting('site_name'), $body);

            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1>Colaborar con la red</h1>
    <p>Buscamos gente para moderación, desarrollo, radio y comunidad. No hace falta experiencia previa, sí ganas de sumar.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Postulación recibida</h2>
        <p>Gracias por querer sumarte. El staff va a revisar tu postulación y se va a poner en contacto.</p>
        <a class="btn btn-ghost" href="/index.php">Volver al inicio</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/colaborar.php">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="full_name">Tu nombre o nick</label>
            <input type="text" id="full_name" name="full_name" value="<?= h($form['full_name']) ?>" required maxlength="120">
          </div>

          <div class="form-group">
            <label for="email">Email de contacto</label>
            <input type="email" id="email" name="email" value="<?= h($form['email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="area">Área de interés</label>
            <select id="area" name="area">
              <?php foreach ($areas as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= $form['area'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="message">Contanos por qué querés sumarte</label>
            <textarea id="message" name="message" required style="min-height: 140px;"><?= h($form['message']) ?></textarea>
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
