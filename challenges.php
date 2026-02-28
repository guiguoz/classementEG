<?php
// Réponses et indices par jeu — NE PAS exposer côté client
define('GAMES', [
    'archi' => [
        'answers' => [
            'T1' => 'test1',
            'T2' => 'test2',
            'T3' => 'test3',
            'T4' => 'test4',
        ],
        'hints' => [
            'T1' => 'La réponse est test1',
            'T2' => 'La réponse est test2',
            'T3' => 'La réponse est test3',
            'T4' => 'La réponse est test4',
        ],
    ],
    // Ajouter d'autres jeux ici :
    // 'nature' => [
    //     'answers' => ['N1' => '...'],
    //     'hints'   => ['N1' => '...'],
    // ],
]);

// Liste des game_id valides
define('VALID_GAME_IDS', array_keys(GAMES));

define('POINTS_BASE', 1000);
define('POINTS_PENALTY', 100);
define('POINTS_HINT_PENALTY', 100);
define('MAX_ATTEMPTS', 10);

// Fonctions utilitaires pour accéder aux données d'un jeu
function getGameAnswers(string $gameId): array {
    return GAMES[$gameId]['answers'] ?? [];
}

function getGameHints(string $gameId): array {
    return GAMES[$gameId]['hints'] ?? [];
}

function isValidGameId(string $gameId): bool {
    return in_array($gameId, VALID_GAME_IDS, true);
}
