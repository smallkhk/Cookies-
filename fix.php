<?php
require_once __DIR__ . '/db.php';

if (($_GET['run'] ?? '') !== '1') {
    echo '<a href="?run=1">Click here to run the fix</a>';
    exit;
}

$db = getDB();

$fixes = [
    "ALTER TABLE app_config ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "INSERT IGNORE INTO app_config (cfg_key, cfg_value) VALUES
       ('register_amount', '300'),
       ('1week_amount',    '450'),
       ('2week_amount',    '300')",
    "UPDATE app_config SET cfg_value='300' WHERE cfg_key='2week_amount'",
    "UPDATE plans SET price=300 WHERE name='Standard'",
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
