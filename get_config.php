<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();
$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$cfg = [];
foreach ($rows as $row) {
    $cfg[$row['cfg_key']] = $row['cfg_value'];
}

// The app reads these keys from the ROOT of the response (not nested under "config").
// maintenance must be a real JSON boolean (app uses JSONObject.optBoolean).
$server      = isset($cfg['server']) ? $cfg['server'] : 'online';
$maintenance = !empty($cfg['maintenance']) && $cfg['maintenance'] !== '0';

json_out([
    'status'          => 'success',
    'server'          => $server,
    'maintenance'     => $maintenance,
    'floating'        => isset($cfg['floating']) ? $cfg['floating'] : 'true',
    'telegram'        => isset($cfg['telegram_url']) ? $cfg['telegram_url']
                         : (isset($cfg['telegram_handle']) ? $cfg['telegram_handle'] : ''),
    'register_amount' => isset($cfg['register_amount']) ? $cfg['register_amount'] : '450',
    '1week_amount'    => isset($cfg['1week_amount']) ? $cfg['1week_amount'] : '450',
    '2week_amount'    => isset($cfg['2week_amount']) ? $cfg['2week_amount'] : '900',
    'app_version'     => isset($cfg['app_version']) ? $cfg['app_version'] : '1.0.0',
]);
