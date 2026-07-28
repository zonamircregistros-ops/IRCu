<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Perfiles de la comunidad';
$activeNav = 'staff';
$pageDescription = 'Perfiles públicos de usuarios de ' . setting('site_name') . '.';

$profiles = get_profiles(true);

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Perfiles de la comunidad</h1>
    <p>Usuarios que quisieron compartir un perfil público. Es opcional y se revisa antes de publicarse.</p>
    <p><a class="back-link" style="display:inline-block; margin:0;" href="/perfil-crear.php">+ Crear mi perfil</a></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (empty($profiles)): ?>
      <p class="empty-note">Todavía no hay perfiles públicos.</p>
    <?php else: ?>
      <div class="staff-grid">
        <?php foreach ($profiles as $p): ?>
          <div class="staff-card">
            <div class="staff-avatar">
              <?php if (!empty($p['avatar_url'])): ?>
                <img src="<?= h($p['avatar_url']) ?>" alt="">
              <?php else: ?>
                <?= h(mb_strtoupper(mb_substr($p['nick'], 0, 2))) ?>
              <?php endif; ?>
            </div>
            <h3>#<?= h($p['nick']) ?></h3>
            <?php if (!empty($p['bio'])): ?><p><?= h($p['bio']) ?></p><?php endif; ?>
            <?php if (!empty($p['favorite_channels'])): ?><p class="table-note">Salas favoritas: <?= h($p['favorite_channels']) ?></p><?php endif; ?>
            <?php if (!empty($p['social_links'])): ?><p class="table-note"><?= h($p['social_links']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
