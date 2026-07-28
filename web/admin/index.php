<?php
declare(strict_types=1);
$pageTitle = 'Resumen';
$activeAdminNav = 'dashboard';
require __DIR__ . '/includes/admin_header.php';

$channelCounts = db()->query(
    "SELECT category, COUNT(*) AS total FROM channels WHERE is_active = 1 GROUP BY category"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$staffTotal = (int) db()->query('SELECT COUNT(*) FROM staff WHERE is_active = 1')->fetchColumn();
$totalChannels = array_sum($channelCounts);
$newsTotal = (int) db()->query('SELECT COUNT(*) FROM news WHERE is_published = 1')->fetchColumn();
$servicesTotal = (int) db()->query('SELECT COUNT(*) FROM services WHERE is_active = 1')->fetchColumn();
$pendingRequests = (int) db()->query("SELECT COUNT(*) FROM bnc_requests WHERE status = 'pendiente'")->fetchColumn();
$pendingAppeals = (int) db()->query("SELECT COUNT(*) FROM gline_appeals WHERE status = 'pendiente'")->fetchColumn();
$pendingIrcop = (int) db()->query("SELECT COUNT(*) FROM ircop_applications WHERE status = 'pendiente'")->fetchColumn();
$openTickets = (int) db()->query("SELECT COUNT(*) FROM tickets WHERE status = 'abierto'")->fetchColumn();
?>

<div class="admin-topbar">
  <h1>Resumen</h1>
</div>

<div class="admin-stat-grid">
  <div class="admin-stat">
    <div class="num"><?= (int) $totalChannels ?></div>
    <div class="label">Salas activas</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= (int) ($channelCounts['adultos'] ?? 0) ?></div>
    <div class="label">Salas de adultos</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $staffTotal ?></div>
    <div class="label">Miembros del staff</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $newsTotal ?></div>
    <div class="label">Noticias publicadas</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $servicesTotal ?></div>
    <div class="label">Servicios activos</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $pendingRequests ?></div>
    <div class="label">Solicitudes BNC pendientes</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $pendingAppeals ?></div>
    <div class="label">Apelaciones G-Line pendientes</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $pendingIrcop ?></div>
    <div class="label">Postulaciones IRCop pendientes</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $openTickets ?></div>
    <div class="label">Tickets abiertos</div>
  </div>
</div>

<div class="admin-card">
  <h2 style="margin-top:0; font-family: var(--font-display);">Accesos rápidos</h2>
  <div class="form-actions">
    <a class="btn btn-primary" href="salas_form.php">+ Nueva sala</a>
    <a class="btn btn-ghost" href="staff_form.php">+ Nuevo staff</a>
    <a class="btn btn-ghost" href="news_form.php">+ Nueva noticia</a>
    <a class="btn btn-ghost" href="bnc_requests.php">Ver solicitudes BNC</a>
    <a class="btn btn-ghost" href="tickets.php">Ver tickets</a>
    <a class="btn btn-ghost" href="settings.php">Editar datos del sitio</a>
  </div>
</div>

<?php require __DIR__ . '/includes/admin_footer.php'; ?>
