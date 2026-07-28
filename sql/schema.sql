-- Chateanos IRC Network — esquema MySQL/MariaDB
-- Ejecutar completo en una base de datos vacía, p.ej.:
--   mysql -u root -p chateanos < sql/schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- admins: usuarios que pueden entrar al panel /admin
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username              VARCHAR(50)  NOT NULL UNIQUE,
  password_hash         VARCHAR(255) NOT NULL,
  email                 VARCHAR(160) NULL,
  role                  ENUM('superadmin','moderador') NOT NULL DEFAULT 'superadmin',
  reset_token           VARCHAR(64)  NULL,
  reset_token_expires   DATETIME     NULL,
  created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- admin_audit_log: quién hizo qué en el panel de administración
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_audit_log (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user    VARCHAR(50)  NOT NULL,
  action        VARCHAR(160) NOT NULL,
  details       VARCHAR(255) NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- error_log: errores/excepciones no capturadas del sitio
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS error_log (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  severity      VARCHAR(20)  NOT NULL,
  message       VARCHAR(500) NOT NULL,
  file          VARCHAR(255) NULL,
  line          INT UNSIGNED NULL,
  request_uri   VARCHAR(255) NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- data_requests: pedidos de acceso/exportación/borrado de datos personales
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS data_requests (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email           VARCHAR(160) NOT NULL,
  request_type    ENUM('exportar','eliminar') NOT NULL DEFAULT 'exportar',
  details         TEXT         NULL,
  admin_notes     TEXT         NULL,
  status          ENUM('pendiente','en_proceso','completado') NOT NULL DEFAULT 'pendiente',
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- settings: pares clave/valor editables desde el panel
-- (nombre del sitio, datos de conexión, etc.)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value TEXT         NULL,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- channels: salas/canales listados en salas.php
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS channels (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(60)  NOT NULL,
  category    ENUM('general','regional','adultos','ayuda') NOT NULL DEFAULT 'general',
  description VARCHAR(160) NULL,
  is_nsfw     TINYINT(1)   NOT NULL DEFAULT 0,
  click_count INT UNSIGNED NOT NULL DEFAULT 0,
  sort_order  SMALLINT     NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_category_active (category, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- staff: equipo de la red (administradores, ircops, soporte)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nick         VARCHAR(40)  NOT NULL,
  role         ENUM('administrador','ircop','soporte') NOT NULL DEFAULT 'soporte',
  bio          VARCHAR(200) NULL,
  avatar_url   VARCHAR(255) NULL,
  sort_order   SMALLINT     NOT NULL DEFAULT 0,
  is_active    TINYINT(1)   NOT NULL DEFAULT 1,
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_role_active (role, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- news: noticias.php / noticia.php
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS news (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(160) NOT NULL,
  slug          VARCHAR(180) NOT NULL UNIQUE,
  excerpt       VARCHAR(280) NULL,
  body          TEXT         NOT NULL,
  cover_image   VARCHAR(255) NULL,
  is_published  TINYINT(1)   NOT NULL DEFAULT 1,
  published_at  DATETIME     NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_published (is_published, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- services: servicios.php (Git, Wiki, Nube, Webmail, BNC...)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(60)  NOT NULL,
  url         VARCHAR(255) NOT NULL,
  description VARCHAR(160) NULL,
  icon        VARCHAR(8)   NULL,
  sort_order  SMALLINT     NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- bnc_networks: redes IRC donde está presente Natasha Bouncer
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS bnc_networks (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  network_name  VARCHAR(80)  NOT NULL,
  host          VARCHAR(120) NOT NULL,
  ip_address    VARCHAR(45)  NULL,
  port          SMALLINT UNSIGNED NOT NULL DEFAULT 6667,
  use_ssl       TINYINT(1)   NOT NULL DEFAULT 1,
  status        ENUM('operativo','no_operativo') NOT NULL DEFAULT 'operativo',
  banned_reason VARCHAR(255) NULL,
  sort_order    SMALLINT     NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- bnc_requests: solicitudes del servicio Natasha Bouncer
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS bnc_requests (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nick            VARCHAR(60)  NOT NULL,
  contact         VARCHAR(160) NOT NULL,
  plan            ENUM('free','premium') NOT NULL DEFAULT 'free',
  datacenter      VARCHAR(40)  NULL,
  networks_wanted VARCHAR(255) NULL,
  notes           TEXT         NULL,
  bnc_password    VARCHAR(64)  NULL,
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
  email_verify_token  VARCHAR(64)  NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- gline_appeals: apelaciones de expulsiones (G-Line)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS gline_appeals (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_or_range     VARCHAR(100) NOT NULL,
  username        VARCHAR(60)  NOT NULL,
  email           VARCHAR(160) NOT NULL,
  reason          TEXT         NOT NULL,
  admin_response  TEXT         NULL,
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
  email_verify_token  VARCHAR(64)  NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- ircop_applications: postulaciones para ser IRCop
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS ircop_applications (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username          VARCHAR(60)  NOT NULL,
  real_name         VARCHAR(120) NOT NULL,
  age               TINYINT UNSIGNED NOT NULL,
  birthdate         DATE         NOT NULL,
  email             VARCHAR(160) NOT NULL,
  user_history      TEXT         NOT NULL,
  notable_history   TEXT         NULL,
  reason            TEXT         NOT NULL,
  admin_notes       TEXT         NULL,
  status            ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
  email_verify_token  VARCHAR(64)  NULL,
  created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- tickets: soporte / reclamos, con seguimiento por token
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS tickets (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token             VARCHAR(64)  NOT NULL UNIQUE,
  subject           VARCHAR(160) NOT NULL,
  category          ENUM('soporte','reclamo','otro','legal_abuso') NOT NULL DEFAULT 'soporte',
  requester_name    VARCHAR(80)  NOT NULL,
  requester_email   VARCHAR(160) NOT NULL,
  status            ENUM('abierto','aprobado','rechazado','cerrado') NOT NULL DEFAULT 'abierto',
  email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
  email_verify_token  VARCHAR(64)  NULL,
  created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- ticket_messages: hilo de conversación de cada ticket
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_messages (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_id   INT UNSIGNED NOT NULL,
  sender      ENUM('usuario','staff') NOT NULL,
  message     TEXT         NOT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ticket (ticket_id, created_at),
  CONSTRAINT fk_ticket_messages_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- rate_limits: control de envíos por IP en formularios públicos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rate_key    VARCHAR(160) NOT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_key_time (rate_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- page_views: analítica propia, sin IP ni cookies de terceros
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS page_views (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path        VARCHAR(255) NOT NULL,
  referrer    VARCHAR(255) NULL,
  utm_source    VARCHAR(100) NULL,
  utm_medium    VARCHAR(100) NULL,
  utm_campaign  VARCHAR(100) NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_path_time (path, created_at),
  KEY idx_time (created_at),
  KEY idx_campaign (utm_campaign)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- testimonials: "lo que dice la comunidad" en el home
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS testimonials (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_nick       VARCHAR(60)  NOT NULL,
  quote             TEXT         NOT NULL,
  years_in_network  SMALLINT UNSIGNED NULL,
  avatar_url        VARCHAR(255) NULL,
  sort_order        SMALLINT     NOT NULL DEFAULT 0,
  is_active         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- credits: creditos.php (fundadores, colaboradores, donantes)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS credits (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  role_label  VARCHAR(120) NULL,
  category    ENUM('fundadores','colaboradores','donantes') NOT NULL DEFAULT 'colaboradores',
  sort_order  SMALLINT     NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_category_active (category, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- service_status: estado.php (Red IRC, Webchat, Git, Wiki, Nube...)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_status (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_name  VARCHAR(60)  NOT NULL,
  url           VARCHAR(255) NULL,
  status        ENUM('operativo','degradado','no_operativo') NOT NULL DEFAULT 'operativo',
  note          VARCHAR(255) NULL,
  sort_order    SMALLINT     NOT NULL DEFAULT 0,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- service_status_history: snapshot histórico para el uptime de estado.php
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_status_history (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_name  VARCHAR(60)  NOT NULL,
  status        ENUM('operativo','degradado','no_operativo') NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_service_time (service_name, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- blog_posts: blog comunitario, moderado antes de publicarse
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS blog_posts (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(160) NOT NULL,
  slug            VARCHAR(180) NOT NULL UNIQUE,
  author_nick     VARCHAR(60)  NOT NULL,
  author_email    VARCHAR(160) NOT NULL,
  body            TEXT         NOT NULL,
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  published_at    DATETIME     NULL,
  email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
  email_verify_token  VARCHAR(64)  NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- forum_topics / forum_replies: tablón asincrónico, moderado
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS forum_topics (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(160) NOT NULL,
  author_nick     VARCHAR(60)  NOT NULL,
  author_email    VARCHAR(160) NOT NULL,
  body            TEXT         NOT NULL,
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  is_locked       TINYINT(1)   NOT NULL DEFAULT 0,
  email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
  email_verify_token  VARCHAR(64)  NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS forum_replies (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id        INT UNSIGNED NOT NULL,
  author_nick     VARCHAR(60)  NOT NULL,
  author_email    VARCHAR(160) NOT NULL,
  body            TEXT         NOT NULL,
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_topic_status (topic_id, status, created_at),
  CONSTRAINT fk_forum_replies_topic FOREIGN KEY (topic_id) REFERENCES forum_topics (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- user_profiles: perfiles públicos opcionales, moderados
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_profiles (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nick            VARCHAR(60)  NOT NULL UNIQUE,
  email           VARCHAR(160) NOT NULL,
  bio             VARCHAR(280) NULL,
  favorite_channels VARCHAR(255) NULL,
  social_links    VARCHAR(255) NULL,
  avatar_url      VARCHAR(255) NULL,
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
  email_verify_token  VARCHAR(64)  NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- community_stories: historias largas de la comunidad, moderadas
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS community_stories (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(160) NOT NULL,
  author_nick     VARCHAR(60)  NOT NULL,
  author_email    VARCHAR(160) NOT NULL,
  body            TEXT         NOT NULL,
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  email_verified      TINYINT(1)   NOT NULL DEFAULT 0,
  email_verify_token  VARCHAR(64)  NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- events: calendario de eventos de la red
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(160) NOT NULL,
  description   TEXT         NULL,
  starts_at     DATETIME     NOT NULL,
  ends_at       DATETIME     NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_active_start (is_active, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- newsletter_subscribers: newsletter mensual (double opt-in)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email               VARCHAR(160) NOT NULL UNIQUE,
  confirmed           TINYINT(1)   NOT NULL DEFAULT 0,
  confirm_token       VARCHAR(64)  NULL,
  unsubscribe_token   VARCHAR(64)  NOT NULL,
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_confirmed (confirmed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- collaboration_applications: postulaciones para colaborar con la red
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS collaboration_applications (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(120) NOT NULL,
  email           VARCHAR(160) NOT NULL,
  area            VARCHAR(80)  NOT NULL,
  message         TEXT         NOT NULL,
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  admin_notes     TEXT         NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
