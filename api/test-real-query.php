<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';

header('Content-Type: application/json; charset=utf-8');
corsHeaders();

$game_id = 'archi';
$limit = 10;

try {
    $pdo = getDbConnection();
    
    echo "Connexion OK\n";
    
    // La VRAIE requête de leaderboard.php
    $sql = "
    SELECT 
        t.uuid,
        t.name,
        t.total_score,
        COUNT(cs.challenge_id) as challenges_completed
    FROM teams t
    LEFT JOIN challenge_scores cs ON t.id = cs.team_id
    WHERE t.game_id = :game_id
    GROUP BY t.id, t.uuid, t.name, t.total_score
    ORDER BY t.total_score DESC
    LIMIT :limit
    ";
    
    echo "Préparation requête...\n";
    $stmt = $pdo->prepare($sql);
    
    echo "Bind values...\n";
    $stmt->bindValue(':game_id', $game_id, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    echo "Exécution...\n";
    $stmt->execute();
    
    echo "Fetch...\n";
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Traitement...\n";
    foreach ($data as &$team) {
        $team['total_score'] = (int)$team['total_score'];
        $team['challenges_completed'] = (int)$team['challenges_completed'];
    }
    
    echo "JSON...\n";
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    echo "Code : " . $e->getCode() . "\n";
}
