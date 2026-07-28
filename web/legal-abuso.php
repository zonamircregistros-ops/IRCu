<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Reportar abuso';
$activeNav = '';
$pageDescription = 'Reportá contenido ilegal, acoso o abuso en ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$typeLabels = [
    'copyright' => 'Infracción de derechos de autor',
    'acoso'     => 'Acoso u hostigamiento',
    'contenido_ilegal' => 'Contenido ilegal',
    'otro'      => 'Otro',
];

$errors = [];
$success = false;
$trackingUrl = null;

$form = [
    'reporter_name' => '',
    'reporter_email' => '',
    'abuse_type' => 'otro',
    'target' => '',
    'details' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['reporter_name'] = trim((string) ($_POST['reporter_name'] ?? ''));
    $form['reporter_email'] = trim((string) ($_POST['reporter_email'] ?? ''));
    $form['abuse_type'] = array_key_exists($_POST['abuse_type'] ?? '', $typeLabels) ? $_POST['abuse_type'] : 'otro';
    $form['target'] = trim((string) ($_POST['target'] ?? ''));
    $form['details'] = trim((string) ($_POST['details'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } elseif (!rate_limit_check('legal_abuso', 5, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        if ($form['reporter_name'] === '' || $form['reporter_email'] === '' || $form['target'] === '' || $form['details'] === '') {
            $errors[] = 'Completá todos los campos obligatorios.';
        } elseif (!filter_var($form['reporter_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        } elseif (!captcha_check((string) ($_POST['captcha_token'] ?? ''), (string) ($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'La respuesta de seguridad no es correcta.';
        }

        if (empty($errors)) {
            $token = random_token();
            $verifyToken = random_token();
            $subject = 'Reporte de abuso: ' . $typeLabels[$form['abuse_type']];
            $message = "Contenido/canal/usuario reportado: {$form['target']}\n\n{$form['details']}";

            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO tickets (token, subject, category, requester_name, requester_email, email_verify_token)
                 VALUES (:token, :subject, "legal_abuso", :name, :email, :verify_token)'
            );
            $stmt->execute([
                'token' => $token,
                'subject' => $subject,
                'name' => $form['reporter_name'],
                'email' => $form['reporter_email'],
                'verify_token' => $verifyToken,
            ]);
            $ticketId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (:id, "usuario", :message)'
            );
            $stmt->execute(['id' => $ticketId, 'message' => $message]);
            $pdo->commit();

            $verifyUrl = 'https://chateanos.com/verificar.php?type=ticket&token=' . $verifyToken;
            $trackingUrl = 'https://chateanos.com/ticket.php?token=' . $token;
            $body = "Hola {$form['reporter_name']},\n\n"
                . "Recibimos tu reporte de abuso ({$typeLabels[$form['abuse_type']]}).\n\n"
                . "Confirmá tu email haciendo clic acá:\n{$verifyUrl}\n\n"
                . "Podés seguir la conversación en este link (guardalo, es privado):\n{$trackingUrl}\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($form['reporter_email'], 'Recibimos tu reporte de abuso — ' . setting('site_name'), $body);

            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <h1>Reportar abuso</h1>
    <p>Contenido ilegal, acoso, infracción de derechos de autor u otro tipo de abuso en <?= h(setting('site_name')) ?>. Este canal es distinto del soporte general: lo revisa directamente el staff con prioridad legal.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Reporte recibido</h2>
        <?php if ($trackingUrl): ?>
          <p>Te mandamos el link de seguimiento por email. También podés guardarlo desde acá:</p>
          <dl class="server-info">
            <div class="server-row">
              <dd style="width:100%; word-break: break-all;"><code><?= h($trackingUrl) ?></code></dd>
            </div>
          </dl>
        <?php else: ?>
          <p>Gracias, en breve el staff lo revisa.</p>
        <?php endif; ?>
        <a class="btn btn-ghost" href="/index.php">Volver al inicio</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/legal-abuso.php">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="reporter_name">Tu nombre o nick</label>
            <input type="text" id="reporter_name" name="reporter_name" value="<?= h($form['reporter_name']) ?>" required maxlength="80">
          </div>

          <div class="form-group">
            <label for="reporter_email">Email de contacto</label>
            <input type="email" id="reporter_email" name="reporter_email" value="<?= h($form['reporter_email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="abuse_type">Tipo de reporte</label>
            <select id="abuse_type" name="abuse_type">
              <?php foreach ($typeLabels as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= $form['abuse_type'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="target">Canal, usuario o contenido reportado</label>
            <input type="text" id="target" name="target" value="<?= h($form['target']) ?>" required maxlength="255">
          </div>

          <div class="form-group">
            <label for="details">Detalles del reporte</label>
            <textarea id="details" name="details" required style="min-height: 140px;"><?= h($form['details']) ?></textarea>
          </div>

          <?= captcha_field() ?>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enviar reporte</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
