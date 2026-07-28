<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$base = 'https://chateanos.com';
$news = get_news_list(true, 20);

function rss_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function rss_cdata(string $html): string
{
    return '<![CDATA[' . str_replace(']]>', ']]&gt;', $html) . ']]>';
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
  <title><?= rss_escape(setting('site_name')) ?> — Noticias</title>
  <link><?= rss_escape($base . '/noticias.php') ?></link>
  <description><?= rss_escape(setting('tagline')) ?></description>
  <language>es</language>
<?php foreach ($news as $item): ?>
  <item>
    <title><?= rss_escape($item['title']) ?></title>
    <link><?= rss_escape($base . '/noticia.php?slug=' . $item['slug']) ?></link>
    <guid isPermaLink="true"><?= rss_escape($base . '/noticia.php?slug=' . $item['slug']) ?></guid>
    <pubDate><?= date(DATE_RSS, strtotime($item['published_at'])) ?></pubDate>
    <?php if (!empty($item['excerpt'])): ?>
    <description><?= rss_escape($item['excerpt']) ?></description>
    <?php endif; ?>
    <?php if (!empty($item['body'])): ?>
    <content:encoded><?= rss_cdata(sanitize_html_content($item['body'])) ?></content:encoded>
    <?php endif; ?>
  </item>
<?php endforeach; ?>
</channel>
</rss>
