<?php

function sendCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (defined('ALLOWED_ORIGIN') && $origin === ALLOWED_ORIGIN) {
        header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
    }
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Content-Type: application/json; charset=utf-8');
}

function checkRateLimit(): void {
    if (!defined('RATE_LIMIT_MAX')) return;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $dir = sys_get_temp_dir() . '/vikazimut_ratelimit';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);

    $file = $dir . '/' . md5($ip) . '.json';
    $now = time();
    $data = ['hits' => [], 'blocked_until' => 0];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
    }

    // Vérifier si bloqué
    if ($data['blocked_until'] > $now) {
        respondError('Trop de requêtes. Réessayez dans ' . ($data['blocked_until'] - $now) . 's.', 429);
    }

    // Nettoyer les hits hors fenêtre
    $window = defined('RATE_LIMIT_WINDOW') ? RATE_LIMIT_WINDOW : 60;
    $data['hits'] = array_values(array_filter($data['hits'], fn($t) => $t > $now - $window));

    // Vérifier la limite
    if (count($data['hits']) >= RATE_LIMIT_MAX) {
        $data['blocked_until'] = $now + $window;
        file_put_contents($file, json_encode($data));
        respondError('Trop de requêtes. Réessayez dans 1 minute.', 429);
    }

    $data['hits'][] = $now;
    file_put_contents($file, json_encode($data));
}

function respondSuccess($data = null): void {
    sendCorsHeaders();
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function respondError(string $message, int $httpCode = 400): void {
    sendCorsHeaders();
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function requireMethod(string $method): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        sendCorsHeaders();
        exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        respondError('Méthode non autorisée', 405);
    }
}

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function uuidV4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    $hex = bin2hex($data);
    return sprintf('%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}

function sanitizeTeamName(string $name): string {
    $name = trim($name);
    $name = preg_replace('/\s+/', ' ', $name);
    return (string)$name;
}

// Alias pour rétrocompatibilité
function corsHeaders() {
    sendCorsHeaders();
}
