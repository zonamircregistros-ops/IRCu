<?php
declare(strict_types=1);
$pageTitle = 'Analítica';
$activeAdminNav = 'analytics';
require __DIR__ . '/includes/admin_header.php';

$totalViews = (int) db()->query('SELECT COUNT(*) FROM page_views')->fetchColumn();

$stmt = db()->prepare('SELECT COUNT(*) FROM page_views WHERE created_at >= :since');
$stmt->execute(['since' => date('Y-m-d H:i:s', strtotime('-30 days'))]);
$last30 = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT path, COUNT(*) AS total FROM page_views WHERE created_at >= :since GROUP BY path ORDER BY total DESC LIMIT 10'
);
$stmt->execute(['since' => date('Y-m-d H:i:s', strtotime('-30 days'))]);
$topPages = $stmt->fetchAll();

$stmt = db()->prepare(
    "SELECT referrer, COUNT(*) AS total FROM page_views
     WHERE referrer IS NOT NULL AND referrer != '' AND referrer NOT LIKE '%chateanos.com%' AND created_at >= :since
     GROUP BY referrer ORDER BY total DESC LIMIT 10"
);
$stmt->execute(['since' => date('Y-m-d H:i:s', strtotime('-30 days'))]);
$topReferrers = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT DATE(created_at) AS day, COUNT(*) AS total FROM page_views
     WHERE created_at >= :since GROUP BY DATE(created_at) ORDER BY day'
);
$stmt->execute(['since' => date('Y-m-d H:i:s', strtotime('-13 days'))]);
$dailyRows = $stmt->fetchAll();
$dailyByDate = array_column($dailyRows, 'total', 'day');

$days = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $days[$d] = (int) ($dailyByDate[$d] ?? 0);
}
$maxDay = max([1, ...array_values($days)]);
?>

<div class="admin-topbar">
  <h1>Analítica</h1>
</div>

<div class="admin-stat-grid">
  <div class="admin-stat">
    <div class="num"><?= $totalViews ?></div>
    <div class="label">Vistas totales</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $last30 ?></div>
    <div class="label">Vistas (últimos 30 días)</div>
  </div>
</div>

<div class="admin-card">
  <h2 style="margin-top:0; font-family: var(--font-display); font-size:1.1rem;">Vistas por día (últimos 14 días)</h2>
  <div class="analytics-bars">
    <?php foreach ($days as $day => $count): ?>
      <div class="analytics-bar-col" title="<?= h($day) ?>: <?= $count ?> vistas">
        <div class="analytics-bar" style="height: <?= max(2, (int) round($count / $maxDay * 100)) ?>%"></div>
        <span class="analytics-bar-label"><?= h(date('d/m', strtotime($day))) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <h2 style="margin: 20px 24px 0; font-family: var(--font-display); font-size:1.1rem;">Páginas más vistas (30 días)</h2>
  <table class="admin-table">
    <thead><tr><th>Página</th><th>Vistas</th></tr></thead>
    <tbody>
      <?php if (empty($topPages)): ?>
        <tr><td colspan="2">Todavía no hay datos suficientes.</td></tr>
      <?php endif; ?>
      <?php foreach ($topPages as $row): ?>
        <tr><td><code><?= h($row['path']) ?></code></td><td><?= (int) $row['total'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="admin-card" style="padding:0; overflow-x:auto;">
  <h2 style="margin: 20px 24px 0; font-family: var(--font-display); font-size:1.1rem;">Referrers externos (30 días)</h2>
  <table class="admin-table">
    <thead><tr><th>Origen</th><th>Vistas</th></tr></thead>
    <tbody>
      <?php if (empty($topReferrers)): ?>
        <tr><td colspan="2">Todavía no hay referrers externos registrados.</td></tr>
      <?php endif; ?>
      <?php foreach ($topReferrers as $row): ?>
        <tr><td><?= h($row['referrer']) ?></td><td><?= (int) $row['total'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<p class="empty-note">No se guarda IP ni identificadores individuales: solo el path visitado, el referrer y la fecha.</p>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
