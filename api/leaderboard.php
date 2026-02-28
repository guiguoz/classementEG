<?php
require_once 'config.php';
require_once 'db.php';
require_once 'utils.php';

header('Content-Type: application/json; charset=utf-8');
corsHeaders();

$game_id = $_GET['game_id'] ?? 'archi';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$limit = max(1, min(100, $limit));

try {
    $pdo = getDbConnection();
    
    $sql = "
    SELECT 
        t.uuid,
        t.name,
        t.total_score,
        COUNT(cs.challenge_id) as challenges_completed
    FROM teams t
    LEFT JOIN challenge_scores cs ON t.id = cs.team_id
    WHERE t.game_id = :game_id
    GROUP BY t.uuid
    ORDER BY t.total_score DESC
    LIMIT :limit
";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':game_id', $game_id, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($data as &$team) {
        $team['total_score'] = (int)$team['total_score'];
        $team['challenges_completed'] = (int)$team['challenges_completed'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur base de données'
    ]);
}
