<?php
declare(strict_types=1);
$pageTitle = 'Newsletter';
$activeAdminNav = 'newsletter';
$requiredRole = ['superadmin'];
require __DIR__ . '/includes/admin_header.php';

$confirmedCount = (int) db()->query('SELECT COUNT(*) FROM newsletter_subscribers WHERE confirmed = 1')->fetchColumn();
$pendingCount = (int) db()->query('SELECT COUNT(*) FROM newsletter_subscribers WHERE confirmed = 0')->fetchColumn();

$sent = false;
$sendCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $subject = trim((string) ($_POST['subject'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));

    if ($subject !== '' && $body !== '') {
        $subscribers = db()->query('SELECT email, unsubscribe_token FROM newsletter_subscribers WHERE confirmed = 1')->fetchAll();
        foreach ($subscribers as $sub) {
            $unsubUrl = 'https://chateanos.com/newsletter-baja.php?token=' . $sub['unsubscribe_token'];
            $fullBody = $body . "\n\n---\nPara dejar de recibir este newsletter: {$unsubUrl}";
            send_mail($sub['email'], $subject, $fullBody);
            $sendCount++;
        }
        audit_log('Newsletter enviado', $subject . ' -> ' . $sendCount . ' suscriptores');
        $sent = true;
    }
}
?>

<div class="admin-topbar">
  <h1>Newsletter</h1>
</div>

<div class="admin-card">
  <dl class="server-info">
    <div class="server-row"><dt>Suscriptores confirmados</dt><dd><?= $confirmedCount ?></dd></div>
    <div class="server-row"><dt>Pendientes de confirmar</dt><dd><?= $pendingCount ?></dd></div>
  </dl>
</div>

<?php if ($sent): ?>
  <div class="flash flash-success">Newsletter enviado a <?= $sendCount ?> suscriptores.</div>
<?php endif; ?>

<div class="admin-card">
  <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Redactar y enviar</h2>
  <p style="color: var(--text-dim); font-size:0.88rem;">Se envía a todos los suscriptores confirmados, uno por uno, con link de baja automático. Esto puede tardar según cuántos suscriptores haya.</p>
  <form class="admin-form" method="post" action="newsletter.php" onsubmit="return confirm('¿Enviar este newsletter a <?= $confirmedCount ?> suscriptores?');">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="subject">Asunto</label>
      <input type="text" id="subject" name="subject" required maxlength="160">
    </div>
    <div class="form-group">
      <label for="body">Contenido (texto plano)</label>
      <textarea id="body" name="body" required style="min-height: 220px;"></textarea>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit" <?= $confirmedCount === 0 ? 'disabled' : '' ?>>Enviar a <?= $confirmedCount ?> suscriptores</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
