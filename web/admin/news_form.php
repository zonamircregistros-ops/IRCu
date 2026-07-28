<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$news = [
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'body' => '',
    'cover_image' => '',
    'is_published' => 1,
    'published_at' => date('Y-m-d\TH:i'),
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró esa noticia.', 'error');
        header('Location: news.php');
        exit;
    }
    $news = $found;
    $news['published_at'] = date('Y-m-d\TH:i', strtotime($news['published_at']));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $news['title'] = trim((string) ($_POST['title'] ?? ''));
    $slugInput = trim((string) ($_POST['slug'] ?? ''));
    $news['slug'] = $slugInput !== '' ? slugify($slugInput) : slugify($news['title']);
    $news['excerpt'] = trim((string) ($_POST['excerpt'] ?? ''));
    $news['body'] = sanitize_html_content((string) ($_POST['body'] ?? ''));
    $news['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $publishedAtInput = trim((string) ($_POST['published_at'] ?? ''));
    $news['published_at'] = $publishedAtInput !== '' ? $publishedAtInput : date('Y-m-d\TH:i');

    if ($news['title'] === '') {
        $errors[] = 'El título es obligatorio.';
    }
    if ($news['slug'] === '') {
        $errors[] = 'No se pudo generar un slug válido, elegí un título distinto.';
    }
    if (trim(strip_tags($news['body'])) === '' && strpos($news['body'], '<img') === false) {
        $errors[] = 'El contenido es obligatorio.';
    }

    if (empty($errors)) {
        try {
            $uploadedCover = handle_uploaded_image('cover_file', 'news');
        } catch (\RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        if ($uploadedCover !== null) {
            $news['cover_image'] = $uploadedCover;
        }
        $publishedAtSql = date('Y-m-d H:i:s', strtotime($news['published_at']));
        try {
            if ($isEdit) {
                $stmt = db()->prepare(
                    'UPDATE news SET title = :title, slug = :slug, excerpt = :excerpt, body = :body, cover_image = :cover_image,
                     is_published = :is_published, published_at = :published_at WHERE id = :id'
                );
                $stmt->execute([
                    'title' => $news['title'],
                    'slug' => $news['slug'],
                    'excerpt' => $news['excerpt'] ?: null,
                    'body' => $news['body'],
                    'cover_image' => $news['cover_image'] ?: null,
                    'is_published' => $news['is_published'],
                    'published_at' => $publishedAtSql,
                    'id' => $id,
                ]);
                flash_set('Noticia actualizada.');
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO news (title, slug, excerpt, body, cover_image, is_published, published_at)
                     VALUES (:title, :slug, :excerpt, :body, :cover_image, :is_published, :published_at)'
                );
                $stmt->execute([
                    'title' => $news['title'],
                    'slug' => $news['slug'],
                    'excerpt' => $news['excerpt'] ?: null,
                    'body' => $news['body'],
                    'cover_image' => $news['cover_image'] ?: null,
                    'is_published' => $news['is_published'],
                    'published_at' => $publishedAtSql,
                ]);
                flash_set('Noticia creada.');
            }
            header('Location: news.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Ya existe una noticia con ese slug. Elegí otro título o editá el slug a mano.';
        }
    }
}

$pageTitle = $isEdit ? 'Editar noticia' : 'Nueva noticia';
$activeAdminNav = 'news';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="news.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="news_form.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="title">Título</label>
      <input type="text" id="title" name="title" value="<?= h($news['title']) ?>" required maxlength="160">
    </div>

    <div class="form-group">
      <label for="slug">Slug (URL, dejalo vacío para generarlo del título)</label>
      <input type="text" id="slug" name="slug" value="<?= h($news['slug']) ?>" maxlength="180" placeholder="se-genera-solo">
    </div>

    <div class="form-group">
      <label for="excerpt">Resumen breve</label>
      <textarea id="excerpt" name="excerpt" maxlength="280"><?= h($news['excerpt']) ?></textarea>
    </div>

    <div class="form-group">
      <label>Contenido</label>
      <div class="wysiwyg-editor" data-target="body">
        <div class="wysiwyg-toolbar">
          <button type="button" data-command="bold" title="Negrita"><b>B</b></button>
          <button type="button" data-command="italic" title="Cursiva"><i>I</i></button>
          <button type="button" data-command="underline" title="Subrayado"><u>U</u></button>
          <button type="button" data-command="formatBlock" data-value="H2" title="Título">H2</button>
          <button type="button" data-command="formatBlock" data-value="BLOCKQUOTE" title="Cita">❝</button>
          <button type="button" data-command="insertUnorderedList" title="Lista">• Lista</button>
          <button type="button" data-command="insertOrderedList" title="Lista numerada">1. Lista</button>
          <button type="button" data-command="createLink" title="Link">🔗</button>
          <button type="button" data-command="insertImage" title="Imagen">🖼️</button>
          <button type="button" data-command="removeFormat" title="Quitar formato">✕</button>
        </div>
        <div class="wysiwyg-content" contenteditable="true"></div>
      </div>
      <textarea id="body" name="body" hidden><?= $news['body'] ?></textarea>
    </div>

    <div class="form-group">
      <label for="cover_file">Imagen de portada (opcional)</label>
      <div class="upload-row">
        <?php if (!empty($news['cover_image'])): ?><img src="<?= h($news['cover_image']) ?>" alt=""><?php endif; ?>
        <input type="file" id="cover_file" name="cover_file" accept="image/png,image/jpeg,image/webp,image/gif">
      </div>
    </div>

    <div class="form-group">
      <label for="published_at">Fecha de publicación</label>
      <input type="datetime-local" id="published_at" name="published_at" value="<?= h($news['published_at']) ?>">
    </div>

    <label class="form-check">
      <input type="checkbox" name="is_published" <?= $news['is_published'] ? 'checked' : '' ?>>
      Publicada (visible en el sitio)
    </label>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear noticia' ?></button>
      <a class="btn btn-ghost" href="news.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
