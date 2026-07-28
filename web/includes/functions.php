<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/error_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

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
        'donation_goal_amount' => '0',
        'donation_goal_raised' => '0',
        'encuestas_url' => 'https://encuestas.chateanos.com',
        'maintenance_mode' => '0',
        'maintenance_message' => 'Estamos hicimos mantenimiento programado. Volvemos en breve.',
        'blocked_domains' => '',
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
    $sql = 'SELECT id, title, slug, excerpt, body, cover_image, published_at FROM news';
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
    if (current_lang() === 'en') {
        return [
            ['question' => 'What is IRC?', 'answer' => 'IRC (Internet Relay Chat) is a real-time chat protocol that has existed since 1988. It works through rooms called channels (starting with #) where many people can talk at once, plus private messages between users.'],
            ['question' => 'What is a channel?', 'answer' => 'A channel is a group chat room, identified by a name starting with #, for example #Chateanos. Each channel can have its own rules, moderators and topic.'],
            ['question' => 'What is a nick?', 'answer' => "It's the nickname you're identified by on the network. You can use any one that's free; if you want to make sure nobody else uses it, you can register it with NickServ."],
            ['question' => 'What are NickServ and ChanServ?', 'answer' => "They're the network's services: NickServ lets you register and protect your nick, and ChanServ does the same for channels (founder, moderators, auto modes, etc). On Chateanos these services are built directly into Natasha IRCd."],
            ['question' => 'What is an IRCop?', 'answer' => "An IRCop (IRC Operator) is part of the network's technical staff: they have special permissions to moderate, apply sanctions and keep the infrastructure running. You can see who they are in the Staff section, or apply yourself from Support."],
            ['question' => 'What is a bouncer (BNC)?', 'answer' => 'A service that keeps your IRC connection active all the time, even if you close your client or lose internet. Our bouncer is called Natasha; you can request it for free from /natasha.'],
            ['question' => 'Do I need to install anything to chat?', 'answer' => 'No. You can join directly from your browser with the webchat, no registration or installation needed. If you prefer a desktop client, Connect has the server details for mIRC, HexChat, Irssi, etc.'],
            ['question' => "What is TLS/SSL and why should I use it?", 'answer' => "It's encryption for your connection: it stops anyone in the middle from reading what you send. We always recommend using the TLS port when connecting with an IRC client."],
            ['question' => 'What is a G-Line?', 'answer' => 'A network ban applied by IP or IP range, usually for breaking the rules. If you think you were banned by mistake, you can appeal from Support.'],
            ['question' => 'What is Natasha IRCd?', 'answer' => 'The software that runs the whole network: an IRCd written in Go, with services (NickServ/ChanServ) and a bouncer built into a single daemon, with 97.5% IRCv3 support. You can read its full story in the About section.'],
        ];
    }

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
        $utmSource = isset($_GET['utm_source']) ? substr((string) $_GET['utm_source'], 0, 100) : null;
        $utmMedium = isset($_GET['utm_medium']) ? substr((string) $_GET['utm_medium'], 0, 100) : null;
        $utmCampaign = isset($_GET['utm_campaign']) ? substr((string) $_GET['utm_campaign'], 0, 100) : null;
        db()->prepare(
            'INSERT INTO page_views (path, referrer, utm_source, utm_medium, utm_campaign)
             VALUES (:path, :referrer, :utm_source, :utm_medium, :utm_campaign)'
        )->execute([
            'path' => substr($path, 0, 255),
            'referrer' => $referrer,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
        ]);
    } catch (PDOException $e) {
        // La analítica nunca debe romper la carga de la página.
    }
}

/**
 * Registra una acción del panel de administración (auditoría interna).
 */
function audit_log(string $action, string $details = ''): void
{
    try {
        db()->prepare('INSERT INTO admin_audit_log (admin_user, action, details) VALUES (:user, :action, :details)')
            ->execute([
                'user' => $_SESSION['admin_user'] ?? 'desconocido',
                'action' => $action,
                'details' => $details !== '' ? substr($details, 0, 255) : null,
            ]);
    } catch (PDOException $e) {
        // La auditoría nunca debe romper la acción que la disparó.
    }
}

/**
 * Genera una pregunta matemática simple (captcha propio, sin servicios de
 * terceros) y guarda la respuesta en sesión para validarla al enviar el form.
 *
 * @return array{token: string, question: string}
 */
function captcha_new(string $lang = 'es'): array
{
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $token = bin2hex(random_bytes(8));
    $_SESSION['captcha'][$token] = $a + $b;
    $question = $lang === 'en' ? "How much is {$a} + {$b}?" : "¿Cuánto es {$a} + {$b}?";
    return ['token' => $token, 'question' => $question];
}

/**
 * Valida la respuesta de un captcha generado por captcha_new(). Consume el
 * token para que no se pueda reutilizar la misma respuesta dos veces.
 */
function captcha_check(string $token, string $answer): bool
{
    if (!isset($_SESSION['captcha'][$token])) {
        return false;
    }
    $expected = $_SESSION['captcha'][$token];
    unset($_SESSION['captcha'][$token]);
    return is_numeric($answer) && (int) $answer === $expected;
}

/**
 * Renderiza el campo de captcha propio (pregunta + input) para un formulario
 * público. Genera un desafío nuevo cada vez que se llama, así que hay que
 * llamarla una sola vez por render del formulario (inclusive tras un error).
 */
function captcha_field(string $lang = 'es'): string
{
    $captcha = captcha_new($lang);
    return '<div class="form-group">'
        . '<label for="captcha_answer">' . h($captcha['question']) . '</label>'
        . '<input type="hidden" name="captcha_token" value="' . h($captcha['token']) . '">'
        . '<input type="text" inputmode="numeric" id="captcha_answer" name="captcha_answer" required maxlength="2" autocomplete="off">'
        . '</div>';
}

/**
 * Sanea el HTML generado por el editor WYSIWYG de noticias antes de
 * guardarlo: solo se permiten un puñado de tags de formato de texto, y se
 * remueven atributos de evento y hrefs/src con javascript: como defensa
 * en profundidad (el contenido ya lo escribe un admin autenticado).
 */
function sanitize_html_content(string $html): string
{
    $allowed = '<p><br><b><strong><i><em><u><a><ul><ol><li><h2><h3><h4><blockquote><img><code><pre>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/i', '$1=$2$2', $html) ?? $html;
    return $html;
}

/**
 * Procesa una imagen subida por un formulario de admin (avatar, portada de
 * noticia, etc). Valida tipo y tamaño, y la guarda en assets/uploads/{subdir}.
 *
 * @return string|null ruta relativa (p.ej. "/assets/uploads/staff/xxx.jpg"), o
 *                      null si no se subió ningún archivo. Lanza RuntimeException
 *                      si el archivo subido no es válido.
 */
function handle_uploaded_image(string $fieldName, string $subdir): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new \RuntimeException('No se pudo subir el archivo (código ' . $file['error'] . ').');
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        throw new \RuntimeException('La imagen no puede pesar más de 3 MB.');
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new \RuntimeException('Formato de imagen no soportado (solo JPG, PNG, WEBP o GIF).');
    }

    $destDir = __DIR__ . '/../assets/uploads/' . $subdir;
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $destPath = $destDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new \RuntimeException('No se pudo guardar la imagen subida.');
    }

    // Convertimos a WebP para pesar menos, salvo GIF (para no perder animaciones).
    if ($mime !== 'image/gif' && function_exists('imagewebp')) {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($destPath),
            'image/png' => @imagecreatefrompng($destPath),
            'image/webp' => @imagecreatefromwebp($destPath),
            default => false,
        };
        if ($image !== false) {
            $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
            $webpPath = $destDir . '/' . $webpFilename;
            if (imagewebp($image, $webpPath, 82)) {
                imagedestroy($image);
                if ($webpPath !== $destPath) {
                    unlink($destPath);
                }
                $filename = $webpFilename;
            } else {
                imagedestroy($image);
            }
        }
    }

    return '/assets/uploads/' . $subdir . '/' . $filename;
}

/**
 * Suma un click al contador de una sala (ranking de canales por actividad).
 */
function track_channel_click(int $channelId): void
{
    try {
        db()->prepare('UPDATE channels SET click_count = click_count + 1 WHERE id = :id')
            ->execute(['id' => $channelId]);
    } catch (PDOException $e) {
        // No debe romper la redirección al webchat.
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_channel_ranking(int $limit = 10): array
{
    try {
        $stmt = db()->prepare(
            'SELECT name, category, click_count FROM channels
             WHERE is_active = 1 ORDER BY click_count DESC, name LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Guarda una foto del estado actual de cada servicio (para el histórico de
 * uptime). Se llama cada vez que se actualiza service_status desde el panel.
 */
function snapshot_service_status(): void
{
    try {
        $rows = db()->query('SELECT service_name, status FROM service_status')->fetchAll();
        $stmt = db()->prepare('INSERT INTO service_status_history (service_name, status) VALUES (:name, :status)');
        foreach ($rows as $row) {
            $stmt->execute(['name' => $row['service_name'], 'status' => $row['status']]);
        }
    } catch (PDOException $e) {
        // No debe romper el guardado del estado.
    }
}

/**
 * % de snapshots en estado "operativo" en los últimos $days días.
 */
function get_uptime_percent(string $serviceName, int $days = 30): ?float
{
    try {
        $stmt = db()->prepare(
            'SELECT
               SUM(status = "operativo") AS ok_count,
               COUNT(*) AS total
             FROM service_status_history
             WHERE service_name = :name AND created_at >= :since'
        );
        $stmt->execute(['name' => $serviceName, 'since' => date('Y-m-d H:i:s', time() - $days * 86400)]);
        $row = $stmt->fetch();
        if (!$row || (int) $row['total'] === 0) {
            return null;
        }
        return round(((int) $row['ok_count'] / (int) $row['total']) * 100, 1);
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_blog_posts(bool $approvedOnly = true, ?int $limit = null): array
{
    $sql = 'SELECT * FROM blog_posts';
    if ($approvedOnly) {
        $sql .= ' WHERE status = "aprobado" AND published_at <= NOW()';
    }
    $sql .= ' ORDER BY COALESCE(published_at, created_at) DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    try {
        return db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function get_blog_post_by_slug(string $slug): ?array
{
    try {
        $stmt = db()->prepare('SELECT * FROM blog_posts WHERE slug = :slug AND status = "aprobado" AND published_at <= NOW() LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_forum_topics(bool $approvedOnly = true): array
{
    $sql = 'SELECT ft.*, (SELECT COUNT(*) FROM forum_replies fr WHERE fr.topic_id = ft.id AND fr.status = "aprobado") AS reply_count
            FROM forum_topics ft';
    if ($approvedOnly) {
        $sql .= ' WHERE ft.status = "aprobado"';
    }
    $sql .= ' ORDER BY ft.created_at DESC';
    try {
        return db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function get_forum_topic(int $id, bool $approvedOnly = true): ?array
{
    $sql = 'SELECT * FROM forum_topics WHERE id = :id';
    if ($approvedOnly) {
        $sql .= ' AND status = "aprobado"';
    }
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_forum_replies(int $topicId, bool $approvedOnly = true): array
{
    $sql = 'SELECT * FROM forum_replies WHERE topic_id = :id';
    if ($approvedOnly) {
        $sql .= ' AND status = "aprobado"';
    }
    $sql .= ' ORDER BY created_at ASC';
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute(['id' => $topicId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_profiles(bool $approvedOnly = true): array
{
    $sql = 'SELECT * FROM user_profiles';
    if ($approvedOnly) {
        $sql .= ' WHERE status = "aprobado"';
    }
    $sql .= ' ORDER BY nick';
    try {
        return db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_stories(bool $approvedOnly = true): array
{
    $sql = 'SELECT * FROM community_stories';
    if ($approvedOnly) {
        $sql .= ' WHERE status = "aprobado"';
    }
    $sql .= ' ORDER BY created_at DESC';
    try {
        return db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_upcoming_events(): array
{
    try {
        return db()->query(
            'SELECT * FROM events WHERE is_active = 1 AND (ends_at >= NOW() OR (ends_at IS NULL AND starts_at >= NOW()))
             ORDER BY starts_at ASC'
        )->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_past_events(int $limit = 10): array
{
    try {
        $stmt = db()->prepare(
            'SELECT * FROM events WHERE is_active = 1 AND COALESCE(ends_at, starts_at) < NOW()
             ORDER BY starts_at DESC LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function is_maintenance_mode(): bool
{
    return setting('maintenance_mode') === '1';
}

/**
 * Extrae URLs (http/https) de un texto libre enviado por un formulario público.
 *
 * @return array<int, string>
 */
function extract_urls(string $text): array
{
    preg_match_all('#https?://[^\s<>"\']+#i', $text, $matches);
    return $matches[0] ?? [];
}

/**
 * Filtro anti-phishing simple: rechaza texto que contenga links a dominios
 * de la lista negra que mantiene el admin (ajuste "blocked_domains",
 * separado por comas). No usa ningún servicio externo.
 */
function contains_blocked_domain(string $text): bool
{
    $blocklist = array_filter(array_map('trim', explode(',', setting('blocked_domains'))));
    if (empty($blocklist)) {
        return false;
    }

    foreach (extract_urls($text) as $url) {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null) {
            continue;
        }
        $host = strtolower($host);
        foreach ($blocklist as $blocked) {
            $blocked = strtolower($blocked);
            if ($host === $blocked || str_ends_with($host, '.' . $blocked)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Vistas recientes de una ruta (para el widget "N personas vieron esto"),
 * aproximado a partir de page_views — sin cookies ni IDs de sesión.
 */
function recent_view_count(string $path, int $minutes = 5): int
{
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM page_views WHERE path = :path AND created_at >= :since'
        );
        $stmt->execute(['path' => $path, 'since' => date('Y-m-d H:i:s', time() - $minutes * 60)]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_campaign_summary(int $days = 30): array
{
    try {
        $stmt = db()->prepare(
            'SELECT utm_source, utm_medium, utm_campaign, COUNT(*) AS visits
             FROM page_views
             WHERE utm_campaign IS NOT NULL AND created_at >= :since
             GROUP BY utm_source, utm_medium, utm_campaign
             ORDER BY visits DESC'
        );
        $stmt->execute(['since' => date('Y-m-d H:i:s', time() - $days * 86400)]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Idioma del sitio: query string ?lang= tiene prioridad y se recuerda en
 * cookie por 1 año; si no hay nada, cae a español. Solo 'es'/'en' válidos.
 */
function current_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $requested = $_GET['lang'] ?? null;
    if (in_array($requested, ['es', 'en'], true)) {
        $lang = $requested;
        if (!headers_sent()) {
            setcookie('chateanos_lang', $lang, time() + 31536000, '/');
        }
        return $lang;
    }

    $cookie = $_COOKIE['chateanos_lang'] ?? null;
    $lang = in_array($cookie, ['es', 'en'], true) ? $cookie : 'es';
    return $lang;
}

/**
 * Diccionario de textos compartidos del sitio (nav, footer, UI común).
 * El contenido específico de cada página vive en la página misma.
 */
function t(string $key): string
{
    static $strings = [
        'nav.inicio' => ['es' => 'Inicio', 'en' => 'Home'],
        'nav.salas' => ['es' => 'Salas', 'en' => 'Rooms'],
        'nav.servicios' => ['es' => 'Servicios', 'en' => 'Services'],
        'nav.staff' => ['es' => 'Staff', 'en' => 'Staff'],
        'nav.noticias' => ['es' => 'Noticias', 'en' => 'News'],
        'nav.gestiones' => ['es' => 'Gestiones', 'en' => 'Support'],
        'nav.conectar' => ['es' => 'Conectar', 'en' => 'Connect'],
        'nav.webchat' => ['es' => 'Entrar al webchat', 'en' => 'Enter webchat'],
        'nav.buscar' => ['es' => 'Buscar en el sitio', 'en' => 'Search the site'],
        'footer.red' => ['es' => 'Red', 'en' => 'Network'],
        'footer.historia' => ['es' => 'Nuestra historia', 'en' => 'Our story'],
        'footer.ranking' => ['es' => 'Ranking de salas', 'en' => 'Room ranking'],
        'footer.comunidad' => ['es' => 'Comunidad', 'en' => 'Community'],
        'footer.blog' => ['es' => 'Blog comunitario', 'en' => 'Community blog'],
        'footer.foro' => ['es' => 'Foro', 'en' => 'Forum'],
        'footer.historias' => ['es' => 'Historias', 'en' => 'Stories'],
        'footer.perfiles' => ['es' => 'Perfiles', 'en' => 'Profiles'],
        'footer.eventos' => ['es' => 'Eventos', 'en' => 'Events'],
        'footer.encuestas' => ['es' => 'Encuestas', 'en' => 'Surveys'],
        'footer.gestiones' => ['es' => 'Gestiones', 'en' => 'Support'],
        'footer.normas' => ['es' => 'Normas', 'en' => 'Rules'],
        'footer.faq' => ['es' => 'Preguntas frecuentes', 'en' => 'FAQ'],
        'footer.colaborar' => ['es' => 'Colaborar', 'en' => 'Volunteer'],
        'footer.contacto' => ['es' => 'Contacto', 'en' => 'Contact'],
        'footer.acceso' => ['es' => 'Acceso', 'en' => 'Access'],
        'footer.clientes' => ['es' => 'Comparar clientes IRC', 'en' => 'Compare IRC clients'],
        'footer.natasha' => ['es' => 'Natasha BNC', 'en' => 'Natasha BNC'],
        'footer.estado' => ['es' => 'Estado del servicio', 'en' => 'Service status'],
        'footer.mas' => ['es' => 'Más', 'en' => 'More'],
        'footer.creditos' => ['es' => 'Créditos', 'en' => 'Credits'],
        'footer.donar' => ['es' => 'Donar', 'en' => 'Donate'],
        'footer.primeros_pasos' => ['es' => 'Primeros pasos', 'en' => 'Getting started'],
        'footer.privacidad' => ['es' => 'Privacidad', 'en' => 'Privacy'],
        'footer.terminos' => ['es' => 'Términos', 'en' => 'Terms'],
        'footer.mis_datos' => ['es' => 'Mis datos', 'en' => 'My data'],
        'footer.reportar_abuso' => ['es' => 'Reportar abuso', 'en' => 'Report abuse'],
        'footer.newsletter_label' => ['es' => 'Recibí las novedades por email', 'en' => 'Get updates by email'],
        'footer.newsletter_btn' => ['es' => 'Sumarme', 'en' => 'Subscribe'],
        'footer.rights' => ['es' => 'Todos los derechos reservados.', 'en' => 'All rights reserved.'],
    ];

    $lang = current_lang();
    return $strings[$key][$lang] ?? $strings[$key]['es'] ?? $key;
}
