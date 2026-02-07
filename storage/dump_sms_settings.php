<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=cultivation;port=3306', 'root', '');
    $stmt = $pdo->query("SELECT `key`, `value` FROM sms_settings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { echo "(no rows)\n"; exit; }
    foreach ($rows as $r) {
        echo $r['key'] . " => " . $r['value'] . "\n";
    }
} catch (Exception $e) {
    echo 'err:' . $e->getMessage() . "\n";
}
