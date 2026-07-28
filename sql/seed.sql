-- Datos de ejemplo para Chateanos.
-- Ejecutar después de schema.sql:
--   mysql -u root -p chateanos < sql/seed.sql
--
-- IMPORTANTE: crea un admin de ejemplo admin/ChangeMe123!
-- Entrá a /admin, iniciá sesión y cambiá esa contraseña
-- inmediatamente desde "Mi cuenta" antes de publicar el sitio.

SET NAMES utf8mb4;

INSERT INTO admins (username, password_hash) VALUES
  ('admin', '$2y$12$2Dk.7hDY66Q9LQn5F3ik8.oqFlvwIDOWkfMDrs.M7CcPQXgXfE4u2')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO settings (setting_key, setting_value) VALUES
  ('site_name',        'Chateanos'),
  ('tagline',           'Chatea en español, sin vueltas.'),
  ('webchat_url',       'https://webchat.chateanos.com'),
  ('irc_server',        'irc.chateanos.com'),
  ('irc_port_tls',      '6697'),
  ('irc_port_plain',    '6667'),
  ('general_channel',   '#Chateanos'),
  ('staff_email',       'staff@chateanos.com'),
  ('radio_stream_url',  'https://radio.chateanos.com/listen/bellaciao/radio.mp3'),
  ('radio_station_name','Radio Chateanos'),
  ('bnc_free_limit',           '5'),
  ('bnc_free_own_choice',      '4'),
  ('bnc_premium_price',        '1.50'),
  ('bnc_premium_extra_ip_price','1'),
  ('bnc_service_status',       'operativo')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO channels (name, category, description, is_nsfw, sort_order) VALUES
  ('Chateanos', 'general',  'Canal general de la red, te unís apenas conectás', 0, 1),
  ('Ayuda',     'general',  'Soporte y dudas de conexión',                      0, 2),

  ('Argentina', 'regional', 'Charla con gente de Argentina',  0, 1),
  ('Mexico',    'regional', 'Charla con gente de México',     0, 2),
  ('Chile',     'regional', 'Charla con gente de Chile',      0, 3),
  ('Uruguay',   'regional', 'Charla con gente de Uruguay',    0, 4),
  ('Paraguay',  'regional', 'Charla con gente de Paraguay',   0, 5),
  ('España',    'regional', 'Charla con gente de España',     0, 6),
  ('Colombia',  'regional', 'Charla con gente de Colombia',   0, 7),
  ('Peru',      'regional', 'Charla con gente de Perú',       0, 8),

  ('Sexo',      'adultos',  'Charla adulta entre mayores de edad', 1, 1),
  ('Porno',     'adultos',  'Intercambio y charla sobre contenido adulto', 1, 2),
  ('Citas',     'adultos',  'Conocé gente para salir o algo más', 1, 3)
;

INSERT INTO staff (nick, role, bio, sort_order) VALUES
  ('Root',  'administrador', 'Fundador de la red y administrador de servidores.', 1),
  ('Nova',  'administrador', 'Administración general y coordinación del staff.',  2),
  ('Kite',  'ircop',         'Moderación de canales y gestión de bans/kills.',    1),
  ('Luna',  'ircop',         'Moderación de canales y soporte a usuarios.',       2),
  ('Bruma', 'soporte',       'Primera línea de ayuda para usuarios nuevos.',      1)
;

INSERT INTO services (name, url, description, icon, sort_order) VALUES
  ('Git',     'https://git.chateanos.com',     'Repositorios de código de la red y sus proyectos', '🧑‍💻', 1),
  ('Wiki',    'https://wiki.chateanos.com',    'Documentación, guías y FAQ de la comunidad',        '📖', 2),
  ('Nube',    'https://nube.chateanos.com',    'Almacenamiento en la nube para usuarios',           '☁️', 3),
  ('Webmail', 'https://mail.chateanos.com',    'Correo electrónico @chateanos.com',                 '✉️', 4),
  ('Natasha Bouncer', 'natasha/', 'BNC gratis y premium: mantené tu conexión IRC siempre activa', '🤖', 5)
;

INSERT INTO bnc_networks (network_name, host, port, use_ssl, status, banned_reason, sort_order) VALUES
  ('Chateanos',   'irc.chateanos.com',    6697, 1, 'operativo',     NULL, 1),
  ('Libera.Chat', 'irc.libera.chat',      6697, 1, 'operativo',     NULL, 2),
  ('Rizon',       'irc.rizon.net',        6697, 1, 'operativo',     NULL, 3),
  ('IRC-Hispano', 'irc.irc-hispano.org',  6667, 0, 'operativo',     NULL, 4),
  ('EFnet',       'irc.efnet.org',        6667, 0, 'operativo',     NULL, 5),
  ('Undernet',    'irc.undernet.org',     6667, 0, 'no_operativo',  'La red prohíbe el uso de bouncers/proxies en su política de conexión', 6)
;

INSERT INTO news (title, slug, excerpt, body, published_at) VALUES
  ('Bienvenidos a la nueva web de Chateanos',
   'bienvenidos-nueva-web',
   'Rediseñamos todo el sitio: más rápido, más moderno y ahora con panel de administración.',
   'Arrancamos de cero el sitio de Chateanos: nueva identidad visual, salas organizadas por categoría, sección de staff y un panel de administración para mantener todo actualizado sin tocar código. Gracias por seguir eligiendo la red.',
   NOW()),
  ('Nuevos servicios: Git, Wiki, Nube y Webmail',
   'nuevos-servicios-git-wiki-nube-webmail',
   'Ya están disponibles los servicios de la red más allá del chat.',
   'Sumamos cuatro servicios nuevos para la comunidad: Git para proyectos de código, una Wiki con guías y documentación, Nube para almacenamiento de archivos y Webmail con tu propia casilla @chateanos.com. Todos accesibles desde la sección Servicios.',
   NOW()),
  ('Natasha Bouncer ya está en línea',
   'natasha-bouncer-en-linea',
   'Mantené tu sesión IRC siempre conectada, gratis hasta 5 redes.',
   'Presentamos Natasha, nuestro servicio de bouncer (BNC): quedás conectado a Chateanos y hasta 4 redes más aunque cierres el cliente. El plan gratuito incluye IPv4; el plan Premium por 1.50 al mes suma redes ilimitadas, IPv6 y la posibilidad de elegir datacenter. Toda la info y el formulario de solicitud en /natasha.',
   NOW())
;
