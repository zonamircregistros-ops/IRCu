<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

/** @var string $pageTitle */
/** @var string $activeNav */
/** @var string $pageDescription */
$pageTitle ??= setting('site_name');
$activeNav ??= '';
$pageDescription ??= setting('tagline');

$navItems = [
    'inicio'    => ['href' => 'index.php',    'label' => 'Inicio'],
    'salas'     => ['href' => 'salas.php',    'label' => 'Salas'],
    'staff'     => ['href' => 'staff.php',    'label' => 'Staff'],
    'normas'    => ['href' => 'normas.php',   'label' => 'Normas'],
    'conectar'  => ['href' => 'conectar.php', 'label' => 'Conectar'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> — <?= h(setting('site_name')) ?></title>
<meta name="description" content="<?= h($pageDescription) ?>">
<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="bg-decor" aria-hidden="true">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="grid-overlay"></div>
</div>

<header class="site-header" id="top">
  <div class="container header-inner">
    <a href="index.php" class="brand">
      <span class="brand-mark">#</span>
      <span class="brand-name"><?= h(setting('site_name')) ?></span>
    </a>

    <nav class="main-nav" id="main-nav">
      <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= h($item['href']) ?>" class="<?= $activeNav === $key ? 'is-active' : '' ?>"><?= h($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <a class="btn btn-primary btn-sm" href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">Entrar al webchat</a>
      <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menú" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<main>
