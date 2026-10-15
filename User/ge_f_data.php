<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();
$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$cfg = [];
foreach ($rows as $row) {
    $cfg[$row['cfg_key']] = $row['cfg_value'];
}

$status = isset($cfg['floating']) ? $cfg['floating'] : 'false';

json_out(['status' => $status]);
