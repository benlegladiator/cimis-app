<?php
/**
 * Script d'exécution automatique de migration pour Railway MySQL
 * Usage CLI : php backend/run_railway_migration.php [HOST] [PORT] [USER] [PASS] [DB_NAME]
 * Ou en utilisant les variables d'environnement DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
 */

$host = $argv[1] ?? getenv('DB_HOST') ?? 'mysql.railway.internal';
$port = $argv[2] ?? getenv('DB_PORT') ?? '3306';
$user = $argv[3] ?? getenv('DB_USER') ?? 'root';
$pass = $argv[4] ?? getenv('DB_PASS') ?? '';
$db   = $argv[5] ?? getenv('DB_NAME') ?? 'railway';

echo "=====================================================\n";
echo " MIGRATION BDD RAILWAY POUR CIMIS (SUSPENSIONS & LOGS)\n";
echo "=====================================================\n";
echo "Host: {$host}:{$port}\n";
echo "Database: {$db}\n";
echo "User: {$user}\n\n";

if ($host === 'localhost' || $host === '127.0.0.1') {
    echo "⚠️ Attention : vous ciblez localhost. Si vous souhaitez cibler Railway, passez les paramètres Railway.\n";
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ Connexion réussie à la base de données !\n\n";

    $sqlFile = __DIR__ . '/migrate_railway_complete.sql';
    if (!file_exists($sqlFile)) {
        die("❌ Fichier {$sqlFile} introuvable.\n");
    }

    $sqlContent = file_get_contents($sqlFile);
    
    // Découper les requêtes par point-virgule
    $queries = array_filter(array_map('trim', explode(';', $sqlContent)));

    foreach ($queries as $index => $query) {
        if (empty($query) || str_starts_with($query, '--')) continue;
        
        try {
            $pdo->exec($query);
            echo " [OK] Requête #" . ($index + 1) . " exécutée avec succès.\n";
        } catch (Exception $eq) {
            echo " [INFO/WARN] Requête #" . ($index + 1) . " : " . $eq->getMessage() . "\n";
        }
    }

    echo "\n🎉 Migration terminée avec succès sur la base de données !\n";

} catch (PDOException $e) {
    echo "❌ Erreur de connexion ou d'exécution : " . $e->getMessage() . "\n";
}
