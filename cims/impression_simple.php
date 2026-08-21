<?php
// Version ultra-simplifiée de impression.php pour InfinityFree
// URL: https://cimis.free.nf/impression_simple.php

session_start();

// Configuration simple d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Inclure la configuration
require_once 'backend/config.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Traitement des actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch($action) {
        case 'visualize':
            $matricule = $_GET['matricule'] ?? '';
            if (!empty($matricule)) {
                header('Location: visualiser_carte.php?matricule=' . urlencode($matricule));
                exit;
            }
            break;
            
        case 'generate':
            $matricule = $_GET['matricule'] ?? '';
            if (!empty($matricule)) {
                header('Location: generate_pdf.php?matricule=' . urlencode($matricule));
                exit;
            }
            break;
    }
}

// Récupérer les candidats (version simple)
$candidats = [];
try {
    $sql = "SELECT id, matricule, nom, prenom, grade, unite, photo, numero_cni, date_dernier_grade FROM candidat ORDER BY date_dernier_grade DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

// Afficher les messages d'erreur
if (isset($_SESSION['error'])) {
    echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px;">';
    echo '<strong>Erreur:</strong> ' . htmlspecialchars($_SESSION['error']);
    echo '</div>';
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    echo '<div style="background: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;">';
    echo '<strong>Succès:</strong> ' . htmlspecialchars($_SESSION['success']);
    echo '</div>';
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impression Simple - CIMIS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; margin-bottom: 20px; }
        .candidat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .candidat-card { background: white; border-radius: 8px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .candidat-photo { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; float: right; }
        .candidat-info { margin-right: 70px; }
        .candidat-name { font-weight: bold; margin-bottom: 5px; }
        .candidat-details { color: #666; font-size: 0.9em; }
        .btn { background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 2px; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .search-box { background: white; padding: 15px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .search-input { width: 200px; padding: 8px; margin: 5px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎖️ CIMIS - Impression Cartes Militaires</h1>
            <p>Version simplifiée pour InfinityFree</p>
        </div>
        
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" class="search-input" placeholder="Rechercher par nom ou matricule" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button type="submit" class="btn">🔍 Rechercher</button>
                <a href="dashboard.php" class="btn">🏠 Retour Dashboard</a>
            </form>
        </div>
        
        <div class="candidat-grid">
            <?php if (isset($error_msg)): ?>
                <div style="grid-column: 1/-1; background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; text-align: center;">
                    <strong>❌ Erreur de base de données:</strong><br><?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php elseif (empty($candidats)): ?>
                <div style="grid-column: 1/-1; background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; text-align: center;">
                    <strong>📭 Aucun candidat trouvé</strong>
                </div>
            <?php else: ?>
                <?php foreach ($candidats as $candidat): ?>
                    <div class="candidat-card">
                        <?php if (!empty($candidat['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($candidat['photo']); ?>" class="candidat-photo" alt="Photo">
                        <?php endif; ?>
                        
                        <div class="candidat-info">
                            <div class="candidat-name"><?php echo htmlspecialchars($candidat['nom'] . ' ' . $candidat['prenom']); ?></div>
                            <div class="candidat-details">
                                🆔 <?php echo htmlspecialchars($candidat['matricule']); ?><br>
                                ⭐ <?php echo htmlspecialchars($candidat['grade']); ?><br>
                                🏢 <?php echo htmlspecialchars($candidat['unite']); ?><br>
                                📅 <?php echo htmlspecialchars($candidat['date_dernier_grade']); ?>
                            </div>
                            
                            <div style="margin-top: 10px;">
                                <a href="?action=visualize&matricule=<?php echo urlencode($candidat['matricule']); ?>" class="btn">👁️ Voir</a>
                                <a href="?action=generate&matricule=<?php echo urlencode($candidat['matricule']); ?>" class="btn">📄 PDF</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;">
            <p><strong>🔧 Outils de diagnostic:</strong></p>
            <a href="test_infinityfree.php" class="btn">🧪 Test Compatibilité</a>
            <a href="impression.php" class="btn">🔄 Version Complète</a>
        </div>
    </div>
</body>
</html>
