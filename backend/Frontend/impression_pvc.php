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
$matriculesArray = array_filter(array_map('trim', $matriculesArray));

// Récupérer les candidats par matricule ou matricule_militaire
$placeholders = str_repeat('?,', count($matriculesArray));
$placeholders = rtrim($placeholders, ',');
$allParams = array_merge($matriculesArray, $matriculesArray);

$stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule IN ($placeholders) OR matricule_militaire IN ($placeholders)");
$stmt->execute($allParams);
$candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($candidats)) {
    $_SESSION['error'] = "Aucun candidat trouvé";
    header('Location: impression.php');
    exit;
}

// 1. Enregistrement direct et garanti dans la file d'attente des reçus en session PHP
if (!isset($_SESSION['pending_receipts'])) {
    $_SESSION['pending_receipts'] = [];
}
$candidat_ids = [];
foreach ($candidats as $cand) {
    if (!empty($cand['id'])) {
        $c_id = (int)$cand['id'];
        $candidat_ids[] = $c_id;
        if (!in_array($c_id, $_SESSION['pending_receipts'])) {
            $_SESSION['pending_receipts'][] = $c_id;
        }
    }
}

// 2. Mettre à jour le compteur d'impression et la date de dernière réimpression par ID primaire
try {
    if (!empty($candidat_ids)) {
        $id_in = str_repeat('?,', count($candidat_ids) - 1) . '?';
        $update_stmt = $pdo->prepare("UPDATE candidat SET nb_reimpressions = COALESCE(nb_reimpressions, 0) + 1, date_derniere_reimpression = CURDATE() WHERE id IN ($id_in)");
        $update_stmt->execute($candidat_ids);
    }
} catch (Exception $e) {}

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
        <!-- Contrôles d'impression -->
    <div class="print-controls no-print">
        <h6><i class="fas fa-print"></i> Impression</h6>
        <button class="print-button" onclick="lancerImpression()">
            <i class="fas fa-print"></i> Imprimer PVC
        </button>

        <?php
        $ids_candidats_tb = array_column($candidats, 'id');
        $ids_str_tb = implode(',', $ids_candidats_tb);
        ?>
        <a href="../backend/generer_recu.php?mode=batch&ids=<?php echo $ids_str_tb; ?>" target="_blank" 
           class="btn btn-sm w-100 mt-2" style="background: linear-gradient(135deg, #10b981, #059669); color: white; font-weight: bold; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 6px;">
            <i class="fas fa-file-invoice"></i> Imprimer Reçu A4
        </a>
        
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

    <!-- Modal de confirmation post-impression -->
    <div id="modalConfirmImpression" style="
        display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
        background:rgba(0,0,0,0.75); z-index:9999; align-items:center; justify-content:center;">
        <div style="
            background:#1a1a2e; border:2px solid #4ade80; border-radius:16px; 
            padding:2rem; max-width:460px; width:90%; text-align:center; box-shadow:0 0 40px rgba(74,222,128,0.3);">
            <div style="font-size:3rem; margin-bottom:0.5rem;">✅</div>
            <h4 style="color:#4ade80; margin-bottom:0.5rem;">Impression PVC lancée !</h4>
            <p style="color:#ccc; margin-bottom:1.5rem; font-size:0.9rem;">
                <?php echo count($candidats); ?> carte(s) envoyée(s) à l'imprimante.<br>
                La carte est enregistrée pour la délivrance du reçu officiel signé.
            </p>
            <div style="display:flex; flex-direction:column; gap:0.6rem;">
                <?php
                $ids_candidats = array_column($candidats, 'id');
                $ids_str = implode(',', $ids_candidats);
                ?>
                <a href="../backend/generer_recu.php?mode=batch&ids=<?php echo $ids_str; ?>" target="_blank" 
                   style="background:linear-gradient(135deg,#28a745,#20c997); color:white; padding:0.6rem 1rem; border-radius:8px; text-decoration:none; font-weight:bold;">
                    <i class="fas fa-file-alt"></i> Imprimer le Reçu A4 maintenant
                </a>
                <a href="impression.php" 
                   style="background:linear-gradient(135deg,#007bff,#0056b3); color:white; padding:0.6rem 1rem; border-radius:8px; text-decoration:none; font-weight:bold;">
                    <i class="fas fa-list"></i> Retour à la liste des cartes
                </a>
                <button onclick="document.getElementById('modalConfirmImpression').style.display='none'" 
                   style="background:transparent; border:1px solid #666; color:#aaa; padding:0.5rem 1rem; border-radius:8px; cursor:pointer;">
                    <i class="fas fa-times"></i> Fermer ce message
                </button>
            </div>
        </div>
    </div>

    <script>
        function lancerImpression() {
            window.print();
            setTimeout(function() {
                var m = document.getElementById('modalConfirmImpression');
                if (m) m.style.display = 'flex';
            }, 1200);
        }

        window.addEventListener('afterprint', function() {
            var m = document.getElementById('modalConfirmImpression');
            if (m) m.style.display = 'flex';
        });
    </script>
</body>
</html>

