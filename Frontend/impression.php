<?php
// Démarrer la session AVANT tout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Activer l'affichage des erreurs AVANT tout (critique pour InfinityFree)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers CORS pour InfinityFree (APRÈS les tests)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Frame-Options: SAMEORIGIN');

// Fonction simple pour afficher les erreurs avec lignes (compatible InfinityFree)
function showError($message, $file = '', $line = 0) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; margin: 10px; border-radius: 5px; font-family: monospace;'>";
    echo "<strong>ERREUR DÉTECTÉE:</strong><br>";
    echo "Message: $message<br>";
    if ($file) echo "Fichier: $file<br>";
    if ($line) echo "Ligne: $line<br>";
    echo "<small>URL: " . $_SERVER['REQUEST_URI'] . "</small>";
    echo "<br><strong>CONSEIL:</strong> Vérifiez cette ligne dans le fichier";
    echo "</div>";
}

// Test simple pour vérifier si le fichier fonctionne
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true); // Mettre à false en production
}

// Activer l'affichage des erreurs AVANT tout (critique pour InfinityFree)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test des require avec chemins absolus
try {
    require_once __DIR__ . '/../backend/config.php';
} catch (Exception $e) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
    echo "ERREUR CONFIG.PHP: " . $e->getMessage() . "<br>";
    echo "Ligne: " . __LINE__ . " dans " . __FILE__;
    echo "</div>";
    exit;
}

try {
    require_once __DIR__ . '/../pdf/CarteMilitaire.php';
} catch (Exception $e) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
    echo "ERREUR CARTEMILITAIRE.PHP: " . $e->getMessage() . "<br>";
    echo "Ligne: " . __LINE__ . " dans " . __FILE__;
    echo "</div>";
    exit;
}

// Test de connexion à la base de données
if (!isset($pdo) || !$pdo) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
    echo "ERREUR PDO: PDO non défini après config.php<br>";
    echo "Ligne: " . __LINE__ . " dans " . __FILE__;
    echo "</div>";
    exit;
}

// Test simple de connexion BDD
try {
    $test = $pdo->query("SELECT 1");
    if (!$test) {
        echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
        echo "ERREUR CONNEXION BDD: query('SELECT 1') a échoué<br>";
        echo "Ligne: " . __LINE__ . " dans " . __FILE__;
        echo "</div>";
        exit;
    }
} catch (PDOException $e) {
    echo "<div style='background: #ff6b6b; color: white; padding: 15px; font-family: monospace;'>";
    echo "ERREUR PDO: " . $e->getMessage() . "<br>";
    echo "Ligne: " . __LINE__ . " dans " . __FILE__;
    echo "</div>";
    exit;
}

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Traitement de la visualisation
if (isset($_GET['action']) && $_GET['action'] == 'visualize') {
    $matricule = $_GET['matricule'] ?? '';
    
    if (!empty($matricule)) {
        // Rediriger vers la page de visualisation avec le matricule
        header('Location: visualiser_carte.php?matricule=' . urlencode($matricule));
        exit;
    }
    
    header('Location: impression.php');
    exit;
}

// Traitement de la génération PDF
if (isset($_GET['action']) && $_GET['action'] == 'generate') {
    $matricule = $_GET['matricule'] ?? '';
    
    if (!empty($matricule)) {
        // Utiliser la nouvelle classe CarteMilitaire
        $carte = new CarteMilitaire($matricule);
        $carte->genererPDF();
        exit;
    }
    
    header('Location: impression.php');
    exit;
}

// Traitement de la génération PDF multiple
if (isset($_GET['action']) && $_GET['action'] == 'generate_batch') {
    $matricules = $_GET['matricules'] ?? '';
    if (!empty($matricules)) {
        $matriculesArray = is_array($matricules) ? $matricules : explode(',', $matricules);
        
        // Utiliser la méthode statique pour générer le PDF multiple
        CarteMilitaire::genererPDFMultiple($matriculesArray);
        exit;
    }
    
    header('Location: impression.php');
    exit;
}

// Traitement de la suppression multiple
if (isset($_POST['action']) && $_POST['action'] == 'delete_multiple') {
    $ids = $_POST['ids'] ?? [];
    if (!empty($ids) && is_array($ids)) {
        $placeholders = str_repeat('?,', count($ids));
        $placeholders = rtrim($placeholders, ',');
        
        // Suppression soft : mettre à jour les attributs au lieu de supprimer
        $sql = "UPDATE candidat SET supprimer = 1, supprimer_par = ?, date_suppression = NOW() WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $params = array_merge([$_SESSION['username']], $ids);
        $stmt->execute($params);
        
        $_SESSION['success'] = count($ids) . " carte(s) déplacée(s) dans la corbeille / " . count($ids) . " card(s) moved to trash";
    } else {
        $_SESSION['error'] = "Aucune carte sélectionnée pour la suppression / No card selected for deletion";
    }
    
    header('Location: impression.php');
    exit;
}

// Traitement AJAX pour la recherche dynamique
if (isset($_POST['ajax_search'])) {
    // Récupérer les personnels avec filtres dynamiques
    $where = [];
    $params = [];

    // Filtre par nom/prénom
    if (!empty($_POST['search_nom'])) {
        $where[] = "(nom LIKE :search_nom OR prenom LIKE :search_nom)";
        $params['search_nom'] = '%' . $_POST['search_nom'] . '%';
    }

    // Filtre par matricule
    if (!empty($_POST['search_matricule'])) {
        $where[] = "matricule LIKE :search_matricule";
        $params['search_matricule'] = '%' . $_POST['search_matricule'] . '%';
    }

    // Filtre par grade
    if (!empty($_POST['search_grade'])) {
        $where[] = "grade = :search_grade";
        $params['search_grade'] = $_POST['search_grade'];
    }

    // Filtre par unité
    if (!empty($_POST['search_unite'])) {
        $where[] = "unite = :search_unite";
        $params['search_unite'] = $_POST['search_unite'];
    }

    // Filtre par année de dernier grade
    if (!empty($_POST['search_annee'])) {
        $where[] = "YEAR(date_dernier_grade) = :search_annee";
        $params['search_annee'] = $_POST['search_annee'];
    }

    // Filtre par CNI
    if (!empty($_POST['search_cni'])) {
        $where[] = "numero_cni LIKE :search_cni";
        $params['search_cni'] = '%' . $_POST['search_cni'] . '%';
    }

    // Construire la requête
    $sql = "SELECT id, matricule, nom, prenom, unite, grade, photo, numero_cni, date_dernier_grade, suspendus FROM candidat WHERE supprimer = 1";
    if (!empty($where)) {
        $sql .= " AND " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY date_enrolement DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Afficher uniquement la grille des personnels
    echo '<div id="personnelsContainer">'; // Ajouter le conteneur
    if (empty($personnels)) {
        echo '<div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <h4>Aucun personnel trouvé / No personnel found</h4>
                <p>Essayez de modifier vos critères de recherche / Try modifying your search criteria</p>
              </div>';
    } else {
        foreach ($personnels as $personnel) {
            echo '<div class="personnel-item' . ($personnel['suspendus'] == 1 ? ' suspended' : '') . '">
                    <div class="personnel-checkbox-wrapper">
                        <input type="checkbox" name="ids[]" value="' . htmlspecialchars($personnel['id']) . '" 
                               class="personnel-checkbox" id="personnel_' . $personnel['id'] . '">
                        <label for="personnel_' . $personnel['id'] . '" class="checkbox-label"></label>
                    </div>';
                    
                    // Indicateur de suspension
                    if ($personnel['suspendus'] == 1) {
                        echo '<div class="suspension-indicator" title="Membre suspendu">
                                <i class="fa-solid fa-pause"></i>
                              </div>';
                    }
                    
                    echo '<div class="personnel-photo">';
            if (!empty($personnel['photo'])) {
                // Gestion du chemin de la photo - SANS file_exists() pour InfinityFree
                $photo_path = '../' . $personnel['photo'];
                echo '<img src="' . $photo_path . '" alt="Photo" onerror="this.src=\'../img/candidats/default.png\'">';
            } else {
                // Utiliser une photo par défaut aléatoire depuis la liste spécifiée
                $default_photos = [
                    'img/1ONANA.PNG',
                    'img/1YANNICK.PNG', 
                    'img/GRACE.PNG',
                    'img/KRISS.PNG',
                    'img/ONANA.PNG',
                    'img/YANNICK.PNG'
                ];
                $random_photo = $default_photos[array_rand($default_photos)];
                echo '<img src="../' . $random_photo . '" alt="Photo par défaut">';
            }
            echo '</div>
                    <div class="personnel-info">
                        <div class="personnel-name">' . htmlspecialchars($personnel['nom'] . ' ' . $personnel['prenom']) . '</div>
                        <div class="personnel-details">
                            <span class="badge badge-' . strtolower(str_replace(' ', '-', $personnel['unite'])) . '">
                                ' . htmlspecialchars($personnel['unite']) . '
                            </span>
                            <span class="matricule">' . htmlspecialchars($personnel['matricule']) . '</span>
                        </div>
                        <div class="personnel-grade">' . htmlspecialchars($personnel['grade']) . '</div>
                    </div>
                    <div class="personnel-actions">
                        <a href="impression.php?action=visualize&matricule=' . urlencode($personnel['matricule']) . '" 
                           class="btn btn-sm" title="Visualiser la carte">
                            <i class="fa-solid fa-id-card"></i>
                        </a>
                        <a href="modifier_candidat.php?id=' . $personnel['id'] . '" 
                           class="btn btn-sm" style="background: linear-gradient(135deg, #ff8c00, #e67e00); color: white;" title="Modifier les informations">
                            <i class="fa-solid fa-edit"></i>
                        </a>
                        <a href="impression.php?action=generate&matricule=' . urlencode($personnel['matricule']) . '" 
                           class="btn btn-sm" style="background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white;" title="Imprimer la carte (PDF)">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                        <a href="impression_pvc.php?matricule=' . urlencode($personnel['matricule']) . '&mode=recto-verso" 
                           class="btn btn-sm" style="background: linear-gradient(135deg, #007bff, #0056b3); color: white;" title="Impression PVC Optimisée (85.60×53.98mm - 0 marge)">
                            <i class="fa-solid fa-credit-card"></i> <span style="font-size: 10px;">PVC</span>
                        </a>
                        <a href="../visualisation_3d.php?matricule=' . urlencode($personnel['matricule']) . '" 
                           class="btn btn-sm btn-info" title="Visualiser la carte en 3D" target="_blank">
                            <i class="fa-solid fa-cube"></i>
                        </a>
                        <button class="btn btn-sm btn-logout" title="Supprimer" style="padding: 2px;"
                                onclick="confirmDelete(\'' . $personnel['id'] . '\')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>';
        }
    }
    echo '</div>'; // Fermer le conteneur
    
    // Afficher les actions principales en haut de la page
    echo '<div class="main-actions" style="margin-bottom: 2rem; padding: 1.5rem; background: linear-gradient(135deg, rgba(74, 222, 128, 0.1) 0%, rgba(74, 222, 128, 0.05) 100%); border-radius: 15px; text-align: center;">
        <button class="btn" onclick="selectAll()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            <i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL
        </button>
        <button class="btn btn-primary" onclick="visualizeMultiple()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            <i class="fa-solid fa-eye"></i> VISUALISER SÉLECTION / VIEW SELECTION
        </button>
        <button class="btn" onclick="generateBatch()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #ffc107, #ff8c00); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            <i class="fa-solid fa-file-pdf"></i> GÉNÉRER PDF MULTIPLE / GENERATE MULTIPLE PDF
        </button>
        <button class="btn btn-logout" onclick="deleteSelected()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            <i class="fa-solid fa-trash"></i> SUPPRIMER SÉLECTION / DELETE SELECTION
        </button>
    </div>';

    
    exit;
}

// Traitement export CSV
if (isset($_POST['export'])) {
    // Récupérer les candidats avec filtres
    $where = [];
    $params = [];

    if (!empty($_POST['search_nom'])) {
        $where[] = "(nom LIKE :search_nom OR prenom LIKE :search_nom)";
        $params['search_nom'] = '%' . $_POST['search_nom'] . '%';
    }

    if (!empty($_POST['search_matricule'])) {
        $where[] = "matricule LIKE :search_matricule";
        $params['search_matricule'] = '%' . $_POST['search_matricule'] . '%';
    }

    if (!empty($_POST['search_grade'])) {
        $where[] = "grade = :search_grade";
        $params['search_grade'] = $_POST['search_grade'];
    }

    if (!empty($_POST['search_unite'])) {
        $where[] = "unite = :search_unite";
        $params['search_unite'] = $_POST['search_unite'];
    }

    if (!empty($_POST['search_annee'])) {
        $where[] = "YEAR(date_dernier_grade) = :search_annee";
        $params['search_annee'] = $_POST['search_annee'];
    }

    if (!empty($_POST['search_cni'])) {
        $where[] = "numero_cni LIKE :search_cni";
        $params['search_cni'] = '%' . $_POST['search_cni'] . '%';
    }

    $sql = "SELECT matricule, nom, prenom, grade, unite, numero_cni, date_dernier_grade FROM candidat WHERE supprimer = 1";
    if (!empty($where)) {
        $sql .= " AND " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY date_enrolement DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Générer le CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="candidats_cimis_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, ['Matricule', 'Nom', 'Prénom', 'Grade', 'Unité', 'CNI', 'Date Dernier Grade']);
    
    // Données
    foreach ($personnels as $candidat) {
        fputcsv($output, [
            $candidat['matricule'],
            $candidat['nom'],
            $candidat['prenom'],
            $candidat['grade'],
            $candidat['unite'],
            $candidat['numero_cni'] ?? '',
            $candidat['date_dernier_grade'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}
// Fonction pour obtenir le nombre total de personnels
function getTotalPersonnels() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM candidat WHERE supprimer = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

$where = [];
$params = [];

// Filtre par nom/prénom
if (!empty($_GET['search_nom'])) {
    $where[] = "(nom LIKE :search_nom OR prenom LIKE :search_nom)";
    $params['search_nom'] = '%' . $_GET['search_nom'] . '%';
}

// Filtre par matricule
if (!empty($_GET['search_matricule'])) {
    $where[] = "matricule LIKE :search_matricule";
    $params['search_matricule'] = '%' . $_GET['search_matricule'] . '%';
}

// Filtre par grade
if (!empty($_GET['search_grade'])) {
    $where[] = "grade = :search_grade";
    $params['search_grade'] = $_GET['search_grade'];
}

// Filtre par unité
if (!empty($_GET['search_unite'])) {
    $where[] = "unite = :search_unite";
    $params['search_unite'] = $_GET['search_unite'];
}

// Filtre par année de dernier grade
if (!empty($_GET['search_annee'])) {
    $where[] = "YEAR(date_dernier_grade) = :search_annee";
    $params['search_annee'] = $_GET['search_annee'];
}

// Filtre par CNI
if (!empty($_GET['search_cni'])) {
    $where[] = "numero_cni LIKE :search_cni";
    $params['search_cni'] = '%' . $_GET['search_cni'] . '%';
}

// Construire la requête
$sql = "SELECT id, matricule, nom, prenom, unite, grade, photo, numero_cni, date_dernier_grade, suspendus FROM candidat WHERE supprimer = 1";
if (!empty($where)) {
    $sql .= " AND " . implode(' AND ', $where);
}
$sql .= " ORDER BY date_enrolement DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier les données - CIMIS</title>
    <link rel="stylesheet" href="../css/enrolement.css">
    <link rel="stylesheet" href="../css/bouton-retour.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .candidat-item.suspended {
            border: 2px solid #dc3545 !important;
            background: rgba(220, 53, 69, 0.1) !important;
            position: relative;
        }
        
        .candidat-item.suspended::before {
            content: 'SUSPENDU';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        
        .suspension-indicator {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #dc3545;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            z-index: 10;
        }
        
        .candidat-item.suspended .candidat-photo {
            opacity: 0.7;
            filter: grayscale(50%);
        }
        
        .candidat-item.suspended .candidat-name {
            color: #dc3545;
            font-weight: bold;
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

        <!-- BOUTON RETOUR VERS DASHBOARD -->
        <div class="back-button-container">
            <a href="dashboard.php" class="btn-back btn-back-dashboard">
                <i class="fa-solid fa-arrow-left"></i>
                <span>RETOUR</span>
            </a>
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

                <!-- SEARCH AND FILTER SECTION -->
                <div class="search-section">
                    <div class="search-header" style="text-align: center;">
                        <h3><i class="fa-solid fa-search"></i> RECHERCHE AVANCÉE / ADVANCED SEARCH</h3>
                    </div>
                    
                    <div class="search-grid">
                        <!-- LIGNE 1 : 3 champs -->
                        <div class="search-group">
                            <label for="search_nom">Recherche Nom/Prénom / Search Name/First Name</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-search"></i>
                                <input type="text" id="search_nom" name="search_nom" 
                                       placeholder="saisir au moins 2 carractere / enter at least 2 characters" 
                                       value="<?php echo htmlspecialchars($_GET['search_nom'] ?? ''); ?>">
                                <div class="search-results" id="nom_results"></div>
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_matricule">Matricule / ID Number</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-id-card"></i>
                                <input type="text" id="search_matricule" name="search_matricule" 
                                       placeholder="Ex: CIM-12345 / Ex: CIM-12345" 
                                       value="<?php echo htmlspecialchars($_GET['search_matricule'] ?? ''); ?>">
                                <div class="unit-indicator" id="unit_indicator"></div>
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_grade">Grade / Rank</label>
                            <div class="search-input-wrapper">
                                <select id="search_grade" name="search_grade">
                                    <option value="">Tous les grades / All ranks</option>
                                    <?php
                                    $grades = [
                                        'SOLDAT DE 2EME CLASSE',
                                        'SOLDAT DE 1EME CLASSE', 
                                        'CAPORAL',
                                        'CAPORAL-CHEF',
                                        'SERGENT',
                                        'SERGENT-CHEF',
                                        'ADJOINT',
                                        'MAJOR',
                                        'SOUS-LIEUTENANT',
                                        'LIEUTENANT',
                                        'CAPITAINE',
                                        'COMMANDANT',
                                        'LIEUTENANT-COLONEL',
                                        'COLONEL',
                                        'GÉNÉRAL DE BRIGADE',
                                        'GÉNÉRAL DE DIVISION',
                                        'GÉNÉRAL DE CORPS D\'ARMÉE',
                                        'GÉNÉRAL D\'ARMÉE'
                                    ];
                                    foreach ($grades as $grade) {
                                        $selected = (isset($_GET['search_grade']) && $_GET['search_grade'] === $grade) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($grade) . "' $selected>" . htmlspecialchars($grade) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- LIGNE 2 : 3 champs -->
                        <div class="search-group">
                            <label for="search_unite">Unité / Unit</label>
                            <div class="search-input-wrapper">
                                <select id="search_unite" name="search_unite">
                                    <option value="">Toutes les unités / All units</option>
                                    <option value="GENDARMERIE NATIONALE" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'GENDARMERIE NATIONALE') ? 'selected' : ''; ?>>GENDARMERIE NATIONALE / National Gendarmerie</option>
                                    <option value="ARMÉE DE TERRE" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'ARMÉE DE TERRE') ? 'selected' : ''; ?>>ARMÉE DE TERRE / Army</option>
                                    <option value="ARMÉE DE L'AIR" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'ARMÉE DE L\'AIR') ? 'selected' : ''; ?>>ARMÉE DE L'AIR / Air Force</option>
                                    <option value="MARINE NATIONALE" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'MARINE NATIONALE') ? 'selected' : ''; ?>>MARINE NATIONALE / Navy</option>
                                    <option value="CIVIL" <?php echo (isset($_GET['search_unite']) && $_GET['search_unite'] === 'CIVIL') ? 'selected' : ''; ?>>CIVIL / Civilian</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_annee">Année de la dernière promotion au grade / Year of last promotion</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-calendar"></i>
                                <input type="number" id="search_annee" name="search_annee" 
                                       placeholder="Ex: 2023 / Ex: 2023" min="2000" max="<?php echo date('Y'); ?>"
                                       value="<?php echo htmlspecialchars($_GET['search_annee'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="search-group">
                            <label for="search_cni">Numéro CNI / CNI Number</label>
                            <div class="search-input-wrapper">
                                <i class="fa-solid fa-id-badge"></i>
                                <input type="text" id="search_cni" name="search_cni" 
                                       placeholder="Numéro CNI... / CNI Number..." 
                                       value="<?php echo htmlspecialchars($_GET['search_cni'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                                    </div>

                <!-- ACTIONS PRINCIPALES -->
                <div class="main-actions" style="margin-bottom: 2rem; padding: 1.5rem; background: linear-gradient(135deg, rgba(74, 222, 128, 0.1) 0%, rgba(74, 222, 128, 0.05) 100%); border-radius: 15px; text-align: center;">
                    <button class="btn" onclick="selectAll()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL
                    </button>
                    <button class="btn btn-primary" onclick="visualizeMultiple()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-eye"></i> VISUALISER SÉLECTION / VIEW SELECTION
                    </button>
                    <button class="btn" onclick="generateBatch()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #ffc107, #ff8c00); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-file-pdf"></i> GÉNÉRER PDF MULTIPLE / GENERATE MULTIPLE PDF
                    </button>
                    <button class="btn" onclick="generateBatchPVC()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-credit-card"></i> IMPRESSION PVC MULTIPLE / MULTIPLE PVC PRINT
                    </button>
                    <button class="btn btn-logout" onclick="deleteSelected()" style="margin: 0.5rem; padding: 0.8rem 1.5rem; background: linear-gradient(135deg, #dc3545, #c82333); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-trash"></i> SUPPRIMER SÉLECTION / DELETE SELECTION
                    </button>
                </div>

                <!-- COMPTEUR DE RÉSULTATS -->
                <div class="results-section">
                    <div class="result-count-display">
                        <span id="result-count" class="result-number">Chargement...</span>
                        <span class="result-text">résultats trouvés / results found</span>
                    </div>
                </div>

                <!-- Liste des candidats -->
                <div class="module-card">
                    <div class="candidats-header">
                        <h3><i class="fa-solid fa-id-card"></i> CARTES DISPONIBLES / AVAILABLE CARDS</h3>
                        <div class="view-options">
                            <button class="btn btn-sm" onclick="toggleView('grid')" id="gridViewBtn">
                                <i class="fa-solid fa-th"></i>
                            </button>
                            <button class="btn btn-sm" onclick="toggleView('list')" id="listViewBtn">
                                <i class="fa-solid fa-list"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="personnels-grid" id="personnelsContainer">
                        <?php if (empty($personnels)): ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <h4>Aucun personnel trouvé / No personnel found</h4>
                                <p>Essayez de modifier vos critères de recherche ou <a href="../enrolement.php">ajoutez un nouveau personnel</a> / Try modifying your search criteria or <a href="../enrolement.php">add new personnel</a></p>
                                <div class="empty-stats">
                                    <span><i class="fa-solid fa-database"></i> Base de données: <?php echo getTotalPersonnels(); ?> personnels / Database: <?php echo getTotalPersonnels(); ?> personnel</span>
                                    <span><i class="fa-solid fa-clock"></i> Dernière mise à jour: <?php echo date('d/m/Y H:i'); ?> / Last update: <?php echo date('d/m/Y H:i'); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($personnels as $personnel): ?>
                                <div class="personnel-item<?php echo $personnel['suspendus'] == 1 ? ' suspended' : ''; ?>">
                                    <div class="personnel-checkbox-wrapper">
                                        <input type="checkbox" name="selected_personnels[]" value="<?php echo $personnel['matricule']; ?>" 
                                               class="personnel-checkbox" id="personnel_<?php echo $personnel['id']; ?>">
                                        <label for="personnel_<?php echo $personnel['id']; ?>" class="checkbox-label"></label>
                                    </div>
                                    
                                    <?php 
                                    // Indicateur de suspension
                                    if ($personnel['suspendus'] == 1) {
                                        echo '<div class="suspension-indicator" title="Membre suspendu">
                                                <i class="fa-solid fa-pause"></i>
                                              </div>';
                                    }
                                    ?>
                                    
                                    <div class="personnel-photo">
                                        <?php if (!empty($personnel['photo'])): ?>
                                            <?php
                                            // Gestion du chemin de la photo - SANS file_exists() pour InfinityFree
                                            $photo_path = '../' . $personnel['photo'];
                                            ?>
                                            <img src="<?php echo $photo_path; ?>" alt="Photo" onerror="this.src='../img/candidats/default.png'">
                                        <?php else: ?>
                                            <?php 
                                            // Utiliser une photo par défaut aléatoire depuis la liste spécifiée
                                            $default_photos = [
                                                'img/1ONANA.PNG',
                                                'img/1YANNICK.PNG', 
                                                'img/GRACE.PNG',
                                                'img/KRISS.PNG',
                                                'img/ONANA.PNG',
                                                'img/YANNICK.PNG'
                                            ];
                                            $random_photo = $default_photos[array_rand($default_photos)];
                                            ?>
                                            <img src="<?php echo '../' . $random_photo; ?>" alt="Photo par défaut">
                                        <?php endif; ?>
                                    </div>
                                    <div class="personnel-info">
                                        <div class="personnel-name"><?php echo htmlspecialchars($personnel['nom'] . ' ' . $personnel['prenom']); ?></div>
                                        <div class="personnel-details">
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $personnel['unite'])); ?>">
                                                <?php echo htmlspecialchars($personnel['unite']); ?>
                                            </span>
                                            <span class="matricule"><?php echo htmlspecialchars($personnel['matricule']); ?></span>
                                        </div>
                                        <div class="personnel-grade"><?php echo htmlspecialchars($personnel['grade']); ?></div>
                                    </div>
                                    <div class="personnel-actions">
                                        <a href="impression.php?action=visualize&matricule=<?php echo urlencode($personnel['matricule']); ?>" 
                                           class="btn btn-sm" title="Visualiser la carte">
                                            <i class="fa-solid fa-id-card"></i>
                                        </a>
                                        <a href="modifier_candidat.php?id=<?php echo $personnel['id']; ?>" 
                                           class="btn btn-sm" style="background: linear-gradient(135deg, #ff8c00, #e67e00); color: white;" title="Modifier les informations">
                                            <i class="fa-solid fa-edit"></i>
                                        </a>
                                        <a href="impression.php?action=generate&matricule=<?php echo urlencode($personnel['matricule']); ?>" 
                                           class="btn btn-sm" style="background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white;" title="Imprimer la carte (PDF)">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                        <a href="impression_pvc.php?matricule=<?php echo urlencode($personnel['matricule']); ?>&mode=recto-verso" 
                                           class="btn btn-sm" style="background: linear-gradient(135deg, #007bff, #0056b3); color: white;" title="Impression PVC Optimisée (85.60×53.98mm - 0 marge)">
                                            <i class="fa-solid fa-credit-card"></i> <span style="font-size: 10px;">PVC</span>
                                        </a>
                                        <a href="visualisation_3d.php?matricule=<?php echo urlencode($personnel['matricule']); ?>" 
                                           class="btn btn-sm btn-info" title="Visualiser la carte en 3D" target="_blank">
                                            <i class="fa-solid fa-cube"></i>
                                        </a>
                                        <button class="btn btn-sm btn-logout" title="Supprimer" style="padding: 2px;"
                                                onclick="confirmDelete('<?php echo $personnel['id']; ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($personnels)): ?>
                        <div class="batch-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid rgba(74, 222, 128, 0.3);">
                            <button class="btn" onclick="selectAll()">
                                <i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL
                            </button>
                            <button class="btn btn-primary" onclick="visualizeMultiple()">
                                <i class="fa-solid fa-eye"></i> VISUALISER SÉLECTION / VIEW SELECTION
                            </button>
                            <button class="btn" onclick="generateBatch()">
                                <i class="fa-solid fa-file-pdf"></i> GÉNÉRER PDF MULTIPLE / GENERATE MULTIPLE PDF
                            </button>
                            <button class="btn btn-logout" onclick="deleteSelected()" style="padding: 2px;">
                                <i class="fa-solid fa-trash"></i> suprimer sélection / Delete selection
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- FOOTER -->
        <footer class="security-footer">
            <div class="footer-version">SYSTÈME SÉCURISÉ</div>
            <div class="footer-left">
                <span><i class="fa-solid fa-shield-alt"></i> SYSTÈME DE GESTION DES CARTES D'IDENTITÉ</span>
                <span><i class="fa-solid fa-lock"></i> Connexion sécurisée</span>
            </div>
            <div class="footer-center">
                <!-- Bouton Corbeille -->
                <a href="corbeille.php" class="trash-btn" title="Corbeille / Trash">
                    <i class="fa-solid fa-trash-can"></i>
                    <span id="trash-count" style="display: none;" class="trash-count">0</span>
                </a>
            </div>
            <div class="footer-right">
                <span id="footer-clock" class="text-mono">00:00:00</span>
                <span><i class="fa-solid fa-server"></i> Serveur: ACTIF</span>
            </div>
        </footer>

        <style>
        .footer-center {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .trash-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: 2px solid #dc3545;
            border-radius: 50%;
            text-decoration: none;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .trash-btn:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
            text-decoration: none;
        }

        .trash-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff6b6b;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            border: 2px solid white;
            min-width: 18px;
            text-align: center;
            line-height: 1;
        }
        </style>

        <script>
        // Fonction pour charger le compteur de la corbeille
        function loadTrashCount() {
            fetch('get_trash_count.php')
                .then(response => response.json())
                .then(data => {
                    const trashCount = document.getElementById('trash-count');
                    if (data.count > 0) {
                        trashCount.textContent = data.count;
                        trashCount.style.display = 'block';
                    } else {
                        trashCount.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement du compteur de corbeille:', error);
                });
        }

        // Charger le compteur au chargement de la page
        document.addEventListener('DOMContentLoaded', loadTrashCount);

        // Mettre à jour le compteur après chaque suppression avec rechargement automatique
        const originalDeleteSelected = window.deleteSelected;
        window.deleteSelected = function() {
            originalDeleteSelected.apply(this, arguments);
            // Mettre à jour le compteur immédiatement
            loadTrashCount();
            // Recharger la page après 1.5 secondes pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        };

        // Mettre à jour le compteur après chaque suppression individuelle
        const originalExecuteDelete = window.executeDelete;
        window.executeDelete = function() {
            originalExecuteDelete.apply(this, arguments);
            // Mettre à jour le compteur immédiatement
            loadTrashCount();
            // Recharger la page après 1.5 secondes pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        };
        </script>

    </div>

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

        // --- SELECTION FUNCTIONS ---
        function selectAll() {
            const checkboxes = document.querySelectorAll('.personnel-checkbox');
            const selectAll = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => cb.checked = !selectAll);
        }

        function generateBatch() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            // Rediriger vers la génération multiple
            const matricules = Array.from(selected).map(cb => cb.value);
            window.location.href = 'generate_pdf.php?matricules=' + matricules.join(',');
        }

        function visualizeMultiple() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            if (selected.length === 1) {
                // Si un seul candidat, rediriger vers la visualisation simple
                const matricule = selected[0].value;
                window.location.href = 'impression.php?action=visualize&matricule=' + matricule;
            } else {
                // Si plusieurs candidats, rediriger vers la visualisation multiple
                const matricules = Array.from(selected).map(cb => cb.value);
                window.location.href = 'visualiser_multiple.php?matricules=' + matricules.join(',');
            }
        }

        function visualizeUniform() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            // Rediriger vers la visualisation uniforme avec les matricules sélectionnés
            const matricules = Array.from(selected).map(cb => cb.value);
            window.location.href = 'visualiser_cartes_uniformes.php?matricules=' + matricules.join(',');
        }

        function deleteSelected() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            if (confirm(`Êtes-vous sûr de vouloir déplacer les ${selected.length} carte(s) sélectionnée(s) dans la corbeille ?\n\nAre you sure you want to move the ${selected.length} selected card(s) to trash?`)) {
                // Récupérer les IDs des candidats sélectionnés (maintenant checkbox.value contient l'ID)
                const ids = [];
                selected.forEach(checkbox => {
                    ids.push(checkbox.value);
                });
                
                // Créer un formulaire et soumettre les données
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'impression.php';
                
                // Ajouter l'action
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_multiple';
                form.appendChild(actionInput);
                
                // Ajouter les IDs
                ids.forEach(id => {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'ids[]';
                    idInput.value = id;
                    form.appendChild(idInput);
                });
                
                // Soumettre le formulaire
                document.body.appendChild(form);
                form.submit();
            }
        }

        function confirmDelete(id) {
            // Créer une modal de confirmation moderne
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: #1a1a1a; padding: 2rem; border-radius: 10px; border: 2px solid var(--neon-red); max-width: 400px; text-align: center;">
                    <h3 style="color: var(--neon-red); margin-bottom: 1rem; font-size: 1.2rem;">
                        <i class="fa-solid fa-exclamation-triangle"></i> DÉPLACER DANS LA CORBEILLE / MOVE TO TRASH
                    </h3>
                    <p style="color: white; margin-bottom: 1.5rem; font-size: 1.1rem;">
                        Êtes-vous sûr de vouloir déplacer cette carte dans la corbeille ?<br>
                        Are you sure you want to move this card to trash?<br><br>
                        <strong>Vous pourrez la restaurer depuis la corbeille / You can restore it from trash.</strong>
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button onclick="this.closest('div').remove()" style="background: #666; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                            <i class="fa-solid fa-times"></i> ANNULER
                        </button>
                        <button onclick="executeDelete(${id})" style="background: var(--neon-red); color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                            <i class="fa-solid fa-trash"></i> SUPPRIMER
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Fermer la modal en cliquant à l'extérieur
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
        
        function executeDelete(id) {
            // Afficher un indicateur de chargement
            const loadingModal = document.createElement('div');
            loadingModal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.9);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10001;
            `;
            
            loadingModal.innerHTML = `
                <div style="background: #1a1a1a; padding: 2rem; border-radius: 10px; border: 2px solid var(--neon-green); text-align: center;">
                    <i class="fa-solid fa-spinner fa-spin" style="color: var(--neon-green); font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p style="color: white;">Suppression de la carte en cours...</p>
                </div>
            `;
            
            document.body.appendChild(loadingModal);
            
            // Fermer la modal de confirmation
            document.querySelector('[style*="z-index: 10000"]').remove();
            
            // Effectuer la suppression via AJAX
            fetch('delete_candidat.php?id=' + id, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingModal.remove();
                
                if (data.success) {
                    // Afficher une notification de succès
                    showNotification(data.message, 'success');
                    
                    // Supprimer la carte du DOM
                    const candidatCard = document.querySelector(`[onclick*="${id}"]`).closest('.candidat-item');
                    if (candidatCard) {
                        candidatCard.style.transition = 'all 0.3s ease';
                        candidatCard.style.opacity = '0';
                        candidatCard.style.transform = 'scale(0.8)';
                        
                        setTimeout(() => {
                            candidatCard.remove();
                            updateResultCount();
                        }, 300);
                    }
                } else {
                    // Afficher une notification d'erreur
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                loadingModal.remove();
                console.error('Erreur:', error);
                showNotification('Erreur lors de la suppression de la carte', 'error');
            });
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? 'var(--neon-green)' : 'var(--neon-red)'};
                color: ${type === 'success' ? 'black' : 'white'};
                padding: 1rem;
                border-radius: 5px;
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2rem;">
                    <i class="fa-solid fa-times"></i>
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
        
        // Animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);

        // --- RECHERCHE DYNAMIQUE AVANCÉE ---
        let searchTimeout;
        let currentView = 'grid';
        let selectedCandidates = new Set();
        
        // Mettre à jour le compteur de résultats
        function updateResultCount() {
            const container = document.getElementById('personnelsContainer');
            const count = container.querySelectorAll('.personnel-item').length;
            document.getElementById('result-count').textContent = count;
        }
        
        // Basculer entre vue grille et liste
        function toggleView(view) {
            currentView = view;
            const container = document.getElementById('personnelsContainer');
            const gridBtn = document.getElementById('gridViewBtn');
            const listBtn = document.getElementById('listViewBtn');
            
            if (view === 'grid') {
                container.className = 'personnels-grid';
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
            } else {
                container.className = 'personnels-list';
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
            }
        }
        
        // Imprimer la sélection au format PVC optimisé
        function printSelected() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            // Récupérer tous les matricules
            const matricules = Array.from(selected).map(cb => cb.value);
            
            if (confirm(`Imprimer les ${selected.length} carte(s) sélectionnée(s) au format PVC (85.60×53.98mm) ?\n\nFormat: Carte PVC sans marges\nPages: ${selected.length * 2} (recto + verso)\nToutes les cartes sur une seule page d'impression`)) {
                window.open(`impression_pvc_multiple.php?matricules=${encodeURIComponent(matricules)}`, '_blank');
            }
        }
        
        // Visualiser 3D la sélection
        function visualize3DSelected() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            if (confirm(`Visualiser en 3D les ${selected.length} candidat(s) sélectionné(s) ?`)) {
                selected.forEach(checkbox => {
                    const matricule = checkbox.value;
                    window.open(`visualisation_3d.php?matricule=${encodeURIComponent(matricule)}`, '_blank');
                });
            }
        }
        
        // Gestion de la sélection multiple
        function updateSelection() {
            selectedCandidates.clear();
            const checkboxes = document.querySelectorAll('.candidat-checkbox:checked');
            checkboxes.forEach(checkbox => selectedCandidates.add(checkbox.value));
            
            // Mettre à jour l'état des boutons batch
            const batchActions = document.querySelector('.batch-actions');
            if (batchActions) {
                batchActions.style.display = selectedCandidates.size > 0 ? 'flex' : 'none';
            }
        }
        
        // Recherche instantanée par nom/prénom avec suggestions
        document.getElementById('search_nom').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                if (value.length >= 2 || value.length === 0) {
                    performSearch();
                    showSuggestions(value, 'nom_results');
                }
            }, 300);
        });

        // Recherche instantanée par matricule avec détection d'unité et validation
        document.getElementById('search_matricule').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();
            
            // Validation du format du matricule
            if (value.length > 0 && !value.match(/^CIM-\d{5}$/)) {
                this.setCustomValidity('Format attendu: CIM-12345');
            } else {
                this.setCustomValidity('');
            }
            
            // Détecter l'unité selon le préfixe du matricule
            const unitIndicator = document.getElementById('unit_indicator');
            if (value.length >= 1) {
                const prefix = value.substring(0, 1).toUpperCase();
                let unite = '';
                
                switch(prefix) {
                    case 'T': unite = 'ARMÉE DE TERRE'; break;
                    case 'M': unite = 'MARINE NATIONALE'; break;
                    case 'A': unite = 'ARMÉE DE L\'AIR'; break;
                    case 'G': unite = 'GENDARMERIE NATIONALE'; break;
                    case 'C': unite = 'CIVIL'; break;
                    default: unite = '';
                }
                
                if (unite) {
                    unitIndicator.innerHTML = `<span style="color: var(--neon-green); font-size: 0.8rem;">${unite}</span>`;
                    // Auto-sélectionner l'unité
                    document.getElementById('search_unite').value = unite;
                } else {
                    unitIndicator.innerHTML = '';
                }
            } else {
                unitIndicator.innerHTML = '';
            }
            
            searchTimeout = setTimeout(() => {
                if (value.length >= 1 || value.length === 0) {
                    performSearch();
                }
            }, 300);
        });

        // Recherche instantanée par grade avec filtre croisé
        document.getElementById('search_grade').addEventListener('change', function() {
            const grade = this.value;
            const unite = document.getElementById('search_unite').value;
            
            // Validation croisée: vérifier si le grade correspond à l'unité
            if (unite && grade) {
                validateGradeUnit(grade, unite);
            }
            
            performSearch();
        });

        // Recherche instantanée par unité avec mise à jour des grades
        document.getElementById('search_unite').addEventListener('change', function() {
            const unite = this.value;
            
            // Mettre à jour les options de grade selon l'unité
            updateGradeOptions(unite);
            performSearch();
        });

        // Recherche instantanée par année avec validation
        document.getElementById('search_annee').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();
            
            // Validation de l'année
            const currentYear = new Date().getFullYear();
            if (value && (value < 2000 || value > currentYear)) {
                this.setCustomValidity(`L'année doit être entre 2000 et ${currentYear}`);
            } else {
                this.setCustomValidity('');
            }
            
            searchTimeout = setTimeout(() => {
                if (value.length === 4 || value.length === 0) {
                    performSearch();
                }
            }, 300);
        });

        // Recherche instantanée par CNI avec formatage automatique
        document.getElementById('search_cni').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            let value = this.value.trim();
            
            // Formater automatiquement (supprimer les espaces et caractères non numériques)
            value = value.replace(/[^0-9]/g, '');
            this.value = value;
            
            searchTimeout = setTimeout(() => {
                if (value.length >= 3 || value.length === 0) {
                    performSearch();
                }
            }, 300);
        });

        // Mettre à jour les options de grade selon l'unité
        function updateGradeOptions(unite) {
            const gradeSelect = document.getElementById('search_grade');
            const currentValue = gradeSelect.value;
            
            const gradesByUnit = {
                'ARMÉE DE TERRE': ['Soldat de 2E Classe', 'Soldat de 1E Classe', 'Caporal (CPL)', 'Caporal-Chef (CPL/C)', 'Sergent (SGT)', 'Sergent-Chef (SGT/C)', 'Adjudant (ADJT)', 'Adjudant-Chef (A/C)', 'Adjudant-Chef Major (ACM)', 'Sous-Lieutenant (S/Lt)', 'Lieutenant (Lt)', 'Capitaine (Cne)', 'Chef de Bataillon (Cdt)', 'Lieutenant-Colonel (LCL)', 'Colonel (COL)', 'Général de Brigade (GB)', 'Général de Division (GD)', 'Général de Corps d\'Armée (GCA)', 'Général d\'Armée (GA)'],
                'MARINE NATIONALE': ['Matelot', 'Quartier-Maître de 2E Classe (QM2)', 'Quartier-Maître de 1E Classe (QM1)', 'Second Maître (SM)', 'Maître (MTR)', 'Premier Maître (PM)', 'Maître Principal (MP)', 'Major', 'Élève Officier 1E année', 'Élève Officier 2E année', 'Aspirant', 'Enseigne de Vaisseau de 2E Classe (EV2)', 'Enseigne de Vaisseau de 1E Classe (EV1)', 'Lieutenant de Vaisseau (LV)', 'Capitaine de Corvette (CC)', 'Capitaine de Frégate (CF)', 'Capitaine de Vaisseau (CV)', 'Contre-Amiral (CA)', 'Vice-Amiral (VA)', 'Vice-Amiral d\'Escadre (VAE)', 'Amiral (AM)'],
                'ARMÉE DE L\'AIR': ['Aviateur de 2E classe', 'Aviateur de 1E classe', 'Caporal', 'Caporal-Chef', 'Sergent', 'Sergent-Chef', 'Adjudant', 'Adjudant-Chef', 'Aspirant', 'Sous-Lieutenant (S/Lt)', 'Lieutenant (Lt)', 'Capitaine (Cne)', 'Commandant (Cdt)', 'Lieutenant-Colonel (LCL)', 'Colonel (COL)', 'Général de Brigade Aérienne', 'Général de Division Aérienne', 'Général de Corps Aérien', 'Général d\'Armée Aérienne'],
                'GENDARMERIE NATIONALE': ['GENDARME', 'MARÉCHAL DES LOGIS', 'ADJOINT', 'SOUS-OFFICIER', 'LIEUTENANT', 'CAPITAINE', 'COMMANDANT', 'COLONEL'],
                'CIVIL': ['AGENT', 'TECHNICIEN', 'INGÉNIEUR', 'CHEF DE SERVICE', 'DIRECTEUR']
            };
            
            // Vider et repeuplir les options
            gradeSelect.innerHTML = '<option value="">Tous les grades</option>';
            
            if (unite && gradesByUnit[unite]) {
                gradesByUnit[unite].forEach(grade => {
                    const option = document.createElement('option');
                    option.value = grade;
                    option.textContent = grade;
                    if (grade === currentValue) {
                        option.selected = true;
                    }
                    gradeSelect.appendChild(option);
                });
            }
        }

        // Valider la cohérence grade/unité
        function validateGradeUnit(grade, unite) {
            const validCombinations = {
                'ARMÉE DE TERRE': ['Soldat de 2E Classe', 'Soldat de 1E Classe', 'Caporal (CPL)', 'Caporal-Chef (CPL/C)', 'Sergent (SGT)', 'Sergent-Chef (SGT/C)', 'Adjudant (ADJT)', 'Adjudant-Chef (A/C)', 'Adjudant-Chef Major (ACM)', 'Sous-Lieutenant (S/Lt)', 'Lieutenant (Lt)', 'Capitaine (Cne)', 'Chef de Bataillon (Cdt)', 'Lieutenant-Colonel (LCL)', 'Colonel (COL)', 'Général de Brigade (GB)', 'Général de Division (GD)', 'Général de Corps d\'Armée (GCA)', 'Général d\'Armée (GA)'],
                'MARINE NATIONALE': ['Matelot', 'Quartier-Maître de 2E Classe (QM2)', 'Quartier-Maître de 1E Classe (QM1)', 'Second Maître (SM)', 'Maître (MTR)', 'Premier Maître (PM)', 'Maître Principal (MP)', 'Major', 'Élève Officier 1E année', 'Élève Officier 2E année', 'Aspirant', 'Enseigne de Vaisseau de 2E Classe (EV2)', 'Enseigne de Vaisseau de 1E Classe (EV1)', 'Lieutenant de Vaisseau (LV)', 'Capitaine de Corvette (CC)', 'Capitaine de Frégate (CF)', 'Capitaine de Vaisseau (CV)', 'Contre-Amiral (CA)', 'Vice-Amiral (VA)', 'Vice-Amiral d\'Escadre (VAE)', 'Amiral (AM)'],
                'ARMÉE DE L\'AIR': ['Aviateur de 2E classe', 'Aviateur de 1E classe', 'Caporal', 'Caporal-Chef', 'Sergent', 'Sergent-Chef', 'Adjudant', 'Adjudant-Chef', 'Aspirant', 'Sous-Lieutenant (S/Lt)', 'Lieutenant (Lt)', 'Capitaine (Cne)', 'Commandant (Cdt)', 'Lieutenant-Colonel (LCL)', 'Colonel (COL)', 'Général de Brigade Aérienne', 'Général de Division Aérienne', 'Général de Corps Aérien', 'Général d\'Armée Aérienne'],
                'GENDARMERIE NATIONALE': ['GENDARME', 'MARÉCHAL DES LOGIS', 'ADJOINT', 'SOUS-OFFICIER', 'LIEUTENANT', 'CAPITAINE', 'COMMANDANT', 'COLONEL'],
                'CIVIL': ['AGENT', 'TECHNICIEN', 'INGÉNIEUR', 'CHEF DE SERVICE', 'DIRECTEUR']
            };
            
            if (unite && validCombinations[unite] && !validCombinations[unite].includes(grade)) {
                console.warn(`Le grade "${grade}" n'est pas typique pour l'unité "${unite}"`);
            }
        }

        // Afficher les suggestions de recherche
        function showSuggestions(query, resultsId) {
            // Implémenter les suggestions si nécessaire
            const resultsDiv = document.getElementById(resultsId);
            if (query.length < 2) {
                resultsDiv.classList.remove('active');
                resultsDiv.innerHTML = '';
                return;
            }
            
            // Pour l'instant, on peut ajouter une logique de suggestions basique
            // TODO: Implémenter avec appel AJAX pour suggestions réelles
        }

        // Effacer la recherche avec animation
        function clearSearch() {
            // Animation de chargement
            const container = document.getElementById('personnelsContainer');
            container.style.opacity = '0.5';
            
            setTimeout(() => {
                document.getElementById('search_nom').value = '';
                document.getElementById('search_matricule').value = '';
                document.getElementById('search_grade').value = '';
                document.getElementById('search_unite').value = '';
                document.getElementById('search_annee').value = '';
                document.getElementById('search_cni').value = '';
                document.getElementById('unit_indicator').innerHTML = '';
                
                // Recharger la page sans filtres
                window.location.href = 'impression.php';
            }, 300);
        }

        // Fonction de recherche dynamique
        function performSearch() {
            // Afficher l'indicateur de chargement
            showLoading();
            
            const formData = new FormData();
            formData.append('ajax_search', '1');
            
            // Ajouter tous les critères de recherche
            const searchFields = ['search_nom', 'search_matricule', 'search_grade', 'search_unite', 'search_annee', 'search_cni'];
            searchFields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    formData.append(field, element.value);
                }
            });

            fetch('impression.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.text();
            })
            .then(html => {
                // Extraire uniquement la grille des candidats
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newGrid = doc.querySelector('#personnelsContainer');
                
                if (newGrid) {
                    document.querySelector('#personnelsContainer').innerHTML = newGrid.innerHTML;
                    
                    // Mettre à jour les actions batch si nécessaire
                    const batchActions = doc.querySelector('.batch-actions');
                    if (batchActions) {
                        const existingBatchActions = document.querySelector('.batch-actions');
                        if (existingBatchActions) {
                            existingBatchActions.innerHTML = batchActions.innerHTML;
                            existingBatchActions.style.display = 'flex';
                        }
                    }
                    
                    // Mettre à jour le compteur de résultats
                    updateResultCount();
                    
                    // Réattacher les événements
                    attachCheckboxListeners();
                } else {
                    // Si pas de grille, afficher message
                    document.querySelector('#personnelsContainer').innerHTML = 
                        '<div class="search-loading">Aucun résultat trouvé</div>';
                    updateResultCount();
                }
                
                hideLoading();
            })
            .catch(error => {
                console.error('Erreur lors de la recherche:', error);
                document.querySelector('#personnelsContainer').innerHTML = 
                    '<div class="search-error">Erreur lors de la recherche. Veuillez réessayer.</div>';
                hideLoading();
            });
        }

        // Afficher l'indicateur de chargement
        function showLoading() {
            const container = document.querySelector('#personnelsContainer');
            container.style.opacity = '0.5';
            container.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin"></i> Recherche en cours... / Searching...</div>';
        }

        // Masquer l'indicateur de chargement
        function hideLoading() {
            const container = document.querySelector('#personnelsContainer');
            container.style.opacity = '1';
        }

        // Attacher les événements aux checkboxes
        function attachCheckboxListeners() {
            document.querySelectorAll('.personnel-checkbox').forEach(checkbox => {
                checkbox.removeEventListener('change', updateSelection);
                checkbox.addEventListener('change', updateSelection);
            });
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            updateResultCount();
            attachCheckboxListeners();
            toggleView('grid'); // Vue par défaut
        });

        // Effacer la recherche
        function clearSearch() {
            document.getElementById('search_nom').value = '';
            document.getElementById('search_matricule').value = '';
            document.getElementById('search_grade').value = '';
            document.getElementById('search_unite').value = '';
            document.getElementById('search_annee').value = '';
            document.getElementById('search_cni').value = '';
            document.getElementById('unit_indicator').innerHTML = '';
            
            // Recharger la page sans filtres
            window.location.href = 'impression.php';
        }

        // Exporter les résultats
        function exportResults() {
            const formData = new FormData();
            formData.append('export', '1');
            
            // Ajouter tous les critères de recherche
            const searchFields = ['search_nom', 'search_matricule', 'search_grade', 'search_unite', 'search_annee', 'search_cni'];
            searchFields.forEach(field => {
                const element = document.getElementById(field);
                if (element) {
                    formData.append(field, element.value);
                }
            });

            fetch('impression.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'candidats_cimis_' + new Date().toISOString().slice(0, 10) + '.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                console.error('Erreur lors de l\'export:', error);
            });
        }
        
        // Visualiser plusieurs candidats
        function visualizeMultiple() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            const matricules = Array.from(selected).map(cb => cb.value);
            window.location.href = 'visualiser_carte.php?matricules=' + matricules.join(',');
        }
        
        // Générer PDF multiple
        function generateBatch() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            if (confirm('Générer un PDF pour les ' + selected.length + ' candidats sélectionnés ?')) {
                const matricules = Array.from(selected).map(cb => cb.value);
                
                // Rediriger vers une page de génération PDF
                window.location.href = 'impression.php?action=generate_batch&matricules=' + matricules.join(',');
            }
        }
        
        // Générer impression PVC multiple
        function generateBatchPVC() {
            const selected = document.querySelectorAll('.personnel-checkbox:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une carte');
                return;
            }
            
            if (confirm('Imprimer en PVC les ' + selected.length + ' cartes sélectionnées ?')) {
                const matricules = Array.from(selected).map(cb => cb.value);
                
                // Rediriger vers la page d'impression PVC multiple
                window.location.href = 'impression_pvc.php?matricules=' + matricules.join(',') + '&mode=recto-verso';
            }
        }
    </script>

    <style>
        .hero-logo {
            width: 240px; /* Augmenté de 120px à 240px (X2) */
            height: 240px; /* Augmenté de 120px à 240px (X2) */
            margin-bottom: 1rem;
        }
        
        .candidats-grid, .personnels-grid {
            display: grid;
            gap: 1rem;
            max-height: 500px;
            overflow-y: auto;
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        /* Masquer les cartes à bordure verte */
        .card-section:has(.id-card-green) {
            display: none !important;
        }

        .results-section {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem 0;
            margin-bottom: 1rem;
        }

        .result-count-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(45deg, var(--neon-green), #00cc00);
            border-radius: 25px;
            box-shadow: 0 0 15px rgba(74, 222, 128, 0.5);
            animation: pulse 2s infinite;
        }

        .result-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: black;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
        }

        .result-text {
            font-size: 0.9rem;
            font-weight: 600;
            color: black;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 15px rgba(74, 222, 128, 0.5);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 25px rgba(74, 222, 128, 0.8);
            }
        }
        
        /* Afficher uniquement les cartes à bordure blanche */
        .card-section:has(.id-card:not(.id-card-green)) {
            display: block !important;
        }
        
        /* Pour visualiser_carte.php - masquer les cartes vertes */
        .card-subsection:has(.id-card-green) {
            display: none !important;
        }
        
        .card-subsection:has(.id-card:not(.id-card-green)) {
            display: block !important;
        }

        @media (max-width: 1024px) {
            .search-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .search-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .search-group label {
            font-size: 0.9rem;
            color: var(--neon-green);
            font-weight: bold;
            text-transform: uppercase;
        }

        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 15px;
            color: var(--neon-green);
            z-index: 2;
        }

        .search-input-wrapper input,
        .search-input-wrapper select {
            width: 100%;
            padding: 10px 12px 10px 45px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input-wrapper input:focus,
        .search-input-wrapper select:focus {
            outline: none;
            border-color: var(--neon-green);
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 10px rgba(74, 222, 128, 0.3);
        }

        .search-input-wrapper select {
            padding-left: 12px;
            cursor: pointer;
        }

        .unit-indicator {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            z-index: 2;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid var(--neon-green);
            border-radius: 8px;
            margin-top: 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .search-results.active {
            display: block;
        }

        .search-result-item {
            padding: 10px 12px;
            cursor: pointer;
            transition: background 0.2s ease;
            border-bottom: 1px solid rgba(74, 222, 128, 0.1);
        }

        .search-result-item:hover {
            background: rgba(74, 222, 128, 0.1);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(74, 222, 128, 0.3);
        }

        .candidat-item, .personnel-item {
            display: grid;
            grid-template-columns: 40px 60px 1fr auto;
            gap: 1rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(74, 222, 128, 0.3);
            border-radius: 10px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .candidat-item:hover, .personnel-item:hover {
            background: rgba(74, 222, 128, 0.1);
            border-color: var(--neon-green);
            transform: translateY(-2px);
        }

        .candidat-checkbox-wrapper, .personnel-checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .candidat-checkbox, .personnel-checkbox {
            width: 18px;
            height: 18px;
            appearance: none;
            border: 2px solid var(--neon-green);
            border-radius: 4px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }

        .candidat-checkbox:checked, .personnel-checkbox:checked {
            background: var(--neon-green);
            border-color: var(--neon-green);
        }

        .candidat-checkbox:checked::after, .personnel-checkbox:checked::after {
            content: '✓';
            position: absolute;
            top: -2px;
            left: 2px;
            color: black;
            font-weight: bold;
            font-size: 12px;
        }

        .checkbox-label {
            display: none;
        }

        .candidat-photo, .personnel-photo {
            width: 50px;
            height: 60px;
            border-radius: 5px;
            overflow: hidden;
            border: 2px solid var(--neon-green);
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.3);
        }

        .candidat-photo img, .personnel-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidat-info, .personnel-info {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .candidat-name, .personnel-name {
            font-weight: bold;
            color: var(--neon-green);
            font-size: 1rem;
        }

        .candidat-details, .personnel-details {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .badge {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-armée-de-terre { background: rgba(34, 139, 34, 0.2); color: var(--neon-green); }
        .badge-marine-nationale { background: rgba(0, 0, 0, 0.2); color: white; }
        .badge-armée-de-l'air { background: rgba(30, 144, 255, 0.2); color: var(--neon-blue); }
        .badge-gendarmerie { background: rgba(128, 128, 128, 0.2); color: #ccc; }
        .badge-civil { background: rgba(255, 255, 255, 0.2); color: white; }

        .matricule {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            color: var(--neon-gold);
        }

        .candidat-grade, .personnel-grade {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .candidat-actions, .personnel-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            min-width: 100px;
            text-align: center;
        }
        
        .btn {
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            min-width: 100px;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-green));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--neon-green), var(--neon-blue));
            transform: translateY(-2px);
        }

        .batch-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .search-loading {
            text-align: center;
            padding: 3rem;
            color: var(--neon-green);
            font-size: 1.2rem;
        }

        .search-loading i {
            margin-right: 1rem;
            font-size: 2rem;
        }

        .search-error {
            text-align: center;
            padding: 3rem;
            color: var(--neon-red);
            font-size: 1.1rem;
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid var(--neon-red);
            border-radius: 10px;
            margin: 1rem 0;
        }

        .fa-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</body>
</html>

