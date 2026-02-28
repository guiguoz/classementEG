<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test leaderboard...\n\n";

// Test 1 : Connexion DB
try {
    require_once 'config.php';
    require_once 'db.php';
    $pdo = getDbConnection();
    echo "✅ Connexion DB OK\n";
} catch (Exception $e) {
    echo "❌ Erreur DB : " . $e->getMessage() . "\n";
    exit;
}

// Test 2 : Requête
try {
    $stmt = $pdo->prepare("
        SELECT t.uuid, t.name, t.total_score,
               COUNT(cs.challenge_id) as challenges_completed
        FROM teams t
        LEFT JOIN challenge_scores cs ON t.uuid = cs.team_uuid
        WHERE t.game_id = 'archi'
        GROUP BY t.uuid
        ORDER BY t.total_score DESC
        LIMIT 10
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Requête OK : " . count($data) . " équipes\n";
} catch (Exception $e) {
    echo "❌ Erreur requête : " . $e->getMessage() . "\n";
    exit;
}

// Test 3 : JSON
try {
    $json = json_encode(['success' => true, 'data' => $data]);
    echo "✅ JSON OK : " . strlen($json) . " octets\n";
} catch (Exception $e) {
    echo "❌ Erreur JSON : " . $e->getMessage() . "\n";
}

// Test 4 : Cache
try {
    $tmpDir = sys_get_temp_dir();
    $testFile = $tmpDir . '/test_cache.json';
    file_put_contents($testFile, 'test');
    $content = file_get_contents($testFile);
    unlink($testFile);
    echo "✅ Cache OK (dossier : $tmpDir)\n";
} catch (Exception $e) {
    echo "❌ Erreur cache : " . $e->getMessage() . "\n";
}

echo "\n✅ Tous les tests passés !";
