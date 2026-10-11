<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();

// Read banner config saved by admin (keys: banner_name, banner_image, banner_url)
$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config WHERE cfg_key IN ('banner_name','banner_image','banner_url')")->fetchAll();
$cfg = [];
foreach ($rows as $r) { $cfg[$r['cfg_key']] = $r['cfg_value']; }

$row = [
    'name'  => $cfg['banner_name']  ?? 'VRX STORE',
    'image' => $cfg['banner_image'] ?? '',
    'link'  => $cfg['banner_url']   ?? '',
];

json_out($row);
