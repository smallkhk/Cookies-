<?php
require_once __DIR__ . '/../db.php';
session_start();

$action = post('action');

if ($action === 'login') {
    $u = post('username');
    $p = post('password');
    if ($u === ADMIN_USERNAME && $p === ADMIN_PASSWORD) {
        $_SESSION['nora_admin'] = true;
        json_out(['status' => 'ok']);
    }
    json_out(['status' => 'error', 'message' => 'Wrong credentials']);
}

if ($action === 'logout') {
    session_destroy();
    json_out(['status' => 'ok']);
}

if ($action === 'check') {
    json_out(['status' => isset($_SESSION['nora_admin']) ? 'ok' : 'unauth']);
}

json_out(['status' => 'error', 'message' => 'Unknown action']);
