<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) {
    echo json_encode(['error' => 'UUID manquant']);
    exit;
}

try {
    $pdo = getDB();
    
    // Récupérer team_id
    $stmt = $pdo->prepare('SELECT id, name FROM teams WHERE uuid = ?');
    $stmt->execute([$uuid]);
    $team = $stmt->fetch();
    
    if (!$team) {
        echo json_encode(['error' => 'Équipe introuvable']);
        exit;
    }
    
    // Récupérer scores
    $stmt2 = $pdo->prepare('
        SELECT challenge_id, points, attempts, completed_at
        FROM challenge_scores
        WHERE team_id = ?
        ORDER BY completed_at ASC
    ');
    $stmt2->execute([$team['id']]);
    $scores = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'team' => $team['name'],
        'scores' => $scores
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
