<?php
require_once __DIR__ . '/../db.php';
cors();

$sign = post('sign');
if (!$sign) {
    json_out(['message' => 'Sign Missing']);
}

json_out(['message' => 'Server Connected']);
