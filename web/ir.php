<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$channelName = trim((string) ($_GET['channel'] ?? ''));

if ($channelName !== '') {
    $stmt = db()->prepare('SELECT id FROM channels WHERE name = :name AND is_active = 1 LIMIT 1');
    $stmt->execute(['name' => $channelName]);
    $channelId = $stmt->fetchColumn();
    if ($channelId) {
        track_channel_click((int) $channelId);
    }
}

header('Location: ' . webchat_link($channelName));
exit;
