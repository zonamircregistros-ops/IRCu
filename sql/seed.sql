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
  ('staff_email',       'staff@chateanos.com')
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
