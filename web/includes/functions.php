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
        'donation_cafecito_url'   => '',
        'donation_paypal_url'     => '',
        'donation_crypto_network' => '',
        'donation_crypto_address' => '',
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

/**
 * Límite de intentos por IP para formularios públicos. Devuelve true si el
 * envío está permitido (y lo registra); false si ya se pasó del límite.
 */
function rate_limit_check(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = $bucket . ':' . $ip;
    $pdo = db();

    try {
        $pdo->prepare('DELETE FROM rate_limits WHERE created_at < :cutoff')
            ->execute(['cutoff' => date('Y-m-d H:i:s', time() - 86400)]);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE rate_key = :key AND created_at >= :window');
        $stmt->execute(['key' => $key, 'window' => date('Y-m-d H:i:s', time() - $windowSeconds)]);
        $count = (int) $stmt->fetchColumn();

        if ($count >= $maxAttempts) {
            return false;
        }

        $pdo->prepare('INSERT INTO rate_limits (rate_key) VALUES (:key)')->execute(['key' => $key]);
        return true;
    } catch (PDOException $e) {
        // Si falla el chequeo, no bloqueamos el envío por un problema de infraestructura.
        return true;
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_testimonials(): array
{
    try {
        return db()->query(
            'SELECT author_nick, quote, years_in_network, avatar_url
             FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id'
        )->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

const CREDIT_CATEGORIES = [
    'fundadores'    => 'Fundadores',
    'colaboradores' => 'Colaboradores',
    'donantes'      => 'Donantes',
];

/**
 * @return array<string, array<int, array<string, mixed>>>
 */
function get_credits_grouped(): array
{
    $grouped = array_fill_keys(array_keys(CREDIT_CATEGORIES), []);

    try {
        $stmt = db()->query(
            'SELECT name, role_label, category FROM credits WHERE is_active = 1 ORDER BY category, sort_order, name'
        );
        foreach ($stmt->fetchAll() as $row) {
            $grouped[$row['category']][] = $row;
        }
    } catch (PDOException $e) {
        // grupos vacíos
    }

    return $grouped;
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_service_statuses(): array
{
    try {
        return db()->query(
            'SELECT service_name, url, status, note, updated_at FROM service_status ORDER BY sort_order, id'
        )->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return array<int, array{question: string, answer: string}>
 */
function get_faq_items(): array
{
    return [
        ['question' => '¿Qué es el IRC?', 'answer' => 'IRC (Internet Relay Chat) es un protocolo de chat en tiempo real que existe desde 1988. Funciona por salas llamadas canales (que empiezan con #) donde mucha gente puede charlar a la vez, además de mensajes privados entre usuarios.'],
        ['question' => '¿Qué es un canal?', 'answer' => 'Un canal es una sala de chat grupal, identificada con un nombre que arranca con #, por ejemplo #Chateanos. Cada canal puede tener sus propias reglas, moderadores y tema de charla.'],
        ['question' => '¿Qué es un nick?', 'answer' => 'Es el apodo con el que te identificás en la red. Podés usar cualquiera que esté libre; si querés asegurarte de que nadie más lo use, podés registrarlo con NickServ.'],
        ['question' => '¿Qué son NickServ y ChanServ?', 'answer' => 'Son los servicios de la red: NickServ te deja registrar y proteger tu nick, y ChanServ hace lo mismo con canales (fundador, moderadores, modos automáticos, etc). En Chateanos estos servicios vienen integrados directamente en Natasha IRCd.'],
        ['question' => '¿Qué es un IRCop?', 'answer' => 'Un IRCop (IRC Operator) es parte del staff técnico de la red: tiene permisos especiales para moderar, aplicar sanciones y mantener la infraestructura funcionando. Podés ver quiénes son en la sección Staff, o postularte vos mismo desde Gestiones.'],
        ['question' => '¿Qué es un bouncer (BNC)?', 'answer' => 'Es un servicio que mantiene tu conexión al IRC activa todo el tiempo, aunque cierres el cliente o se corte tu internet. Nuestro bouncer se llama Natasha; podés pedirlo gratis desde /natasha.'],
        ['question' => '¿Necesito instalar algo para chatear?', 'answer' => 'No. Podés entrar directo desde el navegador con el webchat, sin registrarte ni instalar nada. Si preferís un cliente de escritorio, en Conectar tenés los datos del servidor para mIRC, HexChat, Irssi, etc.'],
        ['question' => '¿Qué es TLS/SSL y por qué me conviene usarlo?', 'answer' => 'Es cifrado para tu conexión: evita que alguien en el medio pueda leer lo que mandás. Recomendamos siempre usar el puerto TLS cuando te conectes con un cliente IRC.'],
        ['question' => '¿Qué es un G-Line?', 'answer' => 'Es una expulsión de la red aplicada por IP o rango de IPs, generalmente por incumplir las normas. Si creés que te banearon por error, podés apelar desde Gestiones.'],
        ['question' => '¿Qué es Natasha IRCd?', 'answer' => 'Es el software que corre toda la red: un IRCd escrito en Go, con servicios (NickServ/ChanServ) y bouncer integrados en un solo daemon, con soporte del 97,5% de IRCv3. Podés leer su historia completa en la sección Quiénes somos.'],
    ];
}

/**
 * Registra una vista de página para la analítica propia (sin IP, sin cookies).
 */
function track_page_view(string $path): void
{
    try {
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referrer !== null) {
            $referrer = substr($referrer, 0, 255);
        }
        db()->prepare('INSERT INTO page_views (path, referrer) VALUES (:path, :referrer)')
            ->execute(['path' => substr($path, 0, 255), 'referrer' => $referrer]);
    } catch (PDOException $e) {
        // La analítica nunca debe romper la carga de la página.
    }
}
