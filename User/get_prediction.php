<?php
require_once __DIR__ . '/../db.php';
cors();

$db = getDB();

$username = post('username');

// Get user's daily prediction limit from app_config
$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$cfg = [];
foreach ($rows as $r) { $cfg[$r['cfg_key']] = $r['cfg_value']; }

$daily_limit = (int)($cfg['prediction_limit'] ?? 5);

// Check/reset daily usage
if ($username) {
    $user = $db->prepare("SELECT * FROM vrx_users WHERE phone=? LIMIT 1");
    $user->execute([$username]);
    $user = $user->fetch();

    if ($user) {
        $today = date('Y-m-d');
        $last_date = substr($user['last_prediction_date'] ?? '', 0, 10);
        $used = ($last_date === $today) ? (int)($user['predictions_used'] ?? 0) : 0;

        if ($used >= $daily_limit) {
            json_out(['status' => 'error', 'message' => 'Daily limit reached', 'limit_left' => '0']);
        }

        // Generate prediction
        $options = ['BIG', 'SMALL'];
        $prediction = $options[array_rand($options)];
        $used++;
        $left = max(0, $daily_limit - $used);

        // Update usage (ignore if columns missing)
        try {
            $db->prepare("UPDATE vrx_users SET predictions_used=?, last_prediction_date=? WHERE phone=?")
               ->execute([$used, $today, $username]);
        } catch (Exception $e) {}

        json_out(['status' => 'success', 'prediction' => $prediction, 'limit_left' => (string)$left]);
    }
}

// No user found or no username — still return a prediction
$options = ['BIG', 'SMALL'];
$prediction = $options[array_rand($options)];
json_out(['status' => 'success', 'prediction' => $prediction, 'limit_left' => (string)$daily_limit]);
