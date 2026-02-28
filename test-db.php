<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

try {
    $db = getDB();
    echo '<p>Connexion via getDB() réussie.</p>';

    // Test création table
    $db->exec("CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        uuid VARCHAR(36) NOT NULL UNIQUE,
        total_score INT DEFAULT 0,
        is_finished TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo '<p>Table teams OK.</p>';

    $db->exec("CREATE TABLE IF NOT EXISTS team_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        challenges_completed JSON,
        current_challenge VARCHAR(50) DEFAULT NULL,
        session_data JSON,
        FOREIGN KEY (team_id) REFERENCES teams(id)
    )");
    echo '<p>Table team_sessions OK.</p>';

    $db->exec("CREATE TABLE IF NOT EXISTS challenge_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        challenge_id VARCHAR(10) NOT NULL,
        points INT NOT NULL DEFAULT 0,
        attempts INT NOT NULL DEFAULT 1,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_team_challenge (team_id, challenge_id),
        FOREIGN KEY (team_id) REFERENCES teams(id)
    )");
    echo '<p>Table challenge_scores OK.</p>';

} catch (Exception $e) {
    echo '<p>Erreur: ' . $e->getMessage() . '</p>';
}
