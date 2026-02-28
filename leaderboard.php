<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/challenges.php';

checkRateLimit();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    getLeaderboard();
}

function getLeaderboard() {
    $limit = (int)($_GET['limit'] ?? 100);
    $limit = min($limit, 500);
    $gameId = (string)($_GET['game_id'] ?? 'archi');

    if (!isValidGameId($gameId)) respondError('Jeu inconnu');

    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            t.name,
            t.total_score,
            t.created_at,
            COUNT(cs.id) as challenges_completed,
            t.is_finished
        FROM teams t
        LEFT JOIN challenge_scores cs ON t.id = cs.team_id
        WHERE t.game_id = ?
        GROUP BY t.id
        ORDER BY t.total_score DESC, t.created_at ASC
        LIMIT ?
    ");
    $stmt->bindValue(1, $gameId, PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    respondSuccess($leaderboard);
}
?>
