<?php
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['nora_admin'])) { json_out(['status'=>'error','message'=>'Unauthorized'], 401); }

$db     = getDB();
$action = post('action');

if ($action === 'list') {
    json_out(['status'=>'ok','plans'=>$db->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll()]);
}

if ($action === 'add') {
    $name     = post('name');
    $signals  = (int)post('signals');
    $price    = (float)post('price');
    $days     = (int)post('days', 30);
    $featured = (int)post('featured', 0);
    if (!$name || !$signals || !$price) { json_out(['status'=>'error','message'=>'Fill all fields']); }
    $db->prepare("INSERT INTO plans (name,signals,price,days,featured) VALUES(?,?,?,?,?)")->execute([$name,$signals,$price,$days,$featured]);
    json_out(['status'=>'ok','id'=>$db->lastInsertId()]);
}

if ($action === 'delete') {
    $id = (int)post('id');
    $db->prepare("DELETE FROM plans WHERE id=?")->execute([$id]);
    json_out(['status'=>'ok']);
}

if ($action === 'set_featured') {
    $id = (int)post('id');
    $db->exec("UPDATE plans SET featured=0");
    $db->prepare("UPDATE plans SET featured=1 WHERE id=?")->execute([$id]);
    json_out(['status'=>'ok']);
}

json_out(['status'=>'error','message'=>'Unknown action']);
