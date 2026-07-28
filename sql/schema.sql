-- Chateanos IRC Network — esquema MySQL/MariaDB
-- Ejecutar completo en una base de datos vacía, p.ej.:
--   mysql -u root -p chateanos < sql/schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- admins: usuarios que pueden entrar al panel /admin
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
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
  status          ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
