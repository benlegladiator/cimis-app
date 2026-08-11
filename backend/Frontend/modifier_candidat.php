<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../backend/config.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Récupérer l'ID du candidat
$id = $_GET['id'] ?? '';
if (empty($id) || !is_numeric($id)) {
    $_SESSION['error'] = "ID de candidat invalide";
    header('Location: ../impression.php');
    exit;
}

// Récupérer les informations du candidat
$stmt = $pdo->prepare("SELECT * FROM candidat WHERE id = :id");
$stmt->execute(['id' => $id]);
$candidat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidat) {
    $_SESSION['error'] = "Candidat non trouvé";
    header('Location: ../impression.php');
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
                'ARMÉE DE TERRE' => '/^T\d{2}\/\d{4,6}$/',
                'ARMÉE DE L\'AIR' => '/^A\d{2}\/\d{4,6}$/',
                'MARINE NATIONALE' => '/^M\d{2}\/\d{4,6}$/',
                'GENDARMERIE NATIONALE' => '/^\d{4,6}$/'
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
            $upload_dir = '../img/candidats/';
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
            'lieu_naissance' => strtoupper(trim($_POST['lieu_naissance'] ?? '')),
            'sexe' => $_POST['sexe'],
            'numero_cni' => $numero_cni,
            'taille' => $_POST['taille'] ?? '',
            'poids' => $_POST['poids'] ?? '',
            'groupe_sanguin' => $_POST['groupe_sanguin'] ?? '',
            'matricule_militaire' => $matricule_militaire,
            'unite' => $unite,
            'grade' => $_POST['grade'] ?? '',
            'categorie_civil' => $_POST['categorie_civil'] ?? '',
            'annee_dernier_galon' => $_POST['annee_dernier_galon'] ?? null,
            'suspendus' => $_POST['suspendus'] ?? 0,
            'statut_militaire' => $_POST['statut_militaire'] ?? 'ACTIF',
            'date_changement_statut' => $_POST['statut_militaire'] !== ($candidat['statut_militaire'] ?? 'ACTIF') ? date('Y-m-d') : null,
            'motif_changement_statut' => $_POST['motif_changement_statut'] ?? null,
            'photo' => $photo_path,
            'id' => $id
        ];

        // Mise à jour dans la base de données
        $sql = "UPDATE candidat SET 
            nom = :nom, 
            prenom = :prenom, 
            date_naissance = :date_naissance, 
            lieu_naissance = :lieu_naissance, 
            sexe = :sexe, 
            numero_cni = :numero_cni, 
            taille = :taille, 
            poids = :poids, 
            groupe_sanguin = :groupe_sanguin, 
            matricule_militaire = :matricule_militaire, 
            unite = :unite, 
            grade = :grade, 
            categorie_civil = :categorie_civil, 
            annee_dernier_galon = :annee_dernier_galon, 
            suspendus = :suspendus,
            statut_militaire = :statut_militaire,
            date_changement_statut = :date_changement_statut,
            motif_changement_statut = :motif_changement_statut,
            photo = :photo 
            WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Candidat modifié avec succès !',
            'redirect' => '../impression.php'
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
    <title>Modifier Personnel - CIMIS</title>
    <link rel="stylesheet" href="../css/enrolement.css">
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
            <a href="../impression.php" class="btn-back-hero" style="font-size: 16px; padding: 12px 24px; background: linear-gradient(45deg, #ff6b6b, #ee5a24); color: white; border: none; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(238, 90, 36, 0.3); transition: all 0.3s ease;">
                <i class="fa-solid fa-arrow-left"></i> RETOUR À LA LISTE
            </a>
        </div>

        <!-- HERO SECTION -->
        <div class="hero-section">
            <div class="hero-content">
                <img src="../img/cimis1.png" alt="CIMIS Logo" class="hero-logo">
                <div class="hero-text">
                    <h1>MODIFICATION DES INFORMATIONS</h1>
                    <div class="hero-divider"></div>
                    <h2>Système d'Identification Militaire</h2>
                    <p>Modification des informations du personnel: <strong><?php echo htmlspecialchars($candidat['matricule']); ?></strong></p>
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
                            <h4><i class="fa-solid fa-camera"></i> PHOTO DU PERSONNEL</h4>
                            <div style="display: flex; align-items: center; gap: 2rem;">
                                <div>
                                    <p class="form-label">Photo actuelle / Current Photo:</p>
                                    <?php 
                                    $photo_path = '';
                                    if (!empty($form_data['photo'])) {
                                        // Gestion du chemin de la photo
                                        if (file_exists($form_data['photo'])) {
                                            $photo_path = $form_data['photo'];
                                        } elseif (file_exists('../img/candidats/' . basename($form_data['photo']))) {
                                            $photo_path = '../img/candidats/' . basename($form_data['photo']);
                                        } else {
                                            $filename = basename($form_data['photo']);
                                            $photo_path = '../img/candidats/' . $filename;
                                        }
                                    }
                                    
                                    if (!empty($photo_path) && file_exists($photo_path)): 
                                    ?>
                                        <img src="<?php echo $photo_path; ?>" class="current-photo" alt="Photo actuelle / Current Photo">
                                    <?php else: ?>
                                        <div style="width: 150px; height: 200px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; border-radius: 10px; border: 2px dashed #666;">
                                            <i class="fa-solid fa-user" style="font-size: 3rem; color: #666;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1;">
                                    <p class="form-label">Nouvelle photo (optionnel) / New Photo (optional):</p>
                                    <input type="file" name="photo" id="photo-upload" accept="image/*" class="form-control">
                                    <small style="color: var(--neon-green); font-size: 0.8rem;">
                                        <i class="fa-solid fa-info-circle"></i> 
                                        Laissez vide pour conserver la photo actuelle. Format: JPG, PNG max 2MB / Leave empty to keep current photo. Format: JPG, PNG max 2MB
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Informations de base -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-hashtag"></i> Matricule CIM / CIMIS ID</label>
                                <input type="text" class="form-control form-readonly" value="<?php echo htmlspecialchars($candidat['matricule']); ?>" readonly>
                                <small><i class="fa-solid fa-lock"></i> Le matricule CIM ne peut pas être modifié / CIMIS ID cannot be modified</small>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-id-card"></i> Matricule/Service Number</label>
                                <input type="text" name="matricule_militaire" id="matricule_militaire" class="form-control" placeholder="EX: 23456 (Gendarmerie Nationale), T17/23456, A17/23456" 
                                       value="<?php echo htmlspecialchars($form_data['matricule_militaire'] ?? ''); ?>">
                                <small style="color: var(--neon-green); font-size: 0.8rem;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Gendarmerie Nationale: 4-6 chiffres | Armée de Terre/Air/Marine: Lettre + année(2 chiffres)/numéro(4-6 chiffres) / Gendarmerie: 4-6 digits | Army/Air/Navy: Letter + year(2 digits)/number(4-6 digits)
                                </small>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-user"></i> Nom/Name</label>
                                <input type="text" name="nom" id="nom" class="form-control" placeholder="EX: DUPONT" required 
                                       value="<?php echo htmlspecialchars($form_data['nom'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-user"></i> Prénom/First Name</label>
                                <input type="text" name="prenom" id="prenom" class="form-control" placeholder="EX: JEAN MARC" required
                                       value="<?php echo htmlspecialchars($form_data['prenom'] ?? ''); ?>">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-calendar"></i> Date Naissance/Birth Date</label>
                                <input type="date" name="date_naissance" id="date_naissance" class="form-control" required 
                                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                       title="Le personnel doit avoir au moins 18 ans"
                                       value="<?php echo htmlspecialchars($form_data['date_naissance'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-map-marker-alt"></i> Lieu Naissance/Birth Place</label>
                                <input type="text" name="lieu_naissance" id="lieu_naissance" class="form-control" placeholder="EX: YAOUNDE, DOUALA, etc." 
                                       value="<?php echo htmlspecialchars($form_data['lieu_naissance'] ?? ''); ?>">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-venus-mars"></i> Sexe / Gender</label>
                                <select name="sexe" id="sexe" class="form-control" required>
                                    <option value="">Sélectionner... / Select...</option>
                                    <option value="MASCULIN" <?php echo ($form_data['sexe'] ?? '') === 'MASCULIN' ? 'selected' : ''; ?>>MASCULIN</option>
                                    <option value="FEMININ" <?php echo ($form_data['sexe'] ?? '') === 'FEMININ' ? 'selected' : ''; ?>>FEMININ</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fa-solid fa-id-card"></i> Numéro CNI / ID Card Number</label>
                            <input type="text" name="numero_cni" id="numero_cni" class="form-control" placeholder="EX: 12345678901234567890 ou ABC123456789012345678" 
                                   required pattern="[A-Z0-9]{9,20}" maxlength="20"
                                   title="Le numéro CNI doit contenir entre 9 et 20 caractères (chiffres et/ou lettres majuscules) / ID card must contain 9-20 characters (digits and/or uppercase letters)"
                                   value="<?php echo htmlspecialchars($form_data['numero_cni'] ?? ''); ?>">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
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
                            <div class="form-group">
                                <label><i class="fa-solid fa-tint"></i> Groupe Sanguin / Blood Group</label>
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

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-shield-alt"></i> Corps/Branch</label>
                                <select name="unite" id="unite" class="form-control" required onchange="updateCivilCategory()">
                                    <option value="">Sélectionner... / Select...</option>
                                    <option value="GENDARMERIE NATIONALE" <?php echo ($form_data['unite'] ?? '') === 'GENDARMERIE NATIONALE' ? 'selected' : ''; ?>>GENDARMERIE NATIONALE</option>
                                    <option value="ARMÉE DE TERRE" <?php echo ($form_data['unite'] ?? '') === 'ARMÉE DE TERRE' ? 'selected' : ''; ?>>ARMÉE DE TERRE</option>
                                    <option value="ARMÉE DE L'AIR" <?php echo ($form_data['unite'] ?? '') === 'ARMÉE DE L\'AIR' ? 'selected' : ''; ?>>ARMÉE DE L'AIR</option>
                                    <option value="MARINE NATIONALE" <?php echo ($form_data['unite'] ?? '') === 'MARINE NATIONALE' ? 'selected' : ''; ?>>MARINE NATIONALE</option>
                                    <option value="CIVIL" <?php echo ($form_data['unite'] ?? '') === 'CIVIL' ? 'selected' : ''; ?>>PERSONNEL CIVIL</option>
                                </select>
                            </div>
                            <div class="form-group" id="categorie_civil_group" style="display: none;">
                                <label><i class="fa-solid fa-briefcase"></i> Catégorie / Category</label>
                                <select name="categorie_civil" id="categorie_civil" class="form-control" onchange="updateGrades()">
                                    <option value="">Sélectionner une catégorie...</option>
                                    <option value="FONCTIONNAIRE">Fonctionnaire</option>
                                    <option value="CADRE_CONTRACTUEL">Cadre contractuel administration</option>
                                    <option value="AGENT_CONTRACTUEL">Agent contractuel administration</option>
                                    <option value="AGENT_DECISION">Agent de décision</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-medal"></i> Grade/Rank</label>
                                <select name="grade" id="grade" class="form-control">
                                    <option value="">Sélectionner d'abord l'unité... / Select unit first...</option>
                                </select>
                            </div>
                        </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-shield-alt"></i> Statut militaire / Military Status</label>
                                <select name="statut_militaire" id="statut_militaire" class="form-control" required>
                                    <optgroup label="Statuts Actifs / Active Status">
                                        <option value="ACTIF" <?php echo ($form_data['statut_militaire'] ?? 'ACTIF') == 'ACTIF' ? 'selected' : ''; ?>>ACTIF</option>
                                        <option value="EN_MISSION" <?php echo ($form_data['statut_militaire'] ?? '') == 'EN_MISSION' ? 'selected' : ''; ?>>EN MISSION</option>
                                        <option value="EN_FORMATION" <?php echo ($form_data['statut_militaire'] ?? '') == 'EN_FORMATION' ? 'selected' : ''; ?>>EN FORMATION</option>
                                        <option value="EN_PERMISSION" <?php echo ($form_data['statut_militaire'] ?? '') == 'EN_PERMISSION' ? 'selected' : ''; ?>>EN PERMISSION</option>
                                        <option value="EN_CONGE" <?php echo ($form_data['statut_militaire'] ?? '') == 'EN_CONGE' ? 'selected' : ''; ?>>EN CONGÉ</option>
                                        <option value="RESERVE" <?php echo ($form_data['statut_militaire'] ?? '') == 'RESERVE' ? 'selected' : ''; ?>>RÉSERVE</option>
                                        <option value="RESERVE_ACTIVE" <?php echo ($form_data['statut_militaire'] ?? '') == 'RESERVE_ACTIVE' ? 'selected' : ''; ?>>RÉSERVE ACTIVE</option>
                                    </optgroup>
                                    <optgroup label="Statuts de Suspension / Suspension Status">
                                        <option value="SUSPENDU" <?php echo ($form_data['statut_militaire'] ?? '') == 'SUSPENDU' ? 'selected' : ''; ?>>SUSPENDU</option>
                                        <option value="SUSPENDU_ADMINISTRATIVEMENT" <?php echo ($form_data['statut_militaire'] ?? '') == 'SUSPENDU_ADMINISTRATIVEMENT' ? 'selected' : ''; ?>>SUSPENDU ADMINISTRATIVEMENT</option>
                                        <option value="SUSPENDU_MEDICAL" <?php echo ($form_data['statut_militaire'] ?? '') == 'SUSPENDU_MEDICAL' ? 'selected' : ''; ?>>SUSPENDU MÉDICAL</option>
                                    </optgroup>
                                    <optgroup label="Statuts Spéciaux / Special Status">
                                        <option value="EN_ATTENTE_AFFECTATION" <?php echo ($form_data['statut_militaire'] ?? '') == 'EN_ATTENTE_AFFECTATION' ? 'selected' : ''; ?>>EN ATTENTE D'AFFECTATION</option>
                                        <option value="EN_RETRAITE_TRANSITOIRE" <?php echo ($form_data['statut_militaire'] ?? '') == 'EN_RETRAITE_TRANSITOIRE' ? 'selected' : ''; ?>>EN RETRAITE TRANSITOIRE</option>
                                        <option value="DESERTEUR" <?php echo ($form_data['statut_militaire'] ?? '') == 'DESERTEUR' ? 'selected' : ''; ?>>DÉSERTEUR</option>
                                        <option value="REVOQUE" <?php echo ($form_data['statut_militaire'] ?? '') == 'REVOQUE' ? 'selected' : ''; ?>>RÉVOQUÉ</option>
                                        <option value="DEMISSIONNAIRE" <?php echo ($form_data['statut_militaire'] ?? '') == 'DEMISSIONNAIRE' ? 'selected' : ''; ?>>DÉMISSIONNAIRE</option>
                                        <option value="RETRAITE" <?php echo ($form_data['statut_militaire'] ?? '') == 'RETRAITE' ? 'selected' : ''; ?>>RETRAITE</option>
                                        <option value="DECES" <?php echo ($form_data['statut_militaire'] ?? '') == 'DECES' ? 'selected' : ''; ?>>DÉCÈS</option>
                                        <option value="HONORAIRE" <?php echo ($form_data['statut_militaire'] ?? '') == 'HONORAIRE' ? 'selected' : ''; ?>>HONORAIRE</option>
                                        <option value="CONTRACTUEL" <?php echo ($form_data['statut_militaire'] ?? '') == 'CONTRACTUEL' ? 'selected' : ''; ?>>CONTRACTUEL</option>
                                        <option value="CIVIL" <?php echo ($form_data['statut_militaire'] ?? '') == 'CIVIL' ? 'selected' : ''; ?>>CIVIL</option>
                                    </optgroup>
                                </select>
                                <small style="color: var(--neon-green); font-size: 0.8rem;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Statut militaire détaillé du personnel / Detailed military status of personnel
                                </small>
                            </div>
                            <div class="form-group">
                                <label><i class="fa-solid fa-credit-card"></i> Statut de la carte (active/suspendu) / Card Status (active/suspended)</label>
                                <select name="suspendus" id="suspendus" class="form-control" disabled>
                                    <option value="0" <?php echo ($form_data['suspendus'] ?? 0) == 0 ? 'selected' : ''; ?>>ACTIF</option>
                                    <option value="1" <?php echo ($form_data['suspendus'] ?? 0) == 1 ? 'selected' : ''; ?>>SUSPENDU</option>
                                </select>
                                <small style="color: var(--neon-orange); font-size: 0.8rem;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Géré automatiquement selon le statut militaire / Automatically managed according to military status
                                </small>
                            </div>
                            <div class="form-group" id="motif_group" style="display: none;">
                                <label><i class="fa-solid fa-comment"></i> Motif du changement de statut / Reason for Status Change</label>
                                <textarea name="motif_changement_statut" id="motif_changement_statut" class="form-control" rows="3" placeholder="Précisez le motif du changement de statut... / Specify the reason for status change..."><?php echo htmlspecialchars($form_data['motif_changement_statut'] ?? ''); ?></textarea>
                                <small style="color: var(--neon-green); font-size: 0.8rem;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Obligatoire pour les changements de statut / Required for status changes
                                </small>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label><i class="fa-solid fa-calendar"></i> Date de la dernière promotion au grade / Last Grade Promotion Date</label>
                                <input type="date" name="annee_dernier_galon" id="annee_dernier_galon" class="form-control" 
                                       max="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo htmlspecialchars($form_data['annee_dernier_galon'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mt-4" style="text-align: right; margin-top: 2rem;">
                            <a href="../impression.php" class="btn" style="margin-right: 1rem; background: linear-gradient(45deg, #f39c12, #e67e22); color: white;">
                                <i class="fa-solid fa-times"></i> ANNULER / CANCEL
                            </a>
                            <button type="submit" class="btn" style="background: linear-gradient(45deg, #27ae60, #2ecc71); color: white; font-weight: bold; padding: 12px 30px;">
                                <i class="fa-solid fa-save"></i> ENREGISTRER LES MODIFICATIONS / SAVE CHANGES
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <!-- FOOTER -->
        <footer class="security-footer">
            <div class="footer-left">
                <span><i class="fa-solid fa-shield-alt"></i> SYSTÈME CIMIS NUMÉRISATION</span>
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
                'Général d\'Armée',
                'Général de Corps d\'Armée',
                'Général de Division',
                'Général de Brigade',
                
                // OFFICIERS SUPERIEURS (3)
                'Colonel',
                'Lieutenant-Colonel',
                'Chef de Bataillon',
                
                // OFFICIERS SUBALTERNES (3)
                'Capitaine',
                'Lieutenant',
                'Sous-Lieutenant',
                
                // ASPIRANTS (1)
                'Aspirant',
                
                // SOUS OFFICIERS SUPERIEURS (3)
                'Adjudant-Chef Major',
                'Adjudant-Chef',
                'Adjudant',
                
                // SOUS OFFICIERS SUBALTERNES (4)
                'Sergent-Chef',
                'Sergent',
                'Caporal-Chef',
                'Caporal',
                
                // MILITAIRES DU RANG (2)
                'Soldat de 1E Classe',
                'Soldat de 2E Classe'
            ],
            'MARINE NATIONALE': [
                // OFFICIERS GENERAUX (4)
                'Amiral d\'Escadre',
                'Vice-Amiral d\'Escadre',
                'Vice-Amiral',
                'Contre-Amiral',
                
                // OFFICIERS SUPERIEURS (3)
                'Capitaine de Vaisseau',
                'Capitaine de Frégate',
                'Capitaine de Corvette',
                
                // OFFICIERS SUBALTERNES (3)
                'Lieutenant de Vaisseau',
                'Enseigne de Vaisseau de 1E Classe',
                'Enseigne de Vaisseau de 2E Classe',
                
                // ASPIRANTS ET ÉLÈVES OFFICIERS (1)
                'Aspirant',
                
                // OFFICIERS MARINS (4)
                'Maître Principal Major',
                'Maître Principal',
                'Maître',
                'Premier Maître',
                
                // SOUS OFFICIERS (5)
                'Second Maître',
                'Quartier-Maître de 1E Classe',
                'Quartier-Maître de 2E Classe',
                'Matelot de 1E Classe',
                'Matelot de 2E Classe'
            ],
            'ARMÉE DE L\'AIR': [
                // OFFICIERS GENERAUX (4)
                'Général d\'Armée Aérienne',
                'Général de Corps Aérien',
                'Général de Division Aérienne',
                'Général de Brigade Aérienne',
                
                // OFFICIERS SUPERIEURS (3)
                'Colonel',
                'Lieutenant-Colonel',
                'Commandant',
                
                // OFFICIERS SUBALTERNES (3)
                'Capitaine',
                'Lieutenant',
                'Sous-Lieutenant',
                
                // ASPIRANTS ET ÉLÈVES OFFICIERS (1)
                'Aspirant',
                
                // SOUS OFFICIERS SUPERIEURS (4)
                'Adjudant-Chef Major',
                'Adjudant-Chef',
                'Adjudant',
                'Sergent-Chef',
                
                // SOUS OFFICIERS SUBALTERNES (3)
                'Sergent',
                'Caporal-Chef',
                'Caporal',
                
                // MILITAIRES DU RANG (2)
                'Soldat de 1E Classe',
                'Soldat de 2E Classe'
            ],
            'GENDARMERIE NATIONALE': [
                // OFFICIERS GENERAUX (4)
                'Général d\'Armée',
                'Général de Corps d\'Armée',
                'Général de Division',
                'Général de Brigade',
                
                // OFFICIERS SUPERIEURS (3)
                'Colonel',
                'Lieutenant-Colonel',
                'Chef d\'Escadron',
                
                // OFFICIERS SUBALTERNES (3)
                'Capitaine',
                'Lieutenant',
                'Sous-Lieutenant',
                
                // ASPIRANTS (1)
                'Aspirant',
                
                // SOUS OFFICIERS SUPERIEURS (3)
                'Adjudant-Chef Major',
                'Adjudant-Chef',
                'Adjudant',
                
                // SOUS OFFICIERS SUBALTERNES (3)
                'Maréchal des Logis-Chef',
                'Maréchal des Logis',
                'Gendarme Major',
                'Gendarme',
                'Élève-Gendarme'
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

        // Gestion dynamique des grades selon l'unité
        
        function updateCivilCategory() {
            const unite = document.getElementById('unite').value;
            const categorieCivilGroup = document.getElementById('categorie_civil_group');
            const categorieCivilField = document.getElementById('categorie_civil');
            const gradeField = document.getElementById('grade');
            
            // Afficher ou cacher le champ catégorie selon l'unité
            if (unite === 'CIVIL') {
                if (categorieCivilGroup) {
                    categorieCivilGroup.style.display = 'block';
                }
                if (categorieCivilField) {
                    categorieCivilField.required = true;
                }
                // Désactiver le grade pour les civils jusqu'à ce qu'une catégorie soit sélectionnée
                if (gradeField) {
                    gradeField.disabled = true;
                    gradeField.required = false;
                    gradeField.innerHTML = '<option value="">Sélectionner d\'abord la catégorie... / Select category first...</option>';
                }
            } else {
                if (categorieCivilGroup) {
                    categorieCivilGroup.style.display = 'none';
                }
                if (categorieCivilField) {
                    categorieCivilField.required = false;
                    categorieCivilField.value = '';
                }
                // Activer le grade pour les militaires
                if (gradeField) {
                    gradeField.disabled = false;
                    gradeField.required = true;
                }
                // Mettre à jour les grades pour les militaires
                updateGrades();
            }
        }
        
        function updateGrades() {
            const unite = document.getElementById('unite').value;
            const categorieCivil = document.getElementById('categorie_civil') ? document.getElementById('categorie_civil').value : null;
            const gradeSelect = document.getElementById('grade');
            const currentGrade = '<?php echo addslashes($form_data["grade"] ?? ""); ?>';
            
            console.log('updateGrades appelé - unité:', unite, 'grade actuel:', currentGrade);
            console.log('gradesParUnite disponible:', gradesParUnite);
            console.log('grades pour unité', unite, ':', gradesParUnite[unite]);
            
            // Vider les options existantes
            gradeSelect.innerHTML = '<option value="">Sélectionner un grade... / Select a rank...</option>';
            
            // Cas spécial pour le personnel civil
            if (unite === 'CIVIL') {
                if (!categorieCivil) {
                    gradeSelect.innerHTML = '<option value="">Sélectionner d\'abord la catégorie... / Select category first...</option>';
                    gradeSelect.disabled = true;
                    gradeSelect.required = false;
                    return;
                }
                
                // Activer le champ grade
                gradeSelect.disabled = false;
                gradeSelect.required = true;
                
                // Grades selon la catégorie civile
                const gradesParCategorie = {
                    'FONCTIONNAIRE': ['AGENT', 'CADRE', 'INGÉNIEUR', 'TECHNICIEN', 'MÉCANICIEN', 'CHAUFFEUR', 'INFORMATICIEN', 'CUISINIER', 'COUTURIER', 'COMPTABLE', 'CONSULTANT', 'PSYCHOLOGUE', 'MÉDECIN', 'OPÉRATEUR', 'CONSEILLER', 'COACH', 'EXPERT', 'ENSEIGNANT', 'ASSISTANT', 'COIFFEUR', 'JURISTE', 'RÉGISSEUR', 'ANALYSTE', 'CONTRÔLEUR', 'AUTRE'],
                    'CADRE_CONTRACTUEL': ['AGENT', 'CADRE', 'INGÉNIEUR', 'TECHNICIEN', 'MÉCANICIEN', 'CHAUFFEUR', 'INFORMATICIEN', 'CUISINIER', 'COUTURIER', 'COMPTABLE', 'CONSULTANT', 'PSYCHOLOGUE', 'MÉDECIN', 'OPÉRATEUR', 'CONSEILLER', 'COACH', 'EXPERT', 'ENSEIGNANT', 'ASSISTANT', 'COIFFEUR', 'JURISTE', 'RÉGISSEUR', 'ANALYSTE', 'CONTRÔLEUR', 'AUTRE'],
                    'AGENT_CONTRACTUEL': ['AGENT', 'CADRE', 'INGÉNIEUR', 'TECHNICIEN', 'MÉCANICIEN', 'CHAUFFEUR', 'INFORMATICIEN', 'CUISINIER', 'COUTURIER', 'COMPTABLE', 'CONSULTANT', 'PSYCHOLOGUE', 'MÉDECIN', 'OPÉRATEUR', 'CONSEILLER', 'COACH', 'EXPERT', 'ENSEIGNANT', 'ASSISTANT', 'COIFFEUR', 'JURISTE', 'RÉGISSEUR', 'ANALYSTE', 'CONTRÔLEUR', 'AUTRE'],
                    'AGENT_DECISION': ['AGENT', 'CADRE', 'INGÉNIEUR', 'TECHNICIEN', 'MÉCANICIEN', 'CHAUFFEUR', 'INFORMATICIEN', 'CUISINIER', 'COUTURIER', 'COMPTABLE', 'CONSULTANT', 'PSYCHOLOGUE', 'MÉDECIN', 'OPÉRATEUR', 'CONSEILLER', 'COACH', 'EXPERT', 'ENSEIGNANT', 'ASSISTANT', 'COIFFEUR', 'JURISTE', 'RÉGISSEUR', 'ANALYSTE', 'CONTRÔLEUR', 'AUTRE']
                };
                
                if (gradesParCategorie[categorieCivil]) {
                    gradesParCategorie[categorieCivil].forEach(function(grade) {
                        const option = document.createElement('option');
                        option.value = grade;
                        option.textContent = grade;
                        if (grade === currentGrade) {
                            option.selected = true;
                            console.log('Grade présélectionné:', grade);
                        }
                        gradeSelect.appendChild(option);
                    });
                }
                return;
            }
            
            // Cas normal pour le personnel militaire
            if (gradesParUnite && gradesParUnite[unite]) {
                console.log('Ajout des grades pour l\'unité:', unite);
                gradesParUnite[unite].forEach(function(grade) {
                    const option = document.createElement('option');
                    option.value = grade;
                    option.textContent = grade;
                    if (grade === currentGrade) {
                        option.selected = true;
                        console.log('Grade présélectionné:', grade);
                    }
                    gradeSelect.appendChild(option);
                });
            } else {
                console.log('Pas de grades trouvés pour l\'unité:', unite);
            }
            
            // Si aucune unité n'est sélectionnée mais qu'il y a un grade existant, l'ajouter quand même
            if (!unite && currentGrade) {
                console.log('Ajout du grade existant sans unité:', currentGrade);
                const option = document.createElement('option');
                option.value = currentGrade;
                option.textContent = currentGrade;
                option.selected = true;
                gradeSelect.appendChild(option);
            }
        }
        
        document.getElementById('unite').addEventListener('change', updateCivilCategory);
        
        // Écouteur pour le champ catégorie civile
        const categorieCivilField = document.getElementById('categorie_civil');
        if (categorieCivilField) {
            categorieCivilField.addEventListener('change', updateGrades);
        }
        
        // Gestion du statut militaire et synchronisation automatique
        const statutMilitaireSelect = document.getElementById('statut_militaire');
        const suspendusSelect = document.getElementById('suspendus');
        const motifGroup = document.getElementById('motif_group');
        const currentStatut = '<?php echo addslashes($form_data["statut_militaire"] ?? "ACTIF"); ?>';
        
        function updateSuspendusField() {
            const selectedStatut = statutMilitaireSelect.value;
            const suspensionStatuts = ['SUSPENDU', 'SUSPENDU_ADMINISTRATIVEMENT', 'SUSPENDU_MEDICAL', 'DESERTEUR', 'REVOQUE'];
            
            // Mettre à jour le champ suspendus selon le statut militaire
            if (suspensionStatuts.includes(selectedStatut)) {
                suspendusSelect.value = '1';
            } else {
                suspendusSelect.value = '0';
            }
            
            // Afficher le champ motif si le statut change
            if (selectedStatut !== currentStatut) {
                motifGroup.style.display = 'block';
            } else {
                motifGroup.style.display = 'none';
            }
        }
        
        statutMilitaireSelect.addEventListener('change', updateSuspendusField);
        
        // Déclencher l'événement pour charger les grades initiaux
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                updateGrades();
            });
        } else {
            updateGrades();
        }
        // Initialiser la synchronisation
        updateSuspendusField();

        // --- VALIDATION FORMULAIRE ---
        // Attacher l'écouteur d'événement au formulaire après le chargement du DOM
        function attachFormListener() {
            const form = document.getElementById('modifierForm');
            if (!form) {
                console.error('Formulaire non trouvé');
                return;
            }
            
            form.addEventListener('submit', function(e) {
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
                    errors.push('La date de naissance est requise');
                    isValid = false;
                }
                
                // Validation du sexe
                const sexe = document.getElementById('sexe').value;
                if (!sexe) {
                    errors.push('Le sexe est requis');
                    isValid = false;
                }
                
                // Validation du CNI
                const cni = document.getElementById('numero_cni').value.trim();
                if (!cni) {
                    errors.push('Le numéro CNI est requis');
                    isValid = false;
                }
                
                // Validation de l'unité
                const unite = document.getElementById('unite').value;
                if (!unite) {
                    errors.push('L\'unité est requise');
                    isValid = false;
                }
                
                // Validation du grade (uniquement pour militaires)
                if (unite !== 'CIVIL') {
                    const grade = document.getElementById('grade').value;
                    if (!grade) {
                        errors.push('Le grade est requis pour le personnel militaire');
                        isValid = false;
                    }
                }
                
                // Validation simple de la photo si uploadée
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
        }
        
        // Déclencher l'initialisation après le chargement du DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                updateGrades();
                attachFormListener();
            });
        } else {
            updateGrades();
            attachFormListener();
        }

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
            
            // Récupérer le formulaire
            const form = document.getElementById('modifierForm');
            
            if (!form) {
                console.error('Formulaire non trouvé');
                return;
            }
            
            // Ajouter un champ caché pour indiquer que c'est une soumission AJAX
            const ajaxInput = document.createElement('input');
            ajaxInput.type = 'hidden';
            ajaxInput.name = 'ajax_submit';
            ajaxInput.value = '1';
            form.appendChild(ajaxInput);
            
            // Désactiver le bouton et afficher l'indicateur
            const submitBtn = form.querySelector('button[type="submit"]');
            if (!submitBtn) {
                console.error('Bouton submit non trouvé');
                return;
            }
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
