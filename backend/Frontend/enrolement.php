<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../backend/config.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Le traitement est maintenant géré par backend/enrolement_traitement.php via AJAX

// Restauration des données du formulaire en cas d'erreur précédente
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']); // Nettoyer après utilisation
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolement - CIMIS</title>
    <link rel="stylesheet" href="../css/enrolement.css">
    <link rel="stylesheet" href="../css/enrolement-custom.css">
    <!-- Cropper.js pour le rognage des photos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- JavaScript d'enrôlement -->
    <script src="../js/enrolement.js"></script>
    <!-- Cropper.js pour le rognage -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
</head>
<body>

    <!-- Canvas Background -->
    <canvas id="particles-canvas"></canvas>

    <div class="app-container">

        <!-- TOP STATUS BAR -->
        <div class="top-status-bar">
            <div class="status-left">
                <span class="status-item warning-flash"><i class="fa-solid fa-triangle-exclamation"></i> SYSTÈME CLASSÉ SECRET DÉFENSE</span>
                <span class="status-item"><i class="fa-solid fa-globe"></i> RÉSEAU SÉCURISÉ</span>
                <span class="status-item"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="status-right">
                <span id="clock" class="text-mono">12:00:00</span>
                <a href="logout.php" class="btn-logout-styled">
                    <i class="fa-solid fa-power-off"></i> DÉCONNEXION
                </a>
            </div>
        </div>

        <!-- BOUTON RETOUR -->
        <div class="back-button-container">
            <a href="dashboard.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i>
                <span>RETOUR</span>
            </a>
        </div>

        <!-- HERO SECTION -->
        <div class="hero-section">
            <div class="hero-content">
                <img src="../img/cimis1.png" alt="CIMIS Logo" class="hero-logo">
                <div class="hero-text">
                    <h1>ENRÔLEMENT</h1>
                    <div class="hero-divider"></div>
                    <p style="font-size: 1.4rem; font-weight: 600; margin-top: 15px;">Enregistrement biométrique des personnels / Biometric Registration of Personnel</p>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div class="container">
                
                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-check-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="enrollmentForm">
                    <div class="hero-grid">

                        <!-- Matricule Généré -->
                        <div class="module-card text-center" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <h3 class="mb-2">Matricule CIMIS / CIMIS ID</h3>

                            <div class="matricule-display">
                                <div class="label">
                                    <i class="fa-solid fa-hashtag"></i> MATRICULE GÉNÉRÉ / GENERATED ID:
                                </div>
                                <div class="value" id="matricule-preview">
                                    Génération en cours... / Generation in progress...
                                </div>
                            </div>
                            
                            <!-- Photo Section -->
                            <div class="form-group" style="margin-top: 1.5rem;">
                                <label><i class="fa-solid fa-camera"></i> Photo d'identité / ID Photo</label>
                                
                                <!-- Boutons de capture -->
                                <div style="text-align: center; margin-bottom: 1rem;">
                                    <button type="button" id="start-camera-btn" class="btn" style="background: linear-gradient(45deg, #007bff, #0056b3); color: white; margin: 5px; padding: 10px 20px; font-size: 0.9rem; border: 1px solid #0056b3; border-radius: 5px;">
                                        <i class="fa-solid fa-video"></i> Démarrer Webcam / Start Webcam
                                    </button>
                                    <button type="button" id="capture-photo-btn" class="btn" style="background: linear-gradient(45deg, #28a745, #20c997); color: white; margin: 5px; padding: 10px 20px; font-size: 0.9rem; border: 1px solid #20c997; border-radius: 5px; display: none;">
                                        <i class="fa-solid fa-camera"></i> Capturer Photo / Capture Photo
                                    </button>
                                    <button type="button" id="stop-camera-btn" class="btn" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white; margin: 5px; padding: 10px 20px; font-size: 0.9rem; border: 1px solid #c82333; border-radius: 5px; display: none;">
                                        <i class="fa-solid fa-stop"></i> Arrêter Webcam / Stop Webcam
                                    </button>
                                    <button type="button" id="switch-camera-btn" class="btn" style="background: linear-gradient(45deg, #17a2b8, #138496); color: white; margin: 5px; padding: 10px 20px; font-size: 0.9rem; border: 1px solid #138496; border-radius: 5px; display: none;">
                                        <i class="fa-solid fa-sync-alt"></i> Inverser Caméra / Switch Camera
                                    </button>
                                </div>
                                
                                <div style="position: relative; margin-bottom: 1rem;">
                                    <!-- Zone de preview vidéo/photo -->
                                    <div id="photo-preview" style="width: 200px; height: 260px; border: 3px dashed var(--neon-green); background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; margin: 0 auto; position: relative; overflow: hidden; border-radius: 10px;">
                                        <!-- Video element caché pour webcam -->
                                        <video id="webcam-video" style="width: 100%; height: 100%; object-fit: cover; display: none;" autoplay></video>
                                        <!-- Canvas caché pour capture -->
                                        <canvas id="capture-canvas" style="display: none;"></canvas>
                                        <!-- Icône par défaut -->
                                        <div id="camera-icon" style="text-align: center;">
                                            <i class="fa-solid fa-camera" style="font-size: 3rem; color: var(--neon-green); opacity: 0.7;"></i>
                                            <span style="display: block; margin-top: 10px; font-size: 0.8rem; color: white;">PHOTO ID / ID PHOTO</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Input file caché pour compatibilité -->
                                <input type="file" id="photo-upload" name="photo" accept="image/jpeg,image/jpg,image/png" class="form-control" required style="display: none;">
                                <input type="hidden" id="photo-data" name="photo_data" value="">
                                
                                <!-- Bouton de rognage (affiché après capture/import) -->
                                <div id="crop-section" style="text-align: center; margin-top: 1rem; display: none;">
                                    <button type="button" id="crop-photo-btn" class="btn" style="background: linear-gradient(45deg, #ffc107, #e0a800); color: #000; padding: 8px 16px; font-size: 0.9rem; margin-right: 10px;">
                                        <i class="fa-solid fa-crop"></i> Rogner la Photo / Crop Photo
                                    </button>
                                    <button type="button" id="cancel-crop-btn" class="btn" style="background: linear-gradient(45deg, #6c757d, #5a6268); color: white; padding: 8px 16px; font-size: 0.9rem;">
                                        <i class="fa-solid fa-times"></i> Annuler / Cancel
                                    </button>
                                </div>
                                
                                <small style="color: white; font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Options: Webcam directe ou importation fichier (JPEG, PNG - Max 2MB) - Rognage disponible après capture / Options: Direct webcam or file import (JPEG, PNG - Max 2MB) - Cropping available after capture
                                </small>
                            </div>
                            
                            <!-- Modal de rognage -->
                            <div id="crop-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; justify-content: center; align-items: center;">
                                <div style="background: #1a1a1a; border: 2px solid var(--neon-green); border-radius: 10px; padding: 20px; max-width: 90%; max-height: 90%; overflow: auto;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <h3 style="color: white; margin: 0;"><i class="fa-solid fa-crop"></i> Rogner la Photo d'Identité / Crop ID Photo</h3>
                                        <button type="button" id="close-crop-modal" style="background: none; border: none; color: #ff4444; font-size: 1.5rem; cursor: pointer;">
                                            <i class="fa-solid fa-times"></i>
                                        </button>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <p style="color: #ccc; font-size: 0.9rem; margin: 0 0 10px 0;">Ajustez le cadre pour sélectionner la zone de la photo à conserver. Format recommandé : 3x4 (portrait). / Adjust the frame to select the area of the photo to keep. Recommended format: 3x4 (portrait).</p>
                                    </div>
                                    
                                    <div style="max-width: 100%; max-height: 400px; margin-bottom: 15px; background: #000; border: 1px solid var(--neon-green);">
                                        <img id="crop-image" style="max-width: 100%; display: block;">
                                    </div>
                                    
                                    <div style="display: flex; justify-content: center; gap: 10px;">
                                        <button type="button" id="apply-crop-btn" class="btn" style="background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 10px 20px;">
                                            <i class="fa-solid fa-check"></i> Appliquer / Apply
                                        </button>
                                        <button type="button" id="reset-crop-btn" class="btn" style="background: linear-gradient(45deg, #ffc107, #e0a800); color: #000; padding: 10px 20px;">
                                            <i class="fa-solid fa-undo"></i> Réinitialiser / Reset
                                        </button>
                                        <button type="button" id="cancel-crop-modal-btn" class="btn" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white; padding: 10px 20px;">
                                            <i class="fa-solid fa-times"></i> Annuler / Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fa-solid fa-id-card"></i> Matricule/Service Number</label>
                                <input type="text" name="matricule_militaire" id="matricule_militaire" class="form-control" placeholder="EX: 23456 (Gendarmerie Nationale), T17/23456, T2017/23456 (Terre/Air/Marine)" 
                                       value="<?php echo htmlspecialchars($form_data['matricule_militaire'] ?? ''); ?>">
                                <small style="color: white; font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Gendarmerie Nationale: 4-6 chiffres (unique) | Terre/Air/Marine: Lettre + année(2-4 chiffres)/numéro(4-6 chiffres) / Gendarmerie: 4-6 digits (unique) | Army/Air/Navy: Letter + year(2-4 digits)/number(4-6 digits)
                                </small>
                            </div>
                            
                            <div class="matricule-display">
                                <div class="label">
                                    <i class="fa-solid fa-qrcode"></i> CODE QR GÉNÉRÉ / QR CODE GENERATED:
                                </div>
                                <div class="value" id="qr-preview">
                                    Généré après enrôlement / Generated after enrollment
                                </div>
                            </div>
                        </div>

                        <!-- Zone Infos -->
                        <div class="module-card">
                            <h2>Données Personnelles / Personal Data</h2>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label><i class="fa-solid fa-user"></i> Nom / Name</label>
                                    <input type="text" name="nom" id="nom" class="form-control" placeholder="EX: DUPONT" required 
                                           value="<?php echo htmlspecialchars($form_data['nom'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fa-solid fa-user"></i> Prénom / First Name</label>
                                    <input type="text" name="prenom" id="prenom" class="form-control" placeholder="EX: JEAN MARC" required
                                           value="<?php echo htmlspecialchars($form_data['prenom'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label><i class="fa-solid fa-calendar-alt"></i> Date de naissance / Birth Date</label>
                                    <input type="date" name="date_naissance" id="date_naissance" class="form-control" required 
                                           max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                           title="Le candidat doit avoir au moins 18 ans"
                                           value="<?php echo htmlspecialchars($form_data['date_naissance'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fa-solid fa-map-marker-alt"></i> Lieu de naissance / Birth Place</label>
                                    <input type="text" name="lieu_naissance" id="lieu_naissance" class="form-control" placeholder="EX: YAOUNDE, DOUALA, etc." 
                                           value="<?php echo htmlspecialchars($form_data['lieu_naissance'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label><i class="fa-solid fa-venus-mars"></i> Sexe / Gender</label>
                                    <select name="sexe" id="sexe" class="form-control" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="MASCULIN" <?php echo ($form_data['sexe'] ?? '') === 'MASCULIN' ? 'selected' : ''; ?>>MASCULIN</option>
                                        <option value="FEMININ" <?php echo ($form_data['sexe'] ?? '') === 'FEMININ' ? 'selected' : ''; ?>>FEMININ</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label><i class="fa-solid fa-id-card"></i> Numéro CNI / ID Card Number</label>
                                    <input type="text" name="numero_cni" id="numero_cni" class="form-control" placeholder="EX: 12345678901234567890 ou ABC123456789012345678" 
                                           required pattern="[A-Z0-9]{9,20}" maxlength="20"
                                           title="Le numéro CNI doit contenir entre 9 et 20 caractères (chiffres et/ou lettres majuscules) / ID card must contain 9-20 characters (digits and/or uppercase letters)"
                                           value="<?php echo htmlspecialchars($form_data['numero_cni'] ?? ''); ?>">
                                    <small style="color: white; font-size: 0.8rem; margin-top: 0.25rem; display: block;">
                                        <i class="fa-solid fa-info-circle"></i> 
                                        Must contain 9-20 characters (digits and/or uppercase letters, unique) / Doit contenir entre 9 et 20 caractères (chiffres et/ou lettres majuscules, unique)
                                    </small>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label><i class="fa-solid fa-ruler-vertical"></i> Taille (cm) / Height (cm)</label>
                                    <input type="number" name="taille" id="taille" class="form-control" placeholder="EX: 175" 
                                           min="140" max="220" step="1"
                                           title="La taille doit être comprise entre 140cm et 220cm / Height must be between 140cm and 220cm"
                                           value="<?php echo htmlspecialchars($form_data['taille'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fa-solid fa-weight"></i> Poids (kg) / Weight (kg)</label>
                                    <input type="number" name="poids" id="poids" class="form-control" placeholder="EX: 70" 
                                           min="45" max="150" step="1"
                                           title="Le poids doit être compris entre 45kg et 150kg / Weight must be between 45kg and 150kg"
                                           value="<?php echo htmlspecialchars($form_data['poids'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label><i class="fa-solid fa-tint"></i> Groupe sanguin / Blood Group</label>
                                    <select name="groupe_sanguin" class="form-control">
                                        <option value="">Sélectionner... / Select...</option>
                                        <option value="A+" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="fa-solid fa-calendar-alt"></i> Date de la dernière promotion au grade / Last Grade Promotion Date</label>
                                    <input type="date" name="annee_dernier_galon" class="form-control" 
                                           max="<?php echo date('Y-m-d'); ?>" title="Date de la dernière promotion au grade"
                                           value="<?php echo htmlspecialchars($form_data['annee_dernier_galon'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="margin-top: 1.5rem; border-top: 2px solid rgba(74, 222, 128, 0.3); padding-top: 1.5rem;">
                                <h2 class="mb-2" style="color: var(--neon-gold);"><i class="fa-solid fa-building"></i> AFFECTATION / ASSIGNMENT</h2>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label><i class="fa-solid fa-building"></i> Corps / Branch</label>
                                        <select name="unite" id="unite" class="form-control" required onchange="updateCivilCategory()">
                                            <option value="">Sélectionner... / Select...</option>
                                            <option value="GENDARMERIE NATIONALE">GENDARMERIE NATIONALE</option>
                                            <option value="ARMÉE DE TERRE">ARMÉE DE TERRE</option>
                                            <option value="ARMÉE DE L'AIR">ARMÉE DE L'AIR</option>
                                            <option value="MARINE NATIONALE">MARINE NATIONALE</option>
                                            <option value="CIVIL">PERSONNEL CIVIL</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="categorie_civil_group" style="display: none;">
                                        <label><i class="fa-solid fa-briefcase"></i> Catégorie / Category</label>
                                        <select name="categorie_civil" id="categorie_civil" class="form-control" onchange="updateGrades()">
                                            <option value="">Sélectionner une catégorie...</option>
                                            <option value="FONCTIONNAIRE">Fonctionnaire</option>
                                            <option value="CADRE_CONTRACTUEL">Cadre contractuel(le) d'administration</option>
                                            <option value="AGENT_CONTRACTUEL">Agent contractuel(le) d'administration</option>
                                            <option value="AGENT_DECISION">Agent décisionnaire</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label><i class="fa-solid fa-medal"></i> Grade / Rank</label>
                                        <select name="grade" id="grade" class="form-control">
                                            <option value="">Sélectionner d'abord l'unité... / Select unit first...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4" style="text-align: right; margin-top: 2rem;">
                                <button type="submit" class="btn" style="background: linear-gradient(45deg, var(--neon-green), #00cc00); color: black; font-weight: bold; padding: 12px 30px;">
                                    <i class="fa-solid fa-user-plus"></i> VALIDATE ENROLLMENT / VALIDER L'ENROLEMENT
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            <div class="footer-right">
                <span id="footer-clock" class="text-mono">00:00:00</span>
                <span><i class="fa-solid fa-server"></i> Server: ACTIVE / Serveur: ACTIF</span>
            </div>
        </footer>

    </div>

    <!-- MODAL CANDIDAT ENROLLÉ -->
    <div id="candidatModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-check-circle" style="color: var(--success-color);"></i> CANDIDAT ENROLLÉ AVEC SUCCÈS</h2>
                <span class="close" onclick="closeCandidatModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="candidat-info-grid">
                    <!-- Photo Section -->
                    <div class="candidat-photo-section">
                        <img id="modal-photo" src="" alt="Photo du personnel" style="width: 150px; height: 180px; object-fit: cover; border: 3px solid var(--neon-green); border-radius: 10px; display: none;">
                        <div class="matricule-badge">
                            <i class="fa-solid fa-hashtag"></i>
                            <span id="modal-matricule">CIM-XXXXX</span>
                        </div>
                        <div id="modal-qr" style="margin-top: 10px; text-align: center;">
                            <!-- QR code sera inséré ici -->
                        </div>
                    </div>
                    
                    <!-- Info Section -->
                    <div class="candidat-details">
                        <div class="info-row">
                            <div class="info-item">
                                <label><i class="fa-solid fa-user"></i> Nom:</label>
                                <span id="modal-nom">-</span>
                            </div>
                            <div class="info-item">
                                <label><i class="fa-solid fa-user"></i> Prénom:</label>
                                <span id="modal-prenom">-</span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-item">
                                <label><i class="fa-solid fa-calendar"></i> Date de Naissance:</label>
                                <span id="modal-date-naissance">-</span>
                            </div>
                            <div class="info-item">
                                <label><i class="fa-solid fa-venus-mars"></i> Sexe:</label>
                                <span id="modal-sexe">-</span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-item">
                                <label><i class="fa-solid fa-shield"></i> Corps d'appartenance:</label>
                                <span id="modal-unite">-</span>
                            </div>
                            <div class="info-item">
                                <label><i class="fa-solid fa-medal"></i> Grade/Qualité:</label>
                                <span id="modal-grade">-</span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-item">
                                <label><i class="fa-solid fa-id-card"></i> Numéro CNI:</label>
                                <span id="modal-cni">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button onclick="window.location.href='./visualiser_carte.php?matricule=' + document.getElementById('modal-matricule').textContent;" class="btn">
                        <i class="fa-solid fa-eye"></i> VOIR LA CARTE
                    </button>
                    <button onclick="redirectToImpression()" class="btn btn-logout">
                        <i class="fa-solid fa-plus"></i> AJOUTER UN AUTRE CANDIDAT
                    </button>
            </div>
        </div>
    </div>
    
    <style>
        .hero-logo {
            width: 240px; /* Augmenté de 120px à 240px (X2) */
            height: 240px; /* Augmenté de 120px à 240px (X2) */
            margin-bottom: 1rem;
        }
        
        /* Bouton retour */
        .back-button-container {
            position: fixed;
            top: 80px;
            left: 20px;
            z-index: 1000;
        }
        
        .btn-back {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(0, 255, 0, 0.1);
            border: 2px solid white;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }
        
        .btn-back:hover {
            background: rgba(0, 255, 0, 0.2);
            border-color: #00ff00;
            color: #00ff00;
            transform: translateX(-5px);
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.5);
        }
        
        .btn-back i {
            font-size: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .back-button-container {
                top: 70px;
                left: 10px;
            }
            
            .btn-back {
                padding: 10px 16px;
                font-size: 0.8rem;
            }
            
            .btn-back span {
                display: none; /* Cacher le texte sur mobile */
            }
            
            .btn-back i {
                font-size: 1.2rem;
            }
        }
    </style>
    
    <script>
        // Gestion de la webcam
        let stream = null;
        let photoCaptured = false;
        
        const startCameraBtn = document.getElementById('start-camera-btn');
        const capturePhotoBtn = document.getElementById('capture-photo-btn');
        const stopCameraBtn = document.getElementById('stop-camera-btn');
        const switchCameraBtn = document.getElementById('switch-camera-btn');
        const video = document.getElementById('webcam-video');
        const canvas = document.getElementById('capture-canvas');
        const photoPreview = document.getElementById('photo-preview');
        const cameraIcon = document.getElementById('camera-icon');
        const photoDataInput = document.getElementById('photo-data');
        const photoUpload = document.getElementById('photo-upload');
        
        // Éléments de rognage
        const cropSection = document.getElementById('crop-section');
        const cropPhotoBtn = document.getElementById('crop-photo-btn');
        const cancelCropBtn = document.getElementById('cancel-crop-btn');
        const cropModal = document.getElementById('crop-modal');
        const cropImage = document.getElementById('crop-image');
        const applyCropBtn = document.getElementById('apply-crop-btn');
        const resetCropBtn = document.getElementById('reset-crop-btn');
        const closeCropModal = document.getElementById('close-crop-modal');
        const cancelCropModalBtn = document.getElementById('cancel-crop-modal-btn');
        
        // Variables pour le rognage
        let cropper = null;
        let originalImageData = null;
        
        // Détection mobile/tablette
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;
        let currentFacingMode = 'user'; // 'user' = caméra avant, 'environment' = caméra arrière
        let hasMultipleCameras = false;
        
        // Démarrer la webcam
        startCameraBtn.addEventListener('click', async function() {
            try {
                // Configuration optimisée pour mobile/tablette
                const videoConstraints = {
                    video: {
                        width: { ideal: window.innerWidth < 768 ? 320 : 640 },
                        height: { ideal: window.innerWidth < 768 ? 240 : 480 },
                        facingMode: 'user',
                        // Options mobiles
                        facingMode: { exact: 'user' }, // Caméra avant par défaut
                        // Qualité adaptée mobile
                        aspectRatio: window.innerWidth < 768 ? 4/3 : 16/9
                    }
                };
                
                // Fallback si facingMode exact n'est pas supporté
                try {
                    stream = await navigator.mediaDevices.getUserMedia(videoConstraints);
                } catch (fallbackError) {
                    // Essayer sans facingMode exact
                    delete videoConstraints.video.facingMode;
                    stream = await navigator.mediaDevices.getUserMedia(videoConstraints);
                }
                
                video.srcObject = stream;
                video.style.display = 'block';
                cameraIcon.style.display = 'none';
                
                startCameraBtn.style.display = 'none';
                capturePhotoBtn.style.display = 'inline-block';
                stopCameraBtn.style.display = 'inline-block';
                
                // Vérifier si plusieurs caméras sont disponibles (mobile)
                if (isMobile) {
                    try {
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        const videoDevices = devices.filter(device => device.kind === 'videoinput');
                        hasMultipleCameras = videoDevices.length > 1;
                        
                        if (hasMultipleCameras) {
                            switchCameraBtn.style.display = 'inline-block';
                        }
                    } catch (e) {
                        console.log('Impossible de détecter les caméras:', e);
                    }
                }
                
                photoCaptured = false;
                
            } catch (error) {
                console.error('Erreur webcam:', error);
                alert('Impossible d\'accéder à la webcam. Vérifiez que vous avez bien autorisé l\'accès et qu\'aucune autre application n\'utilise la webcam.');
            }
        });
        
        // Capturer la photo
        capturePhotoBtn.addEventListener('click', function() {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0);
            
            // Convertir en base64
            const imageData = canvas.toDataURL('image/jpeg', 0.9);
            photoDataInput.value = imageData;
            
            // Afficher la photo capturée
            const img = document.createElement('img');
            img.src = imageData;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            
            photoPreview.innerHTML = '';
            photoPreview.appendChild(img);
            
            // Arrêter la webcam après capture
            stopWebcam();
            
            photoCaptured = true;
            
            // Mettre à jour l'affichage des boutons
            startCameraBtn.style.display = 'inline-block';
            capturePhotoBtn.style.display = 'none';
            stopCameraBtn.style.display = 'none';
            switchCameraBtn.style.display = 'none';
            
            // Masquer l'input file car on a une photo
            photoUpload.required = false;
            photoUpload.style.display = 'none';
            
            // Afficher les boutons de rognage
            cropSection.style.display = 'block';
            originalImageData = imageData;
        });
        
        // Arrêter la webcam
        stopCameraBtn.addEventListener('click', function() {
            stopWebcam();
            
            startCameraBtn.style.display = 'inline-block';
            capturePhotoBtn.style.display = 'none';
            stopCameraBtn.style.display = 'none';
        });
        
        function stopWebcam() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            
            video.style.display = 'none';
            
            if (!photoCaptured) {
                photoPreview.innerHTML = '';
                photoPreview.appendChild(cameraIcon);
                cameraIcon.style.display = 'block';
            }
        }
        
        // Option: Ajouter un bouton pour importer un fichier si webcam non disponible
        const importFileBtn = document.createElement('button');
        importFileBtn.type = 'button';
        importFileBtn.className = 'btn';
        importFileBtn.style.cssText = 'background: linear-gradient(45deg, #6c757d, #5a6268); color: white; padding: 8px 16px; font-size: 0.9rem; margin-left: 10px;';
        importFileBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Importer Fichier / Import File';
        
        importFileBtn.addEventListener('click', function() {
            photoUpload.click();
        });
        
        // Ajouter le bouton d'import à côté des boutons webcam
        startCameraBtn.parentNode.appendChild(importFileBtn);
        
        // Gérer l'import de fichier
        photoUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageData = e.target.result;
                    photoDataInput.value = imageData;
                    
                    const img = document.createElement('img');
                    img.src = imageData;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    
                    photoPreview.innerHTML = '';
                    photoPreview.appendChild(img);
                    
                    photoCaptured = true;
                    photoUpload.required = false;
                    
                    // Afficher les boutons de rognage
                    cropSection.style.display = 'block';
                    originalImageData = imageData;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Basculer entre caméra avant et arrière (mobile)
        switchCameraBtn.addEventListener('click', async function() {
            if (stream) {
                stopWebcam();
                
                // Inverser le mode de caméra
                currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
                
                try {
                    const videoConstraints = {
                        video: {
                            width: { ideal: window.innerWidth < 768 ? 320 : 640 },
                            height: { ideal: window.innerWidth < 768 ? 240 : 480 },
                            facingMode: currentFacingMode,
                            aspectRatio: window.innerWidth < 768 ? 4/3 : 16/9
                        }
                    };
                    
                    stream = await navigator.mediaDevices.getUserMedia(videoConstraints);
                    video.srcObject = stream;
                    video.style.display = 'block';
                    cameraIcon.style.display = 'none';
                    
                    // Mettre à jour le texte du bouton
                    switchCameraBtn.innerHTML = currentFacingMode === 'user' ? 
                        '<i class="fa-solid fa-sync-alt"></i> Caméra Arrière' : 
                        '<i class="fa-solid fa-sync-alt"></i> Caméra Avant';
                        
                } catch (error) {
                    console.error('Erreur changement caméra:', error);
                    alert('Impossible de basculer vers cette caméra.');
                    
                    // Revenir à la caméra précédente
                    currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
                    startCameraBtn.click(); // Redémarrer avec la caméra précédente
                }
            }
        });
        
        // Gestion du rognage des photos
        cropPhotoBtn.addEventListener('click', function() {
            if (originalImageData) {
                // Configurer l'image pour le rognage
                cropImage.src = originalImageData;
                cropModal.style.display = 'flex';
                
                // Initialiser Cropper.js
                setTimeout(() => {
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(cropImage, {
                        aspectRatio: 3/4, // Format portrait 3x4 recommandé pour photos d'identité
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.8,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: true,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: true,
                        movable: true,
                        scalable: true,
                        zoomable: true,
                        zoomOnTouch: true,
                        zoomOnWheel: true,
                        minContainerWidth: 200,
                        minContainerHeight: 300,
                        background: true,
                        modal: true
                    });
                }, 100);
            }
        });
        
        // Appliquer le rognage
        applyCropBtn.addEventListener('click', function() {
            if (cropper) {
                // Obtenir le canvas rogné
                const canvas = cropper.getCroppedCanvas({
                    width: 400,
                    height: 533,
                    minWidth: 200,
                    minHeight: 267,
                    maxWidth: 800,
                    maxHeight: 1067,
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });
                
                if (canvas) {
                    // Convertir en base64
                    const croppedImageData = canvas.toDataURL('image/jpeg', 0.9);
                    photoDataInput.value = croppedImageData;
                    
                    // Mettre à jour le preview
                    const img = document.createElement('img');
                    img.src = croppedImageData;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'cover';
                    
                    photoPreview.innerHTML = '';
                    photoPreview.appendChild(img);
                    
                    // Mettre à jour l'image originale
                    originalImageData = croppedImageData;
                    
                    // Fermer le modal
                    closeCropModalFunc();
                }
            }
        });
        
        // Réinitialiser le rognage
        resetCropBtn.addEventListener('click', function() {
            if (cropper) {
                cropper.reset();
            }
        });
        
        // Annuler le rognage
        cancelCropBtn.addEventListener('click', function() {
            // Revenir à l'image originale
            if (originalImageData) {
                const img = document.createElement('img');
                img.src = originalImageData;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';
                
                photoPreview.innerHTML = '';
                photoPreview.appendChild(img);
                
                photoDataInput.value = originalImageData;
            }
            
            cropSection.style.display = 'none';
        });
        
        // Fermer le modal de rognage
        function closeCropModalFunc() {
            cropModal.style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }
        
        closeCropModal.addEventListener('click', closeCropModalFunc);
        cancelCropModalBtn.addEventListener('click', closeCropModalFunc);
        
        // Fermer avec la touche Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && cropModal.style.display === 'flex') {
                closeCropModalFunc();
            }
        });
        
        // Nettoyer la webcam à la fermeture de la page
        window.addEventListener('beforeunload', function() {
            stopWebcam();
            if (cropper) {
                cropper.destroy();
            }
        });
    </script>
</body>
</html>

