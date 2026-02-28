<?php
/**
 * api/leaderboard.php - AVEC CACHE TEMPS RÉEL
 * 
 * Récupère le classement général avec cache de 15 secondes
 * pour éviter de surcharger la DB lors des rafraîchissements automatiques
 */

require_once 'config.php';
require_once 'db.php';
require_once 'utils.php';

header('Content-Type: application/json; charset=utf-8');
corsHeaders();

// ========== CACHE 15 SECONDES ==========
header('Cache-Control: public, max-age=15');

$game_id = $_GET['game_id'] ?? 'archi';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$limit = max(1, min(100, $limit)); // Entre 1 et 100

$cacheFile = sys_get_temp_dir() . '/leaderboard_cache_' . $game_id . '.json';
$cacheExpiry = 15; // secondes

// Vérifier si cache valide
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheExpiry) {
    $data = json_decode(file_get_contents($cacheFile), true);

    // Appliquer la limite
    $data = array_slice($data, 0, $limit);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'cached' => true,
        'cache_age' => time() - filemtime($cacheFile)
    ]);
    exit;
}

// ========== REQUÊTE BASE DE DONNÉES ==========

try {
    $pdo = getDbConnection();

    // Requête optimisée avec index
    $sql = "
        SELECT 
            t.uuid,
            t.name,
            t.total_score,
            COUNT(cs.challenge_id) as challenges_completed
        FROM teams t
        LEFT JOIN challenge_scores cs ON t.uuid = cs.team_uuid
        WHERE t.game_id = :game_id
        GROUP BY t.uuid
        ORDER BY t.total_score DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['game_id' => $game_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nettoyer les données
    foreach ($data as &$team) {
        $team['total_score'] = (int)$team['total_score'];
        $team['challenges_completed'] = (int)$team['challenges_completed'];
    }

    // Sauvegarder dans le cache
    file_put_contents($cacheFile, json_encode($data));

    // Appliquer la limite demandée
    $data = array_slice($data, 0, $limit);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'cached' => false
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération du classement'
    ]);
}
