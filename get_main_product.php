<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();

// The app reads name/image/url from this response and shows it as the top banner.
// It also auto-opens `url` via an ACTION_VIEW intent, so url MUST be a valid
// http(s) link or the app throws ActivityNotFoundException.
$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$cfg = [];
foreach ($rows as $r) {
    $cfg[$r['cfg_key']] = $r['cfg_value'];
}

$name  = !empty($cfg['banner_name'])  ? $cfg['banner_name']  : 'VEERGAME HACK';
$image = !empty($cfg['banner_image']) ? $cfg['banner_image'] : 'https://i.ibb.co/6H7tYyy/veergame.png';
$url   = !empty($cfg['banner_url'])   ? $cfg['banner_url']   : 'https://www.veergame24.com';

json_out([
    'status' => 'success',
    'name'   => $name,
    'image'  => $image,
    'url'    => $url,
]);
