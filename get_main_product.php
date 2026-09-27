<?php
require_once __DIR__ . '/../db.php';
cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['status' => 'error', 'message' => 'POST required']);
}

$db = getDB();

$device_id = post('device_id');
$api_key   = post('api_key');
$timestamp = post('timestamp');
$signature = post('signature');

if (!$device_id || !$api_key) {
    json_out(['status' => 'error', 'message' => 'Missing required fields']);
}

// Validate API key
if ($api_key !== 'lzr_turbo_api_key_v2.0') {
    json_out(['status' => 'error', 'message' => 'Invalid API key']);
}

// Validate signature: MD5(device_id + secret_key + timestamp)
$secret   = 'LZR_TURBO_SECRET_2026';
$expected = md5($device_id . $secret . $timestamp);
if ($signature && $signature !== $expected) {
    json_out(['status' => 'error', 'message' => 'Invalid signature']);
}

// Look up device
$stmt = $db->prepare("SELECT * FROM users WHERE device_id = ? LIMIT 1");
$stmt->execute([$device_id]);
$user = $stmt->fetch();

if (!$user) {
    json_out(['status' => 'error', 'message' => 'Device not registered']);
}

if ($user['status'] === 'blocked') {
    json_out(['status' => 'blocked', 'limit' => 0, 'message' => 'Account blocked. Contact support.']);
}

if ($user['signal_limit'] <= 0) {
    json_out(['status' => 'blocked', 'limit' => 0, 'message' => 'Signal limit reached. Contact admin to top up.']);
}

// Signal override from config
$ovr = $db->query("SELECT cfg_value FROM app_config WHERE cfg_key='signal_override'")->fetch();
$signals = ['BUY', 'SELL', 'HOLD'];
if (!empty($ovr['cfg_value'])) {
    $signal = strtoupper($ovr['cfg_value']);
} else {
    $signal = $signals[array_rand($signals)];
}

// Decrement limit
$db->prepare("UPDATE users SET signal_limit = signal_limit - 1 WHERE id = ?")->execute([$user['id']]);
$newLimit = max(0, $user['signal_limit'] - 1);

log_activity($db, 'signal', "Signal sent to {$user['username']}: $signal (remaining: $newLimit)", $user['id']);

json_out([
    'status'        => 'success',
    'product_lists' => $signal,
    'limit'         => (string)$newLimit,
    'plan'          => $user['plan'],
]);
