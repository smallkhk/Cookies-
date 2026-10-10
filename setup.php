<?php
if (php_sapi_name() === "cli" || !isset($_GET["run"])) { die("Append ?run=1 to URL"); }
$files = [];
$files['db.php'] = '<?php
require_once __DIR__ . \'/config.php\';

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                )
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([\'status\' => \'error\', \'message\' => \'DB error\']);
            exit;
        }
    }
    return $pdo;
}

function json_out($data, $code = 200) {
    http_response_code($code);
    header(\'Content-Type: application/json\');
    echo json_encode($data);
    exit;
}

function cors() {
    header(\'Access-Control-Allow-Origin: \' . CORS_ORIGIN);
    header(\'Access-Control-Allow-Methods: POST, GET, OPTIONS\');
    header(\'Access-Control-Allow-Headers: Content-Type, Authorization\');
    if ($_SERVER[\'REQUEST_METHOD\'] === \'OPTIONS\') { http_response_code(204); exit; }
}

function post($key, $default = \'\') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function log_activity($db, $type, $message, $user_id = null) {
    $stmt = $db->prepare("INSERT INTO activity_log (type, message, user_id) VALUES (?,?,?)");
    $stmt->execute([$type, $message, $user_id]);
}
';
$files['auth.php'] = '<?php
require_once __DIR__ . \'/config.php\';
session_start();

header(\'Content-Type: application/json\');

$action = isset($_POST[\'action\']) ? $_POST[\'action\'] : \'\';

if ($action === \'login\') {
    $user = isset($_POST[\'username\']) ? trim($_POST[\'username\']) : \'\';
    $pass = isset($_POST[\'password\']) ? $_POST[\'password\'] : \'\';
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION[\'admin\'] = true;
        echo json_encode(array(\'status\' => \'ok\'));
    } else {
        echo json_encode(array(\'status\' => \'error\', \'message\' => \'Invalid credentials\'));
    }
    exit;
}

if ($action === \'logout\') {
    session_destroy();
    echo json_encode(array(\'status\' => \'ok\'));
    exit;
}

if ($action === \'check\') {
    echo json_encode(array(\'status\' => isset($_SESSION[\'admin\']) && $_SESSION[\'admin\'] ? \'ok\' : \'error\'));
    exit;
}

echo json_encode(array(\'status\' => \'error\', \'message\' => \'Unknown action\'));
';
$files['get_config.php'] = '<?php
require_once __DIR__ . \'/db.php\';
cors();

$db = getDB();
$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$cfg = [];
foreach ($rows as $row) {
    $cfg[$row[\'cfg_key\']] = $row[\'cfg_value\'];
}

// The app reads these keys from the ROOT of the response (not nested under "config").
// maintenance must be a real JSON boolean (app uses JSONObject.optBoolean).
$server      = isset($cfg[\'server\']) ? $cfg[\'server\'] : \'online\';
$maintenance = !empty($cfg[\'maintenance\']) && $cfg[\'maintenance\'] !== \'0\';

json_out([
    \'status\'          => \'success\',
    \'server\'          => $server,
    \'maintenance\'     => $maintenance,
    \'floating\'        => isset($cfg[\'floating\']) ? $cfg[\'floating\'] : \'false\',
    \'telegram\'        => isset($cfg[\'telegram_url\']) ? $cfg[\'telegram_url\']
                         : (isset($cfg[\'telegram_handle\']) ? $cfg[\'telegram_handle\'] : \'\'),
    \'register_amount\' => isset($cfg[\'register_amount\']) ? $cfg[\'register_amount\'] : \'450\',
    \'1week_amount\'    => isset($cfg[\'1week_amount\']) ? $cfg[\'1week_amount\'] : \'450\',
    \'2week_amount\'    => isset($cfg[\'2week_amount\']) ? $cfg[\'2week_amount\'] : \'900\',
    \'app_version\'     => isset($cfg[\'app_version\']) ? $cfg[\'app_version\'] : \'1.0.0\',
]);
';
$files['get_main_product.php'] = '<?php
require_once __DIR__ . \'/db.php\';
cors();

$db = getDB();

// The app reads name/image/url from this response and shows it as the top banner.
// It also auto-opens `url` via an ACTION_VIEW intent, so url MUST be a valid
// http(s) link or the app throws ActivityNotFoundException.
$rows = $db->query("SELECT cfg_key, cfg_value FROM app_config")->fetchAll();
$cfg = [];
foreach ($rows as $r) {
    $cfg[$r[\'cfg_key\']] = $r[\'cfg_value\'];
}

$name  = !empty($cfg[\'banner_name\'])  ? $cfg[\'banner_name\']  : \'VEERGAME HACK\';
$image = !empty($cfg[\'banner_image\']) ? $cfg[\'banner_image\'] : \'https://i.ibb.co/6H7tYyy/veergame.png\';
$url   = !empty($cfg[\'banner_url\'])   ? $cfg[\'banner_url\']   : \'https://www.veergame24.com\';

json_out([
    \'status\' => \'success\',
    \'name\'   => $name,
    \'image\'  => $image,
    \'url\'    => $url,
]);
';
$files['get_prediction.php'] = '<?php
require_once __DIR__ . \'/db.php\';
cors();

if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    json_out([\'status\' => \'error\', \'message\' => \'POST required\']);
}

$device_id = post(\'device_id\');
$api_key   = post(\'api_key\');
$timestamp = post(\'timestamp\');
$signature = post(\'signature\');

if (!$device_id || !$api_key) {
    json_out([\'status\' => \'error\', \'message\' => \'Missing required fields\']);
}
if ($api_key !== \'lzr_turbo_api_key_v2.0\') {
    json_out([\'status\' => \'error\', \'message\' => \'Invalid API key\']);
}
$secret   = \'LZR_TURBO_SECRET_2026\';
$expected = md5($device_id . $secret . $timestamp);
if ($signature && $signature !== $expected) {
    json_out([\'status\' => \'error\', \'message\' => \'Invalid signature\']);
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM users WHERE device_id = ? LIMIT 1");
$stmt->execute([$device_id]);
$user = $stmt->fetch();
if (!$user) {
    json_out([\'status\' => \'error\', \'message\' => \'Device not registered\']);
}
if ($user[\'status\'] === \'blocked\') {
    json_out([\'status\' => \'blocked\', \'message\' => \'Account blocked. Contact support.\']);
}
if ((int)$user[\'signal_limit\'] <= 0) {
    // No predictions left -> app will show the Deposit button.
    json_out([\'status\' => \'error\', \'limit_left\' => \'0\', \'message\' => \'No predictions left. Please deposit.\']);
}

// Prediction type is chosen by the admin (app_config.prediction_type):
//   number  -> 0-9        (WinGo number)
//   color   -> RED/GREEN/VIOLET
//   size    -> BIG/SMALL
//   mixed   -> random of the above
$row  = $db->query("SELECT cfg_value FROM app_config WHERE cfg_key=\'prediction_type\'")->fetch();
$type = $row && $row[\'cfg_value\'] ? strtolower(trim($row[\'cfg_value\'])) : \'number\';

// Optional forced value from admin (signal_override) wins if set.
$ovr = $db->query("SELECT cfg_value FROM app_config WHERE cfg_key=\'signal_override\'")->fetch();
$override = $ovr && $ovr[\'cfg_value\'] !== \'\' ? trim($ovr[\'cfg_value\']) : \'\';

function pick_number() { return (string)random_int(0, 9); }
function pick_color()  { $c = [\'RED\',\'GREEN\',\'VIOLET\']; return $c[array_rand($c)]; }
function pick_size()   { $s = [\'BIG\',\'SMALL\'];          return $s[array_rand($s)]; }

if ($override !== \'\') {
    $prediction = strtoupper($override);
} elseif ($type === \'color\') {
    $prediction = pick_color();
} elseif ($type === \'size\') {
    $prediction = pick_size();
} elseif ($type === \'mixed\') {
    $pool = [pick_number(), pick_color(), pick_size()];
    $prediction = $pool[array_rand($pool)];
} else {
    $prediction = pick_number();
}

// The app calls this endpoint on every button tap and only afterwards decides
// whether to show CHANGE (AI on) or DEPOSIT (AI off / floating=true). So only
// charge the user when AI is active; a deposit-mode tap must NOT reduce limit.
$fl  = $db->query("SELECT cfg_value FROM app_config WHERE cfg_key=\'floating\'")->fetch();
$depositMode = $fl && trim((string)$fl[\'cfg_value\']) === \'true\';

if ($depositMode) {
    // Deposit gate showing - return current limit, do not consume.
    json_out([
        \'status\'     => \'success\',
        \'prediction\' => $prediction,
        \'limit_left\' => (string)$user[\'signal_limit\'],
    ]);
}

// Consume one prediction from the user\'s limit (real prediction / change).
$db->prepare("UPDATE users SET signal_limit = signal_limit - 1 WHERE id = ?")->execute([$user[\'id\']]);
$left = max(0, (int)$user[\'signal_limit\'] - 1);

log_activity($db, \'prediction\', "Prediction to {$user[\'username\']}: {$prediction} ({$type}), {$left} left", $user[\'id\']);

json_out([
    \'status\'     => \'success\',
    \'prediction\' => $prediction,
    \'limit_left\' => (string)$left,
]);
';
$files['product_lists.php'] = '<?php
require_once __DIR__ . \'/db.php\';
cors();

$db = getDB();

// Self-contained: create the products table on first hit so no reinstall is needed.
$db->exec("
CREATE TABLE IF NOT EXISTS products (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(120) NOT NULL,
    image    TEXT,
    url      TEXT,
    sort     INT DEFAULT 0,
    active   TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Seed defaults once if the table is empty.
$count = (int)$db->query("SELECT COUNT(*) AS c FROM products")->fetch()[\'c\'];
if ($count === 0) {
    // Games from the app screenshots. Edit these (name, logo, activate link)
    // any time in the admin panel → Products.
    $seed = [
        [\'Yaar Win Hack\',   \'\', \'https://www.yaarwin.com\'],
        [\'Jai Club Hack\',   \'\', \'https://www.jaiclub.com\'],
        [\'Jalwa Game Hack\', \'\', \'https://www.jalwa.game\'],
        [\'Tiranga Lottery\', \'\', \'https://www.tirangagames.com\'],
    ];
    $ins = $db->prepare("INSERT INTO products (name, image, url, sort) VALUES (?,?,?,?)");
    foreach ($seed as $i => $p) {
        $ins->execute([$p[0], $p[1], $p[2], $i]);
    }
}

$rows = $db->query("SELECT name, image, url FROM products WHERE active = 1 ORDER BY sort ASC, id ASC")->fetchAll();

// The app parses this response as a raw JSON ARRAY (not wrapped in an object).
// If it starts with \'{\' the app treats it as an error and shows "message".
$list = [];
foreach ($rows as $r) {
    $list[] = [
        \'name\'  => $r[\'name\'],
        \'image\' => $r[\'image\'],
        \'url\'   => $r[\'url\'],
    ];
}

header(\'Content-Type: application/json\');
echo json_encode($list);
';
$files['register_device.php'] = '<?php
require_once __DIR__ . \'/db.php\';
cors();

if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    json_out([\'status\' => \'error\', \'message\' => \'POST required\']);
}

$device_id = post(\'device_id\');
$api_key   = post(\'api_key\');
$timestamp = post(\'timestamp\');
$signature = post(\'signature\');

if (!$device_id || !$api_key) {
    json_out([\'status\' => \'error\', \'message\' => \'Missing required fields\']);
}

// Validate API key
if ($api_key !== \'lzr_turbo_api_key_v2.0\') {
    json_out([\'status\' => \'error\', \'message\' => \'Invalid API key\']);
}

// Validate signature: MD5(device_id + secret_key + timestamp)
$secret = \'LZR_TURBO_SECRET_2026\';
$expected = md5($device_id . $secret . $timestamp);
if ($signature && $signature !== $expected) {
    json_out([\'status\' => \'error\', \'message\' => \'Invalid signature\']);
}

$db = getDB();

// Look up device
$stmt = $db->prepare("SELECT * FROM users WHERE device_id = ? LIMIT 1");
$stmt->execute([$device_id]);
$user = $stmt->fetch();

if ($user) {
    if ($user[\'status\'] === \'blocked\') {
        json_out([\'status\' => \'blocked\', \'message\' => \'Your account has been blocked. Contact support.\']);
    }
    json_out([
        \'status\'   => \'success\',
        \'username\' => $user[\'username\'],
        \'limit\'    => (string)$user[\'signal_limit\'],
    ]);
}

// Device not registered — check if registration is open
$cfg = $db->query("SELECT cfg_value FROM app_config WHERE cfg_key=\'registration_open\'")->fetch();
if ($cfg && $cfg[\'cfg_value\'] === \'0\') {
    json_out([\'status\' => \'error\', \'message\' => \'Registration is closed. Contact admin.\']);
}

// Auto-register new device
$username = \'user_\' . strtolower(substr(md5($device_id), 0, 8));
$token    = bin2hex(random_bytes(20));

$stmt = $db->prepare(
    "INSERT INTO users (username, password, device_id, token, plan, signal_limit, balance, status) VALUES (?,?,?,?,?,?,?,?)"
);
$stmt->execute([$username, password_hash($token, PASSWORD_DEFAULT), $device_id, $token, \'Starter\', 5, 0, \'active\']);
$userId = $db->lastInsertId();

log_activity($db, \'register\', "Device auto-registered: {$username} ({$device_id})", $userId);

json_out([
    \'status\'   => \'success\',
    \'username\' => $username,
    \'limit\'    => \'5\',
]);
';
$files['admin_products.php'] = '<?php
require_once __DIR__ . \'/db.php\';
session_start();
if (empty($_SESSION[\'admin\'])) { json_out(array(\'status\'=>\'error\',\'message\'=>\'Unauthorized\'), 401); }

$db = getDB();

// Ensure the products table exists (same shape the app endpoint uses).
$db->exec("
CREATE TABLE IF NOT EXISTS products (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR(120) NOT NULL,
    image  TEXT,
    url    TEXT,
    sort   INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$action = post(\'action\');

if ($action === \'list\') {
    json_out([\'status\'=>\'ok\',\'products\'=>$db->query("SELECT * FROM products ORDER BY sort ASC, id ASC")->fetchAll()]);
}

if ($action === \'add\') {
    $name  = post(\'name\');
    $image = post(\'image\');
    $url   = post(\'url\');
    $sort  = (int)post(\'sort\', 0);
    if (!$name) { json_out([\'status\'=>\'error\',\'message\'=>\'Name is required\']); }
    $db->prepare("INSERT INTO products (name,image,url,sort,active) VALUES(?,?,?,?,1)")
       ->execute([$name,$image,$url,$sort]);
    json_out([\'status\'=>\'ok\',\'id\'=>$db->lastInsertId()]);
}

if ($action === \'update\') {
    $id    = (int)post(\'id\');
    $name  = post(\'name\');
    $image = post(\'image\');
    $url   = post(\'url\');
    $sort  = (int)post(\'sort\', 0);
    if (!$id || !$name) { json_out([\'status\'=>\'error\',\'message\'=>\'Missing fields\']); }
    $db->prepare("UPDATE products SET name=?, image=?, url=?, sort=? WHERE id=?")
       ->execute([$name,$image,$url,$sort,$id]);
    json_out([\'status\'=>\'ok\']);
}

if ($action === \'toggle\') {
    $id = (int)post(\'id\');
    $db->prepare("UPDATE products SET active = 1 - active WHERE id=?")->execute([$id]);
    json_out([\'status\'=>\'ok\']);
}

if ($action === \'delete\') {
    $id = (int)post(\'id\');
    $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    json_out([\'status\'=>\'ok\']);
}

json_out([\'status\'=>\'error\',\'message\'=>\'Unknown action\']);
';
$files['products.php'] = '<?php
require_once __DIR__ . \'/admin_products.php\';
';
foreach ($files as $name => $content) {
    file_put_contents(__DIR__ . "/" . $name, $content);
    echo "OK: $name<br>";
}
echo "<br><strong>Done! Delete setup.php now.</strong>";