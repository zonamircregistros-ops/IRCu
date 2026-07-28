<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$statusLabels = [
    'abierto' => 'Abierto',
    'aprobado' => 'Aprobado',
    'rechazado' => 'Rechazado',
    'cerrado' => 'Cerrado',
];

$categoryLabels = [
    'soporte' => 'Soporte',
    'reclamo' => 'Reclamo',
    'otro' => 'Otro',
];

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$ticket = $token !== '' ? get_ticket_by_token($token) : null;

$pageTitle = 'Ticket';
$activeNav = 'gestiones';
$extraStyles = ['/css/admin.css'];

$error = null;

if ($ticket && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim((string) ($_POST['message'] ?? ''));
    if ($ticket['status'] === 'cerrado') {
        $error = 'Este ticket ya está cerrado y no admite más respuestas.';
    } elseif ($message === '') {
        $error = 'Escribí un mensaje antes de enviar.';
    } elseif (!rate_limit_check('ticket_responder', 20, 3600)) {
        $error = 'Demasiados mensajes seguidos. Probá de nuevo en un rato.';
    } else {
        $stmt = db()->prepare('INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (:id, "usuario", :message)');
        $stmt->execute(['id' => $ticket['id'], 'message' => $message]);
        db()->prepare('UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['id' => $ticket['id']]);
        header('Location: /ticket.php?token=' . rawurlencode($token));
        exit;
    }
}

$messages = $ticket ? get_ticket_messages((int) $ticket['id']) : [];

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/gestiones.php">← Volver a Gestiones</a>
    <?php if ($ticket): ?>
      <h1><?= h($ticket['subject']) ?></h1>
      <p>
        <span class="pill <?= $ticket['status'] === 'abierto' ? 'pill-on' : ($ticket['status'] === 'rechazado' ? 'pill-off' : '') ?>"><?= h($statusLabels[$ticket['status']]) ?></span>
        · <?= h($categoryLabels[$ticket['category']]) ?>
        · abierto el <?= h(date('d/m/Y', strtotime($ticket['created_at']))) ?>
      </p>
    <?php else: ?>
      <h1>Ticket no encontrado</h1>
      <p>Revisá que el link sea el mismo que te llegó por email.</p>
    <?php endif; ?>
  </div>
</section>

<?php if ($ticket): ?>
<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($error): ?>
      <div class="flash flash-error"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="ticket-thread">
      <?php foreach ($messages as $msg): ?>
        <div class="ticket-message ticket-message-<?= h($msg['sender']) ?>">
          <span class="ticket-message-author"><?= $msg['sender'] === 'staff' ? 'Staff' : h($ticket['requester_name']) ?></span>
          <p><?= nl2br(h($msg['message'])) ?></p>
          <span class="ticket-message-date"><?= h(date('d/m/Y H:i', strtotime($msg['created_at']))) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($ticket['status'] !== 'cerrado'): ?>
      <form class="admin-form" method="post" action="/ticket.php" style="margin-top:24px;">
        <input type="hidden" name="token" value="<?= h($token) ?>">
        <div class="form-group">
          <label for="message">Tu respuesta</label>
          <textarea id="message" name="message" required style="min-height: 100px;"></textarea>
        </div>
        <div class="form-actions">
          <button class="btn btn-primary" type="submit">Enviar respuesta</button>
        </div>
      </form>
    <?php else: ?>
      <p class="empty-note" style="margin-top:24px;">Este ticket está cerrado.</p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
