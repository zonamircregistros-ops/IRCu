<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$statusLabels = ['abierto' => 'Abierto', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'cerrado' => 'Cerrado'];
$categoryLabels = ['soporte' => 'Soporte', 'reclamo' => 'Reclamo', 'otro' => 'Otro', 'legal_abuso' => 'Legal / Abuso'];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM tickets WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    flash_set('No se encontró ese ticket.', 'error');
    header('Location: tickets.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $status = in_array($_POST['status'] ?? '', array_keys($statusLabels), true) ? $_POST['status'] : $ticket['status'];
    $reply = trim((string) ($_POST['reply'] ?? ''));

    if ($reply !== '') {
        $stmt = db()->prepare('INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (:id, "staff", :message)');
        $stmt->execute(['id' => $id, 'message' => $reply]);

        $trackingUrl = 'https://chateanos.com/ticket.php?token=' . $ticket['token'];
        $body = "Hola {$ticket['requester_name']},\n\n"
            . "Hay una nueva respuesta del staff en tu ticket \"{$ticket['subject']}\":\n\n"
            . "{$reply}\n\n"
            . "Podés ver la conversación completa y responder acá:\n{$trackingUrl}\n\n"
            . "Saludos,\n" . setting('site_name');
        send_mail($ticket['requester_email'], 'Nueva respuesta a tu ticket — ' . setting('site_name'), $body);
    }

    $stmt = db()->prepare('UPDATE tickets SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $id]);

    audit_log('Ticket actualizado', $ticket['subject'] . ' -> ' . $status);
    flash_set('Ticket actualizado.');
    header('Location: ticket_view.php?id=' . $id);
    exit;
}

$messages = get_ticket_messages($id);

$pageTitle = 'Ticket: ' . $ticket['subject'];
$activeAdminNav = 'tickets';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($ticket['subject']) ?></h1>
  <a class="btn btn-ghost" href="tickets.php">← Volver</a>
</div>

<div class="admin-card">
  <dl class="server-info">
    <div class="server-row"><dt>De</dt><dd><?= h($ticket['requester_name']) ?> · <?= h($ticket['requester_email']) ?></dd></div>
    <div class="server-row"><dt>Categoría</dt><dd><?= h($categoryLabels[$ticket['category']]) ?></dd></div>
    <div class="server-row"><dt>Link de seguimiento</dt><dd><code>/ticket.php?token=<?= h($ticket['token']) ?></code></dd></div>
  </dl>
</div>

<div class="admin-card">
  <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Conversación</h2>
  <div class="ticket-thread">
    <?php foreach ($messages as $msg): ?>
      <div class="ticket-message ticket-message-<?= h($msg['sender']) ?>">
        <span class="ticket-message-author"><?= $msg['sender'] === 'staff' ? 'Staff' : h($ticket['requester_name']) ?></span>
        <p><?= nl2br(h($msg['message'])) ?></p>
        <span class="ticket-message-date"><?= h(date('d/m/Y H:i', strtotime($msg['created_at']))) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <form class="admin-form" method="post" action="ticket_view.php?id=<?= (int) $id ?>" style="margin-top:20px;">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="reply">Responder (se envía por email al usuario)</label>
      <textarea id="reply" name="reply" style="min-height: 100px;"></textarea>
    </div>
    <div class="form-group">
      <label for="status">Estado</label>
      <select id="status" name="status">
        <?php foreach ($statusLabels as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $ticket['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
