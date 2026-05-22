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
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Traitement du formulaire d'enrôlement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $_SESSION['form_data'] = $_POST;
        $errors = [];

        // 1. Vérification de l'âge (minimum 18 ans) avec deux formats acceptés
        $date_naissance_input = $_POST['date_naissance'] ?? '';
        $date_naissance = DateTime::createFromFormat('d/m/Y', $date_naissance_input);
        
        if (!$date_naissance) {
            $date_naissance = DateTime::createFromFormat('Y-m-d', $date_naissance_input);
        }
        
        if (!$date_naissance) {
            throw new Exception("Format de date de naissance invalide. Utilisez JJ/MM/AAAA ou YYYY-MM-DD.");
        }
        
        $aujourd_hui = new DateTime();
        $age = $date_naissance->diff($aujourd_hui)->y;
        if ($age < 18) {
            throw new Exception("Le candidat doit avoir au moins 18 ans. Âge calculé: $age ans");
        }
        
        $date_naissance_sql = $date_naissance->format('Y-m-d'); // format PostgreSQL


        // 2. Validation du numéro CNI
        $numero_cni = preg_replace('/[^A-Z0-9]/', '', strtoupper($_POST['numero_cni']));
        if (strlen($numero_cni) < 9 || strlen($numero_cni) > 20) {
            throw new Exception("Le numéro CNI doit contenir entre 9 et 20 caractères.");
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE numero_cni = :numero_cni");
        $stmt->execute(['numero_cni' => $numero_cni]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            throw new Exception("Ce numéro CNI est déjà utilisé.");
        }

        // 2.2. Vérification du matricule militaire
        $matricule_militaire = trim($_POST['matricule_militaire'] ?? '');
        if (!empty($matricule_militaire)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE matricule_militaire = :matricule_militaire");
            $stmt->execute(['matricule_militaire' => $matricule_militaire]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                throw new Exception("Ce matricule militaire est déjà utilisé.");
            }
        }

        // 3. Validation taille/poids
        $taille = (int)$_POST['taille'];
        $poids = (int)$_POST['poids'];
        if ($taille < 140 || $taille > 220) throw new Exception("Taille invalide.");
        if ($poids < 45 || $poids > 150) throw new Exception("Poids invalide.");
        $imc = $poids / pow($taille/100, 2);
        if ($imc < 16 || $imc > 35) throw new Exception("IMC hors normes.");

        // 4. Date de dernière promotion (flexible)
        $unite = $_POST['unite'] ?? '';
        $date_promo_input = $_POST['annee_dernier_galon'] ?? '';
        $date_actuelle = date('Y-m-d');
        
        if ($unite !== 'CIVIL') {
            if (empty($date_promo_input)) {
                throw new Exception("La date ou l'année du dernier galon est requise.");
            }
        
            // Essayer plusieurs formats : AAAA, JJ/MM/AAAA, YYYY-MM-DD
            $dateObj = DateTime::createFromFormat('Y', $date_promo_input);
            if (!$dateObj) {
                $dateObj = DateTime::createFromFormat('d/m/Y', $date_promo_input);
            }
            if (!$dateObj) {
                $dateObj = DateTime::createFromFormat('Y-m-d', $date_promo_input);
            }
        
            if (!$dateObj) {
                throw new Exception("Format de date de promotion invalide. Utilisez AAAA, JJ/MM/AAAA ou YYYY-MM-DD.");
            }
        
            $date_dernier_grade = $dateObj->format('Y-m-d'); // format PostgreSQL
        
            if ($date_dernier_grade > $date_actuelle) {
                throw new Exception("La date du dernier grade doit être antérieure à aujourd'hui.");
            }
        } else {
            $date_dernier_grade = null;
        }


        // 5. Validation matricule militaire
        if ($unite !== 'CIVIL' && empty($matricule_militaire)) {
            throw new Exception("Le matricule militaire est requis pour les unités militaires.");
        }
        if (!empty($matricule_militaire)) {
            $formats_autorises = [
                'ARMÉE DE TERRE' => '/^T\d{2,4}\/\d{4,6}$/',
                'ARMÉE DE L\'AIR' => '/^A\d{2,4}\/\d{4,6}$/',
                'MARINE NATIONALE' => '/^M\d{2,4}\/\d{4,6}$/',
                'GENDARMERIE' => '/^\d{4,6}$/'
            ];
            if (isset($formats_autorises[$unite]) && !preg_match($formats_autorises[$unite], $matricule_militaire)) {
                throw new Exception("Format invalide pour $unite.");
            }
        }

        // 6. Génération matricule CIMIS
        $matricule = 'CIM-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE matricule = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        while ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            $matricule = 'CIM-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $stmt->execute(['matricule' => $matricule]);
        }

        // 7. Photo (base64 ou upload)
        $photoData = null;
        $photoExtension = 'jpg';
        if (!empty($_POST['photo_data'])) {
            if (preg_match('/^data:image\/(\w+);base64,/', $_POST['photo_data'], $matches)) {
                $photoExtension = $matches[1];
                $photoData = preg_replace('/^data:image\/(\w+);base64,/', '', $_POST['photo_data']);
            }
        } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg','image/jpg','image/png'];
            if (!in_array($_FILES['photo']['type'], $allowedTypes)) throw new Exception("Format photo non autorisé.");
            if ($_FILES['photo']['size'] > 2*1024*1024) throw new Exception("Photo trop volumineuse.");
            $photoData = base64_encode(file_get_contents($_FILES['photo']['tmp_name']));
            $photoExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        } else {
            throw new Exception("Photo obligatoire.");
        }

        $upload_dir = __DIR__ . '/../img/candidats/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0775, true)) {
                throw new Exception("Impossible de créer le dossier de stockage des photos.");
            }
        }
        // Vérifier que le répertoire est accessible en écriture
        if (!is_writable($upload_dir)) {
            error_log("ERREUR: Répertoire non accessible en écriture: " . $upload_dir);
            throw new Exception("Erreur serveur: le dossier de stockage des photos n'est pas accessible en écriture.");
        }

        // Génération du nom de fichier
        $filename = $matricule . '_' . uniqid() . '.' . $photoExtension;
        $photo_path = $upload_dir . $filename;

        // Sauvegarde de la photo
        if ($photoData !== null) {
            $decodedPhoto = base64_decode($photoData);
            if ($decodedPhoto === false) {
                throw new Exception("Erreur lors du décodage de la photo.");
            }
            if (file_put_contents($photo_path, $decodedPhoto) === false) {
                throw new Exception("Erreur lors de la sauvegarde de la photo.");
            }
        } else {
            throw new Exception("Erreur lors du téléchargement de la photo.");
        }

        // Chemin relatif pour la base
        $photo_path_rel = 'img/candidats/' . $filename;

        // 11. Dates
        $date_enrolement = date('Y-m-d H:i:s');
        $date_dernier_grade = $date_dernier_grade ?? null; // déjà validée dans la première partie

        // 12. QR code
        $code_qr = !empty($matricule) ? generateQRCodeForMatricule($matricule) : '';

        // 13. Insertion PostgreSQL
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
            'date_naissance' => $date_naissance_sql, // format YYYY-MM-DD
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
            'photo' => $photo_path_rel,
            'date_enrolement' => $date_enrolement,
            'date_dernier_grade' => $date_dernier_grade,
            'code_qr' => $code_qr
        ]);

        // Nettoyage session
        unset($_SESSION['form_data']);

        // Réponse JSON
        $_SESSION['candidat_enrolled'] = [
            'matricule' => $matricule,
            'matricule_militaire' => $_POST['matricule_militaire'] ?? '',
            'nom' => strtoupper(trim($_POST['nom'])),
            'prenom' => strtoupper(trim($_POST['prenom'])),
            'date_naissance' => $date_naissance_sql,
            'sexe' => $_POST['sexe'],
            'unite' => $_POST['unite'],
            'grade' => $_POST['grade'],
            'photo' => $photo_path_rel,
            'numero_cni' => $numero_cni,
            'date_enrolement' => $date_enrolement,
            'code_qr' => $code_qr
        ];

        header('Content-Type: application/json');
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Candidat enrôlé avec succès !',
            'matricule' => $matricule,
            'code_qr' => $code_qr,
            'candidat' => $_SESSION['candidat_enrolled']
        ]);

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Content-Type: application/json');
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'form_data' => $_SESSION['form_data'] ?? []
        ]);
    }
} else {
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
}
?>
