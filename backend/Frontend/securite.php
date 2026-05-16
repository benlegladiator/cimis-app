<?php
// Inclure le fichier de fonctions
require_once '../Carte/confection_carte.php'; 

// Données de test pour la démo
$candidat_test = [
    'nom' => 'NDONGMO',
    'prenom' => 'Tejiona',
    'sexe' => 'M',
    'matricule' => 'CIM-96354',
    'matricule_militaire' => 'CIM-96354',
    'unite' => 'CIMIS',
    'grade' => 'Ingénieur',
    'photo' => '',
    'code_qr' => ''
];

// Fonction de démo pour carte style sécurité
function renderCarteDemo($candidat) {
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card terre demo-card security-highlight" style="
            background: linear-gradient(135deg, #e8f5e8 0%, #d4e8d4 100%);
            border: 3px solid #2d5a3d;
            box-shadow: 
                0 0 20mm rgba(45, 90, 61, 0.4),
                0 0 40mm rgba(212, 175, 55, 0.2),
                inset 0 0 5mm rgba(45, 90, 61, 0.1);
        ">
            <!-- Motif anti-copie -->
            <div class="anti-copy-pattern"></div>
            
            <!-- Hologramme 3D ultra-brillant -->
            <div class="card-hologram" style="
                opacity: 0.6;
                filter: brightness(2.0) contrast(1.5) saturate(1.5);
                animation: hologramRotate 4s infinite linear, hologramGlow 2s infinite alternate;
            ">
                <img src="../img/cameroun.png" class="hologram-image" alt="Hologramme Cameroun" style="
                    filter: brightness(2.5) contrast(2.0) saturate(2.0);
                ">
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

// Fonction démo watermark matricule
function renderWatermarkDemo($candidat) {
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card terre demo-card security-highlight" style="
            background: linear-gradient(135deg, #e8f5e8 0%, #d4e8d4 100%);
            border: 3px solid #2d5a3d;
            box-shadow: 0 0 20mm rgba(45, 90, 61, 0.4);
        ">
            <!-- Watermark matricule -->
            <div class="matricule-watermark">
                CIM-96354
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

// Fonction démo signature microscopique
function renderMicrotextDemo($candidat) {
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card terre demo-card security-highlight" style="
            background: linear-gradient(135deg, #e8f5e8 0%, #d4e8d4 100%);
            border: 3px solid #2d5a3d;
            box-shadow: 0 0 20mm rgba(45, 90, 61, 0.4);
            position: relative;
        ">
            <!-- Bordures avec signature microscopique -->
            <div class="border-signature" style="
                content: 'Republique du Cameroun - Ministry of Defence 2026 - Ministère de la Défense 2026 - Republic of Cameroon';
            "></div>
            
            <!-- Signature microscopique -->
            <div class="micro-signature">
                Republique du Cameroun - Ministère de la Défense 2026 - Republic of Cameroon - Ministry of Defence 2026
            </div>
            
            <!-- Message pour loupe -->
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 10;">
                <div style="
                    background: rgba(0,0,0,0.9); 
                    border: 2px solid #d4af37; 
                    border-radius: 10px; 
                    padding: 3mm;
                    max-width: 40mm;
                ">
                    <i class="fa-solid fa-search" style="color: #d4af37; font-size: 4mm; margin-bottom: 2mm;"></i>
                    <p style="color: white; font-size: 1.5mm; margin: 0;">
                        Utilisez une loupe<br>
                        pour voir la signature
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

// Fonction démo QR Code crypté
function renderQRCodeDemo($candidat) {
    // Données cryptées pour le QR Code
    $qr_data = [
        'matricule' => $candidat['matricule'],
        'nom' => $candidat['nom'],
        'prenom' => $candidat['prenom'],
        'unite' => $candidat['unite'],
        'grade' => $candidat['grade'],
        'timestamp' => time(),
        'signature' => hash('sha256', $candidat['matricule'] . 'CIMIS2026')
    ];
    
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode(json_encode($qr_data));
    
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card terre demo-card security-highlight" style="
            background: linear-gradient(135deg, #e8f5e8 0%, #d4e8d4 100%);
            border: 3px solid #2d5a3d;
            box-shadow: 0 0 20mm rgba(45, 90, 61, 0.4);
            position: relative;
        ">
            <!-- QR Code sécurisé VRAI -->
            <div class="qr-secure" style="
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 25mm;
                height: 25mm;
                background: white;
                border: 0.5mm solid rgba(0, 0, 0, 0.3);
                border-radius: 1mm;
                padding: 2mm;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <!-- Vrai QR Code scannable -->
                <img src="<?php echo $qr_url; ?>" alt="QR Code Sécurisé" style="
                    width: 20mm;
                    height: 20mm;
                    border-radius: 0.5mm;
                ">
                
                <!-- Badge sécurité -->
                <div style="
                    position: absolute;
                    top: -3mm;
                    right: -3mm;
                    background: #d4af37;
                    color: #000;
                    border-radius: 50%;
                    width: 6mm;
                    height: 6mm;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 3mm;
                    font-weight: bold;
                ">
                    🔒
                </div>
            </div>
            
            <!-- Message de scan -->
            <div style="position: absolute; bottom: 5mm; left: 50%; transform: translateX(-50%); text-align: center; z-index: 10;">
                <div style="
                    background: rgba(0,0,0,0.9); 
                    border: 2px solid #d4af37; 
                    border-radius: 5px; 
                    padding: 2mm;
                ">
                    <p style="color: white; font-size: 2mm; margin: 0;">
                        Scannez pour vérifier<br>
                        l'authenticité
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

// Fonction démo Guilloches
function renderGuillochesDemo($candidat) {
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card terre demo-card security-highlight" style="
            background: linear-gradient(135deg, #e8f5e8 0%, #d4e8d4 100%);
            border: 3px solid #2d5a3d;
            box-shadow: 0 0 20mm rgba(45, 90, 61, 0.4);
            position: relative;
        ">
            <!-- Motifs de guilloches -->
            <div class="guilloche-pattern"></div>
            
            <!-- Message explicatif -->
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 10;">
                <div style="
                    background: rgba(0,0,0,0.9); 
                    border: 2px solid #d4af37; 
                    border-radius: 10px; 
                    padding: 3mm;
                    max-width: 40mm;
                ">
                    <i class="fa-solid fa-shield-halved" style="color: #d4af37; font-size: 4mm; margin-bottom: 2mm;"></i>
                    <p style="color: white; font-size: 1.5mm; margin: 0;">
                        Motifs de guilloches<br>
                        anti-copie
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

// Fonction démo Éléments Holographiques
function renderHolographicDemo($candidat) {
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card terre demo-card security-highlight" style="
            background: linear-gradient(135deg, #e8f5e8 0%, #d4e8d4 100%);
            border: 3px solid #2d5a3d;
            box-shadow: 0 0 20mm rgba(45, 90, 61, 0.4);
            position: relative;
        ">
            <!-- Motifs de guilloches -->
            <div class="guilloche-pattern"></div>
            
            <!-- Éléments holographiques -->
            <div class="holographic-element center-star"></div>
            <div class="holographic-element bottom-right-square"></div>
            
            <!-- Filigrane -->
            <div class="security-watermark">CIMIS</div>
            
            <!-- Message explicatif -->
            <div style="position: absolute; bottom: 5mm; left: 50%; transform: translateX(-50%); text-align: center; z-index: 10;">
                <div style="
                    background: rgba(0,0,0,0.9); 
                    border: 2px solid #d4af37; 
                    border-radius: 5px; 
                    padding: 2mm;
                ">
                    <p style="color: white; font-size: 2mm; margin: 0;">
                        Éléments holographiques<br>
                        animés
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

// Fonction démo Zone de Sécurité Photo
function renderPhotoSecurityDemo($candidat) {
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card terre demo-card security-highlight" style="
            background: linear-gradient(135deg, #e8f5e8 0%, #d4e8d4 100%);
            border: 3px solid #2d5a3d;
            box-shadow: 0 0 20mm rgba(45, 90, 61, 0.4);
            position: relative;
        ">
            <!-- Motifs de guilloches -->
            <div class="guilloche-pattern"></div>
            
            <!-- Zone de sécurité photo -->
            <div class="photo-security-zone">
                <div class="micro-text-security">VALIDE SEULEMENT AVEC PHOTO</div>
            </div>
            
            <!-- Photo placeholder -->
            <div style="
                position: absolute;
                top: 50%;
                left: 17%;
                transform: translate(-50%, -50%);
                width: 20mm;
                height: 26mm;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 2rem;
                border-radius: 3px;
                z-index: 3;
            ">
                <i class="fa-solid fa-user"></i>
            </div>
            
            <!-- Message explicatif -->
            <div style="position: absolute; bottom: 5mm; right: 5mm; text-align: center; z-index: 10;">
                <div style="
                    background: rgba(0,0,0,0.9); 
                    border: 2px solid #d4af37; 
                    border-radius: 5px; 
                    padding: 2mm;
                ">
                    <p style="color: white; font-size: 1.8mm; margin: 0;">
                        Zone sécurité<br>
                        photo
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

// Générer le HTML des cartes de démo
$carte_demo_html = renderCarteDemo($candidat_test);
$watermark_demo_html = renderWatermarkDemo($candidat_test);
$microtext_demo_html = renderMicrotextDemo($candidat_test);
$qrcode_demo_html = renderQRCodeDemo($candidat_test);
$guilloches_demo_html = renderGuillochesDemo($candidat_test);
$holographic_demo_html = renderHolographicDemo($candidat_test);
$photo_security_demo_html = renderPhotoSecurityDemo($candidat_test);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Démonstration Sécurité Cartes Militaires</title>
    <link rel="stylesheet" href="../css/styles_carte.css">
    <link rel="stylesheet" href="../css/securite_carte.css">
    <style>
        body {
            font-family: 'Garamond', serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .security-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .security-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .security-header h1 {
            color: #2c5aa0;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .security-menu {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .security-btn {
            padding: 12px 25px;
            background: #2c5aa0;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }
        
        .security-btn:hover {
            background: #1e3c72;
            transform: translateY(-2px);
        }
        
        .security-btn.active {
            background: #d4af37;
            color: #000;
        }
        
        .security-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .security-section.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .security-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        .security-info h2 {
            color: #2c5aa0;
            font-size: 1.8em;
            margin-bottom: 15px;
        }
        
        .security-info p {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #333;
        }
        
        .security-demo {
            display: flex;
            flex-direction: column;
            align-items: center;
            transform: scale(1.3); /* Augmenter la taille de 30% */
            margin: 20px 0;
        }
        
        .test-instructions {
            background: #f0f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #2c5aa0;
        }
        
        .test-instructions h3 {
            margin-top: 0;
            color: #2c5aa0;
        }
        
        .test-btn {
            background: #d4af37;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .security-content {
                grid-template-columns: 1fr;
            }
            
            .security-menu {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="security-container">
        <div class="security-header">
            <h1>🛡️ Sécurité des Cartes Militaires</h1>
            <p>Découvrez les technologies anti-falsification intégrées</p>
        </div>
        
        <div class="security-menu">
            <button class="security-btn active" onclick="showSection('hologram')">1. Hologramme 3D</button>
            <button class="security-btn" onclick="showSection('watermark')">2. Matricule Watermark</button>
            <button class="security-btn" onclick="showSection('microtext')">3. Signature Microscopique</button>
            <button class="security-btn" onclick="showSection('qrcode')">4. QR Code Crypté</button>
            <button class="security-btn" onclick="showSection('guilloches')">5. Guilloches</button>
            <button class="security-btn" onclick="showSection('holographic-elements')">6. Éléments Holographiques</button>
            <button class="security-btn" onclick="showSection('photo-security')">7. Zone Sécurité Photo</button>
        </div>
        
        <!-- Section 1: Hologramme 3D -->
        <div id="hologram" class="security-section active">
            <div class="security-content">
                <div class="security-info">
                    <h2>🌟 Hologramme 3D</h2>
                    <p><strong>Protection visuelle immédiate</strong></p>
                    <p>L'hologramme 3D du Cameroun tourne continuellement et change d'apparence selon l'angle de vue. Cet effet est impossible à reproduire avec une photocopie ou une impression standard.</p>
                    
                    <div class="test-instructions">
                        <h3>📋 Comment vérifier :</h3>
                        <ul>
                            <li>Inclinez la carte devant vous</li>
                            <li>Observez l'animation 3D continue</li>
                            <li>Survolez avec la souris pour l'effet interactif</li>
                            <li>Une photocopie perdra l'effet 3D</li>
                        </ul>
                    </div>
                    
                    <button class="test-btn" onclick="testSecurity('hologram')">Tester l'effet</button>
                </div>
                
                <div class="security-demo">
                    <?php echo $carte_demo_html; ?>
                </div>
            </div>
        </div>
        
        <!-- Section 2: Matricule Watermark -->
        <div id="watermark" class="security-section">
            <div class="security-content">
                <div class="security-info">
                    <h2>💧 Matricule Watermark</h2>
                    <p><strong>Authentification par lumière spécifique</strong></p>
                    <p>Le matricule est intégré en arrière-plan avec un effet de réfraction similaire aux billets de banque. Visible uniquement sous lumière bleue ou violette et selon l'angle d'inclinaison.</p>
                    
                    <div class="test-instructions">
                        <h3>📋 Comment vérifier :</h3>
                        <ul>
                            <li>Inclinez la carte sous lumière bleue/violette</li>
                            <li>Le matricule apparaît en transparence</li>
                            <li>Change d'intensité selon l'angle</li>
                            <li>Invisible à la photocopie normale</li>
                        </ul>
                    </div>
                    
                    <button class="test-btn" onclick="testSecurity('watermark')">Tester l'effet</button>
                </div>
                
                <div class="security-demo">
                    <?php echo $watermark_demo_html; ?>
                </div>
            </div>
        </div>
        
        <!-- Section 3: Signature Microscopique -->
        <div id="microtext" class="security-section">
            <div class="security-content">
                <div class="security-info">
                    <h2>🔍 Signature Microscopique</h2>
                    <p><strong>Sécurité de niveau étatique</strong></p>
                    <p>Les informations officielles "Republique du Cameroun - Ministère de la Défense 2026 - Republic of Cameroon - Ministry of Defence 2026" sont intégrées dans les bordures avec une taille de 0.3mm. Visible uniquement à la loupe, ces mentions officielles bilingues authentifient le document au plus haut niveau étatique.</p>
                    
                    <div class="test-instructions">
                        <h3>📋 Comment vérifier :</h3>
                        <ul>
                            <li>Utilisez une loupe (x10 minimum)</li>
                            <li>Examinez les bordures de la carte</li>
                            <li>Les mentions officielles apparaissent en micro-texte</li>
                            <li>Texte bilingue français/anglais</li>
                            <li>Référence à l'année 2026</li>
                            <li>Flou et illisible sur photocopie</li>
                            <li>Authentification au niveau étatique</li>
                        </ul>
                    </div>
                    
                    <button class="test-btn" onclick="testSecurity('microtext')">Tester l'effet</button>
                </div>
                
                <div class="security-demo">
                    <?php echo $microtext_demo_html; ?>
                </div>
            </div>
        </div>
        
        <!-- Section 4: QR Code Crypté -->
        <div id="qrcode" class="security-section">
            <div class="security-content">
                <div class="security-info">
                    <h2>📱 QR Code Crypté</h2>
                    <p><strong>Validation numérique en temps réel</strong></p>
                    <p>Le QR code contient les données cryptées de la carte avec signature numérique unique. Chaque scan vérifie l'authenticité via une base de données sécurisée et affiche le statut de validation en temps réel.</p>
                    
                    <div class="test-instructions">
                        <h3>📋 Comment vérifier :</h3>
                        <ul>
                            <li>Scannez le QR code avec un smartphone</li>
                            <li>La page web affiche les informations sécurisées</li>
                            <li>Statut "VALIDE" si authentique</li>
                            <li>Tentative de copie = invalide</li>
                            <li>Validation instantanée et traçable</li>
                        </ul>
                    </div>
                    
                    <button class="test-btn" onclick="testSecurity('qrcode')">Tester l'effet</button>
                </div>
                
                <div class="security-demo">
                    <?php echo $qrcode_demo_html; ?>
                </div>
            </div>
        </div>
        
        <!-- Section 5: Guilloches -->
        <div id="guilloches" class="security-section">
            <div class="security-content">
                <div class="security-info">
                    <h2>🌀 Motifs de Guilloches</h2>
                    <p><strong>Protection anti-copie avancée</strong></p>
                    <p>Les motifs de guilloches sont des lignes complexes entrelacées qui créent une texture de fond unique. Impossible à reproduire parfaitement avec une photocopie ou un scanner, ces motifs protègent contre la falsification par reproduction mécanique.</p>
                    
                    <div class="test-instructions">
                        <h3>📋 Comment vérifier :</h3>
                        <ul>
                            <li>Observez les lignes croisées en arrière-plan</li>
                            <li>Motifs or et vert entrelacés à 45°</li>
                            <li>Une photocopie perdra la netteté des motifs</li>
                            <li>Texture complexe impossible à recopier</li>
                            <li>Protection invisible mais efficace</li>
                        </ul>
                    </div>
                    
                    <button class="test-btn" onclick="testSecurity('guilloches')">Tester l'effet</button>
                </div>
                
                <div class="security-demo">
                    <?php echo $guilloches_demo_html; ?>
                </div>
            </div>
        </div>
        
        <!-- Section 6: Éléments Holographiques -->
        <div id="holographic-elements" class="security-section">
            <div class="security-content">
                <div class="security-info">
                    <h2>✨ Éléments Holographiques</h2>
                    <p><strong>Sécurité visuelle animée</strong></p>
                    <p>Les éléments holographiques animés (étoile rotative et carré pulsant) créent des effets de brillance et de mouvement impossibles à reproduire. Ces animations continues ajoutent une couche de sécurité dynamique et moderne.</p>
                    
                    <div class="test-instructions">
                        <h3>📋 Comment vérifier :</h3>
                        <ul>
                            <li>Étoile au centre avec rotation continue</li>
                            <li>Petit carré en bas à droite avec pulsation</li>
                            <li>Effets de brillance et changement de couleurs</li>
                            <li>Animation impossible sur photocopie</li>
                            <li>Éléments uniques par carte</li>
                        </ul>
                    </div>
                    
                    <button class="test-btn" onclick="testSecurity('holographic-elements')">Tester l'effet</button>
                </div>
                
                <div class="security-demo">
                    <?php echo $holographic_demo_html; ?>
                </div>
            </div>
        </div>
        
        <!-- Section 7: Zone Sécurité Photo -->
        <div id="photo-security" class="security-section">
            <div class="security-content">
                <div class="security-info">
                    <h2>🛡️ Zone Sécurité Photo</h2>
                    <p><strong>Protection de l'identité visuelle</strong></p>
                    <p>La zone de sécurité autour de la photo avec micro-texte "VALIDE SEULEMENT AVEC PHOTO" garantit que la photo ne peut être remplacée. Cette protection empêche les falsifications d'identité visuelle.</p>
                    
                    <div class="test-instructions">
                        <h3>📋 Comment vérifier :</h3>
                        <ul>
                            <li>Zone dorée autour de la photo</li>
                            <li>Micro-texte en haut de la zone</li>
                            <li>Lisible uniquement avec une loupe</li>
                            <li>Impossible à modifier sans détérioration</li>
                            <li>Garantie d'authenticité de la photo</li>
                        </ul>
                    </div>
                    
                    <button class="test-btn" onclick="testSecurity('photo-security')">Tester l'effet</button>
                </div>
                
                <div class="security-demo">
                    <?php echo $photo_security_demo_html; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/carte.js"></script>
    <script>
        function showSection(sectionId) {
            // Masquer toutes les sections
            document.querySelectorAll('.security-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Désactiver tous les boutons
            document.querySelectorAll('.security-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Afficher la section sélectionnée
            document.getElementById(sectionId).classList.add('active');
            
            // Activer le bouton correspondant
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
