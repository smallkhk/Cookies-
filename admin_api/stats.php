<?php
require_once __DIR__ . '/../db.php';
session_start();
if (empty($_SESSION['nora_admin'])) { json_out(['status'=>'error','message'=>'Unauthorized'], 401); }

$db = getDB();

$total   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$active  = $db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$blocked = $db->query("SELECT COUNT(*) FROM users WHERE status='blocked'")->fetchColumn();
$expired = $db->query("SELECT COUNT(*) FROM users WHERE status='expired'")->fetchColumn();
$signals = $db->query("SELECT SUM(signal_limit) FROM users")->fetchColumn();
$revenue = $db->query("SELECT SUM(balance) FROM users")->fetchColumn();
$today   = $db->query("SELECT COUNT(*) FROM activity_log WHERE DATE(created_at)=CURDATE() AND type='signal'")->fetchColumn();
$new_today = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetchColumn();

$recent = $db->query(
    "SELECT username,plan,signal_limit,status,created_at FROM users ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

json_out([
    'status'    => 'ok',
    'total'     => (int)$total,
    'active'    => (int)$active,
    'blocked'   => (int)$blocked,
    'expired'   => (int)$expired,
    'signals_remaining' => (int)$signals,
    'revenue'   => (float)$revenue,
    'signals_today' => (int)$today,
    'new_today' => (int)$new_today,
    'recent'    => $recent,
]);
