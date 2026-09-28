<?php
require_once __DIR__ . '/../db.php';
cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['status' => 'error', 'message' => 'POST required']);
}

$device_id = post('device_id');
$api_key   = post('api_key');
$timestamp = post('timestamp');
$signature = post('signature');

if (!$device_id || !$api_key) {
    json_out(['status' => 'error', 'message' => 'Missing required fields']);
}
if ($api_key !== 'lzr_turbo_api_key_v2.0') {
    json_out(['status' => 'error', 'message' => 'Invalid API key']);
}
$secret   = 'LZR_TURBO_SECRET_2026';
$expected = md5($device_id . $secret . $timestamp);
if ($signature && $signature !== $expected) {
    json_out(['status' => 'error', 'message' => 'Invalid signature']);
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM users WHERE device_id = ? LIMIT 1");
$stmt->execute([$device_id]);
$user = $stmt->fetch();
if (!$user) {
    json_out(['status' => 'error', 'message' => 'Device not registered']);
}
if ($user['status'] === 'blocked') {
    json_out(['status' => 'blocked', 'message' => 'Account blocked. Contact support.']);
}
if ((int)$user['signal_limit'] <= 0) {
    // No predictions left -> app will show the Deposit button.
    json_out(['status' => 'error', 'limit_left' => '0', 'message' => 'No predictions left. Please deposit.']);
}

// Prediction type is chosen by the admin (app_config.prediction_type):
//   number  -> 0-9        (WinGo number)
//   color   -> RED/GREEN/VIOLET
//   size    -> BIG/SMALL
//   mixed   -> random of the above
$row  = $db->query("SELECT cfg_value FROM app_config WHERE cfg_key='prediction_type'")->fetch();
$type = $row && $row['cfg_value'] ? strtolower(trim($row['cfg_value'])) : 'number';

// Optional forced value from admin (signal_override) wins if set.
$ovr = $db->query("SELECT cfg_value FROM app_config WHERE cfg_key='signal_override'")->fetch();
$override = $ovr && $ovr['cfg_value'] !== '' ? trim($ovr['cfg_value']) : '';

function pick_number() { return (string)random_int(0, 9); }
function pick_color()  { $c = ['RED','GREEN','VIOLET']; return $c[array_rand($c)]; }
function pick_size()   { $s = ['BIG','SMALL'];          return $s[array_rand($s)]; }

if ($override !== '') {
    $prediction = strtoupper($override);
} elseif ($type === 'color') {
    $prediction = pick_color();
} elseif ($type === 'size') {
    $prediction = pick_size();
} elseif ($type === 'mixed') {
    $pool = [pick_number(), pick_color(), pick_size()];
    $prediction = $pool[array_rand($pool)];
} else {
    $prediction = pick_number();
}

// Consume one prediction from the user's limit.
$db->prepare("UPDATE users SET signal_limit = signal_limit - 1 WHERE id = ?")->execute([$user['id']]);
$left = max(0, (int)$user['signal_limit'] - 1);

log_activity($db, 'prediction', "Prediction to {$user['username']}: {$prediction} ({$type}), {$left} left", $user['id']);

json_out([
    'status'     => 'success',
    'prediction' => $prediction,
    'limit_left' => (string)$left,
]);
