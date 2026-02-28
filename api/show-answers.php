<?php
require_once __DIR__ . '/challenges.php';

header('Content-Type: application/json; charset=utf-8');

$gameId = 'archi';
$answers = getGameAnswers($gameId);
$hints = getGameHints($gameId);

echo json_encode([
    'game_id' => $gameId,
    'answers' => $answers,
    'hints' => $hints
], JSON_PRETTY_PRINT);
