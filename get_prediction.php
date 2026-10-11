<?php
require_once __DIR__ . '/db.php';
cors();

$db = getDB();

$username = post('username');

$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$cfg = [];
foreach ($rows as $r) { $cfg[$r['cfg_key']] = $r['cfg_value']; }

$daily_limit = (int)($cfg['prediction_limit'] ?? 5);
$floating    = trim((string)($cfg['floating'] ?? 'false'));

// When floating=true the app is in deposit-gate mode; return success so the app
// can show the deposit button without an error popup.
if ($floating === 'true') {
    json_out(['status' => 'success', 'prediction' => 'BIG', 'limit_left' => '0']);
}

if ($username) {
    $stmt = $db->prepare("SELECT * FROM vrx_users WHERE phone=? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $today     = date('Y-m-d');
        $last_date = substr($user['last_prediction_date'] ?? '', 0, 10);
        $used      = ($last_date === $today) ? (int)($user['predictions_used'] ?? 0) : 0;

        if ($used >= $daily_limit) {
            json_out(['status' => 'error', 'message' => 'Daily limit reached', 'limit_left' => '0']);
        }

        $prediction = ['BIG', 'SMALL'][array_rand(['BIG', 'SMALL'])];
        $used++;
        $left = max(0, $daily_limit - $used);

        try {
            $db->prepare("UPDATE vrx_users SET predictions_used=?, last_prediction_date=? WHERE phone=?")
               ->execute([$used, $today, $username]);
        } catch (Exception $e) {}

        json_out(['status' => 'success', 'prediction' => $prediction, 'limit_left' => (string)$left]);
    }
}

$prediction = ['BIG', 'SMALL'][array_rand(['BIG', 'SMALL'])];
json_out(['status' => 'success', 'prediction' => $prediction, 'limit_left' => (string)$daily_limit]);
