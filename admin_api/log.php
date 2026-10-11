<?php
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['nora_admin'])) { json_out(['status'=>'error','message'=>'Unauthorized'], 401); }

$db    = getDB();
$limit = min((int)($_GET['limit'] ?? 50), 200);

$rows = $db->prepare(
    "SELECT l.id, l.type, l.message, l.created_at, u.username
     FROM activity_log l
     LEFT JOIN users u ON u.id = l.user_id
     ORDER BY l.created_at DESC
     LIMIT ?"
);
$rows->execute([$limit]);

json_out(['status'=>'ok','log'=>$rows->fetchAll()]);
