<?php
try {
    $databaseUrl = getenv('DATABASE_URL');
    if (!$databaseUrl) {
        die("DATABASE_URL manquant !");
    }

    $dbUrlParams = parse_url($databaseUrl);
    $host   = $dbUrlParams["host"];
    $port   = isset($dbUrlParams["port"]) ? $dbUrlParams["port"] : 5432;
    $user   = $dbUrlParams["user"];
    $pass   = isset($dbUrlParams["pass"]) ? urldecode($dbUrlParams["pass"]) : '';
    $dbname = ltrim($dbUrlParams["path"], "/");

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO($dsn, $user, $pass);

    echo "✅ Connexion PostgreSQL réussie sur Render<br>";

    // Vérifier une table simple
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
    $row = $stmt->fetch();
    echo "Nombre d'utilisateurs dans la table users : " . $row['total'];

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>
