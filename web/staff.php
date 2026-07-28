<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Staff';
$activeNav = 'staff';
$pageDescription = 'El equipo que mantiene ' . setting('site_name') . ' funcionando.';

$roles = [
    'administrador' => 'Administradores',
    'ircop'         => 'IRCops',
    'soporte'       => 'Soporte',
];

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Nuestro staff</h1>
    <p>Gente real, moderando en vivo. Si necesitás ayuda, buscalos en <code><?= h(setting('general_channel')) ?></code> o escribí a <code><?= h(setting('staff_email')) ?></code>.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php foreach ($roles as $role => $label):
      $members = get_staff_by_role($role);
      if (empty($members)) continue;
    ?>
      <div>
        <h2 class="staff-role-head"><?= h($label) ?></h2>
        <div class="staff-grid">
          <?php foreach ($members as $member): ?>
            <div class="staff-card">
              <div class="staff-avatar">
                <?php if (!empty($member['avatar_url'])): ?>
                  <img src="<?= h($member['avatar_url']) ?>" alt="<?= h($member['nick']) ?>">
                <?php else: ?>
                  <?= h(mb_strtoupper(mb_substr($member['nick'], 0, 2))) ?>
                <?php endif; ?>
              </div>
              <div>
                <h3><?= h($member['nick']) ?></h3>
                <?php if (!empty($member['bio'])): ?>
                  <p><?= h($member['bio']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
