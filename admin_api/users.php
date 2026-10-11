<?php
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['nora_admin'])) { json_out(['status'=>'error','message'=>'Unauthorized'], 401); }

$db     = getDB();
$action = post('action') ?: ($_GET['action'] ?? '');

if ($action === 'list') {
    $stmt = $db->query("SELECT id,username,device_id,plan,signal_limit,balance,status,created_at FROM users ORDER BY created_at DESC");
    json_out(['status'=>'ok','users'=>$stmt->fetchAll()]);
}

if ($action === 'add') {
    $username = post('username');
    $password = post('password');
    $plan     = post('plan', 'Starter');
    $limit    = (int)post('limit', 50);
    $balance  = (float)post('balance', 0);
    $status   = post('status', 'active');
    $device   = post('device_id');

    if (!$username || !$password) { json_out(['status'=>'error','message'=>'Username and password required']); }

    $exists = $db->prepare("SELECT id FROM users WHERE username=?");
    $exists->execute([$username]);
    if ($exists->fetch()) { json_out(['status'=>'error','message'=>'Username already exists']); }

    $hash  = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(20));

    $db->prepare("INSERT INTO users (username,password,device_id,token,plan,signal_limit,balance,status) VALUES(?,?,?,?,?,?,?,?)")
       ->execute([$username,$hash,$device,$token,$plan,$limit,$balance,$status]);

    log_activity($db,'admin',"Admin added user: $username");
    json_out(['status'=>'ok','id'=>$db->lastInsertId()]);
}

if ($action === 'update') {
    $id      = (int)post('id');
    $plan    = post('plan');
    $limit   = (int)post('limit');
    $balance = (float)post('balance');
    $status  = post('status');

    $stmt = $db->prepare("UPDATE users SET plan=?,signal_limit=?,balance=?,status=? WHERE id=?");
    $stmt->execute([$plan,$limit,$balance,$status,$id]);

    $user = $db->prepare("SELECT username FROM users WHERE id=?"); $user->execute([$id]);
    $u = $user->fetch();
    log_activity($db,'admin',"Admin updated user: ".($u['username']??$id));
    json_out(['status'=>'ok']);
}

if ($action === 'delete') {
    $id = (int)post('id');
    $user = $db->prepare("SELECT username FROM users WHERE id=?"); $user->execute([$id]);
    $u = $user->fetch();
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    log_activity($db,'admin',"Admin deleted user: ".($u['username']??$id));
    json_out(['status'=>'ok']);
}

if ($action === 'block') {
    $id = (int)post('id');
    $db->prepare("UPDATE users SET status='blocked' WHERE id=?")->execute([$id]);
    json_out(['status'=>'ok']);
}

if ($action === 'unblock') {
    $id = (int)post('id');
    $db->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]);
    json_out(['status'=>'ok']);
}

if ($action === 'topup') {
    $id     = (int)post('id');
    $add    = (int)post('signals', 0);
    $db->prepare("UPDATE users SET signal_limit=signal_limit+? WHERE id=?")->execute([$add,$id]);
    json_out(['status'=>'ok']);
}

if ($action === 'reset_password') {
    $id   = (int)post('id');
    $pass = post('password');
    if (!$pass) { json_out(['status'=>'error','message'=>'Password required']); }
    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($pass,PASSWORD_DEFAULT),$id]);
    json_out(['status'=>'ok']);
}

json_out(['status'=>'error','message'=>'Unknown action']);
