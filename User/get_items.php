<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();
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

$rows = $db->query("SELECT name, image, link FROM vrx_items WHERE active = 1 ORDER BY sort ASC, id ASC")->fetchAll();
echo json_encode($rows);
