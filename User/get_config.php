<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();

$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$cfg = [];
foreach ($rows as $r) {
    $cfg[$r['cfg_key']] = $r['cfg_value'];
}

$defaults = [
    'register_amount' => '300',
    '1week_amount'    => '450',
    '2week_amount'    => '300',
    'floating'        => 'false',
    'telegram'        => '',
];

$out = array_merge($defaults, $cfg);

json_out($out);
