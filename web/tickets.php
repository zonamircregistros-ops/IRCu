<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Abrir un ticket';
$activeNav = 'gestiones';
$pageDescription = 'Soporte, reclamos y consultas en ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$categoryLabels = [
    'soporte' => 'Soporte',
    'reclamo' => 'Reclamo',
    'otro' => 'Otro',
];

$errors = [];
$success = false;
$trackingUrl = null;

$form = [
    'subject' => '',
    'category' => 'soporte',
    'requester_name' => '',
    'requester_email' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['subject'] = trim((string) ($_POST['subject'] ?? ''));
    $form['category'] = array_key_exists($_POST['category'] ?? '', $categoryLabels) ? $_POST['category'] : 'soporte';
    $form['requester_name'] = trim((string) ($_POST['requester_name'] ?? ''));
    $form['requester_email'] = trim((string) ($_POST['requester_email'] ?? ''));
    $form['message'] = trim((string) ($_POST['message'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } else {
        if ($form['subject'] === '' || $form['requester_name'] === '' || $form['requester_email'] === '' || $form['message'] === '') {
            $errors[] = 'Completá todos los campos obligatorios.';
        } elseif (!filter_var($form['requester_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        }

        if (empty($errors)) {
            $token = random_token();

            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO tickets (token, subject, category, requester_name, requester_email)
                 VALUES (:token, :subject, :category, :requester_name, :requester_email)'
            );
            $stmt->execute([
                'token' => $token,
                'subject' => $form['subject'],
                'category' => $form['category'],
                'requester_name' => $form['requester_name'],
                'requester_email' => $form['requester_email'],
            ]);
            $ticketId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (:id, "usuario", :message)'
            );
            $stmt->execute(['id' => $ticketId, 'message' => $form['message']]);
            $pdo->commit();

            $trackingUrl = 'https://chateanos.com/ticket.php?token=' . $token;
            $body = "Hola {$form['requester_name']},\n\n"
                . "Creamos tu ticket \"{$form['subject']}\".\n\n"
                . "Podés seguir la conversación y las respuestas del staff en este link (guardalo, es privado):\n"
                . "{$trackingUrl}\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($form['requester_email'], 'Tu ticket fue creado — ' . setting('site_name'), $body);

            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/gestiones.php">← Volver a Gestiones</a>
    <h1>Abrir un ticket</h1>
    <p>Soporte, reclamos o cualquier otra consulta con el staff.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Ticket creado</h2>
        <?php if ($trackingUrl): ?>
          <p>Te mandamos el link de seguimiento por email. También podés guardarlo desde acá:</p>
          <dl class="server-info">
            <div class="server-row">
              <dd style="width:100%; word-break: break-all;"><code><?= h($trackingUrl) ?></code></dd>
            </div>
          </dl>
        <?php else: ?>
          <p>Gracias, en breve el staff se pone en contacto.</p>
        <?php endif; ?>
        <a class="btn btn-ghost" href="/gestiones.php">Volver a Gestiones</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/tickets.php">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="subject">Asunto</label>
            <input type="text" id="subject" name="subject" value="<?= h($form['subject']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="category">Categoría</label>
            <select id="category" name="category">
              <?php foreach ($categoryLabels as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= $form['category'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="requester_name">Tu nombre o nick</label>
            <input type="text" id="requester_name" name="requester_name" value="<?= h($form['requester_name']) ?>" required maxlength="80">
          </div>

          <div class="form-group">
            <label for="requester_email">Email de contacto</label>
            <input type="email" id="requester_email" name="requester_email" value="<?= h($form['requester_email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="message">Contanos qué necesitás</label>
            <textarea id="message" name="message" required style="min-height: 140px;"><?= h($form['message']) ?></textarea>
          </div>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Crear ticket</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
