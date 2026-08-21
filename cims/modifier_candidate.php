<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/config.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Récupérer l'ID du candidat
$id = $_GET['id'] ?? '';
if (empty($id) || !is_numeric($id)) {
    $_SESSION['error'] = "ID de candidat invalide";
    header('Location: impression.php');
    exit;
}

// Récupérer les informations du candidat
$stmt = $pdo->prepare("SELECT * FROM candidat WHERE id = :id");
$stmt->execute(['id' => $id]);
$candidat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidat) {
    $_SESSION['error'] = "Candidat non trouvé";
    header('Location: impression.php');
    exit;
}

// Traitement AJAX pour la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_submit'])) {
    try {
        // Validation des données
        $required_fields = ['nom', 'prenom', 'date_naissance', 'sexe', 'numero_cni'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Le champ $field est obligatoire");
            }
        }

        // Validation du matricule militaire selon le corps d'armée
        $matricule_militaire = $_POST['matricule_militaire'] ?? '';
        $unite = $_POST['unite'] ?? '';
        
        if (!empty($matricule_militaire)) {
            $formats_autorises = [
                'ARMÉE DE TERRE' => '/^T\d{2,4}\/\d{4,6}$/',
                'ARMÉE DE L\'AIR' => '/^A\d{2,4}\/\d{4,6}$/',
                'MARINE NATIONALE' => '/^M\d{2,4}\/\d{4,6}$/',
                'GENDARMERIE' => '/^\d{4,6}$/'
            ];
            
            if (isset($formats_autorises[$unite]) && !preg_match($formats_autorises[$unite], $matricule_militaire)) {
                throw new Exception("Format du matricule militaire invalide pour $unite");
            }
        }

        // Validation du format du numéro CNI (9 à 20 caractères: lettres majuscules et chiffres)
        $numero_cni = preg_replace('/[^A-Z0-9]/', '', strtoupper($_POST['numero_cni']));
        if (strlen($numero_cni) < 9 || strlen($numero_cni) > 20) {
            throw new Exception("Le numéro CNI doit contenir entre 9 et 20 caractères (lettres majuscules et chiffres)");
        }

        // Vérification de l'unicité du numéro CNI (exclure le candidat actuel)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE numero_cni = :numero_cni AND id != :id");
        $stmt->execute(['numero_cni' => $numero_cni, 'id' => $id]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            throw new Exception("Ce numéro CNI est déjà utilisé par un autre candidat");
        }

        // Traitement de la photo si une nouvelle est uploadée
        $photo_path = $candidat['photo']; // Garder l'ancienne photo par défaut
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $_FILES['photo'];
            
            // Validation du type de fichier
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $photo['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception("Le format de la photo n'est pas valide (JPG ou PNG requis)");
            }
            
            // Validation de la taille (max 2MB)
            if ($photo['size'] > 2 * 1024 * 1024) {
                throw new Exception("La photo ne doit pas dépasser 2MB");
            }
            
            // Validation des dimensions minimales
            $image_info = getimagesize($photo['tmp_name']);
            if (!$image_info || $image_info[0] < 200 || $image_info[1] < 200) {
                throw new Exception("La photo doit faire au minimum 200x200 pixels");
            }
            
            // Création du répertoire img/candidats si nécessaire
            $upload_dir = 'img/candidats/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Génération du nom de fichier unique
            $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg';
            }
            $filename = $candidat['matricule'] . '_' . time() . '.' . $extension;
            $photo_path = $upload_dir . $filename;
            
            // Déplacement du fichier
            if (!move_uploaded_file($photo['tmp_name'], $photo_path)) {
                throw new Exception("Erreur lors du téléchargement de la photo");
            }
            
            // Supprimer l'ancienne photo si elle existe
            if (!empty($candidat['photo']) && file_exists($candidat['photo'])) {
                unlink($candidat['photo']);
            }
        }

        // Préparation des données pour la mise à jour
        $data = [
            'nom' => strtoupper(trim($_POST['nom'])),
            'prenom' => strtoupper(trim($_POST['prenom'])),
            'date_naissance' => $_POST['date_naissance'],
            'sexe' => $_POST['sexe'],
            'numero_cni' => $numero_cni,
            'taille' => $_POST['taille'] ?? '',
            'poids' => $_POST['poids'] ?? '',
            'groupe_sanguin' => $_POST['groupe_sanguin'] ?? '',
            'matricule_militaire' => $matricule_militaire,
            'unite' => $unite,
            'grade' => $_POST['grade'] ?? '',
            'annee_dernier_galon' => $_POST['annee_dernier_galon'] ?? null,
            'suspendus' => $_POST['suspendus'] ?? 0,
            'photo' => $photo_path,
            'id' => $id
        ];

        // Mise à jour dans la base de données
        $sql = "UPDATE candidat SET 
            nom = :nom, 
            prenom = :prenom, 
            date_naissance = :date_naissance, 
            sexe = :sexe, 
            numero_cni = :numero_cni, 
            taille = :taille, 
            poids = :poids, 
            groupe_sanguin = :groupe_sanguin, 
            matricule_militaire = :matricule_militaire, 
            unite = :unite, 
            grade = :grade, 
            annee_dernier_galon = :annee_dernier_galon, 
            suspendus = :suspendus,
            photo = :photo 
            WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Candidat modifié avec succès !',
            'redirect' => 'impression.php'
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Utiliser les données du candidat ou les données soumises en cas d'erreur
$form_data = $form_data ?? $candidat;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Candidat - CIMIS</title>
    <link rel="stylesheet" href="css/enrolement.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <style>
        .btn-warning {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
        }
        .btn-warning:hover {
            background: linear-gradient(45deg, #e67e22, #d35400);
            transform: translateY(-2px);
        }
        .photo-section {
            border: 2px dashed var(--neon-green);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(0,0,0,0.3);
        }
        .current-photo {
            width: 150px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 3px solid var(--neon-green);
        }
        .form-readonly {
            background: rgba(255,255,255,0.1);
            color: #888;
            cursor: not-allowed;
        }
    </style>
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
            </div>
        </div>

        <!-- BOUTON RETOUR -->
        <div style="padding: 20px; text-align: left;">
            <a href="impression.php" class="btn-back-hero" style="font-size: 16px; padding: 12px 24px; background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; border: none; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(238, 90, 36, 0.3); transition: all 0.3s ease;">
                <i class="fa-solid fa-arrow-left"></i> RETOUR À LA LISTE
            </a>
        </div>

        <!-- HERO SECTION -->
        <div class="hero-section">
            <div class="hero-content">
                <img src="img/cimis1.png" alt="CIMIS Logo" class="hero-logo">
                <div class="hero-text">
                    <h1>MODIFIER CANDIDAT</h1>
                    <div class="hero-divider"></div>
                    <h2>Centre d'Identification Militaire Intégré Système</h2>
                    <p>Modification des informations du candidat: <strong><?php echo htmlspecialchars($candidat['matricule']); ?></strong></p>
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

                <!-- FORMULAIRE DE MODIFICATION -->
                <div class="module-card">
                    <form id="modifierForm" method="POST" enctype="multipart/form-data">
                        
                        <!-- Photo actuelle -->
                        <div class="photo-section">
                            <h4><i class="fa-solid fa-camera"></i> PHOTO DU CANDIDAT</h4>
                            <div style="display: flex; align-items: center; gap: 2rem;">
                                <div>
                                    <p class="form-label">Photo actuelle:</p>
                                    <?php 
                                    $photo_path = '';
                                    if (!empty($form_data['photo'])) {
                                        // Gestion du chemin de la photo
                                        if (file_exists($form_data['photo'])) {
                                            $photo_path = $form_data['photo'];
                                        } elseif (file_exists('img/candidats/' . basename($form_data['photo']))) {
                                            $photo_path = 'img/candidats/' . basename($form_data['photo']);
                                        } else {
                                            $filename = basename($form_data['photo']);
                                            $photo_path = 'img/candidats/' . $filename;
                                        }
                                    }
                                    
                                    if (!empty($photo_path) && file_exists($photo_path)): 
                                    ?>
                                        <img src="<?php echo $photo_path; ?>" class="current-photo" alt="Photo actuelle">
                                    <?php else: ?>
                                        <div style="width: 150px; height: 200px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; border-radius: 10px; border: 2px dashed #666;">
                                            <i class="fa-solid fa-user" style="font-size: 3rem; color: #666;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1;">
                                    <p class="form-label">Nouvelle photo (optionnel):</p>
                                    <input type="file" name="photo" id="photo-upload" accept="image/*" class="form-control">
                                    <small style="color: var(--neon-green); font-size: 0.8rem;">
                                        <i class="fa-solid fa-info-circle"></i> 
                                        Laissez vide pour conserver la photo actuelle. Format: JPG, PNG max 2MB
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Informations de base -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-hashtag"></i> Matricule CIM</label>
                                <input type="text" class="form-control form-readonly" value="<?php echo htmlspecialchars($candidat['matricule']); ?>" readonly>
                                <small><i class="fa-solid fa-lock"></i> Le matricule CIM ne peut pas être modifié</small>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-id-card"></i> Matricule Militaire</label>
                                <input type="text" name="matricule_militaire" id="matricule_militaire" class="form-control" placeholder="EX: T17/23456, A17/23456, 23456" 
                                       value="<?php echo htmlspecialchars($form_data['matricule_militaire'] ?? ''); ?>">
                                <small style="color: var(--neon-green); font-size: 0.8rem;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Terre/Air/Marine: Lettre + année(2-4 chiffres)/numéro(4-6 chiffres) | Gendarmerie: 4-6 chiffres
                                </small>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-user"></i> Nom</label>
                                <input type="text" name="nom" id="nom" class="form-control" placeholder="EX: DUPONT" required 
                                       value="<?php echo htmlspecialchars($form_data['nom'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-user"></i> Prénom(s)</label>
                                <input type="text" name="prenom" id="prenom" class="form-control" placeholder="EX: JEAN MARC" required
                                       value="<?php echo htmlspecialchars($form_data['prenom'] ?? ''); ?>">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-calendar"></i> Date de Naissance</label>
                                <input type="date" name="date_naissance" id="date_naissance" class="form-control" required 
                                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                       title="Le candidat doit avoir au moins 18 ans"
                                       value="<?php echo htmlspecialchars($form_data['date_naissance'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-venus-mars"></i> Sexe</label>
                                <select name="sexe" id="sexe" class="form-control" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="MASCULIN" <?php echo ($form_data['sexe'] ?? '') === 'MASCULIN' ? 'selected' : ''; ?>>MASCULIN</option>
                                    <option value="FEMININ" <?php echo ($form_data['sexe'] ?? '') === 'FEMININ' ? 'selected' : ''; ?>>FEMININ</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fa-solid fa-id-card"></i> Numéro CNI</label>
                            <input type="text" name="numero_cni" id="numero_cni" class="form-control" placeholder="EX: 12345678901234567890 ou ABC123456789012345678" 
                                   required pattern="[A-Z0-9]{9,20}" maxlength="20"
                                   title="Le numéro CNI doit contenir entre 9 et 20 caractères (chiffres et/ou lettres majuscules)"
                                   value="<?php echo htmlspecialchars($form_data['numero_cni'] ?? ''); ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-ruler-vertical"></i> Taille (cm)</label>
                                <input type="number" name="taille" id="taille" class="form-control" placeholder="EX: 175" 
                                       min="140" max="220" step="1"
                                       title="La taille doit être comprise entre 140cm et 220cm"
                                       value="<?php echo htmlspecialchars($form_data['taille'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-weight"></i> Poids (kg)</label>
                                <input type="number" name="poids" id="poids" class="form-control" placeholder="EX: 70" 
                                       min="45" max="150" step="1"
                                       title="Le poids doit être compris entre 45kg et 150kg"
                                       value="<?php echo htmlspecialchars($form_data['poids'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-tint"></i> Groupe Sanguin</label>
                                <select name="groupe_sanguin" id="groupe_sanguin" class="form-control">
                                    <option value="">Sélectionner...</option>
                                    <option value="A+" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                                    <option value="O+" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                                    <option value="AB+" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo ($form_data['groupe_sanguin'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-shield-alt"></i> Unité</label>
                                <select name="unite" id="unite" class="form-control" required onchange="updateGrades()">
                                    <option value="">Sélectionner...</option>
                                    <option value="ARMÉE DE TERRE" <?php echo ($form_data['unite'] ?? '') === 'ARMÉE DE TERRE' ? 'selected' : ''; ?>>ARMÉE DE TERRE</option>
                                    <option value="MARINE NATIONALE" <?php echo ($form_data['unite'] ?? '') === 'MARINE NATIONALE' ? 'selected' : ''; ?>>MARINE NATIONALE</option>
                                    <option value="ARMÉE DE L'AIR" <?php echo ($form_data['unite'] ?? '') === 'ARMÉE DE L\'AIR' ? 'selected' : ''; ?>>ARMÉE DE L'AIR</option>
                                    <option value="GENDARMERIE" <?php echo ($form_data['unite'] ?? '') === 'GENDARMERIE' ? 'selected' : ''; ?>>GENDARMERIE</option>
                                    <option value="CIVIL" <?php echo ($form_data['unite'] ?? '') === 'CIVIL' ? 'selected' : ''; ?>>CIVIL</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-medal"></i> Grade</label>
                                <select name="grade" id="grade" class="form-control" required>
                                    <option value="">Sélectionner d'abord l'unité...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-pause-circle"></i> Statut de suspension</label>
                                <select name="suspendus" id="suspendus" class="form-control">
                                    <option value="0" <?php echo ($form_data['suspendus'] ?? 0) == 0 ? 'selected' : ''; ?>>ACTIF</option>
                                    <option value="1" <?php echo ($form_data['suspendus'] ?? 0) == 1 ? 'selected' : ''; ?>>SUSPENDU</option>
                                </select>
                                <small style="color: var(--neon-green); font-size: 0.8rem;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Indique si le membre est actuellement sous suspension
                                </small>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-calendar"></i> Année dernier galon</label>
                                <input type="number" name="annee_dernier_galon" id="annee_dernier_galon" class="form-control" 
                                       placeholder="EX: 2023" min="1970" max="<?php echo date('Y'); ?>"
                                       value="<?php echo htmlspecialchars($form_data['annee_dernier_galon'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mt-4" style="text-align: right; margin-top: 2rem;">
                            <a href="impression.php" class="btn" style="margin-right: 1rem; background: linear-gradient(45deg, #f39c12, #e67e22); color: white;">
                                <i class="fa-solid fa-times"></i> ANNULER
                            </a>
                            <button type="submit" class="btn" style="background: linear-gradient(45deg, #27ae60, #2ecc71); color: white; font-weight: bold; padding: 12px 30px;">
                                <i class="fa-solid fa-save"></i> ENREGISTRER LES MODIFICATIONS
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <!-- FOOTER -->
        <footer class="security-footer">
            <div class="footer-left">
                <span><i class="fa-solid fa-shield-alt"></i> SYSTÈME CIMIS v2.0</span>
                <span><i class="fa-solid fa-lock"></i> Connexion sécurisée</span>
            </div>
            <div class="footer-right">
                <span id="footer-clock" class="text-mono">00:00:00</span>
                <span><i class="fa-solid fa-server"></i> Serveur: ACTIF</span>
            </div>
        </footer>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        // --- CLOCK ---
        setInterval(() => {
            const now = new Date();
            const clockElement = document.getElementById('clock');
            const footerClock = document.getElementById('footer-clock');
            if (clockElement) clockElement.innerText = now.toLocaleTimeString('fr-FR');
            if (footerClock) footerClock.innerText = now.toLocaleTimeString('fr-FR');
        }, 1000);

        // --- PARTICLE SYSTEM ---
        const canvas = document.getElementById('particles-canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        const particles = [];

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2;
                this.speedX = (Math.random() - 0.5) * 0.5;
                this.speedY = (Math.random() - 0.5) * 0.5;
                this.opacity = Math.random() * 0.5;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.opacity > 0) this.opacity -= 0.002;
                if (this.opacity <= 0) {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.opacity = Math.random() * 0.5;
                }
            }
            draw() {
                ctx.fillStyle = `rgba(10, 255, 186, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }
        
        for (let i = 0; i < 100; i++) particles.push(new Particle());

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animate);
        }
        animate();

        // Handle window resize
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });

        // --- GRADES PAR UNITÉ (MISE À JOUR SANS ABRÉVIATIONS) ---
        const gradesParUnite = {
            'ARMÉE DE TERRE': [
                // OFFICIERS GENERAUX (4)
                'GENERAL D ARMEE',
                'GENERAL DE CORPS D ARMEE',
                'GENERAL DE DIVISION',
                'GENERAL DE BRIGADE',
                
                // OFFICIERS SUPERIEURS (3)
                'COLONEL',
                'LIEUTENANT COLONEL',
                'CHEF DE BATAILLON',
                
                // OFFICIERS SUBALTERNES (4)
                'CAPITAINE',
                'LIEUTENANT',
                'SOUS LIEUTENANT',
                'ASPIRANT',
                
                // SOUS OFFICIERS SUPERIEURS (3)
                'ADJUDANT CHEF MAJOR',
                'ADJUDANT CHEF',
                'ADJUDANT',
                
                // SOUS OFFICIERS SUBALTERNES (2)
                'SERGENT CHEF',
                'SERGENT',
                
                // MILITAIRES DE RANG (5)
                'CAPORAL CHEF',
                'CAPORAL',
                'SOLDAT DE 1ERE CLASSE',
                'SOLDAT DE 2EME CLASSE',
                'ELEVE OFFICIER 2EME ANNEE'
            ],
            'MARINE NATIONALE': [
                // OFFICIERS GENERAUX (4)
                'AMIRAL',
                'VICE AMIRAL D ESCADRE',
                'VICE AMIRAL',
                'CONTRE AMIRAL',
                
                // OFFICIERS SUPERIEURS (3)
                'CAPITAINE DE VAISSEAU',
                'CAPITAINE DE FREGATE',
                'CAPITAINE DE CORVETTE',
                
                // OFFICIERS SUBALTERNES (4)
                'LIEUTENANT DE VAISSEAU',
                'ENSEIGNE DE VAISSEAU 1ERE CLASSE',
                'ENSEIGNE DE VAISSEAU 2EME CLASSE',
                'ASPIRANT',
                
                // SOUS OFFICIERS SUPERIEURS (3)
                'MAITRE PRINCIPAL MAJOR',
                'MAITRE PRINCIPAL',
                'PREMIER MAITRE',
                
                // SOUS OFFICIERS SUBALTERNES (2)
                'MAITRE',
                'SECOND MAITRE',
                
                // MILITAIRES DE RANG (5)
                'QUARTIER MAITRE DE 1ERE CLASSE',
                'QUARTIER MAITRE DE 2EME CLASSE',
                'MATELOT DE 1ERE CLASSE',
                'MATELOT DE 2EME CLASSE',
                'ASPIRANT'
            ],
            'ARMÉE DE L\'AIR': [
                // OFFICIERS GENERAUX (4)
                'GENERAL D ARMEE AERIENNE',
                'GENERAL DE CORPS AERIEN',
                'GENERAL DE DIVISION AERIENNE',
                'GENERAL DE BRIGADE AERIENNE',
                
                // OFFICIERS SUPERIEURS (3)
                'COLONEL',
                'LIEUTENANT COLONEL',
                'COMMANDANT',
                
                // OFFICIERS SUBALTERNES (4)
                'CAPITAINE',
                'LIEUTENANT',
                'SOUS LIEUTENANT',
                'ASPIRANT',
                
                // SOUS OFFICIERS SUPERIEURS (3)
                'ADJUDANT CHEF MAJOR',
                'ADJUDANT CHEF',
                'ADJUDANT',
                
                // SOUS OFFICIERS SUBALTERNES (2)
                'SERGENT CHEF',
                'SERGENT',
                
                // MILITAIRES DE RANG (5)
                'CAPORAL CHEF',
                'CAPORAL',
                'AVIATEUR DE 1ERE CLASSE',
                'AVIATEUR DE 2EME CLASSE',
                'ELEVE OFFICIER 1ERE ANNEE'
            ],
            'GENDARMERIE': [
                // OFFICIERS GENERAUX (1)
                'GENERAL DE GENDARMERIE',
                
                // OFFICIERS SUPERIEURS (3)
                'COLONEL',
                'LIEUTENANT COLONEL',
                'COMMANDANT',
                
                // OFFICIERS SUBALTERNES (4)
                'CAPITAINE',
                'LIEUTENANT',
                'SOUS LIEUTENANT',
                'ASPIRANT',
                
                // SOUS OFFICIERS SUPERIEURS (3)
                'ADJUDANT CHEF MAJOR',
                'ADJUDANT CHEF',
                'ADJUDANT',
                
                // SOUS OFFICIERS SUBALTERNES (2)
                'MARECHAL DES LOGIS CHEF',
                'MARECHAL DES LOGIS',
                
                // MILITAIRES DE RANG (5)
                'GENDARME MAJOR',
                'GENDARME',
                'GENDARME DE 1ERE CLASSE',
                'GENDARME DE 2EME CLASSE',
                'ELEVE GENDARME'
            ],
            'CIVIL': [
                'AGENT', 'AGENT PRINCIPAL',
                'CHEF DE SERVICE', 'DIRECTEUR ADJOINT',
                'DIRECTEUR', 'DIRECTEUR GENERAL',
                // Métiers populaires collaborant avec l'armée
                'ENSEIGNANT', 'AVOCAT', 'MÉDECIN',
                'ENSEIGNANT', 'AVOCAT', 'MÉDECIN',
                'INFIRMIER', 'INGÉNIEUR', 'MÉCANICIEN',
                'TECHNICIEN', 'INFORMATICIEN',
                'COMPTABLE', 'CHERCHEUR',
                'ENTREPRENEUR', 'ARTISAN', 'COMMERÇANT',
                'CHEF TRADITIONNEL',
                // Catégorie ouverte
                'AUTRE'
            ]
        };

        function updateGrades() {
            const unite = document.getElementById('unite').value;
            const gradeSelect = document.getElementById('grade');
            const currentGrade = '<?php echo $form_data['grade'] ?? ''; ?>';
            
            // Vider le select
            gradeSelect.innerHTML = '<option value="">Sélectionner...</option>';
            
            if (unite && gradesParUnite[unite]) {
                gradesParUnite[unite].forEach(grade => {
                    const option = document.createElement('option');
                    option.value = grade;
                    option.textContent = grade;
                    if (grade === currentGrade) {
                        option.selected = true;
                    }
                    gradeSelect.appendChild(option);
                });
            }
        }

        // Initialiser les grades au chargement
        updateGrades();

        // --- VALIDATION FORMULAIRE ---
        document.getElementById('modifierForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validation côté client avant envoi
            let isValid = true;
            const errors = [];
            
            // Validation du nom
            const nom = document.getElementById('nom').value.trim();
            if (nom.length < 2) {
                errors.push('Le nom doit contenir au moins 2 caractères');
                isValid = false;
            }
            
            // Validation du prénom
            const prenom = document.getElementById('prenom').value.trim();
            if (prenom.length < 2) {
                errors.push('Le prénom doit contenir au moins 2 caractères');
                isValid = false;
            }
            
            // Validation de la date de naissance
            const dateNaissance = document.getElementById('date_naissance').value;
            if (!dateNaissance) {
                errors.push('La date de naissance est obligatoire');
                isValid = false;
            } else {
                const birthDate = new Date(dateNaissance);
                const minDate = new Date();
                minDate.setFullYear(minDate.getFullYear() - 18);
                if (birthDate > minDate) {
                    errors.push('Le candidat doit avoir au moins 18 ans');
                    isValid = false;
                }
            }
            
            // Validation du sexe
            const sexe = document.getElementById('sexe').value;
            if (!sexe) {
                errors.push('Le sexe est obligatoire');
                isValid = false;
            }
            
            // Validation du numéro CNI
            const numeroCni = document.getElementById('numero_cni').value.trim();
            if (!numeroCni) {
                errors.push('Le numéro CNI est obligatoire');
                isValid = false;
            } else if (!/^[A-Z0-9]{9,20}$/.test(numeroCni.toUpperCase())) {
                errors.push('Le numéro CNI doit contenir entre 9 et 20 caractères (chiffres et/ou lettres majuscules)');
                isValid = false;
            }
            
            // Validation du matricule militaire selon le corps d'armée
            const matriculeMilitaire = document.getElementById('matricule_militaire').value.trim();
            const unite = document.getElementById('unite').value;
            
            if (!unite) {
                errors.push('L\'unité est obligatoire');
                isValid = false;
            }
            
            if (matriculeMilitaire) {
                const formatsAutorises = {
                    'ARMÉE DE TERRE': /^T\d{2,4}\/\d{4,6}$/,
                    'ARMÉE DE L\'AIR': /^A\d{2,4}\/\d{4,6}$/,
                    'MARINE NATIONALE': /^M\d{2,4}\/\d{4,6}$/,
                    'GENDARMERIE': /^\d{4,6}$/
                };
                
                if (formatsAutorises[unite] && !formatsAutorises[unite].test(matriculeMilitaire)) {
                    const messagesFormat = {
                        'ARMÉE DE TERRE': 'Format: T17/23456 ou T2017/23456 (T + année sur 2-4 chiffres / 4-6 chiffres)',
                        'ARMÉE DE L\'AIR': 'Format: A17/23456 ou A2017/23456 (A + année sur 2-4 chiffres / 4-6 chiffres)',
                        'MARINE NATIONALE': 'Format: M17/23456 ou M2017/23456 (M + année sur 2-4 chiffres / 4-6 chiffres)',
                        'GENDARMERIE': 'Format: 23456 ou 123456 (4 à 6 chiffres uniquement)'
                    };
                    errors.push(messagesFormat[unite]);
                    isValid = false;
                }
            }
            
            // Validation simple de la photo si uploadée (juste format et taille)
            const photoInput = document.getElementById('photo-upload');
            if (photoInput.files && photoInput.files[0]) {
                const photo = photoInput.files[0];
                
                // Validation du type de fichier
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(photo.type)) {
                    errors.push('Le format de la photo n\'est pas valide (JPG ou PNG requis)');
                    isValid = false;
                }
                
                // Validation de la taille
                if (photo.size > 2 * 1024 * 1024) {
                    errors.push('La photo ne doit pas dépasser 2MB');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                showValidationErrors(errors);
            } else {
                submitForm();
            }
        });

        function showValidationErrors(errors) {
            // Supprimer les anciennes erreurs
            const oldAlerts = document.querySelectorAll('.alert-danger');
            oldAlerts.forEach(alert => alert.remove());
            
            // Créer et afficher les nouvelles erreurs
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger';
            alertDiv.style.marginBottom = '1rem';
            alertDiv.innerHTML = `
                <i class="fa-solid fa-exclamation-triangle"></i>
                <strong>Erreurs de validation:</strong><br>
                <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                    ${errors.map(error => `<li>${error}</li>`).join('')}
                </ul>
            `;
            
            // Insérer avant le formulaire
            const form = document.getElementById('modifierForm');
            form.parentNode.insertBefore(alertDiv, form);
            
            // Faire défiler vers les erreurs
            alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function submitForm() {
            // Supprimer les alertes d'erreur
            const oldAlerts = document.querySelectorAll('.alert-danger');
            oldAlerts.forEach(alert => alert.remove());
            
            // Ajouter un champ caché pour indiquer que c'est une soumission AJAX
            const form = document.getElementById('modifierForm');
            const ajaxInput = document.createElement('input');
            ajaxInput.type = 'hidden';
            ajaxInput.name = 'ajax_submit';
            ajaxInput.value = '1';
            form.appendChild(ajaxInput);
            
            // Créer FormData pour l'envoi
            const formData = new FormData(form);
            
            // Désactiver le bouton et afficher l'indicateur
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> MODIFICATION EN COURS...';
            
            // Envoyer la requête AJAX
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - afficher notification et rediriger
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    // Erreur - afficher le message
                    showValidationErrors([data.message]);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showValidationErrors(['Erreur lors de la soumission. Veuillez réessayer.']);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function showNotification(message, type) {
            // Créer et afficher une notification
            const notificationDiv = document.createElement('div');
            notificationDiv.className = `alert alert-${type}`;
            notificationDiv.style.marginBottom = '1rem';
            notificationDiv.innerHTML = `
                <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            
            // Insérer avant le formulaire
            const form = document.getElementById('modifierForm');
            form.parentNode.insertBefore(notificationDiv, form);
            
            // Faire défiler vers la notification
            notificationDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    </script>
</body>
</html>
