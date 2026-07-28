<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Foro';
$activeNav = 'gestiones';
$pageDescription = 'Tablón de la comunidad de ' . setting('site_name') . '.';

$topics = get_forum_topics(true);

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Foro</h1>
    <p>Tablón asincrónico para charlar de lo que no entra en un solo mensaje de IRC.</p>
    <p><a class="back-link" style="display:inline-block; margin:0;" href="/foro-nuevo.php">+ Nuevo tema</a></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (empty($topics)): ?>
      <p class="empty-note">Todavía no hay temas. ¡Abrí el primero!</p>
    <?php else: ?>
      <div class="admin-card" style="padding:0; overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr><th>Tema</th><th>Autor</th><th>Respuestas</th><th>Fecha</th></tr>
          </thead>
          <tbody>
            <?php foreach ($topics as $t): ?>
              <tr>
                <td><a href="/foro-tema.php?id=<?= (int) $t['id'] ?>"><?= h($t['title']) ?></a> <?= $t['is_locked'] ? '<span class="pill pill-off">Cerrado</span>' : '' ?></td>
                <td><?= h($t['author_nick']) ?></td>
                <td><?= (int) $t['reply_count'] ?></td>
                <td><?= h(date('d/m/Y', strtotime($t['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
