<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=cultivation', 'root', '');
    $jobs = $pdo->query("SELECT COUNT(*) AS c FROM jobs")->fetch(PDO::FETCH_ASSOC)['c'];
    $failed = $pdo->query("SELECT COUNT(*) AS c FROM failed_jobs")->fetch(PDO::FETCH_ASSOC)['c'];
    echo "jobs:" . $jobs . "\nfailed_jobs:" . $failed . "\n";
} catch (Exception $e) {
    echo 'err:' . $e->getMessage();
}
