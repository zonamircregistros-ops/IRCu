<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

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
        'radio_stream_url'   => 'https://radio.chateanos.com/listen/bellaciao/radio.mp3',
        'radio_station_name' => 'Radio Chateanos',
        'bnc_free_limit'             => '5',
        'bnc_free_own_choice'        => '4',
        'bnc_premium_price'          => '1.50',
        'bnc_premium_extra_ip_price' => '1',
        'bnc_service_status'         => 'operativo',
        'bnc_connect_host'   => 'natasha.chateanos.com',
        'bnc_connect_port'   => '1025',
        'natasha_mail_from'  => 'natasha@chateanos.com',
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

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_news_list(bool $publishedOnly = true, ?int $limit = null): array
{
    $sql = 'SELECT id, title, slug, excerpt, published_at FROM news';
    if ($publishedOnly) {
        $sql .= ' WHERE is_published = 1 AND published_at <= NOW()';
    }
    $sql .= ' ORDER BY published_at DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    try {
        return db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return array<string, mixed>|null
 */
function get_news_by_slug(string $slug): ?array
{
    try {
        $stmt = db()->prepare(
            'SELECT * FROM news WHERE slug = :slug AND is_published = 1 AND published_at <= NOW() LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_services(): array
{
    try {
        return db()->query(
            'SELECT name, url, description, icon FROM services WHERE is_active = 1 ORDER BY sort_order, name'
        )->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_bnc_networks(): array
{
    try {
        return db()->query(
            'SELECT network_name, host, ip_address, port, use_ssl, status, banned_reason
             FROM bnc_networks WHERE is_active = 1 ORDER BY sort_order, network_name'
        )->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return array<string, mixed>|null
 */
function get_ticket_by_token(string $token): ?array
{
    $stmt = db()->prepare('SELECT * FROM tickets WHERE token = :token LIMIT 1');
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_ticket_messages(int $ticketId): array
{
    $stmt = db()->prepare('SELECT * FROM ticket_messages WHERE ticket_id = :id ORDER BY created_at ASC, id ASC');
    $stmt->execute(['id' => $ticketId]);
    return $stmt->fetchAll();
}
