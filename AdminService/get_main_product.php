<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();
$db->exec("
CREATE TABLE IF NOT EXISTS vrx_main_product (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(120) NOT NULL,
    image VARCHAR(255) DEFAULT '',
    link  TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$row = $db->query("SELECT name, image, link FROM vrx_main_product ORDER BY id DESC LIMIT 1")->fetch();
if (!$row) {
    $row = ['name' => 'VRX STORE', 'image' => '', 'link' => ''];
}
json_out($row);
