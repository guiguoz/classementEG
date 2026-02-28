<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/challenges.php';

checkRateLimit();
$action = $_GET['action'] ?? '';

if ($action === 'register') {
    requireMethod('POST');
    $body = getJsonBody();
    $name = sanitizeTeamName((string)($body['name'] ?? ''));
    $gameId = (string)($body['game_id'] ?? 'archi');

    if (mb_strlen($name) < 3) respondError('Nom trop court (min 3 caractères)');
    if (mb_strlen($name) > 100) respondError('Nom trop long (max 100 caractères)');
    if (!isValidGameId($gameId)) respondError('Jeu inconnu');

    $uuid = uuidV4();
    $db = getDB();

    try {
        $stmt = $db->prepare('INSERT INTO teams (name, uuid, game_id) VALUES (?, ?, ?)');
        $stmt->execute([$name, $uuid, $gameId]);

        $teamId = (int)$db->lastInsertId();
        $stmt2 = $db->prepare('
            INSERT INTO team_sessions (team_id, challenges_completed, current_challenge, session_data)
            VALUES (?, JSON_ARRAY(), NULL, JSON_OBJECT())
        ');
        $stmt2->execute([$teamId]);

        respondSuccess(['uuid' => $uuid, 'team_name' => $name]);
    } catch (PDOException $e) {
        respondError('Nom déjà utilisé, choisis-en un autre', 409);
    }
}

if ($action === 'check') {
    requireMethod('GET');
    $uuid = (string)($_GET['uuid'] ?? '');
    if (!$uuid) respondError('UUID manquant');

    $db = getDB();
    $stmt = $db->prepare('SELECT name, uuid, game_id, total_score, is_finished FROM teams WHERE uuid = ? LIMIT 1');
    $stmt->execute([$uuid]);
    $team = $stmt->fetch();

    if (!$team) respondError('Équipe introuvable', 404);

    respondSuccess([
        'name' => $team['name'],
        'uuid' => $team['uuid'],
        'game_id' => $team['game_id'],
        'total_score' => (int)$team['total_score'],
        'is_finished' => (bool)$team['is_finished']
    ]);
}

if ($action === 'session') {
    requireMethod('GET');
    $uuid = (string)($_GET['uuid'] ?? '');
    if (!$uuid) respondError('UUID manquant');

    $db = getDB();
    $stmt = $db->prepare('
        SELECT t.id, t.name, t.uuid, t.game_id, t.total_score, t.is_finished, ts.challenges_completed
        FROM teams t
        LEFT JOIN team_sessions ts ON ts.team_id = t.id
        WHERE t.uuid = ?
        LIMIT 1
    ');
    $stmt->execute([$uuid]);
    $row = $stmt->fetch();

    if (!$row) respondError('Équipe introuvable', 404);

    $completed = [];
    if (!empty($row['challenges_completed'])) {
        $decoded = json_decode($row['challenges_completed'], true);
        if (is_array($decoded)) $completed = $decoded;
    }

    respondSuccess([
        'name' => $row['name'],
        'uuid' => $row['uuid'],
        'game_id' => $row['game_id'],
        'total_score' => (int)$row['total_score'],
        'is_finished' => (bool)$row['is_finished'],
        'challenges_completed' => $completed
    ]);
}

if ($action === 'finish') {
    requireMethod('POST');
    $body = getJsonBody();
    $uuid = (string)($body['uuid'] ?? '');
    if (!$uuid) respondError('UUID manquant');

    $db = getDB();
    $stmt = $db->prepare('UPDATE teams SET is_finished = 1 WHERE uuid = ?');
    $stmt->execute([$uuid]);

    respondSuccess(['ok' => true]);
}

respondError('Action inconnue', 404);
