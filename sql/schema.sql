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
