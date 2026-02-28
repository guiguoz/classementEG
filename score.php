<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/challenges.php';

checkRateLimit();
$action = $_GET['action'] ?? '';

// Valider une réponse côté serveur
if ($action === 'validate') {
    requireMethod('POST');
    $body = getJsonBody();

    $uuid = (string)($body['uuid'] ?? '');
    $challengeId = (string)($body['challenge_id'] ?? '');
    $answer = mb_strtolower(trim((string)($body['answer'] ?? '')));
    $attempts = (int)($body['attempts'] ?? 1);
    $hintUsed = (bool)($body['hint_used'] ?? false);

    if (!$uuid) respondError('UUID manquant');
    if (!$challengeId) respondError('challenge_id manquant');
    if (!preg_match('/^[0-9A-Za-z_-]{1,10}$/', $challengeId)) respondError('challenge_id invalide');
    if ($answer === '') respondError('Réponse manquante');
    if ($attempts < 1 || $attempts > MAX_ATTEMPTS) respondError('Tentatives invalides');

    // Récupérer l'équipe et son game_id
    $db = getDB();
    $stmt = $db->prepare('SELECT id, game_id FROM teams WHERE uuid = ? LIMIT 1');
    $stmt->execute([$uuid]);
    $team = $stmt->fetch();
    if (!$team) respondError('Équipe introuvable', 404);

    $teamId = (int)$team['id'];
    $gameId = $team['game_id'];

    // Chercher la réponse dans le bon jeu
    $answers = getGameAnswers($gameId);
    if (!isset($answers[$challengeId])) respondError('Épreuve inconnue', 404);

    $correctAnswer = mb_strtolower($answers[$challengeId]);

    // Mauvaise réponse
    if ($answer !== $correctAnswer) {
        respondSuccess(['correct' => false, 'message' => 'Réponse incorrecte']);
    }

    // Bonne réponse — calculer les points côté serveur
    $points = POINTS_BASE - ($attempts - 1) * POINTS_PENALTY;
    if ($hintUsed) $points -= POINTS_HINT_PENALTY;
    $points = max(0, $points);

    try {
        $db->beginTransaction();

        $stmt2 = $db->prepare('
            INSERT INTO challenge_scores (team_id, challenge_id, points, attempts)
            VALUES (?, ?, ?, ?)
        ');
        $stmt2->execute([$teamId, $challengeId, $points, $attempts]);

        $stmt3 = $db->prepare('
            UPDATE teams
            SET total_score = (SELECT COALESCE(SUM(points), 0) FROM challenge_scores WHERE team_id = ?)
            WHERE id = ?
        ');
        $stmt3->execute([$teamId, $teamId]);

        $stmt4 = $db->prepare('SELECT challenges_completed FROM team_sessions WHERE team_id = ? LIMIT 1');
        $stmt4->execute([$teamId]);
        $sess = $stmt4->fetch();

        $completed = [];
        if ($sess && !empty($sess['challenges_completed'])) {
            $decoded = json_decode($sess['challenges_completed'], true);
            if (is_array($decoded)) $completed = $decoded;
        }
        if (!in_array($challengeId, $completed, true)) $completed[] = $challengeId;

        if (!$sess) {
            $stmtIns = $db->prepare('
                INSERT INTO team_sessions (team_id, challenges_completed, current_challenge, session_data)
                VALUES (?, ?, NULL, JSON_OBJECT())
            ');
            $stmtIns->execute([$teamId, json_encode($completed, JSON_UNESCAPED_UNICODE)]);
        } else {
            $stmt5 = $db->prepare('
                UPDATE team_sessions
                SET challenges_completed = ?, current_challenge = NULL
                WHERE team_id = ?
            ');
            $stmt5->execute([json_encode($completed, JSON_UNESCAPED_UNICODE), $teamId]);
        }

        $db->commit();
        respondSuccess(['correct' => true, 'points' => $points]);
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        respondError('Épreuve déjà validée pour cette équipe', 409);
    }
}

// Récupérer l'indice
if ($action === 'hint') {
    requireMethod('GET');
    $challengeId = (string)($_GET['challenge_id'] ?? '');
    $gameId = (string)($_GET['game_id'] ?? 'archi');

    if (!isValidGameId($gameId)) respondError('Jeu inconnu');

    $hints = getGameHints($gameId);
    if (!$challengeId || !isset($hints[$challengeId])) respondError('Épreuve inconnue', 404);

    respondSuccess(['hint' => $hints[$challengeId]]);
}

if ($action === 'team') {
    requireMethod('GET');
    $uuid = (string)($_GET['uuid'] ?? '');
    if (!$uuid) respondError('UUID manquant');

    $db = getDB();

    $stmt = $db->prepare('SELECT id FROM teams WHERE uuid = ? LIMIT 1');
    $stmt->execute([$uuid]);
    $team = $stmt->fetch();
    if (!$team) respondError('Équipe introuvable', 404);

    $teamId = (int)$team['id'];

    $stmt2 = $db->prepare('
        SELECT challenge_id, points, attempts, completed_at
        FROM challenge_scores
        WHERE team_id = ?
        ORDER BY completed_at ASC
    ');
    $stmt2->execute([$teamId]);
    $scores = $stmt2->fetchAll();

    $scores = array_map(function($r) {
        return [
            'challenge_id' => (string)$r['challenge_id'],
            'points' => (int)$r['points'],
            'attempts' => (int)$r['attempts'],
            'completed_at' => $r['completed_at'],
        ];
    }, $scores);

    respondSuccess($scores);
}

respondError('Action inconnue', 404);
