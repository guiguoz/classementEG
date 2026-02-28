<?php
// Charge les credentials depuis un fichier local non versionné
$localConfig = __DIR__ . '/config.local.php';
if (!file_exists($localConfig)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Configuration manquante']);
    exit;
}
require_once $localConfig;

// Domaine autorisé pour CORS
define('ALLOWED_ORIGIN', 'https://hebergementvikazimut.vikazim.fr');

// Rate limiting : max requêtes par IP par minute
define('RATE_LIMIT_MAX', 30);
define('RATE_LIMIT_WINDOW', 60); // secondes
