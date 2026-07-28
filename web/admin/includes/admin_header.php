<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifications.php';
require_login();

/** @var array<int, string> $requiredRole */
if (isset($requiredRole)) {
    require_role($requiredRole);
}

$adminNotifications = get_admin_notifications();
$notificationCount = array_sum(array_column($adminNotifications, 'count'));

/** @var string $pageTitle */
/** @var string $activeAdminNav */
$pageTitle ??= 'Panel';
$activeAdminNav ??= '';

$allRoles = ['superadmin', 'moderador'];
$onlySuperadmin = ['superadmin'];

$adminNavItemsAll = [
    'dashboard'           => ['href' => 'index.php',               'label' => 'Resumen',                'roles' => $allRoles],
    'salas'               => ['href' => 'salas.php',               'label' => 'Salas',                   'roles' => $allRoles],
    'staff'                => ['href' => 'staff.php',              'label' => 'Staff',                   'roles' => $allRoles],
    'news'                 => ['href' => 'news.php',               'label' => 'Noticias',                'roles' => $allRoles],
    'blog'                 => ['href' => 'blog.php',                'label' => 'Blog comunitario',       'roles' => $allRoles],
    'forum'                => ['href' => 'forum.php',               'label' => 'Foro',                   'roles' => $allRoles],
    'stories'               => ['href' => 'stories.php',             'label' => 'Historias',              'roles' => $allRoles],
    'profiles'             => ['href' => 'profiles.php',             'label' => 'Perfiles',                'roles' => $allRoles],
    'events'               => ['href' => 'events.php',               'label' => 'Eventos',                 'roles' => $allRoles],
    'moderation'           => ['href' => 'moderation.php',           'label' => 'Moderación',              'roles' => $allRoles],
    'services'             => ['href' => 'services.php',           'label' => 'Servicios',               'roles' => $allRoles],
    'bnc_networks'         => ['href' => 'bnc_networks.php',       'label' => 'Redes BNC',               'roles' => $allRoles],
    'bnc_requests'         => ['href' => 'bnc_requests.php',       'label' => 'Solicitudes BNC',         'roles' => $allRoles],
    'gline_appeals'        => ['href' => 'gline_appeals.php',      'label' => 'Apelaciones G-Line',      'roles' => $allRoles],
    'ircop_applications'   => ['href' => 'ircop_applications.php', 'label' => 'Postulaciones IRCop',     'roles' => $allRoles],
    'collaboration'        => ['href' => 'collaboration.php',      'label' => 'Colaboración',            'roles' => $allRoles],
    'tickets'              => ['href' => 'tickets.php',            'label' => 'Tickets',                 'roles' => $allRoles],
    'testimonials'         => ['href' => 'testimonials.php',       'label' => 'Testimonios',             'roles' => $allRoles],
    'credits'              => ['href' => 'credits.php',            'label' => 'Créditos',                'roles' => $allRoles],
    'service_status'       => ['href' => 'service_status.php',     'label' => 'Estado del servicio',     'roles' => $allRoles],
    'newsletter'           => ['href' => 'newsletter.php',         'label' => 'Newsletter',              'roles' => $onlySuperadmin],
    'analytics'            => ['href' => 'analytics.php',          'label' => 'Analítica',               'roles' => $onlySuperadmin],
    'data_requests'        => ['href' => 'data_requests.php',      'label' => 'Datos personales',        'roles' => $onlySuperadmin],
    'admins'               => ['href' => 'admins.php',             'label' => 'Administradores',         'roles' => $onlySuperadmin],
    'audit_log'            => ['href' => 'audit_log.php',          'label' => 'Auditoría',               'roles' => $onlySuperadmin],
    'errors'               => ['href' => 'errors.php',             'label' => 'Errores',                 'roles' => $onlySuperadmin],
    'settings'             => ['href' => 'settings.php',           'label' => 'Ajustes del sitio',       'roles' => $onlySuperadmin],
    'account'              => ['href' => 'account.php',            'label' => 'Mi cuenta',               'roles' => $allRoles],
];

$adminNavItems = array_filter($adminNavItemsAll, fn ($item) => in_array(admin_role(), $item['roles'], true));

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> — Panel · <?= h(setting('site_name')) ?></title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
      <a href="index.php" class="brand">
        <span class="brand-mark">#</span>
        <span class="brand-name"><?= h(setting('site_name')) ?></span>
      </a>
      <div style="position:relative;">
        <button class="notification-bell" id="notification-bell" aria-label="Notificaciones" type="button">
          🔔
          <?php if ($notificationCount > 0): ?>
            <span class="badge-count"><?= $notificationCount > 99 ? '99+' : $notificationCount ?></span>
          <?php endif; ?>
        </button>
        <div class="notification-dropdown" id="notification-dropdown">
          <?php if (empty($adminNotifications)): ?>
            <p class="notification-empty">No hay pendientes. 🎉</p>
          <?php else: ?>
            <?php foreach ($adminNotifications as $n): ?>
              <a href="<?= h($n['href']) ?>"><?= h($n['label']) ?> — <strong><?= $n['count'] ?></strong></a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <nav class="admin-nav">
      <?php foreach ($adminNavItems as $key => $navItem): ?>
        <a href="<?= h($navItem['href']) ?>" class="<?= $activeAdminNav === $key ? 'is-active' : '' ?>"><?= h($navItem['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-footer">
      <span>Conectado como <strong><?= h($_SESSION['admin_user'] ?? '') ?></strong></span>
      <a href="../index.php" target="_blank" rel="noopener">Ver el sitio ↗</a>
      <a href="logout.php">Cerrar sesión</a>
    </div>
  </aside>

  <main class="admin-main">
    <?php if ($flash): ?>
      <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>
