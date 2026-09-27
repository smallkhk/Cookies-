<?php
require_once __DIR__ . '/db.php';

$db = getDB();

$db->exec("
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(80) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    device_id   VARCHAR(100),
    token       VARCHAR(64),
    plan        VARCHAR(50) DEFAULT 'Starter',
    signal_limit INT DEFAULT 50,
    balance     DECIMAL(10,2) DEFAULT 0,
    status      ENUM('active','blocked','expired') DEFAULT 'active',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plans (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80) NOT NULL UNIQUE,
    signals     INT NOT NULL DEFAULT 100,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    days        INT NOT NULL DEFAULT 30,
    featured    TINYINT(1) DEFAULT 0,
    active      TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    type        VARCHAR(50),
    message     TEXT,
    user_id     INT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS app_config (
    cfg_key     VARCHAR(80) PRIMARY KEY,
    cfg_value   TEXT,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR(120) NOT NULL,
    image  TEXT,
    url    TEXT,
    sort   INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Default products (trending hack list). Edit in admin → Products.
$db->exec("
INSERT IGNORE INTO products (id, name, image, url, sort) VALUES
  (1, 'Yaar Win Hack',   '', 'https://www.yaarwin.com',       0),
  (2, 'Jai Club Hack',   '', 'https://www.jaiclub.com',       1),
  (3, 'Jalwa Game Hack', '', 'https://www.jalwa.game',        2),
  (4, 'Tiranga Lottery', '', 'https://www.tirangagames.com',  3);
");

// Default plans
$db->exec("
INSERT IGNORE INTO plans (name, signals, price, days, featured) VALUES
  ('Starter',   50,    450,  7,  0),
  ('Standard',  100,   900,  14, 0),
  ('Pro',       500,   1800, 30, 1),
  ('Unlimited', 99999, 4500, 30, 0);
");

// Default config
$db->exec("
INSERT IGNORE INTO app_config (cfg_key, cfg_value) VALUES
  ('maintenance_mode', '0'),
  ('registration_open', '1'),
  ('signal_override', ''),
  ('server', 'online'),
  ('maintenance', '0'),
  ('telegram_handle', '@noraphillip'),
  ('telegram_url', 'https://t.me/noraphillip'),
  ('app_version', '1.0.0'),
  ('banner_name', 'VEERGAME HACK'),
  ('banner_image', ''),
  ('banner_url', 'https://www.veergame24.com');
");

echo '<h2 style=\"font-family:sans-serif;color:green;\">✓ Nora database installed successfully!</h2>';
echo '<p style=\"font-family:sans-serif;\">You can now delete <strong>install.php</strong> from your server.</p>';
echo '<p style=\"font-family:sans-serif;\"><a href=\"admin/\">→ Open Admin Panel</a></p>';
