<?php
require_once __DIR__ . '/../db.php';
cors();

$name     = post('name');
$phone    = post('phone');
$password = post('password');

if (!$name || !$phone || !$password) {
    json_out(['message' => 'All fields required']);
}

$db = getDB();
$db->exec("
CREATE TABLE IF NOT EXISTS vrx_users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(100) NOT NULL,
    phone    VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$check = $db->prepare("SELECT id FROM vrx_users WHERE phone = ? LIMIT 1");
$check->execute([$phone]);
if ($check->fetch()) {
    json_out(['message' => 'Phone already registered']);
}

$db->prepare("INSERT INTO vrx_users (name, phone, password) VALUES (?, ?, ?)")
   ->execute([$name, $phone, $password]);

json_out([
    'message' => 'Registered successfully',
    'user'    => ['name' => $name],
]);
