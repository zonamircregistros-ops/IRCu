<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM forum_topics WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$topic = $stmt->fetch();

if (!$topic) {
    flash_set('No se encontró ese tema.', 'error');
    header('Location: forum.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'toggle_lock') {
        $stmt = db()->prepare('UPDATE forum_topics SET is_locked = :locked WHERE id = :id');
        $stmt->execute(['locked' => $topic['is_locked'] ? 0 : 1, 'id' => $id]);
        audit_log('Tema de foro ' . ($topic['is_locked'] ? 'reabierto' : 'cerrado'), $topic['title']);
        flash_set('Tema actualizado.');
    } elseif ($action === 'reply_status') {
        $replyId = (int) ($_POST['reply_id'] ?? 0);
        $status = in_array($_POST['reply_status'] ?? '', ['pendiente', 'aprobado', 'rechazado'], true) ? $_POST['reply_status'] : 'pendiente';
        $stmt = db()->prepare('UPDATE forum_replies SET status = :status WHERE id = :id AND topic_id = :topic_id');
        $stmt->execute(['status' => $status, 'id' => $replyId, 'topic_id' => $id]);
        audit_log('Respuesta de foro actualizada', 'id ' . $replyId . ' -> ' . $status);
        flash_set('Respuesta actualizada.');
    } elseif ($action === 'reply_delete') {
        $replyId = (int) ($_POST['reply_id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM forum_replies WHERE id = :id AND topic_id = :topic_id');
        $stmt->execute(['id' => $replyId, 'topic_id' => $id]);
        audit_log('Respuesta de foro eliminada', 'id ' . $replyId);
        flash_set('Respuesta eliminada.');
    }
    header('Location: forum_topic_view.php?id=' . $id);
    exit;
}

$replies = get_forum_replies($id, false);
$replyStatusLabels = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];

$pageTitle = $topic['title'];
$activeAdminNav = 'forum';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($topic['title']) ?></h1>
  <a class="btn btn-ghost" href="forum.php">← Volver</a>
</div>

<div class="admin-card">
  <dl class="server-info">
    <div class="server-row"><dt>Autor</dt><dd><?= h($topic['author_nick']) ?> · <?= h($topic['author_email']) ?></dd></div>
    <div class="server-row"><dt>Fecha</dt><dd><?= h(date('d/m/Y H:i', strtotime($topic['created_at']))) ?></dd></div>
  </dl>
  <p style="white-space: pre-wrap;"><?= h($topic['body']) ?></p>
  <form method="post" action="forum_topic_view.php?id=<?= (int) $id ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="toggle_lock">
    <button class="btn btn-ghost btn-sm" type="submit"><?= $topic['is_locked'] ? 'Reabrir tema' : 'Cerrar tema' ?></button>
  </form>
</div>

<div class="admin-card">
  <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Respuestas (<?= count($replies) ?>)</h2>
  <?php if (empty($replies)): ?>
    <p class="empty-note">Todavía no hay respuestas.</p>
  <?php endif; ?>
  <?php foreach ($replies as $r): ?>
    <div class="ticket-message ticket-message-usuario" style="margin-bottom:12px;">
      <span class="ticket-message-author"><?= h($r['author_nick']) ?> · <?= h($r['author_email']) ?></span>
      <p><?= nl2br(h($r['body'])) ?></p>
      <span class="ticket-message-date"><?= h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></span>
      <form method="post" action="forum_topic_view.php?id=<?= (int) $id ?>" style="display:flex; gap:6px; align-items:center; margin-top:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reply_status">
        <input type="hidden" name="reply_id" value="<?= (int) $r['id'] ?>">
        <select name="reply_status" onchange="this.form.submit()">
          <?php foreach ($replyStatusLabels as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= $r['status'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <form method="post" action="forum_topic_view.php?id=<?= (int) $id ?>" onsubmit="return confirm('¿Eliminar esta respuesta?');" style="margin-top:4px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reply_delete">
        <input type="hidden" name="reply_id" value="<?= (int) $r['id'] ?>">
        <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
