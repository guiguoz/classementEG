<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$uuid = 'feec7707-05d3-45c5-9473-47c655424af4'; // Fufu

try {
    $pdo = getDB();
    
    // Récupérer team_id
    $stmt = $pdo->prepare('SELECT id, name, total_score FROM teams WHERE uuid = ?');
    $stmt->execute([$uuid]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$team) {
        echo json_encode(['error' => 'Équipe introuvable']);
        exit;
    }
    
    $teamId = $team['id'];
    
    // Vérifier scores
    $stmt2 = $pdo->prepare('
        SELECT challenge_id, points, attempts, completed_at
        FROM challenge_scores
        WHERE team_id = ?
        ORDER BY completed_at ASC
    ');
    $stmt2->execute([$teamId]);
    $scores = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier session
    $stmt3 = $pdo->prepare('SELECT * FROM team_sessions WHERE team_id = ?');
    $stmt3->execute([$teamId]);
    $session = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'team' => $team,
        'scores_count' => count($scores),
        'scores' => $scores,
        'session' => $session
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
