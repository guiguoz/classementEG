<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/challenges.php';

header('Content-Type: application/json; charset=utf-8');

$uuid = 'feec7707-05d3-45c5-9473-47c655424af4'; // Fufu
$challengeId = 'T1';
$answer = 'colombe'; // Réponse correcte T1

try {
    $db = getDB();
    
    // Récupérer équipe
    $stmt = $db->prepare('SELECT id, game_id FROM teams WHERE uuid = ?');
    $stmt->execute([$uuid]);
    $team = $stmt->fetch();
    
    if (!$team) {
        echo json_encode(['error' => 'Équipe introuvable']);
        exit;
    }
    
    $teamId = $team['id'];
    $gameId = $team['game_id'];
    
    // Vérifier réponse
    $answers = getGameAnswers($gameId);
    $correct = isset($answers[$challengeId]) && mb_strtolower($answers[$challengeId]) === mb_strtolower($answer);
    
    if (!$correct) {
        echo json_encode(['error' => 'Mauvaise réponse']);
        exit;
    }
    
    // Enregistrer score (1000 points = 1 tentative sans indice)
    $points = 1000;
    
    $db->beginTransaction();
    
    $stmt2 = $db->prepare('
        INSERT INTO challenge_scores (team_id, challenge_id, points, attempts)
        VALUES (?, ?, ?, 1)
    ');
    $stmt2->execute([$teamId, $challengeId, $points]);
    
    $stmt3 = $db->prepare('
        UPDATE teams
        SET total_score = (SELECT COALESCE(SUM(points), 0) FROM challenge_scores WHERE team_id = ?)
        WHERE id = ?
    ');
    $stmt3->execute([$teamId, $teamId]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Score enregistré !',
        'points' => $points
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
