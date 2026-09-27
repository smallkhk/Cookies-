<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();
$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$config = [];
foreach ($rows as $row) {
    $config[$row['cfg_key']] = $row['cfg_value'];
}

json_out(['status' => 'success', 'config' => $config]);
