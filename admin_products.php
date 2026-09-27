<?php
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['nora_admin'])) { json_out(['status'=>'error','message'=>'Unauthorized'], 401); }

$db = getDB();

// Ensure the products table exists (same shape the app endpoint uses).
$db->exec("
CREATE TABLE IF NOT EXISTS products (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR(120) NOT NULL,
    image  TEXT,
    url    TEXT,
    sort   INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$action = post('action');

if ($action === 'list') {
    json_out(['status'=>'ok','products'=>$db->query("SELECT * FROM products ORDER BY sort ASC, id ASC")->fetchAll()]);
}

if ($action === 'add') {
    $name  = post('name');
    $image = post('image');
    $url   = post('url');
    $sort  = (int)post('sort', 0);
    if (!$name) { json_out(['status'=>'error','message'=>'Name is required']); }
    $db->prepare("INSERT INTO products (name,image,url,sort,active) VALUES(?,?,?,?,1)")
       ->execute([$name,$image,$url,$sort]);
    json_out(['status'=>'ok','id'=>$db->lastInsertId()]);
}

if ($action === 'update') {
    $id    = (int)post('id');
    $name  = post('name');
    $image = post('image');
    $url   = post('url');
    $sort  = (int)post('sort', 0);
    if (!$id || !$name) { json_out(['status'=>'error','message'=>'Missing fields']); }
    $db->prepare("UPDATE products SET name=?, image=?, url=?, sort=? WHERE id=?")
       ->execute([$name,$image,$url,$sort,$id]);
    json_out(['status'=>'ok']);
}

if ($action === 'toggle') {
    $id = (int)post('id');
    $db->prepare("UPDATE products SET active = 1 - active WHERE id=?")->execute([$id]);
    json_out(['status'=>'ok']);
}

if ($action === 'delete') {
    $id = (int)post('id');
    $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    json_out(['status'=>'ok']);
}

json_out(['status'=>'error','message'=>'Unknown action']);
