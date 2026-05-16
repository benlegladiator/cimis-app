<?php
// impression_pvc.php - Impression PVC optimisée

require_once '../backend/config.php';
require_once '../Carte/confection_carte.php';

// Vérification de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Récupérer les matricules (un seul ou plusieurs)
$matricules = $_GET['matricules'] ?? $_GET['matricule'] ?? '';

if (empty($matricules)) {
    $_SESSION['error'] = "Matricule non spécifié";
    header('Location: impression.php');
    exit;
}

// Gérer plusieurs matricules
$matriculesArray = is_array($matricules) ? $matricules : explode(',', $matricules);
$matriculesArray = array_filter($matriculesArray, 'trim');

// Récupérer les candidats
$placeholders = str_repeat('?,', count($matriculesArray));
$placeholders = rtrim($placeholders, ',');

$stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule IN ($placeholders)");
$stmt->execute($matriculesArray);
$candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($candidats)) {
    $_SESSION['error'] = "Aucun candidat trouvé";
    header('Location: impression.php');
    exit;
}

// Configuration des dimensions
define('CARTE_WIDTH_MM', 85.6);
define('CARTE_HEIGHT_MM', 53.98);
define('CARTE_WIDTH_PX', 324);
define('CARTE_HEIGHT_PX', 204);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartes Militaires PVC - <?php echo count($candidats); ?> carte(s)</title>
    <link rel="stylesheet" href="../css/enrolement.css">
    <link rel="stylesheet" href="../css/styles_carte.css">
    <link rel="stylesheet" href="../css/bouton-retour.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            
            .no-print {
                display: none !important;
            }
            
            .carte-militaire-container {
                page-break-inside: avoid;
            }
            
            .visualization-container {
                background: white;
                padding: 10mm;
            }
        }
        
        .visualization-container {
            background: rgba(0, 0, 0, 0.05);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 15px;
            padding: 1rem;
            margin: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            color: var(--neon-green);
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .cards-wrapper {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }
        
        .candidat-header {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            color: var(--neon-green);
            font-weight: bold;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .print-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40,167,69,0.3);
        }
        
        .info-panel {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #333;
            border: 1px solid #dee2e6;
        }
        
        .info-panel strong {
            color: #000;
            font-weight: 600;
        }
        
        .info-panel .row {
            margin-bottom: 0.5rem;
        }
        
        .info-panel .col-md-6 {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="visualization-container">
        <!-- Header -->
        <div class="header no-print">
            <div class="header-title">
                <i class="fa-solid fa-id-card"></i> CARTES MILITAIRES PVC
            </div>
            <div>
                <span class="status-item"><i class="fa-solid fa-users"></i> <?php echo count($candidats); ?> carte(s)</span>
                <span class="status-item"><i class="fa-solid fa-list"></i> Impression Multiple</span>
            </div>
        </div>
        
        <!-- Info Panel -->
        <div class="info-panel no-print">
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fas fa-users"></i> Nombre de cartes:</strong>
                    <?php echo count($candidats); ?> carte(s) à imprimer
                </div>
                <div class="col-md-6">
                    <strong><i class="fas fa-ruler-combined"></i> Dimensions:</strong>
                    <?php echo CARTE_WIDTH_MM; ?>mm × <?php echo CARTE_HEIGHT_MM; ?>mm
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <strong><i class="fas fa-print"></i> Format:</strong>
                    Carte PVC NFC - Impression Multiple
                </div>
            </div>
        </div>
        
        <!-- Carte Header -->
        <div class="candidat-header no-print">
            <h3><i class="fa-solid fa-id-card"></i> CARTES D'IDENTITÉ MILITAIRES INFORMATISÉES ET SÉCURISÉES</h3>
            <p><strong>Impression Multiple - <?php echo count($candidats); ?> carte(s)</strong></p>
        </div>
        
        <!-- Cartes -->
        <div class="cards-wrapper">
            <?php foreach ($candidats as $candidat): ?>
                <?php echo renderCarte($candidat); ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Contrôles d'impression -->
    <div class="print-controls no-print">
        <h6><i class="fas fa-print"></i> Impression</h6>
        <button class="print-button" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimer
        </button>
        
        <button class="btn btn-primary btn-sm w-100 mt-2" onclick="window.location.href='impression.php'">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </button>
        
        <button class="btn btn-outline-secondary btn-sm w-100 mt-2" onclick="window.close()">
            <i class="fas fa-times"></i> Fermer
        </button>
        
        <div class="mt-3">
            <small class="text-muted">
                <strong>Instructions:</strong><br>
                • Papier PVC adhésif<br>
                • 300 DPI minimum<br>
                • Sans marges
            </small>
        </div>
    </div>
</body>
</html>
