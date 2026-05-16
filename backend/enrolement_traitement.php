<?php
// Bufferiser toute sortie pour éviter les affichages non désirés
ob_start();

// Désactiver l'affichage des erreurs pour éviter de polluer le JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'qrcode_generator.php';

// Vérification de la connexion
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Content-Type: application/json');
    ob_end_clean(); // Nettoyer tout buffer avant JSON
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Traitement du formulaire d'enrôlement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CONSERVATION DES DONNÉES EN SESSION EN CAS D'ERREUR
        $_SESSION['form_data'] = $_POST;
        
        // CONTRÔLES AVANCÉS
        $errors = [];
        
        // 1. Vérification de l'âge (minimum 18 ans)
        $date_naissance = new DateTime($_POST['date_naissance']);
        $aujourd_hui = new DateTime();
        $age = $date_naissance->diff($aujourd_hui)->y;
        
        if ($age < 18) {
            throw new Exception("Le candidat doit avoir au moins 18 ans. Âge calculé: $age ans");
        }
        
        // 2. Validation du format du numéro CNI (9 à 20 caractères: lettres majuscules et chiffres)
        $numero_cni = preg_replace('/[^A-Z0-9]/', '', strtoupper($_POST['numero_cni']));
        if (strlen($numero_cni) < 9 || strlen($numero_cni) > 20) {
            throw new Exception("Le numéro CNI doit contenir entre 9 et 20 caractères (lettres majuscules et chiffres)");
        }
        
        // 2.1. Vérification de l'unicité du numéro CNI
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE numero_cni = :numero_cni");
        $stmt->execute(['numero_cni' => $numero_cni]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            throw new Exception("Ce numéro CNI est déjà utilisé par un autre candidat");
        }
        
        // 2.2. Vérification de l'unicité du matricule militaire
        $matricule_militaire = trim($_POST['matricule_militaire'] ?? '');
        if (!empty($matricule_militaire)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE matricule_militaire = :matricule_militaire");
            $stmt->execute(['matricule_militaire' => $matricule_militaire]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                throw new Exception("Ce matricule militaire est déjà utilisé par un autre candidat");
            }
        }
        
        // 3. Validation de la taille et du poids
        $taille = (int)$_POST['taille'];
        $poids = (int)$_POST['poids'];
        
        if ($taille < 140 || $taille > 220) {
            throw new Exception("La taille doit être comprise entre 140cm et 220cm");
        }
        
        if ($poids < 45 || $poids > 150) {
            throw new Exception("Le poids doit être compris entre 45kg et 150kg");
        }
        
        // 4. Calcul et validation de l'IMC
        $taille_m = $taille / 100;
        $imc = $poids / ($taille_m * $taille_m);
        
        if ($imc < 16 || $imc > 35) {
            throw new Exception("L'IMC ($imc) est hors des normes acceptables (16-35)");
        }
        
        // 5. Validation de la date de la dernière promotion au grade (optionnelle pour les civils)
        $unite = $_POST['unite'] ?? ''; // Définir $unite avant de l'utiliser
        $date_promotion = $_POST['annee_dernier_galon'] ?? '';
        $date_actuelle = date('Y-m-d');
        
        // La date de la dernière promotion n'est requise que pour les militaires
        if ($unite !== 'CIVIL') {
            if (empty($date_promotion) || $date_promotion > $date_actuelle) {
                throw new Exception("La date de la dernière promotion au grade doit être valide et antérieure à la date actuelle");
            }
        } else {
            // Pour les civils, la date n'est pas requise
            $date_promotion = null;
        }
        
        // 6. Validation du matricule militaire selon le corps d'armée
        $matricule_militaire = trim($_POST['matricule_militaire'] ?? '');
        
        // Le matricule militaire n'est requis que pour les unités militaires (pas pour CIVIL)
        if ($unite !== 'CIVIL' && empty($matricule_militaire)) {
            throw new Exception("Le matricule militaire est requis pour les unités militaires");
        }
        
        // Validation du format uniquement si un matricule est fourni (pour les militaires)
        if (!empty($matricule_militaire)) {
            
            if (strlen($matricule_militaire) < 4) {
                throw new Exception("Le matricule militaire doit contenir au moins 4 caractères");
            }
            
            $formats_autorises = [
                'ARMÉE DE TERRE' => '/^T\d{2,4}\/\d{4,6}$/',
                'ARMÉE DE L\'AIR' => '/^A\d{2,4}\/\d{4,6}$/',
                'MARINE NATIONALE' => '/^M\d{2,4}\/\d{4,6}$/',
                'GENDARMERIE' => '/^\d{4,6}$/'
            ];
            
            $messages_format = [
                'ARMÉE DE TERRE' => 'Format: T17/23456 ou T2017/23456 (T + année sur 2-4 chiffres / 4-6 chiffres)',
                'ARMÉE DE L\'AIR' => 'Format: A17/23456 ou A2017/23456 (A + année sur 2-4 chiffres / 4-6 chiffres)',
                'MARINE NATIONALE' => 'Format: M17/23456 ou M2017/23456 (M + année sur 2-4 chiffres / 4-6 chiffres)',
                'GENDARMERIE' => 'Format: 23456 ou 123456 (4 à 6 chiffres uniquement)'
            ];
            
            if (isset($formats_autorises[$unite])) {
                $format_requis = $formats_autorises[$unite];
                if (!preg_match($format_requis, $matricule_militaire)) {
                    throw new Exception("Format invalide pour $unite. " . $messages_format[$unite]);
                }
            }
        }
        
        // 7. Génération du matricule
        $matricule = 'CIM-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        // 8. Vérification du matricule unique
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE matricule = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            // Régénérer si déjà utilisé
            do {
                $matricule = 'CIM-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $stmt->execute(['matricule' => $matricule]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } while ($count > 0);
        }
        
        // 10. Validation et traitement de la photo (obligatoire pour tous)
        $photoData = null;
        $photoExtension = 'jpg';
        
        // Priorité 1: Photo rognée via photo-data (base64)
        if (!empty($_POST['photo_data']) && $_POST['photo_data'] !== '') {
            $photoData = $_POST['photo_data'];
            // Extraire l'extension du base64 si disponible
            if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $matches)) {
                $photoExtension = $matches[1];
                // Supprimer le préfixe base64
                $photoData = preg_replace('/^data:image\/(\w+);base64,/', '', $photoData);
            }
        }
        // Priorité 2: Fichier uploadé normal
        elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $_FILES['photo'];
            
            // Validation simple
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($photo['type'], $allowedTypes)) {
                throw new Exception("Format de photo non autorisé. Utilisez JPEG ou PNG.");
            }
            
            if ($photo['size'] > 2 * 1024 * 1024) { // 2MB max
                throw new Exception("La photo est trop volumineuse. Maximum 2MB.");
            }
            
            // Lire le fichier et le convertir en base64
            $photoData = base64_encode(file_get_contents($photo['tmp_name']));
            // Extraire l'extension
            $photoExtension = pathinfo($photo['name'], PATHINFO_EXTENSION);
        }
        else {
            throw new Exception("La photo d'identité est obligatoire pour tous les candidats");
        }
        
        // Création du répertoire si nécessaire
        $upload_dir = __DIR__ . '/../img/candidats/'; // chemin absolu depuis le backend
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Génération du nom de fichier
        $filename = $matricule . '_' . time() . '.' . $photoExtension;
        $photo_path = $upload_dir . $filename;
        
        // Sauvegarde de la photo (base64 ou fichier)
        if ($photoData !== null) {
            // Décoder et sauvegarder les données base64
            $decodedPhoto = base64_decode($photoData);
            if ($decodedPhoto === false) {
                throw new Exception("Erreur lors du décodage de la photo.");
            }
            
            if (!file_put_contents($photo_path, $decodedPhoto)) {
                throw new Exception("Erreur lors de la sauvegarde de la photo.");
            }
            error_log("Chemin complet: " . realpath($photo_path));
        } else {
            error_log("ERREUR move_uploaded_file: " . $photo['tmp_name'] . " -> " . $photo_path);
            error_log("Erreur PHP: " . (error_get_last()['message'] ?? 'Inconnue'));
            throw new Exception("Erreur lors du téléchargement de la photo.");
        }
        
        // Stocker le chemin web pour la réponse (accessible depuis Frontend/)
        $photo_path = '../img/candidats/' . $filename;
        
        // 11. Préparation des données pour l'insertion
        $date_enrolement = date('Y-m-d H:i:s');
        $date_dernier_grade = $annee_galon . '-01-01'; // 1er janvier de l'année
        
        // Génération du QR code TOUJOURS basé sur le matricule CIMIS
        $type_personnel = $_POST['type_personnel'] ?? 'MILITAIRE';
        $matricule_militaire = $_POST['matricule_militaire'] ?? '';
        
        // TOUJOURS générer le QR avec le matricule CIMIS (civil ou militaire)
        if (!empty($matricule)) {
            error_log("Tentative génération QR pour matricule CIMIS: " . $matricule . " (Type: " . $type_personnel . ")");
            $code_qr = generateQRCodeForMatricule($matricule);
            error_log("QR CIMIS généré, chemin retourné: " . $code_qr);
            error_log("Fichier QR existe: " . (file_exists(__DIR__ . '/../' . $code_qr) ? 'OUI' : 'NON'));
        } else {
            $code_qr = ''; // Pas de matricule CIMIS = pas de QR
            error_log("Pas de matricule CIMIS disponible, pas de QR généré");
        }
        
        // 12. Insertion dans la base de données
        $sql = "INSERT INTO candidat (
            matricule, matricule_militaire, nom, prenom, date_naissance, lieu_naissance, sexe, numero_cni, 
            taille, poids, groupe_sanguin, annee_dernier_galon, 
            unite, grade, categorie_civil, photo, date_enrolement, date_dernier_grade, code_qr
        ) VALUES (
            :matricule, :matricule_militaire, :nom, :prenom, :date_naissance, :lieu_naissance, :sexe, :numero_cni,
            :taille, :poids, :groupe_sanguin, :annee_dernier_galon,
            :unite, :grade, :categorie_civil, :photo, :date_enrolement, :date_dernier_grade, :code_qr
        )";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            'matricule' => $matricule,
            'matricule_militaire' => $_POST['matricule_militaire'] ?? '',
            'nom' => strtoupper(trim($_POST['nom'])),
            'prenom' => strtoupper(trim($_POST['prenom'])),
            'date_naissance' => $_POST['date_naissance'],
            'lieu_naissance' => strtoupper(trim($_POST['lieu_naissance'] ?? '')),
            'sexe' => $_POST['sexe'],
            'numero_cni' => $numero_cni,
            'taille' => $taille,
            'poids' => $poids,
            'groupe_sanguin' => $_POST['groupe_sanguin'] ?? '',
            'annee_dernier_galon' => $annee_galon,
            'unite' => $_POST['unite'],
            'grade' => $_POST['grade'],
            'categorie_civil' => $_POST['categorie_civil'] ?? '',
            'photo' => $photo_path,
            'date_enrolement' => $date_enrolement,
            'date_dernier_grade' => $date_dernier_grade,
            'code_qr' => $code_qr
        ]);
        
        // Debug: Vérification après insertion
        error_log("Insertion réussie pour matricule: " . $matricule);
        
        // 13. Nettoyage de la session
        unset($_SESSION['form_data']);
        
        // 15. Préparation des données pour la réponse
        $candidat_data = [
            'matricule' => $matricule,
            'matricule_militaire' => $_POST['matricule_militaire'] ?? '',
            'nom' => strtoupper(trim($_POST['nom'])),
            'prenom' => strtoupper(trim($_POST['prenom'])),
            'date_naissance' => $_POST['date_naissance'],
            'sexe' => $_POST['sexe'],
            'unite' => $_POST['unite'],
            'grade' => $_POST['grade'],
            'photo' => $photo_path,
            'numero_cni' => $numero_cni,
            'date_enrolement' => $date_enrolement,
            'code_qr' => $code_qr
        ];
        
        // Debug: Vérifier la photo dans les données
        error_log("Photo dans candidat_data: " . ($photo_path ?? 'VIDE'));
        error_log("Photo existe: " . ($photo_path && file_exists(__DIR__ . '/../' . $photo_path) ? 'OUI' : 'NON'));
        
        // 16. Stockage pour la modal
        $_SESSION['candidat_enrolled'] = $candidat_data;
        
        // 17. Réponse JSON pour le JavaScript
        header('Content-Type: application/json');
        
        // Logging de la réponse
        $response = [
            'success' => true,
            'message' => 'Candidat enrôlé avec succès !',
            'matricule' => $matricule,
            'code_qr' => $code_qr,
            'candidat' => $candidat_data
        ];
        error_log("Réponse JSON: " . json_encode($response));
        
        ob_end_clean(); // Nettoyer tout buffer avant JSON
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Gestion des erreurs
        $_SESSION['error'] = $e->getMessage();
        
        header('Content-Type: application/json');
        ob_end_clean(); // Nettoyer tout buffer avant JSON
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'form_data' => $_SESSION['form_data'] ?? []
        ]);
    }
} else {
    // Méthode non autorisée
    header('Content-Type: application/json');
    ob_end_clean(); // Nettoyer tout buffer avant JSON
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
}
?>
