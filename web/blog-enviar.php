<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Escribir un artículo';
$activeNav = 'noticias';
$pageDescription = 'Mandá un artículo para el blog comunitario de ' . setting('site_name') . '.';
$extraStyles = ['/css/admin.css'];

$errors = [];
$success = false;

$form = [
    'title' => '',
    'author_nick' => '',
    'author_email' => '',
    'body' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['title'] = trim((string) ($_POST['title'] ?? ''));
    $form['author_nick'] = trim((string) ($_POST['author_nick'] ?? ''));
    $form['author_email'] = trim((string) ($_POST['author_email'] ?? ''));
    $form['body'] = trim((string) ($_POST['body'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));

    if ($honeypot !== '') {
        $success = true;
    } elseif (!rate_limit_check('blog_enviar', 5, 3600)) {
        $errors[] = 'Demasiados intentos desde tu conexión. Probá de nuevo más tarde.';
    } else {
        if ($form['title'] === '' || $form['author_nick'] === '' || $form['author_email'] === '' || $form['body'] === '') {
            $errors[] = 'Completá todos los campos obligatorios.';
        } elseif (!filter_var($form['author_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingresá un email válido.';
        } elseif (mb_strlen($form['body']) < 200) {
            $errors[] = 'El artículo tiene que tener al menos 200 caracteres.';
        } elseif (contains_blocked_domain($form['body'])) {
            $errors[] = 'El artículo contiene un link no permitido.';
        } elseif (!captcha_check((string) ($_POST['captcha_token'] ?? ''), (string) ($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'La respuesta de seguridad no es correcta.';
        }

        if (empty($errors)) {
            $slug = slugify($form['title']);
            $suffix = 1;
            $baseSlug = $slug;
            while (get_blog_post_by_slug($slug) !== null) {
                $slug = $baseSlug . '-' . (++$suffix);
            }

            $verifyToken = random_token();
            $stmt = db()->prepare(
                'INSERT INTO blog_posts (title, slug, author_nick, author_email, body, email_verify_token)
                 VALUES (:title, :slug, :author_nick, :author_email, :body, :token)'
            );
            $stmt->execute([
                'title' => $form['title'],
                'slug' => $slug,
                'author_nick' => $form['author_nick'],
                'author_email' => $form['author_email'],
                'body' => $form['body'],
                'token' => $verifyToken,
            ]);

            $verifyUrl = 'https://chateanos.com/verificar.php?type=blog&token=' . $verifyToken;
            $body = "Hola {$form['author_nick']},\n\n"
                . "Recibimos tu artículo \"{$form['title']}\" para el blog comunitario.\n\n"
                . "Confirmá tu email haciendo clic acá:\n{$verifyUrl}\n\n"
                . "El staff lo va a revisar antes de publicarlo.\n\n"
                . "Saludos,\n" . setting('site_name');
            send_mail($form['author_email'], 'Recibimos tu artículo — ' . setting('site_name'), $body);

            $success = true;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-tight">
  <div class="container">
    <a class="back-link" href="/blog.php">← Blog comunitario</a>
    <h1>Escribir un artículo</h1>
    <p>Contanos algo sobre la red, un tutorial, una experiencia. El staff lo revisa antes de publicarlo.</p>
  </div>
</section>

<section class="section section-tight">
  <div class="container container-narrow">
    <?php if ($success): ?>
      <div class="admin-card">
        <h2 style="margin-top:0; font-family: var(--font-display);">Artículo recibido</h2>
        <p>Te mandamos un email de confirmación. Cuando el staff lo apruebe, se va a publicar en el blog.</p>
        <a class="btn btn-ghost" href="/blog.php">Volver al blog</a>
      </div>
    <?php else: ?>
      <?php if (!empty($errors)): ?>
        <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <form class="admin-form" method="post" action="/blog-enviar.php">
          <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="form-group">
            <label for="title">Título</label>
            <input type="text" id="title" name="title" value="<?= h($form['title']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="author_nick">Tu nick</label>
            <input type="text" id="author_nick" name="author_nick" value="<?= h($form['author_nick']) ?>" required maxlength="60">
          </div>

          <div class="form-group">
            <label for="author_email">Email de contacto</label>
            <input type="email" id="author_email" name="author_email" value="<?= h($form['author_email']) ?>" required maxlength="160">
          </div>

          <div class="form-group">
            <label for="body">Contenido (mínimo 200 caracteres, texto plano)</label>
            <textarea id="body" name="body" required style="min-height: 260px;"><?= h($form['body']) ?></textarea>
          </div>

          <?= captcha_field() ?>

          <div class="form-actions">
            <button class="btn btn-primary" type="submit">Enviar artículo</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
