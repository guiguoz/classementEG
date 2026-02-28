<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$uuid = $_GET['uuid'] ?? '';

try {
    $pdo = getDB();
    
    // Chercher l'équipe
    $stmt = $pdo->prepare('SELECT * FROM teams WHERE uuid = ?');
    $stmt->execute([$uuid]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($team) {
        echo json_encode([
            'found' => true,
            'team' => $team
        ], JSON_PRETTY_PRINT);
    } else {
        // Lister toutes les équipes
        $stmt2 = $pdo->query('SELECT uuid, name, total_score FROM teams ORDER BY id DESC LIMIT 10');
        $teams = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'found' => false,
            'searched_uuid' => $uuid,
            'recent_teams' => $teams
        ], JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
