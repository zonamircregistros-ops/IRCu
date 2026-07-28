<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_login();

/** @var string $pageTitle */
/** @var string $activeAdminNav */
$pageTitle ??= 'Panel';
$activeAdminNav ??= '';

$adminNavItems = [
    'dashboard'    => ['href' => 'index.php',          'label' => 'Resumen'],
    'salas'        => ['href' => 'salas.php',          'label' => 'Salas'],
    'staff'        => ['href' => 'staff.php',          'label' => 'Staff'],
    'news'         => ['href' => 'news.php',           'label' => 'Noticias'],
    'services'     => ['href' => 'services.php',       'label' => 'Servicios'],
    'bnc_networks' => ['href' => 'bnc_networks.php',   'label' => 'Redes BNC'],
    'bnc_requests' => ['href' => 'bnc_requests.php',   'label' => 'Solicitudes BNC'],
    'settings'     => ['href' => 'settings.php',       'label' => 'Ajustes del sitio'],
    'account'      => ['href' => 'account.php',        'label' => 'Mi cuenta'],
];

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
    <a href="index.php" class="brand">
      <span class="brand-mark">#</span>
      <span class="brand-name"><?= h(setting('site_name')) ?></span>
    </a>
    <nav class="admin-nav">
      <?php foreach ($adminNavItems as $key => $item): ?>
        <a href="<?= h($item['href']) ?>" class="<?= $activeAdminNav === $key ? 'is-active' : '' ?>"><?= h($item['label']) ?></a>
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
