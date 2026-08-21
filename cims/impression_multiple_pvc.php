<?php
// impression_multiple_pvc.php - Impression multiple de cartes PVC avec sélection

require_once 'backend/config.php';

// Vérification de session
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Récupérer les candidats sélectionnés
$candidats = [];
if (isset($_POST['selected_candidats']) && is_array($_POST['selected_candidats'])) {
    $matricules = $_POST['selected_candidats'];
    $placeholders = str_repeat('?,', count($matricules) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule IN ($placeholders) ORDER BY nom, prenom");
    $stmt->execute($matricules);
    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (isset($_GET['matricules'])) {
    $matricules = explode(',', $_GET['matricules']);
    $placeholders = str_repeat('?,', count($matricules) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule IN ($placeholders) ORDER BY nom, prenom");
    $stmt->execute($matricules);
    $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Configuration pour l'impression
define('CARTE_WIDTH_MM', 85.6);
define('CARTE_HEIGHT_MM', 53.98);
define('CARTE_DPI', 300);
define('CARTE_WIDTH_PX', 1005);
define('CARTE_HEIGHT_PX', 637);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impression Multiple Cartes PVC - CIMIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            
            .print-break {
                page-break-after: always;
            }
            
            .carte-sheet {
                page-break-inside: avoid;
            }
            
            .carte-container {
                page-break-after: always;
            }
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .carte-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .carte-item {
            page-break-inside: avoid;
        }
        
        .carte-recto {
            width: <?php echo CARTE_WIDTH_PX; ?>px;
            height: <?php echo CARTE_HEIGHT_PX; ?>px;
            position: relative;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e8ba3 100%);
            color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            margin: 0 auto 15px;
            transform: scale(0.8);
            transform-origin: top center;
        }
        
        .carte-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('img/carte-pattern.png');
            opacity: 0.1;
            background-size: cover;
        }
        
        .carte-header {
            text-align: center;
            padding: 15px 10px 5px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
        }
        
        .republique {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        
        .carte-titre {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        
        .carte-content {
            display: flex;
            padding: 15px;
            height: calc(100% - 120px);
        }
        
        .carte-photo-section {
            position: relative;
            margin-right: 15px;
        }
        
        .carte-photo {
            width: 100px;
            height: 100px;
            border: 3px solid white;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .carte-empreinte {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 30px;
            height: 30px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e3c72;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .carte-info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .carte-nom-complet {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .carte-prenom {
            font-size: 14px;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        
        .carte-details {
            font-size: 11px;
            line-height: 1.4;
        }
        
        .carte-grade {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .carte-unite {
            opacity: 0.8;
            margin-bottom: 2px;
        }
        
        .carte-matricule {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
            font-weight: bold;
        }
        
        .carte-footer {
            position: absolute;
            bottom: 10px;
            left: 15px;
            right: 15px;
            text-align: center;
        }
        
        .carte-validite {
            font-size: 9px;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 4px;
            backdrop-filter: blur(10px);
        }
        
        .carte-qrcode {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 50px;
            height: 50px;
            background: white;
            padding: 5px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            z-index: 1000;
            min-width: 250px;
        }
        
        .print-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 20px;
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
        
        .format-selector {
            margin-bottom: 15px;
        }
        
        .format-selector label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .format-selector select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .preview-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .carte-container {
                grid-template-columns: 1fr;
            }
            
            .print-controls {
                position: static;
                margin-bottom: 20px;
            }
            
            .carte-recto {
                transform: scale(0.6);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <h2 class="text-center mb-4">
                <i class="fas fa-id-card"></i> Impression Multiple Cartes PVC
            </h2>
            
            <div class="preview-info">
                <div class="row">
                    <div class="col-md-4">
                        <strong><i class="fas fa-ruler-combined"></i> Dimensions:</strong>
                        <?php echo CARTE_WIDTH_MM; ?>mm × <?php echo CARTE_HEIGHT_MM; ?>mm
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fas fa-print"></i> Qualité:</strong>
                        <?php echo CARTE_DPI; ?> DPI
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fas fa-layer-group"></i> Cartes:</strong>
                        <?php echo count($candidats); ?> sélectionnées
                    </div>
                </div>
            </div>
            
            <?php if (empty($candidats)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Aucun candidat sélectionné pour l'impression.
                    <br>
                    <a href="impression.php" class="btn btn-primary mt-2">
                        <i class="fas fa-arrow-left"></i> Retour à la sélection
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong><?php echo count($candidats); ?></strong> carte(s) prête(s) pour l'impression professionnelle.
                </div>
                
                <!-- Boucle d'impression -->
                <?php 
                $carte_count = 0;
                $cartes_per_page = 4; // 2x2 grid per page
                
                foreach ($candidats as $index => $candidat):
                    $carte_count++;
                    
                    // Nouvelle page toutes les 4 cartes
                    if ($carte_count > 1 && ($carte_count - 1) % $cartes_per_page === 0):
                        echo '</div><div class="print-break"></div><div class="carte-container">';
                    elseif ($carte_count === 1):
                        echo '<div class="carte-container">';
                    endif;
                ?>
                
                    <div class="carte-item">
                        <div class="text-center mb-2">
                            <strong><?php echo htmlspecialchars($candidat['nom'] . ' ' . $candidat['prenom']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo htmlspecialchars($candidat['matricule']); ?></small>
                        </div>
                        
                        <div class="carte-recto">
                            <div class="carte-background"></div>
                            
                            <div class="carte-header">
                                <div class="republique">RÉPUBLIQUE DU CAMEROUN</div>
                                <div class="carte-titre">CARTE D'IDENTITÉ MILITAIRE</div>
                            </div>
                            
                            <div class="carte-content">
                                <div class="carte-photo-section">
                                    <?php if ($candidat['photo'] && file_exists($candidat['photo'])): ?>
                                        <img src="<?php echo $candidat['photo']; ?>" class="carte-photo" alt="Photo">
                                    <?php else: ?>
                                        <div class="carte-photo" style="background: #ccc; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user" style="font-size: 30px; color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="carte-empreinte">
                                        <i class="fas fa-fingerprint"></i>
                                    </div>
                                </div>
                                
                                <div class="carte-info-section">
                                    <div class="carte-nom-complet"><?php echo htmlspecialchars(strtoupper($candidat['nom'])); ?></div>
                                    <div class="carte-prenom"><?php echo htmlspecialchars(ucfirst(strtolower($candidat['prenom']))); ?></div>
                                    <div class="carte-details">
                                        <div class="carte-grade"><?php echo htmlspecialchars(strtoupper($candidat['grade'] ?? '')); ?></div>
                                        <div class="carte-unite"><?php echo htmlspecialchars(strtoupper($candidat['unite'] ?? '')); ?></div>
                                        <div class="carte-matricule">MAT: <?php echo htmlspecialchars($candidat['matricule']); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="carte-footer">
                                <div class="carte-validite">
                                    VALIDE JUSQU'AU: <?php echo date('d/m/Y', strtotime('+5 years')); ?>
                                </div>
                            </div>
                            
                            <?php if ($candidat['code_qr'] && file_exists($candidat['code_qr'])): ?>
                                <img src="<?php echo $candidat['code_qr']; ?>" class="carte-qrcode" alt="QR Code">
                            <?php endif; ?>
                        </div>
                    </div>
                
                <?php 
                // Fermer le conteneur à la fin
                if ($carte_count % $cartes_per_page === 0 || $carte_count === count($candidats)):
                    echo '</div>';
                endif;
                endforeach;
                ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Contrôles d'impression -->
    <div class="print-controls no-print">
        <h5><i class="fas fa-cog"></i> Options d'impression</h5>
        
        <div class="format-selector">
            <label for="printFormat">Format:</label>
            <select id="printFormat" class="form-select" onchange="changePrintFormat(this.value)">
                <option value="A4">Format A4 (4 cartes/feuille)</option>
                <option value="PVC">Format PVC (impression directe)</option>
                <option value="ETIQUETTE">Format Étiquette (prédécoupé)</option>
            </select>
        </div>
        
        <button class="print-button" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimer
        </button>
        
        <button class="btn btn-outline-secondary btn-sm w-100" onclick="window.close()">
            <i class="fas fa-times"></i> Fermer
        </button>
        
        <div class="mt-3">
            <small class="text-muted">
                <strong>Instructions:</strong><br>
                1. Utiliser du papier PVC adhésif<br>
                2. Configurer l'imprimante en 300 DPI<br>
                3. Activer "sans marges"<br>
                4. Utiliser des feuilles prédécoupées
            </small>
        </div>
    </div>
    
    <script>
        function changePrintFormat(format) {
            // Mettre à jour le CSS pour le format sélectionné
            document.body.className = document.body.className.replace(/format-\w+/g, '');
            document.body.classList.add('format-' + format.toLowerCase());
            
            // Ajuster les marges selon le format
            const styleSheet = document.styleSheets[0];
            const rules = styleSheet.cssRules || styleSheet.rules;
            
            for (let i = 0; i < rules.length; i++) {
                if (rules[i].selectorText && rules[i].selectorText.includes('@page')) {
                    styleSheet.deleteRule(i);
                    break;
                }
            }
            
            let pageCSS = '@page { size: A4; margin: 10mm; }';
            
            if (format === 'PVC') {
                pageCSS = '@page { size: 100mm 65mm; margin: 5mm; }';
            } else if (format === 'ETIQUETTE') {
                pageCSS = '@page { size: A4; margin: 5mm; }';
            }
            
            styleSheet.insertRule(pageCSS, 0);
        }
        
        // Optimiser pour l'impression
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
        
        // Initialiser
        changePrintFormat('A4');
    </script>
</body>
</html>
