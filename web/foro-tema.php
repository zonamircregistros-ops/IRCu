<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? $_POST['topic_id'] ?? 0);
$topic = $id > 0 ? get_forum_topic($id, true) : null;

if (!$topic) {
    http_response_code(404);
    $pageTitle = 'Tema no encontrado';
    $activeNav = 'gestiones';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
      <div class="container">
        <h1>Tema no encontrado</h1>
        <p>Puede que todavía esté en revisión o haya sido eliminado. Volvé al <a href="/foro.php">foro</a>.</p>
      </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $topic['title'];
$activeNav = 'gestiones';
$pageDescription = 'Foro — ' . setting('site_name');
$extraStyles = ['/css/admin.css'];

$errors = [];
$replySuccess = false;

$replyForm = ['author_nick' => '', 'author_email' => '', 'body' => ''];

if (!$topic['is_locked'] && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $replyForm['author_nick'] = trim((string) ($_POST['author_nick'] ?? ''));
    $replyForm['author_email'] = trim((string) ($_POST['author_email'] ?? ''));
    $replyForm['body'] = trim((string) ($_POST['body'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $replySuccess = true;
    } elseif (!rate_limit_check('foro_responder', 20, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        if ($replyForm['author_nick'] === '' || $replyForm['author_email'] === '' || $replyForm['body'] === '') {
            $errors[] = 'Completá todos los campos obligatorios.';
        } elseif (!filter_var($replyForm['author_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        } elseif (contains_blocked_domain($replyForm['body'])) {
            $errors[] = 'El mensaje contiene un link no permitido.';
        } elseif (!captcha_check((string) ($_POST['captcha_token'] ?? ''), (string) ($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'La respuesta de seguridad no es correcta.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare(
                'INSERT INTO forum_replies (topic_id, author_nick, author_email, body) VALUES (:topic_id, :nick, :email, :body)'
            );
            $stmt->execute([
                'topic_id' => $topic['id'],
                'nick' => $replyForm['author_nick'],
                'email' => $replyForm['author_email'],
                'body' => $replyForm['body'],
            ]);
            $replySuccess = true;
            $replyForm = ['author_nick' => '', 'author_email' => '', 'body' => ''];
        }
    }
}

$replies = get_forum_replies($topic['id'], true);

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/foro.php">← Foro</a>
    <h1><?= h($topic['title']) ?></h1>
    <p>por <?= h($topic['author_nick']) ?> · <?= h(date('d/m/Y H:i', strtotime($topic['created_at']))) ?></p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <div class="ticket-thread">
      <div class="ticket-message ticket-message-usuario">
        <span class="ticket-message-author"><?= h($topic['author_nick']) ?></span>
        <p><?= nl2br(h($topic['body'])) ?></p>
      </div>
      <?php foreach ($replies as $r): ?>
        <div class="ticket-message ticket-message-usuario">
          <span class="ticket-message-author"><?= h($r['author_nick']) ?></span>
          <p><?= nl2br(h($r['body'])) ?></p>
          <span class="ticket-message-date"><?= h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($topic['is_locked']): ?>
      <p class="empty-note" style="margin-top:24px;">Este tema está cerrado y no admite más respuestas.</p>
    <?php else: ?>
      <?php if ($replySuccess): ?>
        <div class="flash flash-success" style="margin-top:24px;">Tu respuesta quedó en revisión. Se va a mostrar acá una vez aprobada.</div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error" style="margin-top:24px;"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card" style="margin-top:24px;">
        <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Responder</h2>
        <form class="admin-form" method="post" action="/foro-tema.php?id=<?= (int) $topic['id'] ?>">
          <input type="hidden" name="topic_id" value="<?= (int) $topic['id'] ?>">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="author_nick">Tu nick</label>
            <input type="text" id="author_nick" name="author_nick" value="<?= h($replyForm['author_nick']) ?>" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="author_email">Email de contacto</label>
            <input type="email" id="author_email" name="author_email" value="<?= h($replyForm['author_email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="body">Respuesta</label>
            <textarea id="body" name="body" required style="min-height: 120px;"><?= h($replyForm['body']) ?></textarea>
          </div>

          <?= captcha_field() ?>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Responder</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
