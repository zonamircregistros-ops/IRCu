<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = 'https://chateanos.com';

$staticPaths = [
    '/index.php',
    '/historia.php',
    '/salas.php',
    '/servicios.php',
    '/staff.php',
    '/noticias.php',
    '/normas.php',
    '/conectar.php',
    '/faq.php',
    '/gestiones.php',
    '/apelar.php',
    '/ircop.php',
    '/tickets.php',
    '/natasha/index.php',
    '/natasha/en.php',
    '/natasha/tutorial.php',
    '/creditos.php',
    '/donaciones.php',
    '/estado.php',
    '/privacidad.php',
    '/terminos.php',
];

$news = get_news_list(true);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticPaths as $path): ?>
  <url>
    <loc><?= h($base . $path) ?></loc>
  </url>
<?php endforeach; ?>
<?php foreach ($news as $item): ?>
  <url>
    <loc><?= h($base . '/noticia.php?slug=' . $item['slug']) ?></loc>
    <lastmod><?= h(date('Y-m-d', strtotime($item['published_at']))) ?></lastmod>
  </url>
<?php endforeach; ?>
</urlset>
