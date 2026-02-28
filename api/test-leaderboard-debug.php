<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. PHP démarre OK\n";

try {
    echo "2. Test require config.php...\n";
    require_once __DIR__ . '/config.php';
    echo "3. config.php OK\n";
    
    echo "4. Test require db.php...\n";
    require_once __DIR__ . '/db.php';
    echo "5. db.php OK\n";
    
    echo "6. Test require utils.php...\n";
    require_once __DIR__ . '/utils.php';
    echo "7. utils.php OK\n";
    
    echo "8. Test getDbConnection()...\n";
    $pdo = getDbConnection();
    echo "9. Connexion DB OK\n";
    
    echo "10. Test requête simple...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM teams");
    $result = $stmt->fetch();
    echo "11. Requête OK - Total teams: " . $result['total'] . "\n";
    
    echo "\n✅ TOUT FONCTIONNE !\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
}
