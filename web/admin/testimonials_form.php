<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$item = [
    'author_nick' => '',
    'quote' => '',
    'years_in_network' => '',
    'avatar_url' => '',
    'sort_order' => 0,
    'is_active' => 1,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM testimonials WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('No se encontró ese testimonio.', 'error');
        header('Location: testimonials.php');
        exit;
    }
    $item = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $item['author_nick'] = trim((string) ($_POST['author_nick'] ?? ''));
    $item['quote'] = trim((string) ($_POST['quote'] ?? ''));
    $item['years_in_network'] = trim((string) ($_POST['years_in_network'] ?? ''));
    $item['avatar_url'] = trim((string) ($_POST['avatar_url'] ?? ''));
    $item['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
    $item['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($item['author_nick'] === '' || $item['quote'] === '') {
        $errors[] = 'El nick y la frase son obligatorios.';
    }

    if (empty($errors)) {
        try {
            $uploadedAvatar = handle_uploaded_image('avatar_file', 'testimonials');
            if ($uploadedAvatar !== null) {
                $item['avatar_url'] = $uploadedAvatar;
            }
        } catch (\RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $params = [
            'author_nick' => $item['author_nick'],
            'quote' => $item['quote'],
            'years_in_network' => $item['years_in_network'] !== '' ? (int) $item['years_in_network'] : null,
            'avatar_url' => $item['avatar_url'] ?: null,
            'sort_order' => $item['sort_order'],
            'is_active' => $item['is_active'],
        ];

        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE testimonials SET author_nick = :author_nick, quote = :quote, years_in_network = :years_in_network,
                 avatar_url = :avatar_url, sort_order = :sort_order, is_active = :is_active WHERE id = :id'
            );
            $params['id'] = $id;
            $stmt->execute($params);
            flash_set('Testimonio actualizado.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO testimonials (author_nick, quote, years_in_network, avatar_url, sort_order, is_active)
                 VALUES (:author_nick, :quote, :years_in_network, :avatar_url, :sort_order, :is_active)'
            );
            $stmt->execute($params);
            flash_set('Testimonio creado.');
        }
        header('Location: testimonials.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Editar testimonio' : 'Nuevo testimonio';
$activeAdminNav = 'testimonials';
require __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-topbar">
  <h1><?= h($pageTitle) ?></h1>
  <a class="btn btn-ghost" href="testimonials.php">← Volver</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash-error"><?= h(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" action="testimonials_form.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

    <div class="form-group">
      <label for="author_nick">Nick</label>
      <input type="text" id="author_nick" name="author_nick" value="<?= h($item['author_nick']) ?>" required maxlength="60">
    </div>

    <div class="form-group">
      <label for="quote">Frase</label>
      <textarea id="quote" name="quote" required style="min-height: 100px;"><?= h($item['quote']) ?></textarea>
    </div>

    <div class="form-group">
      <label for="years_in_network">Años en la red (opcional)</label>
      <input type="number" id="years_in_network" name="years_in_network" value="<?= h((string) $item['years_in_network']) ?>" min="0" max="99">
    </div>

    <div class="form-group">
      <label for="avatar_url">URL de avatar (opcional)</label>
      <div class="upload-row">
        <?php if (!empty($item['avatar_url'])): ?><img src="<?= h($item['avatar_url']) ?>" alt=""><?php endif; ?>
        <input type="url" id="avatar_url" name="avatar_url" value="<?= h($item['avatar_url']) ?>" placeholder="https://..." style="flex:1;">
      </div>
    </div>

    <div class="form-group">
      <label for="avatar_file">O subí una imagen (opcional, tiene prioridad sobre la URL)</label>
      <input type="file" id="avatar_file" name="avatar_file" accept="image/png,image/jpeg,image/webp,image/gif">
    </div>

    <div class="form-group">
      <label for="sort_order">Orden (menor primero)</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= (int) $item['sort_order'] ?>">
    </div>

    <label class="form-check">
      <input type="checkbox" name="is_active" <?= $item['is_active'] ? 'checked' : '' ?>>
      Activo (visible en el sitio)
    </label>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear testimonio' ?></button>
      <a class="btn btn-ghost" href="testimonials.php">Cancelar</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
