<?php
// Inclure le fichier de fonctions
require_once '../Carte/confection_carte.php'; 

// Récupérer le matricule depuis l'URL
$matricule_url = $_GET['matricule'] ?? '';

// Si un matricule est fourni, récupérer les données du candidat
if (!empty($matricule_url)) {
    try {
        require_once '../backend/config.php';
        
        $stmt = $pdo->prepare("SELECT * FROM candidat WHERE matricule LIKE ? OR matricule_militaire LIKE ?");
        $stmt->execute(['%' . $matricule_url . '%', '%' . $matricule_url . '%']);
        $candidat_db = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug temporaire
        error_log("Matricule recherché: " . $matricule_url);
        error_log("Candidat trouvé: " . ($candidat_db ? 'OUI' : 'NON'));
        
        if ($candidat_db) {
            $candidat_test = [
                'nom' => $candidat_db['nom'] ?? 'NDONGMO',
                'prenom' => $candidat_db['prenom'] ?? 'Tejiona',
                'sexe' => $candidat_db['sexe'] === 'MASCULIN' ? 'M' : ($candidat_db['sexe'] === 'FEMININ' ? 'F' : 'M'),
                'matricule' => $candidat_db['matricule'] ?? 'CIM-96354',
                'matricule_militaire' => $candidat_db['matricule_militaire'] ?? $candidat_db['matricule'] ?? 'CIM-96354',
                'unite' => $candidat_db['unite'] ?? 'ARMÉE DE TERRE',
                'grade' => $candidat_db['grade'] ?? 'Ingénieur',
                'photo' => $candidat_db['photo'] ?? '',
                'code_qr' => $candidat_db['code_qr'] ?? '',
                'date_enrolement' => $candidat_db['date_enrolement'] ?? '2024-01-15',
                'numero_cni' => $candidat_db['numero_cni'] ?? '1234567890123',
                'taille' => $candidat_db['taille'] ?? '175',
                'groupe_sanguin' => $candidat_db['groupe_sanguin'] ?? 'O+',
                'id' => $candidat_db['id'] ?? 0
            ];
            
            // Debug des données récupérées
            error_log("Nom récupéré: " . $candidat_test['nom']);
            error_log("Prénom récupéré: " . $candidat_test['prenom']);
            error_log("Grade récupéré: " . $candidat_test['grade']);
        } else {
            // Si le candidat n'est pas trouvé, utiliser les données par défaut
            error_log("Candidat non trouvé, utilisation des données par défaut");
            $candidat_test = [
                'nom' => 'NDONGMO',
                'prenom' => 'Tejiona',
                'sexe' => 'M',
                'matricule' => 'CIM-96354',
                'matricule_militaire' => 'CIM-96354',
                'unite' => 'ARMÉE DE TERRE',
                'grade' => 'Ingénieur',
                'photo' => '',
                'code_qr' => '',
                'date_enrolement' => '2024-01-15',
                'numero_cni' => '1234567890123',
                'taille' => '175',
                'groupe_sanguin' => 'O+',
                'id' => 0
            ];
        }
    } catch (Exception $e) {
        // En cas d'erreur de base de données, utiliser les données par défaut
        error_log("Erreur base de données: " . $e->getMessage());
        $candidat_test = [
            'nom' => 'NDONGMO',
            'prenom' => 'Tejiona',
            'sexe' => 'M',
            'matricule' => 'CIM-96354',
            'matricule_militaire' => 'CIM-96354',
            'unite' => 'ARMÉE DE TERRE',
            'grade' => 'Ingénieur',
            'photo' => '',
            'code_qr' => '',
            'date_enrolement' => '2024-01-15',
            'numero_cni' => '1234567890123',
            'taille' => '175',
            'groupe_sanguin' => 'O+',
            'id' => 0
        ];
    }
} else {
    // Données de test par défaut si aucun matricule n'est fourni
    error_log("Aucun matricule fourni, utilisation des données par défaut");
    $candidat_test = [
        'nom' => 'NDONGMO',
        'prenom' => 'Tejiona',
        'sexe' => 'M',
        'matricule' => 'CIM-96354',
        'matricule_militaire' => 'CIM-96354',
        'unite' => 'ARMÉE DE TERRE',
        'grade' => 'Ingénieur',
        'photo' => '',
        'code_qr' => '',
        'date_enrolement' => '2024-01-15',
        'numero_cni' => '1234567890123',
        'taille' => '175',
        'groupe_sanguin' => 'O+',
        'id' => 0
    ];
}

// Générer la carte complète
$carte_complete_html = renderCarte($candidat_test);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisation 3D - Carte Militaire</title>
    <link rel="stylesheet" href="../css/styles_carte.css">
    <link rel="stylesheet" href="../css/bouton-retour.css">
    <style>
        body {
            font-family: 'Garamond', serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            perspective: 2000px;
            overflow-x: hidden;
        }
        
        .container-3d {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header-3d {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }
        
        .header-3d h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .viewport-3d {
            width: 100%;
            height: 400px;
            position: relative;
            perspective: 1000px; /* Réduire la perspective pour un effet 3D plus prononcé */
            margin: 40px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .carte-3d-container {
            width: 85.6mm; /* Une seule carte de large */
            height: 53.98mm;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4, 0.0, 0.2, 1);
            transform-origin: center center; /* Rotation depuis le centre */
        }
        
        .carte-3d-container.rotating {
            animation: rotate3d 8s infinite linear;
        }
        
        .carte-3d-container.paused {
            animation-play-state: paused;
        }
        
        @keyframes rotate3d {
            0% {
                transform: rotateY(0deg) rotateX(0deg);
            }
            100% {
                transform: rotateY(360deg) rotateX(0deg);
            }
        }
        
        .carte-face {
            position: absolute;
            width: 85.6mm;
            height: 53.98mm;
            backface-visibility: hidden;
            border-radius: 5mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .carte-face.recto {
            transform: rotateY(0deg) translateZ(0.5mm); /* Face avant */
        }
        
        .carte-face.verso {
            transform: rotateY(180deg) translateZ(0.5mm); /* Face arrière */
        }
        
        .controls-3d {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .control-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .control-btn.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .speed-control {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px 25px;
            border-radius: 25px;
            backdrop-filter: blur(10px);
        }
        
        .speed-control label {
            color: white;
            font-weight: bold;
        }
        
        .speed-slider {
            width: 150px;
            height: 6px;
            -webkit-appearance: none;
            appearance: none;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
            outline: none;
        }
        
        .speed-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            background: #f5576c;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .info-3d {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .info-3d h3 {
            margin-top: 0;
            color: #f5576c;
        }
        
        .shortcuts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .shortcut {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .key {
            background: #f5576c;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }
        
        .view-modes {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }
        
        .view-btn {
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .view-btn.active {
            background: #f5576c;
            border-color: #f5576c;
        }
        
        @media (max-width: 768px) {
            .container-3d {
                padding: 20px;
            }
            
            .viewport-3d {
                height: 300px;
            }
            
            .carte-3d-container {
                transform: scale(0.8);
            }
            
            .controls-3d {
                flex-direction: column;
                align-items: center;
            }
            
            .header-3d h1 {
                font-size: 2rem;
            }
            
            .control-btn {
                font-size: 0.9rem;
                padding: 10px 20px;
            }
            
            .speed-control {
                flex-direction: column;
                gap: 10px;
            }
            
            .view-modes {
                flex-direction: column;
                gap: 10px;
            }
            
            .view-btn {
                font-size: 0.85rem;
                padding: 6px 15px;
            }
            
            .shortcuts {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .shortcut {
                padding: 8px;
            }
            
            .key {
                min-width: 25px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .container-3d {
                padding: 15px;
            }
            
            .header-3d {
                margin-bottom: 20px;
            }
            
            .header-3d h1 {
                font-size: 1.6rem;
            }
            
            .viewport-3d {
                height: 250px;
                margin: 20px 0;
            }
            
            .carte-3d-container {
                transform: scale(0.6);
            }
            
            .controls-3d {
                gap: 15px;
                margin: 20px 0;
            }
            
            .control-btn {
                font-size: 0.8rem;
                padding: 8px 15px;
            }
            
            .speed-control {
                padding: 10px 15px;
            }
            
            .speed-slider {
                width: 120px;
            }
            
            .view-modes {
                gap: 8px;
                margin: 15px 0;
            }
            
            .view-btn {
                font-size: 0.75rem;
                padding: 5px 12px;
            }
            
            .info-3d {
                padding: 15px;
                margin: 15px 0;
            }
            
            .info-3d h3 {
                font-size: 1rem;
            }
            
            .shortcuts {
                gap: 5px;
                margin-top: 10px;
            }
            
            .shortcut {
                padding: 6px;
            }
            
            .key {
                min-width: 20px;
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 360px) {
            .container-3d {
                padding: 10px;
            }
            
            .header-3d h1 {
                font-size: 1.4rem;
            }
            
            .viewport-3d {
                height: 200px;
            }
            
            .carte-3d-container {
                transform: scale(0.5);
            }
            
            .controls-3d {
                gap: 10px;
                margin: 15px 0;
            }
            
            .control-btn {
                font-size: 0.75rem;
                padding: 6px 12px;
            }
            
            .speed-control {
                padding: 8px 12px;
            }
            
            .speed-slider {
                width: 100px;
            }
            
            .view-modes {
                gap: 6px;
                margin: 10px 0;
            }
            
            .view-btn {
                font-size: 0.7rem;
                padding: 4px 10px;
            }
            
            .info-3d {
                padding: 10px;
                margin: 10px 0;
            }
            
            .info-3d h3 {
                font-size: 0.9rem;
            }
            
            .shortcuts {
                gap: 3px;
            }
            
            .shortcut {
                padding: 4px;
            }
            
            .key {
                min-width: 18px;
                font-size: 0.6rem;
            }
        }
        
        @media (orientation: landscape) and (max-height: 600px) {
            .container-3d {
                padding: 15px;
            }
            
            .header-3d {
                margin-bottom: 15px;
            }
            
            .viewport-3d {
                height: 180px;
                margin: 15px 0;
            }
            
            .carte-3d-container {
                transform: scale(0.7);
            }
            
            .controls-3d {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
                margin: 15px 0;
            }
            
            .speed-control {
                padding: 8px 15px;
            }
            
            .view-modes {
                gap: 8px;
                margin: 10px 0;
            }
            
            .info-3d {
                padding: 10px;
                margin: 10px 0;
            }
        }
        
        @media (prefers-reduced-motion: reduce) {
            .carte-3d-container.rotating {
                animation: none;
            }
            
            .control-btn:hover {
                transform: none;
            }
            
            .view-btn:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="container-3d">
        <div class="header-3d">
            <h1>🎯 Visualisation 3D</h1>
            <p>Carte de : <strong><?php echo htmlspecialchars($candidat_test['nom'] . ' ' . $candidat_test['prenom']); ?></strong> | Matricule : <?php echo htmlspecialchars($candidat_test['matricule']); ?></p>
            <p>Debug: Matricule URL = <?php echo htmlspecialchars($matricule_url); ?> | Unité = <?php echo htmlspecialchars($candidat_test['unite']); ?></p>
            <?php if (!empty($matricule_url) && $candidat_test['matricule'] === 'CIM-96354'): ?>
                <p style="color: red; font-weight: bold;">⚠️ Candidat avec matricule '<?php echo htmlspecialchars($matricule_url); ?>' non trouvé dans la base de données!</p>
            <?php endif; ?>
            <p>Découvrez votre carte militaire sous tous les angles</p>
        </div>
        
        <div class="viewport-3d">
            <div class="carte-3d-container" id="carte3d">
                <div class="carte-face recto">
                    <?php 
                    // Afficher le recto avec la bonne configuration
                    $config_unites = include '../Carte/config_unites.php';
                    $unite = $candidat_test['unite'] ?? 'ARMÉE DE TERRE';
                    $config = $config_unites[$unite] ?? $config_unites['ARMÉE DE TERRE'];
                    $fond_image = file_exists($config['fond']) ? $config['fond'] : '../img/default_fond.png';
                    $logo_unit = !empty($config['logo']) && file_exists($config['logo']) ? $config['logo'] : '';
                    
                    echo renderRecto($candidat_test, $config, $unite, $fond_image, $logo_unit);
                    ?>
                </div>
                <div class="carte-face verso">
                    <?php 
                    // Afficher le verso avec la même configuration
                    echo renderVerso($candidat_test, $config, $unite, $fond_image, $logo_unit);
                    ?>
                </div>
            </div>
        </div>
        
        <!-- BOUTON RETOUR -->
        <div class="back-button-container">
            <a href="impression.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i>
                <span>RETOUR</span>
            </a>
        </div>
        
        <div class="controls-3d">
            <button class="control-btn" id="playBtn" onclick="toggleRotation()">
                ▶️ Lecture
            </button>
            <button class="control-btn" onclick="resetRotation()">
                🔄 Réinitialiser
            </button>
            <button class="control-btn" onclick="flipCard()">
                🔀 Retourner
            </button>
        </div>
        
        <div class="view-modes">
            <button class="view-btn active" onclick="setView('front')">Recto</button>
            <button class="view-btn" onclick="setView('back')">Verso</button>
            <button class="view-btn" onclick="setView('rotate')">Rotation 360°</button>
        </div>
        
        <div class="speed-control">
            <label for="speedSlider">⚡ Vitesse:</label>
            <input type="range" id="speedSlider" class="speed-slider" min="1" max="20" value="8" onchange="updateSpeed(this.value)">
            <span id="speedValue">8s</span>
        </div>
        
        <div class="info-3d">
            <h3>🎮 Contrôles</h3>
            <div class="shortcuts">
                <div class="shortcut">
                    <span class="key">Espace</span>
                    <span>Lecture/Pause</span>
                </div>
                <div class="shortcut">
                    <span class="key">R</span>
                    <span>Réinitialiser</span>
                </div>
                <div class="shortcut">
                    <span class="key">F</span>
                    <span>Retourner</span>
                </div>
                <div class="shortcut">
                    <span class="key">1</span>
                    <span>Recto</span>
                </div>
                <div class="shortcut">
                    <span class="key">2</span>
                    <span>Verso</span>
                </div>
                <div class="shortcut">
                    <span class="key">3</span>
                    <span>Rotation</span>
                </div>
            </div>
        </div>
        
        <div class="info-3d">
            <h3>🔍 Fonctionnalités</h3>
            <ul style="color: white; line-height: 1.8;">
                <li><strong>Rotation 360°</strong> : Visualisation complète de la carte</li>
                <li><strong>Recto/Verso</strong> : Accès rapide aux deux faces</li>
                <li><strong>Vitesse ajustable</strong> : Contrôle de la vitesse de rotation</li>
                <li><strong>Contrôles clavier</strong> : Navigation rapide avec les raccourcis</li>
                <li><strong>Effets 3D réalistes</strong> : Simulation de profondeur et d'ombre</li>
            </ul>
        </div>
    </div>
    
    <script>
        let isRotating = false;
        let currentView = 'front';
        const carte3d = document.getElementById('carte3d');
        const playBtn = document.getElementById('playBtn');
        const speedSlider = document.getElementById('speedSlider');
        const speedValue = document.getElementById('speedValue');
        
        function toggleRotation() {
            if (isRotating) {
                pauseRotation();
            } else {
                startRotation();
            }
        }
        
        function startRotation() {
            carte3d.classList.add('rotating');
            carte3d.classList.remove('paused');
            playBtn.textContent = '⏸️ Pause';
            playBtn.classList.add('active');
            isRotating = true;
            currentView = 'rotate';
            updateViewButtons();
        }
        
        function pauseRotation() {
            carte3d.classList.add('paused');
            playBtn.textContent = '▶️ Lecture';
            playBtn.classList.remove('active');
            isRotating = false;
        }
        
        function resetRotation() {
            carte3d.classList.remove('rotating', 'paused');
            carte3d.style.transform = 'rotateY(0deg)';
            playBtn.textContent = '▶️ Lecture';
            playBtn.classList.remove('active');
            isRotating = false;
            currentView = 'front';
            updateViewButtons();
        }
        
        function flipCard() {
            const currentRotation = getCurrentRotation();
            const newRotation = currentRotation + 180;
            carte3d.style.transform = `rotateY(${newRotation}deg)`;
            
            // Mettre à jour la vue actuelle
            if (currentView === 'front') {
                currentView = 'back';
            } else if (currentView === 'back') {
                currentView = 'front';
            }
            updateViewButtons();
        }
        
        function getCurrentRotation() {
            const transform = window.getComputedStyle(carte3d).transform;
            if (transform === 'none') return 0;
            
            const values = transform.split('(')[1].split(')')[0].split(',');
            const a = values[0];
            const b = values[1];
            let angle = Math.atan2(b, a) * (180/Math.PI);
            
            return angle;
        }
        
        function setView(view) {
            resetRotation();
            currentView = view;
            
            switch(view) {
                case 'front':
                    carte3d.style.transform = 'rotateY(0deg)';
                    break;
                case 'back':
                    carte3d.style.transform = 'rotateY(180deg)';
                    break;
                case 'rotate':
                    startRotation();
                    break;
            }
            updateViewButtons();
        }
        
        function updateViewButtons() {
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            if (currentView === 'front') {
                document.querySelector('.view-btn[onclick="setView(\'front\')"]').classList.add('active');
            } else if (currentView === 'back') {
                document.querySelector('.view-btn[onclick="setView(\'back\')"]').classList.add('active');
            } else if (currentView === 'rotate') {
                document.querySelector('.view-btn[onclick="setView(\'rotate\')"]').classList.add('active');
            }
        }
        
        function updateSpeed(value) {
            speedValue.textContent = value + 's';
            carte3d.style.animationDuration = value + 's';
        }
        
        // Contrôles clavier
        document.addEventListener('keydown', (e) => {
            switch(e.key) {
                case ' ':
                    e.preventDefault();
                    toggleRotation();
                    break;
                case 'r':
                case 'R':
                    resetRotation();
                    break;
                case 'f':
                case 'F':
                    flipCard();
                    break;
                case '1':
                    setView('front');
                    break;
                case '2':
                    setView('back');
                    break;
                case '3':
                    setView('rotate');
                    break;
            }
        });
        
        // Interaction avec la souris pour rotation manuelle
        let isDragging = false;
        let startX = 0;
        let currentRotation = 0;
        
        carte3d.addEventListener('mousedown', (e) => {
            if (!isRotating) {
                isDragging = true;
                startX = e.clientX;
                currentRotation = getCurrentRotation();
                carte3d.style.cursor = 'grabbing';
            }
        });
        
        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                const deltaX = e.clientX - startX;
                const rotation = currentRotation + (deltaX * 0.5);
                carte3d.style.transform = `rotateY(${rotation}deg)`;
            }
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
            carte3d.style.cursor = 'grab';
        });
        
        // Touch support pour mobile
        carte3d.addEventListener('touchstart', (e) => {
            if (!isRotating) {
                isDragging = true;
                startX = e.touches[0].clientX;
                currentRotation = getCurrentRotation();
            }
        });
        
        document.addEventListener('touchmove', (e) => {
            if (isDragging) {
                const deltaX = e.touches[0].clientX - startX;
                const rotation = currentRotation + (deltaX * 0.5);
                carte3d.style.transform = `rotateY(${rotation}deg)`;
            }
        });
        
        document.addEventListener('touchend', () => {
            isDragging = false;
        });
        
        // Initialiser le curseur
        carte3d.style.cursor = 'grab';
    </script>
</body>
</html>
