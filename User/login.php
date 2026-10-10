<?php
require_once __DIR__ . '/../db.php';
cors();

$phone    = post('phone');
$password = post('password');

if (!$phone || !$password) {
    json_out(['message' => 'Phone and password required']);
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM vrx_users WHERE phone = ? AND password = ? LIMIT 1");
$stmt->execute([$phone, $password]);
$user = $stmt->fetch();

if (!$user) {
    json_out(['message' => 'Invalid phone or password']);
}

json_out([
    'message' => 'Login successful',
    'user'    => ['name' => $user['name']],
]);
