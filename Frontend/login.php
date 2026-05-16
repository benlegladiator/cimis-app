<?php
session_start();
require_once '../backend/config.php';

// Vérifier si le code d'accès a été validé
if (!isset($_SESSION['access_code']) || $_SESSION['access_code'] !== 'CIMIS2.02026') {
    header('Location: ../index.php');
    exit;
}

// Traitement du formulaire d'authentification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            // Vérification par nom d'utilisateur/mot de passe (base de données)
            $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE username = :username AND actif = 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            // Vérification du mot de passe
            if ($user && password_verify($password, $user['password'])) {
                // Succès: création de la session
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['matricule'] = $user['matricule'] ?? '';
                
                // Mise à jour de la date de dernière connexion
                $updateStmt = $pdo->prepare("UPDATE utilisateur SET date_derniere_connexion = NOW() WHERE id = :id");
                $updateStmt->execute(['id' => $user['id']]);
                
                // Nettoyer le code d'accès de la session
                unset($_SESSION['access_code']);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Nom d\'utilisateur ou mot de passe incorrect';
            }
        } catch(PDOException $e) {
            $error = 'Erreur lors de la connexion: ' . $e->getMessage();
        }
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
}

// Redirection si déjà connecté
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification - CIMIS</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #0a0a0a 100%);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            overflow: hidden;
            position: relative;
        }
        
        .space-field {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }
        
        .atom {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(74, 222, 128, 0.8);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(74, 222, 128, 0.6);
        }
        
        .molecule {
            position: absolute;
            width: 60px;
            height: 60px;
            animation: moleculeFloat 20s ease-in-out infinite;
        }
        
        .molecule-atom {
            position: absolute;
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.7);
        }
        
        .molecule-bond {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            transform-origin: center;
        }
        
        .electron {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(147, 51, 234, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 8px rgba(147, 51, 234, 0.8);
        }
        
        .photon {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(251, 191, 36, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(251, 191, 36, 0.8);
        }
        
        .quark {
            position: absolute;
            width: 1px;
            height: 1px;
            background: rgba(239, 68, 68, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.8);
        }
        
        .quantum-wave {
            position: absolute;
            width: 100px;
            height: 100px;
            border: 1px solid rgba(74, 222, 128, 0.2);
            border-radius: 50%;
            animation: waveRipple 15s ease-in-out infinite;
        }
        
        .auth-container {
            position: relative;
            min-height: 100vh;
            display: flex;
            z-index: 10;
        }
        
        .auth-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(74, 222, 128, 0.1);
        }
        
        .auth-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-img {
            width: 200px;
            height: 200px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 0 30px rgba(74, 222, 128, 0.3));
            transition: all 0.3s ease;
        }
        
        .logo-img:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 40px rgba(74, 222, 128, 0.5));
        }
        
        .auth-info {
            text-align: center;
            color: #fff;
        }
        
        .auth-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4ade80;
            margin: 0 0 1rem 0;
            text-shadow: 0 0 20px rgba(74, 222, 128, 0.2);
            letter-spacing: 2px;
        }
        
        .auth-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0 0 2rem 0;
            font-weight: 300;
        }
        
        .access-verified {
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            color: #4ade80;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .auth-form {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(74, 222, 128, 0.2);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(74, 222, 128, 0.1),
                inset 0 0 20px rgba(74, 222, 128, 0.05);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.9rem;
            color: #4ade80;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4ade80;
            box-shadow: 0 0 20px rgba(74, 222, 128, 0.2);
            background: rgba(0, 0, 0, 0.5);
        }
        
        .form-input::placeholder {
            color: #666;
            letter-spacing: 1px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 1.25rem;
            background: linear-gradient(135deg, rgba(74, 222, 128, 0.9) 0%, rgba(74, 222, 128, 0.8) 100%);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, rgba(74, 222, 128, 1) 0%, rgba(74, 222, 128, 0.9) 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(74, 222, 128, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #dc3545;
            font-size: 0.9rem;
            text-align: center;
            animation: errorPulse 2s ease-in-out infinite;
        }
        
        .back-btn {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 0.75rem 1.25rem;
            color: #fff;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 20;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .security-badge {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(74, 222, 128, 0.2);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: rgba(74, 222, 128, 0.6);
            backdrop-filter: blur(5px);
            z-index: 20;
        }
        
        @keyframes atomFloat {
            0%, 100% { 
                transform: translate(0, 0) scale(1);
                opacity: 0.8;
            }
            50% { 
                transform: translate(20px, -15px) scale(1.2);
                opacity: 1;
            }
        }
        
        @keyframes moleculeFloat {
            0%, 100% { 
                transform: translate(0, 0) rotate(0deg);
                opacity: 0.7;
            }
            25% { 
                transform: translate(30px, -20px) rotate(90deg);
                opacity: 0.9;
            }
            50% { 
                transform: translate(-20px, -30px) rotate(180deg);
                opacity: 0.6;
            }
            75% { 
                transform: translate(-40px, 10px) rotate(270deg);
                opacity: 0.8;
            }
        }
        
        @keyframes electronOrbit {
            0% { 
                transform: rotate(0deg) translateX(30px);
                opacity: 0.9;
            }
            100% { 
                transform: rotate(360deg) translateX(30px);
                opacity: 0.9;
            }
        }
        
        @keyframes photonPulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 0.8;
            }
            50% { 
                transform: scale(2);
                opacity: 0.3;
            }
        }
        
        @keyframes quarkSpin {
            0% { 
                transform: rotate(0deg) translateX(15px);
                opacity: 0.8;
            }
            100% { 
                transform: rotate(720deg) translateX(15px);
                opacity: 0.8;
            }
        }
        
        @keyframes waveRipple {
            0% { 
                transform: scale(1);
                opacity: 0.3;
            }
            100% { 
                transform: scale(3);
                opacity: 0;
            }
        }
        
        @keyframes errorPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        @media (max-width: 1024px) {
            .auth-container {
                flex-direction: column;
            }
            
            .auth-left, .auth-right {
                border-right: none;
                border-bottom: 1px solid rgba(74, 222, 128, 0.1);
            }
            
            .auth-left {
                padding: 2rem;
            }
            
            .auth-right {
                padding: 2rem;
            }
            
            .logo-img {
                width: 150px;
                height: 150px;
            }
            
            .auth-title {
                font-size: 2rem;
            }
            
            .auth-form {
                padding: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .auth-left, .auth-right {
                padding: 1.5rem;
            }
            
            .logo-img {
                width: 120px;
                height: 120px;
            }
            
            .auth-title {
                font-size: 1.8rem;
            }
            
            .auth-subtitle {
                font-size: 1rem;
            }
            
            .auth-form {
                padding: 1.5rem;
            }
            
            .back-btn {
                position: relative;
                top: auto;
                left: auto;
                margin-bottom: 1rem;
                width: 100%;
                justify-content: center;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .form-input {
                font-size: 0.95rem;
                padding: 0.875rem 0.75rem;
            }
            
            .submit-btn {
                font-size: 0.85rem;
                padding: 0.875rem 0.75rem;
            }
        }
        
        @media (max-width: 480px) {
            .auth-left, .auth-right {
                padding: 1rem;
            }
            
            .logo-img {
                width: 100px;
                height: 100px;
            }
            
            .auth-title {
                font-size: 1.6rem;
            }
            
            .auth-subtitle {
                font-size: 0.9rem;
            }
            
            .auth-form {
                padding: 1rem;
            }
            
            .form-group {
                margin-bottom: 0.875rem;
            }
            
            .form-input {
                font-size: 0.9rem;
                padding: 0.75rem 0.625rem;
            }
            
            .submit-btn {
                font-size: 0.8rem;
                padding: 0.75rem 0.625rem;
            }
        }
        
        @media (max-width: 360px) {
            .auth-container {
                padding: 0.5rem;
            }
            
            .logo-img {
                width: 80px;
                height: 80px;
            }
            
            .auth-title {
                font-size: 1.4rem;
            }
            
            .auth-subtitle {
                font-size: 0.85rem;
            }
            
            .form-input {
                font-size: 0.85rem;
                padding: 0.625rem 0.5rem;
            }
            
            .submit-btn {
                font-size: 0.75rem;
                padding: 0.625rem 0.5rem;
            }
        }
        
        @media (orientation: landscape) and (max-height: 600px) {
            .auth-container {
                flex-direction: row;
                padding: 1rem;
            }
            
            .auth-left, .auth-right {
                padding: 1rem;
            }
            
            .logo-img {
                width: 100px;
                height: 100px;
            }
            
            .auth-title {
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }
            
            .auth-form {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Effets de fond quantique -->
    <div class="space-field" id="spaceField"></div>

    <!-- Bouton retour -->
    <a href="../index.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Retour
    </a>

    <!-- Badge de sécurité -->
    <div class="security-badge">
        <i class="fa-solid fa-shield-halved"></i>
    </div>

    <!-- Contenu principal à deux compartiments -->
    <div class="auth-container">
        <!-- Compartiment gauche - Logo -->
        <div class="auth-left">
            <div class="logo-container">
                <img src="../img/cimis.png" alt="CIMIS" class="logo-img">
            </div>
            
            <div class="auth-info">
                <h1 class="auth-title">CIMIS</h1>
                <p class="auth-subtitle">CARTE D'IDENTITÉ MILITAIRE INFORMATISÉE ET SÉCURISÉE</p>
                
                <div class="access-verified">
                    <i class="fa-solid fa-check-circle"></i>
                    Accès Vérifié
                </div>
                
                <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.9rem; margin-top: 2rem;">
                    Système d'authentification sécurisé<br>
                    Ministère de la Défense
                </p>
            </div>
        </div>

        <!-- Compartiment droit - Formulaire -->
        <div class="auth-right">
            <form method="POST" action="" class="auth-form">
                <h2 style="text-align: center; color: #4ade80; margin: 0 0 2rem 0; font-size: 1.8rem;">
                    Connexion Sécurisée
                </h2>
                
                <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fa-solid fa-user"></i> Nom d'Utilisateur
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        id="username" 
                        class="form-input" 
                        placeholder="EX: CIMIS17" 
                        required
                        autocomplete="username"
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fa-solid fa-lock"></i> Mot de Passe
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-input" 
                        placeholder="•••••••••" 
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-sign-in-alt"></i> Se Connecter
                </button>
            </form>
        </div>
    </div>

    <script>
        // Espace quantique avec atomes et molécules
        document.addEventListener('DOMContentLoaded', function() {
            const spaceField = document.getElementById('spaceField');
            
            // Créer des atomes
            for (let i = 0; i < 25; i++) {
                const atom = document.createElement('div');
                atom.className = 'atom';
                atom.style.left = Math.random() * 100 + '%';
                atom.style.top = Math.random() * 100 + '%';
                atom.style.animationDelay = Math.random() * 10 + 's';
                atom.style.animationDuration = (Math.random() * 4 + 3) + 's';
                atom.style.animation = `atomFloat ${Math.random() * 3 + 2}s ease-in-out infinite`;
                spaceField.appendChild(atom);
            }
            
            // Créer des molécules
            for (let i = 0; i < 12; i++) {
                const molecule = document.createElement('div');
                molecule.className = 'molecule';
                
                // Position de la molécule
                const molX = Math.random() * 80 + 10 + '%';
                const molY = Math.random() * 80 + 10 + '%';
                molecule.style.left = molX;
                molecule.style.top = molY;
                
                // Créer 2-4 atomes pour la molécule
                const atomCount = Math.floor(Math.random() * 3) + 2;
                for (let j = 0; j < atomCount; j++) {
                    const molAtom = document.createElement('div');
                    molAtom.className = 'molecule-atom';
                    
                    // Position relative à la molécule
                    const angle = (j / atomCount) * Math.PI * 2;
                    const distance = 20 + Math.random() * 10;
                    const atomX = Math.cos(angle) * distance + 'px';
                    const atomY = Math.sin(angle) * distance + 'px';
                    
                    molAtom.style.left = atomX;
                    molAtom.style.top = atomY;
                    molAtom.style.animationDelay = Math.random() * 2 + 's';
                    molecule.appendChild(molAtom);
                }
                
                // Créer des liaisons
                for (let j = 0; j < atomCount; j++) {
                    const bond = document.createElement('div');
                    bond.className = 'molecule-bond';
                    
                    const angle1 = (j / atomCount) * Math.PI * 2;
                    const angle2 = ((j + 1) / atomCount) * Math.PI * 2;
                    const distance = 20 + Math.random() * 10;
                    
                    const x1 = Math.cos(angle1) * distance + 'px';
                    const y1 = Math.sin(angle1) * distance + 'px';
                    const x2 = Math.cos(angle2) * distance + 'px';
                    const y2 = Math.sin(angle2) * distance + 'px';
                    
                    const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
                    const rotation = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI + 90;
                    
                    bond.style.width = length + 'px';
                    bond.style.height = '1px';
                    bond.style.left = '50%';
                    bond.style.top = '50%';
                    bond.style.transform = `translate(-50%, -50%) rotate(${rotation}deg)`;
                    bond.style.animationDelay = Math.random() * 3 + 's';
                    
                    molecule.appendChild(bond);
                }
                
                molecule.style.animation = `moleculeFloat ${Math.random() * 20 + 15}s ease-in-out infinite`;
                spaceField.appendChild(molecule);
            }
            
            // Créer des électrons
            for (let i = 0; i < 40; i++) {
                const electron = document.createElement('div');
                electron.className = 'electron';
                electron.style.left = Math.random() * 100 + '%';
                electron.style.top = Math.random() * 100 + '%';
                electron.style.animationDelay = Math.random() * 8 + 's';
                electron.style.animationDuration = (Math.random() * 6 + 4) + 's';
                electron.style.animation = `electronOrbit ${Math.random() * 3 + 2}s linear infinite`;
                spaceField.appendChild(electron);
            }
            
            // Créer des photons
            for (let i = 0; i < 30; i++) {
                const photon = document.createElement('div');
                photon.className = 'photon';
                photon.style.left = Math.random() * 100 + '%';
                photon.style.top = Math.random() * 100 + '%';
                photon.style.animationDelay = Math.random() * 12 + 's';
                photon.style.animation = `photonPulse ${Math.random() * 8 + 6}s ease-in-out infinite`;
                spaceField.appendChild(photon);
            }
            
            // Créer des quarks
            for (let i = 0; i < 20; i++) {
                const quark = document.createElement('div');
                quark.className = 'quark';
                quark.style.left = Math.random() * 100 + '%';
                quark.style.top = Math.random() * 100 + '%';
                quark.style.animationDelay = Math.random() * 15 + 's';
                quark.style.animation = `quarkSpin ${Math.random() * 10 + 8}s ease-in-out infinite`;
                spaceField.appendChild(quark);
            }
            
            // Créer des ondes quantiques
            for (let i = 0; i < 15; i++) {
                const wave = document.createElement('div');
                wave.className = 'quantum-wave';
                wave.style.left = Math.random() * 100 + '%';
                wave.style.top = Math.random() * 100 + '%';
                wave.style.animationDelay = Math.random() * 20 + 's';
                wave.style.animation = `waveRipple ${Math.random() * 8 + 5}s ease-in-out infinite`;
                spaceField.appendChild(wave);
            }
            
            // Animation du logo
            const logo = document.querySelector('.logo-img');
            if (logo) {
                logo.addEventListener('mouseenter', function() {
                    this.style.filter = 'drop-shadow(0 0 40px rgba(74, 222, 128, 0.6))';
                });
                
                logo.addEventListener('mouseleave', function() {
                    this.style.filter = 'drop-shadow(0 0 30px rgba(74, 222, 128, 0.3))';
                });
            }
            
            // Animation du formulaire
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
                
                // Validation quantique
                input.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        this.style.borderColor = 'rgba(74, 222, 128, 0.5)';
                        this.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
                    } else {
                        this.style.borderColor = 'rgba(74, 222, 128, 0.3)';
                        this.style.backgroundColor = 'rgba(0, 0, 0, 0.3)';
                    }
                });
            });
            
            // Animation du bouton submit
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const btn = this.querySelector('.submit-btn');
                    const originalText = btn.innerHTML;
                    
                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Authentification...';
                    btn.style.opacity = '0.7';
                    btn.disabled = true;
                    
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.style.opacity = '1';
                        btn.disabled = false;
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>
