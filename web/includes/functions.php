<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** @return array<string,string> */
function get_settings(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $defaults = [
        'site_name'      => 'Chateanos',
        'tagline'        => 'Chatea en español, sin vueltas.',
        'webchat_url'    => 'https://webchat.chateanos.com',
        'irc_server'     => 'irc.chateanos.com',
        'irc_port_tls'   => '6697',
        'irc_port_plain' => '6667',
        'general_channel'=> '#Chateanos',
        'staff_email'    => 'staff@chateanos.com',
    ];

    try {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $stored = array_column($rows, 'setting_value', 'setting_key');
        $cache = array_merge($defaults, $stored);
    } catch (PDOException $e) {
        $cache = $defaults;
    }

    return $cache;
}

function setting(string $key): string
{
    $settings = get_settings();
    return $settings[$key] ?? '';
}

const CHANNEL_CATEGORIES = [
    'general'  => 'General',
    'regional' => 'Regionales',
    'adultos'  => 'Adultos',
    'ayuda'    => 'Ayuda',
];

/**
 * @return array<string, array<int, array<string, mixed>>> canales activos agrupados por categoría
 */
function get_channels_grouped(): array
{
    $grouped = array_fill_keys(array_keys(CHANNEL_CATEGORIES), []);

    try {
        $stmt = db()->query(
            'SELECT name, category, description, is_nsfw
             FROM channels
             WHERE is_active = 1
             ORDER BY category, sort_order, name'
        );
        foreach ($stmt->fetchAll() as $row) {
            $grouped[$row['category']][] = $row;
        }
    } catch (PDOException $e) {
        // Sin conexión a datos: se devuelven grupos vacíos.
    }

    return $grouped;
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_staff_by_role(string $role): array
{
    try {
        $stmt = db()->prepare(
            'SELECT nick, bio, avatar_url
             FROM staff
             WHERE is_active = 1 AND role = :role
             ORDER BY sort_order, nick'
        );
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function webchat_link(string $channelName = ''): string
{
    $url = setting('webchat_url');
    if ($channelName === '') {
        return $url;
    }
    return $url . '/?channels=' . rawurlencode($channelName);
}
