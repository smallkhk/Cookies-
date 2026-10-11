<?php
require_once __DIR__ . '/db.php';
cors();

$db = getDB();

// Admin writes products to vrx_items; create it if missing.
$db->exec("
CREATE TABLE IF NOT EXISTS vrx_items (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR(120) NOT NULL,
    image  VARCHAR(255) DEFAULT '',
    link   TEXT,
    sort   INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$rows = $db->query("SELECT name, image, link AS url FROM vrx_items WHERE active = 1 ORDER BY sort ASC, id ASC")->fetchAll();

// The app parses this response as a raw JSON ARRAY (not wrapped in an object).
// If it starts with '{' the app treats it as an error and shows "message".
$list = [];
foreach ($rows as $r) {
    $list[] = [
        'name'  => $r['name'],
        'image' => $r['image'],
        'url'   => $r['url'],
    ];
}

header('Content-Type: application/json');
echo json_encode($list);
