<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=cultivation', 'root', '');
    $map = json_encode([
        'api_key' => '{api_key}',
        'msg' => '{message}',
        'to' => '{to}',
        'sender_id' => '{sender}'
    ], JSON_PRETTY_PRINT);

    $stmt = $pdo->prepare("REPLACE INTO sms_settings (`key`,`value`) VALUES (:k,:v)");
    $stmt->execute([':k' => 'http_param_map', ':v' => $map]);
    $stmt->execute([':k' => 'sender', ':v' => 'SCHOOL']);
    echo "updated\n";
} catch (Exception $e) {
    echo 'err:' . $e->getMessage() . "\n";
}
