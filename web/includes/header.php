<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

/** @var string $pageTitle */
/** @var string $activeNav */
/** @var string $pageDescription */
$pageTitle ??= setting('site_name');
$activeNav ??= '';
$pageDescription ??= setting('tagline');
/** @var array<int,string> $extraStyles */
$extraStyles ??= [];

$canonicalUrl = 'https://chateanos.com' . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$ogImageUrl = 'https://chateanos.com/assets/og-image.png';

track_page_view(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

$navItems = [
    'inicio'     => ['href' => '/index.php',     'label' => 'Inicio'],
    'salas'      => ['href' => '/salas.php',     'label' => 'Salas'],
    'servicios'  => ['href' => '/servicios.php', 'label' => 'Servicios'],
    'staff'      => ['href' => '/staff.php',     'label' => 'Staff'],
    'noticias'   => ['href' => '/noticias.php',  'label' => 'Noticias'],
    'gestiones'  => ['href' => '/gestiones.php', 'label' => 'Gestiones'],
    'conectar'   => ['href' => '/conectar.php',  'label' => 'Conectar'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> — <?= h(setting('site_name')) ?></title>
<meta name="description" content="<?= h($pageDescription) ?>">
<link rel="canonical" href="<?= h($canonicalUrl) ?>">
<link rel="alternate" type="application/rss+xml" title="<?= h(setting('site_name')) ?> — Noticias" href="/rss.php">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= h(setting('site_name')) ?>">
<meta property="og:title" content="<?= h($pageTitle) ?> — <?= h(setting('site_name')) ?>">
<meta property="og:description" content="<?= h($pageDescription) ?>">
<meta property="og:url" content="<?= h($canonicalUrl) ?>">
<meta property="og:image" content="<?= h($ogImageUrl) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="es_AR">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= h($pageTitle) ?> — <?= h(setting('site_name')) ?>">
<meta name="twitter:description" content="<?= h($pageDescription) ?>">
<meta name="twitter:image" content="<?= h($ogImageUrl) ?>">

<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
<link rel="manifest" href="/assets/site.webmanifest">
<meta name="theme-color" content="#ff1f4d">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/style.css">
<?php foreach ($extraStyles as $href): ?>
<link rel="stylesheet" href="<?= h($href) ?>">
<?php endforeach; ?>
</head>
<body>

<div class="bg-decor" aria-hidden="true">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="grid-overlay"></div>
</div>

<header class="site-header" id="top">
  <div class="container header-inner">
    <a href="/index.php" class="brand">
      <span class="brand-mark">#</span>
      <span class="brand-name"><?= h(setting('site_name')) ?></span>
    </a>

    <nav class="main-nav" id="main-nav">
      <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= h($item['href']) ?>" class="<?= $activeNav === $key ? 'is-active' : '' ?>"><?= h($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="header-actions">
      <a class="search-icon-btn" href="/buscar.php" aria-label="Buscar en el sitio">🔎</a>
      <a class="btn btn-primary btn-sm" href="<?= h(webchat_link()) ?>" target="_blank" rel="noopener">Entrar al webchat</a>
      <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menú" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<main>
