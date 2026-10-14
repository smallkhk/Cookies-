<?php
require_once __DIR__ . '/db.php';

if (($_GET['run'] ?? '') !== '1') {
    echo '<a href="?run=1">Click here to run the fix</a>';
    exit;
}

$db = getDB();

$fixes = [
    "CREATE TABLE IF NOT EXISTS app_config (
        cfg_key    VARCHAR(80) PRIMARY KEY,
        cfg_value  TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "ALTER TABLE app_config ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "INSERT IGNORE INTO app_config (cfg_key, cfg_value) VALUES
       ('register_amount', '300'),
       ('1week_amount',    '450'),
       ('2week_amount',    '300')",
    "UPDATE app_config SET cfg_value='300' WHERE cfg_key='2week_amount'",
    "UPDATE plans SET price=300 WHERE name='Standard'",
    "ALTER TABLE vrx_users ADD COLUMN IF NOT EXISTS predictions_used INT DEFAULT 0",
    "ALTER TABLE vrx_users ADD COLUMN IF NOT EXISTS last_prediction_date DATE DEFAULT NULL",
    "INSERT IGNORE INTO app_config (cfg_key, cfg_value) VALUES ('prediction_limit', '5'), ('floating', 'false'), ('telegram_url', ''), ('telegram_handle', '')",
    "INSERT IGNORE INTO app_config (cfg_key, cfg_value) VALUES ('banner_name', 'VRX STORE'), ('banner_image', ''), ('banner_url', '')",
    "CREATE TABLE IF NOT EXISTS vrx_items (
        id     INT AUTO_INCREMENT PRIMARY KEY,
        name   VARCHAR(120) NOT NULL,
        image  VARCHAR(255) DEFAULT '',
        link   TEXT,
        sort   INT DEFAULT 0,
        active TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "INSERT IGNORE INTO vrx_items (id, name, image, link, sort) VALUES
       (1, 'Yaar Win Hack',   '', 'https://www.yaarwin.com',      0),
       (2, 'Jai Club Hack',   '', 'https://www.jaiclub.com',      1),
       (3, 'Jalwa Game Hack', '', 'https://www.jalwa.game',       2),
       (4, 'Tiranga Lottery', '', 'https://www.tirangagames.com', 3)",
];

foreach ($fixes as $sql) {
    try {
        $db->exec($sql);
        echo "OK<br>";
    } catch (Exception $e) {
        echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
    }
}

echo "<br><strong>Done! Delete fix.php now.</strong>";
