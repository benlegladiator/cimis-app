<?php
session_start();

// Vérifier si l'utilisateur est authentifié et administrateur
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: ../403.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['SUPER_ADMIN', 'ADMIN_ENROLEMENT', 'ADMIN_IMPRESSION'])) {
    header('Location: dashboard.php');
    exit();
}

require_once '../backend/config.php';

// Configuration de la base de données GESMIL2.0
$gesmil_config = [
    'host' => 'localhost',
    'dbname' => 'gesmil2',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// SIMULATION - En attendant l'accès à GESMIL2.0
$simulation_mode = true; // Mettre à false quand la base sera accessible

try {
    if ($simulation_mode) {
        // Mode simulation avec données fictives réalistes
        $gesmil_pdo = null; // Pas de connexion réelle
    } else {
        // Connexion réelle à la base GESMIL2.0
        $gesmil_pdo = new PDO(
            "mysql:host={$gesmil_config['host']};dbname={$gesmil_config['dbname']};charset={$gesmil_config['charset']}",
            $gesmil_config['username'],
            $gesmil_config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
} catch(PDOException $e) {
    if ($simulation_mode) {
        $gesmil_pdo = null; // Continuer en mode simulation
    } else {
        die("Erreur de connexion à GESMIL2.0: " . $e->getMessage());
    }
}

// Fonction pour récupérer les informations militaires nécessaires
function getMilitaryInfoFromGESMIL($pdo_gesmil, $simulation_mode) {
    global $gesmil_pdo;
    
    if ($simulation_mode) {
        // MODE SIMULATION - Données fictives réalistes
        return [
            [
                'matricule_militaire' => 'T17/12345',
                'nom' => 'KAMDEM',
                'prenom' => 'JEAN PIERRE',
                'date_naissance' => '1985-03-15',
                'sexe' => 'MASCULIN',
                'grade' => 'CAPITAINE',
                'corps_armee' => 'ARMÉE DE TERRE',
                'annee_dernier_galon' => '2023',
                'groupe_sanguin' => 'O+',
                'taille' => '175',
                'poids' => '72',
                'numero_cni' => '123456789012345678',
                'date_enrolement' => '2010-06-15'
            ],
            [
                'matricule_militaire' => 'T23/67890',
                'nom' => 'MBARGA',
                'prenom' => 'MARIE CLAIRE',
                'date_naissance' => '1990-07-22',
                'sexe' => 'FEMININ',
                'grade' => 'LIEUTENANT',
                'corps_armee' => 'ARMÉE DE TERRE',
                'annee_dernier_galon' => '2022',
                'groupe_sanguin' => 'A+',
                'taille' => '165',
                'poids' => '58',
                'numero_cni' => '987654321098765432',
                'date_enrolement' => '2015-09-10'
            ],
            [
                'matricule_militaire' => 'A11/24680',
                'nom' => 'FOUOPOU',
                'prenom' => 'ALAIN ERIC',
                'date_naissance' => '1982-11-08',
                'sexe' => 'MASCULIN',
                'grade' => 'COLONEL',
                'corps_armee' => 'ARMÉE DE L\'AIR',
                'annee_dernier_galon' => '2021',
                'groupe_sanguin' => 'B+',
                'taille' => '180',
                'poids' => '78',
                'numero_cni' => '456789012345678901',
                'date_enrolement' => '2008-04-20'
            ],
            [
                'matricule_militaire' => 'M15/13579',
                'nom' => 'OUMAR',
                'prenom' => 'SALIFATOU',
                'date_naissance' => '1988-05-30',
                'sexe' => 'MASCULIN',
                'grade' => 'CAPITAINE DE VAISSEAU',
                'corps_armee' => 'MARINE NATIONALE',
                'annee_dernier_galon' => '2023',
                'groupe_sanguin' => 'AB+',
                'taille' => '178',
                'poids' => '75',
                'numero_cni' => '789012345678901234',
                'date_enrolement' => '2012-11-05'
            ],
            [
                'matricule_militaire' => 'G20/86420',
                'nom' => 'TCHUENTE',
                'prenom' => 'JOSIANE',
                'date_naissance' => '1992-09-18',
                'sexe' => 'FEMININ',
                'grade' => 'LIEUTENANT',
                'corps_armee' => 'GENDARMERIE',
                'annee_dernier_galon' => '2022',
                'groupe_sanguin' => 'O-',
                'taille' => '170',
                'poids' => '62',
                'numero_cni' => '321098765432109876',
                'date_enrolement' => '2018-07-12'
            ],
            [
                'matricule_militaire' => 'T17/98765',
                'nom' => 'NGUEMA',
                'prenom' => 'PATRICE ARMAND',
                'date_naissance' => '1987-12-03',
                'sexe' => 'MASCULIN',
                'grade' => 'COMMANDANT',
                'corps_armee' => 'ARMÉE DE TERRE',
                'annee_dernier_galon' => '2020',
                'groupe_sanguin' => 'A-',
                'taille' => '182',
                'poids' => '80',
                'numero_cni' => '654321098765432109',
                'date_enrolement' => '2011-02-28'
            ]
        ];
    }
    
    $militaires = [];
    
    try {
        // Récupérer les informations militaires essentielles pour CIMIS
        // (On ne prend que les attributs nécessaires pour la confection des cartes)
        $stmt = $pdo_gesmil->prepare("
            SELECT 
                matricule_militaire,
                nom,
                prenom,
                date_naissance,
                sexe,
                grade,
                corps_armee,
                annee_dernier_galon,
                groupe_sanguin,
                taille,
                poids,
                numero_cni,
                date_enrolement
            FROM militaires 
            WHERE statut = 'ACTIF'
            ORDER BY nom, prenom
        ");
        
        $stmt->execute();
        $militaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erreur récupération militaires GESMIL: " . $e->getMessage());
    }
    
    return $militaires;
}

// Fonction pour générer un matricule CIMIS
function generateMatriculeCIMIS($pdo_cimis) {
    do {
        // Générer un matricule unique: CIM-XXXXX
        $matricule = 'CIM-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        // Vérifier si le matricule existe déjà
        $stmt = $pdo_cimis->prepare("SELECT COUNT(*) as count FROM candidat WHERE matricule = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } while ($result['count'] > 0);
    
    return $matricule;
}

// Fonction pour créer un candidat dans CIMIS
function createCandidateInCIMIS($pdo_cimis, $militaire) {
    try {
        // Vérifier si le candidat existe déjà (basé sur le matricule militaire)
        $stmt = $pdo_cimis->prepare("SELECT id FROM candidat WHERE matricule_militaire = :matricule_militaire");
        $stmt->execute(['matricule_militaire' => $militaire['matricule_militaire']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Le militaire existe déjà dans CIMIS',
                'matricule' => null
            ];
        }
        
        // Générer un matricule CIMIS
        $matricule_cimis = generateMatriculeCIMIS($pdo_cimis);
        
        // Déterminer le type de personnel et l'unité
        $type_personnel = 'MILITAIRE';
        $unite = $militaire['corps_armee'] ?? 'ARMÉE DE TERRE';
        
        // Insérer le nouveau candidat
        $stmt = $pdo_cimis->prepare("
            INSERT INTO candidat (
                matricule, 
                matricule_militaire, 
                nom, 
                prenom, 
                date_naissance, 
                sexe, 
                numero_cni, 
                taille, 
                poids, 
                groupe_sanguin, 
                annee_dernier_galon, 
                unite, 
                grade, 
                date_enrolement,
                date_dernier_grade
            ) VALUES (
                :matricule,
                :matricule_militaire,
                :nom,
                :prenom,
                :date_naissance,
                :sexe,
                :numero_cni,
                :taille,
                :poids,
                :groupe_sanguin,
                :annee_dernier_galon,
                :unite,
                :grade,
                :date_enrolement,
                :date_dernier_grade
            )
        ");
        
        $date_dernier_grade = $militaire['annee_dernier_galon'] ? $militaire['annee_dernier_galon'] . '-01-01' : null;
        
        $stmt->execute([
            'matricule' => $matricule_cimis,
            'matricule_militaire' => $militaire['matricule_militaire'],
            'nom' => strtoupper($militaire['nom']),
            'prenom' => strtoupper($militaire['prenom']),
            'date_naissance' => $militaire['date_naissance'],
            'sexe' => $militaire['sexe'],
            'numero_cni' => $militaire['numero_cni'],
            'taille' => $militaire['taille'],
            'poids' => $militaire['poids'],
            'groupe_sanguin' => $militaire['groupe_sanguin'],
            'annee_dernier_galon' => $militaire['annee_dernier_galon'],
            'unite' => $unite,
            'grade' => $militaire['grade'],
            'date_enrolement' => $militaire['date_enrolement'] ?? date('Y-m-d H:i:s'),
            'date_dernier_grade' => $date_dernier_grade
        ]);
        
        return [
            'success' => true,
            'message' => 'Candidat créé avec succès',
            'matricule' => $matricule_cimis
        ];
        
    } catch(PDOException $e) {
        error_log("Erreur création candidat CIMIS: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur lors de la création: ' . $e->getMessage(),
            'matricule' => null
        ];
    }
}

// Traitement des actions
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$militaires = [];
$stats = [];

if ($action === 'fetch_military') {
    // Action 1: Récupérer les informations militaires
    $militaires = getMilitaryInfoFromGESMIL($gesmil_pdo, $simulation_mode);
    
    // Statistiques
    $stats = [
        'total_militaires' => count($militaires),
        'avec_matricule_cimis' => 0,
        'sans_matricule_cimis' => 0,
        'par_corps_armee' => []
    ];
    
    // Compter les militaires avec/sans matricule CIMIS
    foreach ($militaires as $militaire) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE matricule_militaire = :matricule_militaire");
        $stmt->execute(['matricule_militaire' => $militaire['matricule_militaire']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $stats['avec_matricule_cimis']++;
        } else {
            $stats['sans_matricule_cimis']++;
        }
        
        // Compter par corps d'armée
        $corps = $militaire['corps_armee'] ?? 'Non spécifié';
        if (!isset($stats['par_corps_armee'][$corps])) {
            $stats['par_corps_armee'][$corps] = 0;
        }
        $stats['par_corps_armee'][$corps]++;
    }
    
} elseif ($action === 'generate_matricules') {
    // Action 2: Générer les matricules CIMIS
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricules'])) {
        $matricules_to_process = $_POST['matricules'];
        $results = [];
        
        foreach ($matricules_to_process as $matricule_militaire) {
            // Récupérer les infos du militaire
            $stmt = $gesmil_pdo->prepare("SELECT * FROM militaires WHERE matricule_militaire = :matricule_militaire");
            $stmt->execute(['matricule_militaire' => $matricule_militaire]);
            $militaire = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($militaire) {
                $result = createCandidateInCIMIS($pdo, $militaire);
                $results[] = [
                    'matricule_militaire' => $matricule_militaire,
                    'nom' => $militaire['nom'],
                    'prenom' => $militaire['prenom'],
                    'result' => $result
                ];
            }
        }
        
        $message = count($results) . ' militaires traités';
        
        // Rediriger avec les résultats
        $_SESSION['api_results'] = $results;
        header('Location: api.php?action=results');
        exit();
    }
} elseif ($action === 'results') {
    // Afficher les résultats du traitement
    $results = $_SESSION['api_results'] ?? [];
    unset($_SESSION['api_results']);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>API GESMIL2.0 - CIMIS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .nav-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .nav-tab {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .nav-tab.active {
            background: white;
            color: #007bff;
            border-bottom: 3px solid #007bff;
        }
        
        .nav-tab:hover {
            background: #e9ecef;
        }
        
        .content {
            padding: 20px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .military-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .military-table th,
        .military-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .military-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .military-table tr:hover {
            background: #f8f9fa;
        }
        
        .checkbox-cell {
            width: 50px;
            text-align: center;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-badge.has-cimis {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.no-cimis {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .btn.success {
            background: #28a745;
        }
        
        .btn.success:hover {
            background: #1e7e34;
        }
        
        .btn.warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn.warning:hover {
            background: #e0a800;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .result-card {
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid;
        }
        
        .result-card.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        
        .result-card.error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if ($simulation_mode): ?>
                <div style="background: #ffc107; color: #212529; padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center;">
                    <i class="fa-solid fa-exclamation-triangle"></i> 
                    <strong>MODE SIMULATION</strong> - Données fictives en attendant l'accès à GESMIL2.0
                </div>
            <?php endif; ?>
            
            <h1><i class="fa-solid fa-database"></i> API GESMIL2.0 - CIMIS</h1>
            <p>Synchronisation des données militaires et génération de matricules CIMIS</p>
        </div>
        
        <div class="nav-tabs">
            <button class="nav-tab <?php echo $action === 'dashboard' ? 'active' : ''; ?>" onclick="showTab('dashboard')">
                <i class="fa-solid fa-tachometer-alt"></i> Tableau de bord
            </button>
            <button class="nav-tab <?php echo $action === 'fetch_military' ? 'active' : ''; ?>" onclick="showTab('fetch_military')">
                <i class="fa-solid fa-users"></i> Militaires GESMIL
            </button>
            <button class="nav-tab <?php echo $action === 'generate_matricules' ? 'active' : ''; ?>" onclick="showTab('generate_matricules')">
                <i class="fa-solid fa-id-card"></i> Générer Matricules
            </button>
            <button class="nav-tab <?php echo $action === 'results' ? 'active' : ''; ?>" onclick="showTab('results')">
                <i class="fa-solid fa-check-circle"></i> Résultats
            </button>
        </div>
        
        <div class="content">
            <!-- Tableau de bord -->
            <div id="dashboard" class="tab-content <?php echo $action === 'dashboard' ? 'active' : ''; ?>">
                <h2>Tableau de bord de l'API</h2>
                <p>Bienvenue dans l'interface de synchronisation GESMIL2.0 - CIMIS.</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">52</div>
                        <div class="stat-label">Attributs disponibles dans GESMIL2.0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">2</div>
                        <div class="stat-label">Fonctions principales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">∞</div>
                        <div class="stat-label">Matricules CIMIS générables</div>
                    </div>
                </div>
                
                <h3>Fonctionnalités disponibles:</h3>
                <ul>
                    <li><strong>Récupération des données:</strong> Import des 52 attributs militaires depuis GESMIL2.0</li>
                    <li><strong>Filtrage intelligent:</strong> Sélection des informations essentielles pour CIMIS</li>
                    <li><strong>Génération automatique:</strong> Création des matricules CIMIS uniques</li>
                    <li><strong>Synchronisation:</strong> Intégration transparente dans la base CIMIS</li>
                </ul>
            </div>
            
            <!-- Militaires GESMIL -->
            <div id="fetch_military" class="tab-content <?php echo $action === 'fetch_military' ? 'active' : ''; ?>">
                <?php if (!empty($stats)): ?>
                    <h2>Statistiques des militaires GESMIL2.0</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_militaires']; ?></div>
                            <div class="stat-label">Total militaires</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                            <div class="stat-number"><?php echo $stats['avec_matricule_cimis']; ?></div>
                            <div class="stat-label">Avec matricule CIMIS</div>
                        </div>
                        <div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
                            <div class="stat-number"><?php echo $stats['sans_matricule_cimis']; ?></div>
                            <div class="stat-label">Sans matricule CIMIS</div>
                        </div>
                    </div>
                    
                    <h3>Répartition par corps d'armée:</h3>
                    <div class="stats-grid">
                        <?php foreach ($stats['par_corps_armee'] as $corps => $count): ?>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $count; ?></div>
                                <div class="stat-label"><?php echo htmlspecialchars($corps); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <h3>Liste des militaires</h3>
                    <form method="post" action="api.php?action=generate_matricules">
                        <table class="military-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
                                    </th>
                                    <th>Matricule Militaire</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Grade</th>
                                    <th>Corps d'Armée</th>
                                    <th>Statut CIMIS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($militaires as $militaire): ?>
                                    <?php 
                                    // Vérifier si le militaire a déjà un matricule CIMIS
                                    $stmt = $pdo->prepare("SELECT matricule FROM candidat WHERE matricule_militaire = :matricule_militaire");
                                    $stmt->execute(['matricule_militaire' => $militaire['matricule_militaire']]);
                                    $cimis_result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $has_cimis = $cimis_result !== false;
                                    ?>
                                    <tr>
                                        <td class="checkbox-cell">
                                            <?php if (!$has_cimis): ?>
                                                <input type="checkbox" name="matricules[]" value="<?php echo htmlspecialchars($militaire['matricule_militaire']); ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($militaire['matricule_militaire']); ?></td>
                                        <td><?php echo htmlspecialchars($militaire['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($militaire['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($militaire['grade']); ?></td>
                                        <td><?php echo htmlspecialchars($militaire['corps_armee'] ?? 'Non spécifié'); ?></td>
                                        <td>
                                            <?php if ($has_cimis): ?>
                                                <span class="status-badge has-cimis">
                                                    <?php echo htmlspecialchars($cimis_result['matricule']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge no-cimis">Non synchronisé</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn success">
                                <i class="fa-solid fa-id-card"></i> 
                                Générer les matricules CIMIS sélectionnés
                            </button>
                            <small style="margin-left: 10px;">
            <div id="generate_matricules" class="tab-content <?php echo $action === 'generate_matricules' ? 'active' : ''; ?>">
                <h2>Génération de Matricules CIMIS</h2>
                <p>Cette fonction permet de générer automatiquement des matricules CIMIS pour les militaires sélectionnés.</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">CIM-XXXXX</div>
                        <div class="stat-label">Format des matricules</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">Unique</div>
                        <div class="stat-label">Validation automatique</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">Immédiat</div>
                        <div class="stat-label">Intégration CIMIS</div>
                    </div>
                </div>
                
                <h3>Processus de génération:</h3>
                <ol>
                    <li>Sélectionnez les militaires sans matricule CIMIS dans l'onglet "Militaires GESMIL"</li>
                    <li>Cliquez sur "Générer les matricules CIMIS sélectionnés"</li>
                    <li>Le système crée automatiquement les candidats dans CIMIS</li>
                    <li>Les matricules générés apparaissent dans l'onglet "Résultats"</li>
                </ol>
                
                <p><strong>Note:</strong> Seuls les militaires actifs et sans matricule CIMIS peuvent être traités.</p>
            </div>
            
            <!-- Résultats -->
            <div id="results" class="tab-content <?php echo $action === 'results' ? 'active' : ''; ?>">
                <a href="./api.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Retour au tableau de bord
                </a>
                
                <div style="text-align: center; margin: 20px 0;">
                    <a href="./securite_admin.php" class="btn" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                        <i class="fa-solid fa-shield-alt"></i> 
                        Retour à l'administration
                    </a>
                </div>
                
                <h2>Résultats de la synchronisation</h2>
                
                <?php if (!empty($results)): ?>
                    <div class="results-grid">
                        <?php foreach ($results as $result): ?>
                            <div class="result-card <?php echo $result['result']['success'] ? 'success' : 'error'; ?>">
                                <h4>
                                    <?php echo htmlspecialchars($result['nom'] . ' ' . $result['prenom']); ?>
                                </h4>
                                <p><strong>Matricule militaire:</strong> <?php echo htmlspecialchars($result['matricule_militaire']); ?></p>
                                
                                <?php if ($result['result']['success']): ?>
                                    <p class="success">
                                        <i class="fa-solid fa-check-circle"></i>
                                        <strong>Succès:</strong> <?php echo htmlspecialchars($result['result']['message']); ?>
                                    </p>
                                    <p><strong>Matricule CIMIS généré:</strong> 
                                        <code><?php echo htmlspecialchars($result['result']['matricule']); ?></code>
                                    </p>
                                <?php else: ?>
                                    <p class="error">
                                        <i class="fa-solid fa-exclamation-triangle"></i>
                                        <strong>Erreur:</strong> <?php echo htmlspecialchars($result['result']['message']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Aucun résultat à afficher. Veuillez d'abord exécuter une synchronisation.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Masquer tous les contenus
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Désactiver tous les onglets
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Afficher le contenu sélectionné
            document.getElementById(tabName).classList.add('active');
            
            // Activer l'onglet sélectionné
            event.target.classList.add('active');
        }
        
        function toggleAllCheckboxes() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="matricules[]"]');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
    </script>
</body>
</html>
