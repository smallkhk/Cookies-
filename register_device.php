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

// Validate API key
if ($api_key !== 'lzr_turbo_api_key_v2.0') {
    json_out(['status' => 'error', 'message' => 'Invalid API key']);
}

// Validate signature: MD5(device_id + secret_key + timestamp)
$secret = 'LZR_TURBO_SECRET_2026';
$expected = md5($device_id . $secret . $timestamp);
if ($signature && $signature !== $expected) {
    json_out(['status' => 'error', 'message' => 'Invalid signature']);
}

$db = getDB();

// Look up device
$stmt = $db->prepare("SELECT * FROM users WHERE device_id = ? LIMIT 1");
$stmt->execute([$device_id]);
$user = $stmt->fetch();

if ($user) {
    if ($user['status'] === 'blocked') {
        json_out(['status' => 'blocked', 'message' => 'Your account has been blocked. Contact support.']);
    }
    json_out([
        'status'   => 'success',
        'username' => $user['username'],
        'limit'    => (string)$user['signal_limit'],
    ]);
}

// Device not registered — check if registration is open
$cfg = $db->query("SELECT cfg_value FROM app_config WHERE cfg_key='registration_open'")->fetch();
if ($cfg && $cfg['cfg_value'] === '0') {
    json_out(['status' => 'error', 'message' => 'Registration is closed. Contact admin.']);
}

// Auto-register new device
$username = 'user_' . strtolower(substr(md5($device_id), 0, 8));
$token    = bin2hex(random_bytes(20));

$stmt = $db->prepare(
    "INSERT INTO users (username, password, device_id, token, plan, signal_limit, balance, status) VALUES (?,?,?,?,?,?,?,?)"
);
$stmt->execute([$username, password_hash($token, PASSWORD_DEFAULT), $device_id, $token, 'Starter', 5, 0, 'active']);
$userId = $db->lastInsertId();

log_activity($db, 'register', "Device auto-registered: {$username} ({$device_id})", $userId);

json_out([
    'status'   => 'success',
    'username' => $username,
    'limit'    => '5',
]);
