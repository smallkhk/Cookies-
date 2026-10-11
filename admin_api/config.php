<?php
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['nora_admin'])) { json_out(['status'=>'error','message'=>'Unauthorized'], 401); }

$db     = getDB();
$action = post('action');

if ($action === 'get') {
    $rows = $db->query("SELECT cfg_key,cfg_value FROM app_config")->fetchAll();
    $cfg = [];
    foreach ($rows as $r) { $cfg[$r['cfg_key']] = $r['cfg_value']; }
    json_out(['status'=>'ok','config'=>$cfg]);
}

if ($action === 'set') {
    $key = post('key');
    $val = post('value');
    if (!$key) { json_out(['status'=>'error','message'=>'Key required']); }
    $db->prepare("INSERT INTO app_config (cfg_key,cfg_value) VALUES(?,?) ON DUPLICATE KEY UPDATE cfg_value=?, updated_at=NOW()")
       ->execute([$key,$val,$val]);
    json_out(['status'=>'ok']);
}

if ($action === 'batch') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { $data = $_POST; }
    $stmt = $db->prepare("INSERT INTO app_config (cfg_key,cfg_value) VALUES(?,?) ON DUPLICATE KEY UPDATE cfg_value=?, updated_at=NOW()");
    foreach ($data as $k => $v) {
        if ($k === 'action') continue;
        $stmt->execute([$k,$v,$v]);
    }
    json_out(['status'=>'ok']);
}

json_out(['status'=>'error','message'=>'Unknown action']);
