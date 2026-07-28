<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Mis datos personales';
$activeNav = '';
$pageDescription = 'Pedí acceder, exportar o eliminar los datos que nos diste en ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$errors = [];
$success = false;

$form = [
    'email' => '',
    'request_type' => 'exportar',
    'details' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['request_type'] = in_array($_POST['request_type'] ?? '', ['exportar', 'eliminar'], true) ? $_POST['request_type'] : 'exportar';
    $form['details'] = trim((string) ($_POST['details'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } elseif (!rate_limit_check('mis_datos', 5, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        if ($form['email'] === '') {
            $errors[] = 'El email es obligatorio.';
        } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        } elseif (!captcha_check((string) ($_POST['captcha_token'] ?? ''), (string) ($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'La respuesta de seguridad no es correcta.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare(
                'INSERT INTO data_requests (email, request_type, details) VALUES (:email, :type, :details)'
            );
            $stmt->execute([
                'email' => $form['email'],
                'type' => $form['request_type'],
                'details' => $form['details'] ?: null,
            ]);

            $actionLabel = $form['request_type'] === 'eliminar' ? 'eliminar' : 'exportar';
            $body = "Hola,\n\n"
                . "Recibimos tu pedido para {$actionLabel} los datos asociados a este email en " . setting('site_name') . ".\n\n"
                . "El staff lo va a procesar y te va a responder a este mismo correo.\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($form['email'], 'Recibimos tu pedido de datos — ' . setting('site_name'), $body);

            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1>Mis datos personales</h1>
    <p>Pedí acceder, exportar o eliminar los datos que nos diste al usar los formularios del sitio.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Pedido recibido</h2>
        <p>Te mandamos un email de confirmación. El staff va a procesar tu pedido y te va a responder por ese medio.</p>
        <a class="btn btn-ghost" href="/privacidad.php">Volver a Privacidad</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/mis-datos.php">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="email">Tu email (el mismo que usaste en el formulario original)</label>
            <input type="email" id="email" name="email" value="<?= h($form['email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="request_type">¿Qué querés hacer con tus datos?</label>
            <select id="request_type" name="request_type">
              <option value="exportar" <?= $form['request_type'] === 'exportar' ? 'selected' : '' ?>>Exportar (ver qué tenemos guardado)</option>
              <option value="eliminar" <?= $form['request_type'] === 'eliminar' ? 'selected' : '' ?>>Eliminar todo</option>
            </select>
          </div>

          <div class="form-group">
            <label for="details">Detalles (opcional, p.ej. en qué formulario lo enviaste)</label>
            <textarea id="details" name="details" style="min-height: 100px;"><?= h($form['details']) ?></textarea>
          </div>

          <?= captcha_field() ?>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enviar pedido</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
