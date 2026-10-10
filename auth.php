<?php
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'login') {
    $user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $pass = isset($_POST['password']) ? $_POST['password'] : '';
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        echo json_encode(array('status' => 'ok'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid credentials'));
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(array('status' => 'ok'));
    exit;
}

if ($action === 'check') {
    echo json_encode(array('status' => isset($_SESSION['admin']) && $_SESSION['admin'] ? 'ok' : 'error'));
    exit;
}

echo json_encode(array('status' => 'error', 'message' => 'Unknown action'));
