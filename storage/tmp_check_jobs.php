<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=cultivation', 'root', '');
    $stmt = $pdo->query("SHOW TABLES LIKE 'jobs'");
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
    echo count($rows) ? "exists" : "missing";
} catch (Exception $e) {
    echo 'err:' . $e->getMessage();
}
