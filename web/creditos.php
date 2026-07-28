<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Créditos';
$activeNav = '';
$pageDescription = 'Quiénes hacen posible ' . setting('site_name') . '.';

$credits = get_credits_grouped();
$staffAdmins = get_staff_by_role('administrador');

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
  <div class="container">
    <h1>Créditos</h1>
    <p><?= h(setting('site_name')) ?> existe gracias a la gente que la construyó y la sostiene.</p>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php if (!empty($credits['fundadores'])): ?>
      <h2 class="staff-role-head">Fundadores de Natasha IRCd</h2>
      <div class="staff-grid">
        <?php foreach ($credits['fundadores'] as $c): ?>
          <div class="staff-card">
            <div class="staff-avatar"><?= h(mb_strtoupper(mb_substr($c['name'], 0, 2))) ?></div>
            <div>
              <h3><?= h($c['name']) ?></h3>
              <?php if (!empty($c['role_label'])): ?><p><?= h($c['role_label']) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($staffAdmins)): ?>
      <h2 class="staff-role-head">Administración actual</h2>
      <div class="staff-grid">
        <?php foreach ($staffAdmins as $member): ?>
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
              <?php if (!empty($member['bio'])): ?><p><?= h($member['bio']) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($credits['colaboradores'])): ?>
      <h2 class="staff-role-head">Colaboradores</h2>
      <div class="staff-grid">
        <?php foreach ($credits['colaboradores'] as $c): ?>
          <div class="staff-card">
            <div class="staff-avatar"><?= h(mb_strtoupper(mb_substr($c['name'], 0, 2))) ?></div>
            <div>
              <h3><?= h($c['name']) ?></h3>
              <?php if (!empty($c['role_label'])): ?><p><?= h($c['role_label']) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($credits['donantes'])): ?>
      <h2 class="staff-role-head">Donantes</h2>
      <div class="channel-grid">
        <?php foreach ($credits['donantes'] as $c): ?>
          <span class="channel-chip" style="cursor: default;"><?= h($c['name']) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="rules-footnote">
      ¿Querés sumarte a esta lista? Mirá cómo <a href="/donaciones.php">ayudar a sostener la red</a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
