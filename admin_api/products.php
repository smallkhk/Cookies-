<?php
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['nora_admin'])) { json_out(['status'=>'error','message'=>'Unauthorized'], 401); }

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

$action = post('action') ?: ($_GET['action'] ?? '');

if ($action === 'list') {
    json_out(['status'=>'ok','products'=>$db->query("SELECT id, name, image, link AS url, sort, active FROM vrx_items ORDER BY sort ASC, id ASC")->fetchAll()]);
}

if ($action === 'add') {
    $name  = post('name');
    $image = post('image');
    $url   = post('url');
    $sort  = (int)post('sort', 0);
    if (!$name) { json_out(['status'=>'error','message'=>'Name is required']); }
    $db->prepare("INSERT INTO vrx_items (name,image,link,sort,active) VALUES(?,?,?,?,1)")->execute([$name,$image,$url,$sort]);
    json_out(['status'=>'ok','id'=>$db->lastInsertId()]);
}

if ($action === 'update') {
    $id    = (int)post('id');
    $name  = post('name');
    $image = post('image');
    $url   = post('url');
    $sort  = (int)post('sort', 0);
    if (!$id || !$name) { json_out(['status'=>'error','message'=>'Missing fields']); }
    $db->prepare("UPDATE vrx_items SET name=?, image=?, link=?, sort=? WHERE id=?")->execute([$name,$image,$url,$sort,$id]);
    json_out(['status'=>'ok']);
}

if ($action === 'toggle') {
    $id = (int)post('id');
    $db->prepare("UPDATE vrx_items SET active = 1 - active WHERE id=?")->execute([$id]);
    json_out(['status'=>'ok']);
}

if ($action === 'delete') {
    $id = (int)post('id');
    $db->prepare("DELETE FROM vrx_items WHERE id=?")->execute([$id]);
    json_out(['status'=>'ok']);
}

json_out(['status'=>'error','message'=>'Unknown action']);
