<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();

// Self-contained: create the products table on first hit so no reinstall is needed.
$db->exec("
CREATE TABLE IF NOT EXISTS products (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(120) NOT NULL,
    image    TEXT,
    url      TEXT,
    sort     INT DEFAULT 0,
    active   TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Seed defaults once if the table is empty.
$count = (int)$db->query("SELECT COUNT(*) AS c FROM products")->fetch()['c'];
if ($count === 0) {
    $seed = [
        ['Yaar Win Hack',   'https://i.ibb.co/6H7tYyy/yaarwin.png',  'https://yaarwin.com'],
        ['Jai Club Hack',   'https://i.ibb.co/6H7tYyy/jaiclub.png',  'https://jaiclub.com'],
        ['Jalwa Game Hack', 'https://i.ibb.co/6H7tYyy/jalwa.png',    'https://jalwagame.com'],
        ['Tiranga Lottery', 'https://i.ibb.co/6H7tYyy/tiranga.png',  'https://tirangagame.com'],
    ];
    $ins = $db->prepare("INSERT INTO products (name, image, url, sort) VALUES (?,?,?,?)");
    foreach ($seed as $i => $p) {
        $ins->execute([$p[0], $p[1], $p[2], $i]);
    }
}

$rows = $db->query("SELECT name, image, url FROM products WHERE active = 1 ORDER BY sort ASC, id ASC")->fetchAll();

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
