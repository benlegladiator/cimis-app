<?php
// impression_pvc_multiple.php - Impression PVC multiple optimisée
// Affiche tous les candidats sélectionnés sur une seule page au format PVC

require_once '../backend/config.php';
require_once '../Carte/confection_carte.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Récupérer les matricules depuis GET
$matricules = $_GET['matricules'] ?? '';
if (empty($matricules)) {
    $_SESSION['error'] = "Aucun matricule spécifié";
    header('Location: ../impression.php');
    exit;
}

// Séparer les matricules
$matricule_array = explode(',', $matricules);
$matricule_array = array_map('trim', $matricule_array);
$matricule_array = array_filter($matricule_array);

if (empty($matricule_array)) {
    $_SESSION['error'] = "Aucun matricule valide";
    header('Location: ../impression.php');
    exit;
}

// Récupérer tous les candidats
$candidats = [];
foreach ($matricule_array as $matricule) {
    $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule = ?");
    $stmt->execute([$matricule]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($candidat) {
        $candidats[] = $candidat;
    }
}

if (empty($candidats)) {
    $_SESSION['error'] = "Aucun candidat trouvé";
    header('Location: ../impression.php');
    exit;
}

// Récupérer config unités
$config_unites = include '../Carte/config_unites.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Impression PVC Multiple - <?php echo count($candidats); ?> cartes</title>
    <link rel="stylesheet" href="../css/styles_carte.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === CONFIGURATION IMPRESSION PVC CRITIQUE === */
        
        /* Définir la taille de page = taille carte PVC (85.60mm × 53.98mm) */
        @page {
            size: 85.60mm 53.98mm;
            margin: 0;
            padding: 0;
        }
        
        /* En impression: cacher tout sauf les cartes */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
            }
            
            /* Cacher tous les éléments UI */
            .no-print,
            .print-controls,
            .btn,
            h1, h2, h3,
            .alert,
            .header,
            .menu,
            .navbar,
            .sidebar {
                display: none !important;
            }
            
            /* Afficher uniquement les cartes */
            .print-only {
                display: block !important;
            }
            
            /* Chaque carte sur une page séparée */
            .pvc-card-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                page-break-after: always;
                page-break-inside: avoid;
                overflow: hidden;
                box-shadow: none !important;
                border: none !important;
            }
            
            .pvc-card-wrapper:last-child {
                page-break-after: auto;
            }
            
            /* Forcer les dimensions exactes de la carte */
            .id-card {
                width: 85.60mm !important;
                height: 53.98mm !important;
                margin: 0 !important;
                border-radius: 3mm !important;
                box-shadow: none !important;
                border: none !important;
                transform: none !important;
            }
        }
        
        /* === STYLE ÉCRAN (PREVIEW) === */
        body {
            background: #2c3e50;
            padding: 20px;
            font-family: Arial, sans-serif;
            margin: 0;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 1000;
            width: 250px;
        }
        
        .print-controls h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 11px;
            margin-top: 10px;
            border-left: 3px solid #28a745;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .preview-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
            padding: 20px;
            margin-top: 80px;
        }
        
        .pvc-card-wrapper {
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-radius: 3mm;
            margin: 20px auto;
            width: fit-content;
        }
        
        .card-label {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .print-instructions {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
            max-width: 600px;
        }
        
        .print-instructions h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .print-instructions ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }
        
        .print-instructions li {
            margin-bottom: 5px;
        }
        
        .cards-counter {
            background: #007bff;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Panneau de contrôle (caché à l'impression) -->
    <div class="print-controls no-print">
        <h3><i class="fas fa-print"></i> Impression PVC Multiple</h3>
        
        <div class="cards-counter">
            <i class="fas fa-id-card"></i> <?php echo count($candidats); ?> carte(s)
        </div>
        
        <div>
            <button class="btn btn-primary" onclick="printCards()">
                <i class="fas fa-print"></i> Imprimer tout (Ctrl+P)
            </button>
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
        
        <div class="info-box">
            <strong><i class="fas fa-ruler"></i> Format:</strong> 85.60mm × 53.98mm<br>
            <strong><i class="fas fa-microchip"></i> NFC:</strong> Zone marquée<br>
            <strong><i class="fas fa-file"></i> Pages:</strong> <?php echo count($candidats); ?> pages<br>
            <strong>Imprimante:</strong> PVC Card Printer
        </div>
    </div>
    
    <!-- Instructions -->
    <div class="print-instructions no-print" style="margin: 100px auto 20px;">
        <h4><i class="fas fa-info-circle"></i> Configuration requise</h4>
        <ul>
            <li><strong>Taille papier:</strong> Définir "Carte PVC" ou "Custom" (85.60mm × 53.98mm)</li>
            <li><strong>Marges:</strong> Aucune (0mm)</li>
            <li><strong>Échelle:</strong> 100% (pas de mise à l'échelle)</li>
            <li><strong>Recto-verso:</strong> Activer l'impression duplex manuel ou automatique</li>
            <li><strong>NFC:</strong> Ne pas imprimer sur la zone inférieure droite (position puce)</li>
            <li><strong>Pages:</strong> <?php echo count($candidats); ?> pages - 1 carte par page</li>
        </ul>
    </div>
    
    <!-- Zone d'impression (seul contenu imprimé) -->
    <div class="preview-container" id="print-area">
        
        <?php 
        foreach ($candidats as $index => $candidat): 
            // Récupérer config unité pour ce candidat
            $unite = $candidat['unite'] ?? 'ARMÉE DE TERRE';
            $config = $config_unites[$unite] ?? $config_unites['ARMÉE DE TERRE'];
            
            $fond_image = file_exists('../' . $config['fond']) ? '../' . $config['fond'] : '../img/default_fond.png';
            $logo_unit = !empty($config['logo']) && file_exists('../' . $config['logo']) ? '../' . $config['logo'] : '../img/cimis.png';
        ?>
        
        <!-- CARTE <?php echo $index + 1; ?> -->
        <div class="pvc-card-wrapper print-only" data-carte="<?php echo $index + 1; ?>">
            <div class="card-label no-print">
                CARTE <?php echo $index + 1; ?> - <?php echo htmlspecialchars($candidat['matricule']); ?>
            </div>
            
            <!-- RECTO -->
            <div class="id-card" data-face="recto">
                <?php 
                echo renderRecto($candidat, $config, $unite, $fond_image, $logo_unit); 
                ?>
            </div>
        </div>
        
        <!-- VERSO (page séparée) -->
        <div class="pvc-card-wrapper print-only" data-carte="<?php echo $index + 1; ?>-verso">
            <div class="card-label no-print">
                CARTE <?php echo $index + 1; ?> VERSO - <?php echo htmlspecialchars($candidat['matricule']); ?>
            </div>
            
            <div class="id-card" data-face="verso">
                <?php 
                echo renderVerso($candidat, $config, $unite, $fond_image, $logo_unit); 
                ?>
            </div>
        </div>
        
        <?php endforeach; ?>
        
    </div>
    
    <script>
        function printCards() {
            const count = <?php echo count($candidats); ?>;
            const message = `Confirmer l'impression de ${count} carte(s) PVC ?\n\n` +
                          `Format: 85.60mm × 53.98mm\n` +
                          `Pages: ${count * 2} (recto + verso)\n` +
                          `Pour recto-verso automatique : utilisez l'option duplex de votre imprimante.`;
            
            if (confirm(message)) {
                // Cacher les labels avant impression
                document.querySelectorAll('.card-label').forEach(el => el.style.display = 'none');
                
                // Lancer l'impression
                window.print();
                
                // Restaurer après impression
                setTimeout(() => {
                    document.querySelectorAll('.card-label').forEach(el => el.style.display = 'block');
                }, 1000);
            }
        }
        
        // Raccourci clavier Ctrl+P
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printCards();
            }
        });
        
        // Détection avant impression pour ajuster
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
    </script>
</body>
</html>
