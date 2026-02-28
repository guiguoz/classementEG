<?php
require_once 'db.php';

$pdo = getDB();

// Voir structure challenge_scores
$stmt = $pdo->query("DESCRIBE challenge_scores");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Colonnes de challenge_scores :\n";
foreach ($columns as $col) {
    echo "  - $col\n";
}

// Voir structure teams
$stmt = $pdo->query("DESCRIBE teams");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "\nColonnes de teams :\n";
foreach ($columns as $col) {
    echo "  - $col\n";
}
