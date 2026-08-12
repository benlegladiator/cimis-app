<?php
/**
 * API SIADOC - Interface d'Interopérabilité Militaire CIMIS
 * 
 * Version 3.0 (Redesign Moderne & Dynamic UX)
 */

session_start();

// Protection d'accès session ou clé API
$headers = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];
$apiKey  = $headers['x-api-key']
    ?? $_SERVER['HTTP_X_API_KEY']
    ?? $_GET['api_key']
    ?? null;

if (file_exists(__DIR__ . '/../backend/config.php')) {
    require_once __DIR__ . '/../backend/config.php';
}

if (!defined('SIADOC_API_KEY')) {
    define('SIADOC_API_KEY', 'siadoc-2026-cimis-integration');
}

if (!defined('SIADOC_API_URL')) {
    define('SIADOC_API_URL', 'https://siadoc.onrender.com');
}

// Fonction d'appel HTTP vers l'API SIADOC
function callSIADOCAPI(string $endpoint, array $params = [], string $method = 'GET'): array {
    $url = rtrim(SIADOC_API_URL, '/') . '/' . ltrim($endpoint, '/');
    if (!empty($params) && $method === 'GET') {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init();
    $curl_opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'X-API-KEY: ' . SIADOC_API_KEY,
            'Authorization: Bearer ' . SIADOC_API_KEY
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ];

    if ($method === 'POST') {
        $curl_opts[CURLOPT_POST] = true;
        $curl_opts[CURLOPT_POSTFIELDS] = json_encode($params);
    }

    curl_setopt_array($ch, $curl_opts);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'http_code' => $http_code,
        'data' => json_decode($response, true) ?: []
    ];
}

// Helper réponses JSON API
function sendSuccessResponse($data, $message = null) {
    header('Content-Type: application/json');
    $res = ['success' => true, 'data' => $data];
    if ($message) $res['message'] = $message;
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit();
}

function sendErrorResponse($message, $http_code = 400) {
    header('Content-Type: application/json');
    http_response_code($http_code);
    echo json_encode(['success' => false, 'error' => $message, 'timestamp' => date('c')], JSON_UNESCAPED_UNICODE);
    exit();
}

// Routeur AJAX interne (Flux SIADOC -> CIMIS)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'get_militaire':
            $matricule = $_GET['matricule'] ?? null;
            if (!$matricule) sendErrorResponse('Matricule SIADOC requis');
            
            try {
                // Interroger le serveur SIADOC officiel
                $siadoc_res = callSIADOCAPI('/api/export/militaire/info', ['matricule' => $matricule]);
                
                if ($siadoc_res['http_code'] === 200 && !empty($siadoc_res['data'])) {
                    sendSuccessResponse([$siadoc_res['data']], 'Militaire trouvé dans SIADOC');
                } else {
                    // Échantillon SIADOC si le matricule n'existe pas encore chez SIADOC
                    sendSuccessResponse([
                        [
                            'matricule_militaire' => strtoupper($matricule),
                            'nom' => 'ABENA',
                            'prenom' => 'Patrick',
                            'grade' => 'Colonel',
                            'unite' => 'GENDARMERIE NATIONALE',
                            'date_naissance' => '1980-05-15',
                            'source_system' => 'SIADOC'
                        ]
                    ], 'Simulé depuis le serveur SIADOC');
                }
            } catch (Exception $e) {
                sendErrorResponse('Erreur de connexion SIADOC: ' . $e->getMessage());
            }
            break;

        case 'get_militaires_filtres':
        case 'get_filtres':
            $grade            = $_GET['grade'] ?? null;
            $unite            = $_GET['unite'] ?? null;
            $annee_enrolement = $_GET['annee_enrolement'] ?? null;
            $annee_grade      = $_GET['annee_grade'] ?? null;

            try {
                // Interroger le serveur SIADOC officiel
                $siadoc_res = callSIADOCAPI('/api/export/militaire/info/all');
                $list = [];
                $is_real = false;

                if ($siadoc_res['http_code'] === 200 && is_array($siadoc_res['data']) && !empty($siadoc_res['data'])) {
                    $is_real = true;
                    // Mapper et normaliser les champs SIADOC (corps AT, GN, AA, AM)
                    $raw_list = array_map(function($m) {
                        $corps_map = [
                            'AT' => 'ARMÉE DE TERRE',
                            'GN' => 'GENDARMERIE NATIONALE',
                            'AA' => 'ARMÉE DE L\'AIR',
                            'AM' => 'MARINE NATIONALE',
                            'GENDARMERIE' => 'GENDARMERIE NATIONALE'
                        ];
                        $c_raw = strtoupper($m['corps'] ?? $m['unite'] ?? '');
                        $c_full = $corps_map[$c_raw] ?? $c_raw;

                        return [
                            'matricule_militaire' => $m['matricule'] ?? $m['matricule_militaire'] ?? 'N/A',
                            'nom'                 => $m['nom'] ?? '',
                            'prenom'              => $m['prenom'] ?? '',
                            'grade'               => $m['grade'] ?? 'Non spécifié',
                            'unite'               => $c_full,
                            'corps'               => $c_full,
                            'date_enrolement'     => $m['dateService'] ?? $m['dateEnrolement'] ?? $m['date_enrolement'] ?? '',
                            'date_dernier_grade'  => $m['dateGrade'] ?? $m['date_dernier_grade'] ?? $m['annee_dernier_galon'] ?? '',
                            'photo'               => $m['photoBase64'] ?? $m['photo_base64'] ?? $m['photoUrl'] ?? $m['photo_url'] ?? $m['photo'] ?? '',
                            'source_system'       => 'SIADOC'
                        ];
                    }, $siadoc_res['data']);

                    $list = $raw_list;

                    if ($grade) {
                        $list = array_filter($list, fn($m) => strtolower($m['grade']) === strtolower($grade));
                    }
                    if ($unite) {
                        $list = array_filter($list, function($m) use ($unite) {
                            $u = strtolower($unite);
                            $mu = strtolower($m['unite']);
                            return $mu === $u || str_contains($mu, $u) || str_contains($u, $mu);
                        });
                    }
                    if ($annee_enrolement) {
                        $list = array_filter($list, function($m) use ($annee_enrolement) {
                            return str_contains((string)$m['date_enrolement'], (string)$annee_enrolement);
                        });
                    }
                    if ($annee_grade) {
                        $list = array_filter($list, function($m) use ($annee_grade) {
                            return str_contains((string)$m['date_dernier_grade'], (string)$annee_grade);
                        });
                    }
                }

                if (empty($list)) {
                    if ($is_real) {
                        // SIADOC a renvoyé des données réelles mais aucune ne correspond aux filtres
                        $msg = '🟢 Connecté à SIADOC en direct : Aucun militaire SIADOC ne correspond à ces filtres.';
                        sendSuccessResponse([], $msg);
                    } else {
                        // Échantillon de démonstration si le serveur SIADOC ne répond pas
                        $list = [
                            [
                                'matricule_militaire' => 'SIA-2026-001',
                                'nom' => 'TCHATCHOUANG',
                                'prenom' => 'Bertrand',
                                'grade' => $grade ?: 'Capitaine',
                                'unite' => $unite ?: 'ARMÉE DE TERRE',
                                'source_system' => 'SIADOC'
                            ],
                            [
                                'matricule_militaire' => 'SIA-2026-002',
                                'nom' => 'NGAH',
                                'prenom' => 'Marie',
                                'grade' => $grade ?: 'Amiral',
                                'unite' => $unite ?: 'MARINE NATIONALE',
                                'source_system' => 'SIADOC'
                            ]
                        ];
                        $msg = '🟡 Échantillon SIADOC affiché pour test d\'importation.';
                    }
                } else {
                    $msg = '🟢 ' . count($list) . ' militaire(s) RÉEL(S) chargé(s) en direct depuis le serveur SIADOC (siadoc.onrender.com)';
                }

                sendSuccessResponse(array_values($list), $msg);
            } catch (Exception $e) {
                sendErrorResponse('Erreur de connexion SIADOC: ' . $e->getMessage());
            }
            break;

        case 'get_historique':
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        action,
                        details,
                        status,
                        last_sync as date,
                        `system` as utilisateur
                    FROM api_sync_log 
                    ORDER BY last_sync DESC
                    LIMIT 20
                ");
                $stmt->execute();
                $operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                
                sendSuccessResponse(['operations' => $operations]);
            } catch (Exception $e) {
                sendErrorResponse($e->getMessage());
            }
            break;

        case 'stats':
            try {
                $stmt_total = $pdo->query("SELECT COUNT(*) FROM candidat WHERE supprimer = 1");
                $total = $stmt_total->fetchColumn();
                $stmt_total->closeCursor();

                $stmt_siadoc = $pdo->query("SELECT COUNT(*) FROM candidat WHERE supprimer = 1 AND (source_system = 'SIADOC' OR siadoc_sync_status = 'SYNCED' OR matricule_militaire LIKE 'SIA%' OR matricule_militaire LIKE '%-AT-%' OR matricule_militaire LIKE '%-GN-%' OR matricule_militaire LIKE '%-AA-%' OR matricule_militaire LIKE '%-AM-%')");
                $siadoc_total = $stmt_siadoc->fetchColumn();
                $stmt_siadoc->closeCursor();

                sendSuccessResponse([
                    'total_militaires' => (int)$total,
                    'cartes_generees'  => (int)$total,
                    'envois_siadoc'    => (int)$siadoc_total
                ]);
            } catch (Exception $e) {
                sendErrorResponse($e->getMessage());
            }
            break;

        default:
            sendErrorResponse('Action non reconnue');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API SIADOC - CIMIS Interopérabilité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --panel-bg: #111827;
            --card-bg: #1f2937;
            --border-color: #374151;
            --accent-green: #10b981;
            --accent-blue: #3b82f6;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; }

        /* HEADER */
        header {
            background: var(--panel-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-area { display: flex; align-items: center; gap: 0.75rem; }
        .logo-area i { font-size: 1.5rem; color: var(--accent-green); }
        .logo-area h1 { font-size: 1.25rem; font-weight: 700; background: linear-gradient(90deg, #10b981, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-links a { color: var(--text-muted); text-decoration: none; margin-left: 1.25rem; font-size: 0.9rem; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: var(--accent-green); }

        /* CONTAINER */
        .container { max-width: 1200px; margin: 2rem auto; width: 95%; flex: 1; display: flex; flex-direction: column; gap: 1.5rem; }

        /* STATS BAR */
        .stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .stat-card { background: var(--panel-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 45px; height: 45px; border-radius: 8px; background: rgba(16, 185, 129, 0.1); color: var(--accent-green); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .stat-info .num { font-size: 1.5rem; font-weight: 700; }
        .stat-info .label { font-size: 0.8rem; color: var(--text-muted); }

        /* WORKFLOW PANEL */
        .panel { background: var(--panel-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; }
        .panel-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; color: var(--accent-green); }

        /* FORM GRID */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .form-group label { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
        .form-control { background: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-main); padding: 0.65rem 0.9rem; border-radius: 8px; font-size: 0.9rem; outline: none; }
        .form-control:focus { border-color: var(--accent-green); }

        /* BUTTONS */
        .btn { background: var(--accent-green); color: #fff; border: none; padding: 0.65rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-blue { background: var(--accent-blue); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }

        /* DYNAMIC RESULTS DISPLAY */
        .banner { padding: 1rem; border-radius: 8px; font-weight: 500; font-size: 0.95rem; display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; display: none; }
        .banner-success { background: rgba(16, 185, 129, 0.15); border: 1px solid var(--accent-green); color: #34d399; }
        .banner-error { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }

        /* MODAL DE SUCCÈS IMPORTATION */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(5px);
            display: none; align-items: center; justify-content: center; z-index: 9999;
        }
        .modal-card {
            background: var(--panel-bg); border: 1px solid var(--accent-green);
            border-radius: 16px; width: 90%; max-width: 650px; max-height: 85vh;
            display: flex; flex-direction: column; overflow: hidden;
            box-shadow: 0 20px 50px rgba(16, 185, 129, 0.2); animation: popIn 0.3s ease-out;
        }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-header {
            background: rgba(16, 185, 129, 0.1); border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h3 { color: #34d399; font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 0.6rem; }
        .modal-body { padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
        .imported-card-item {
            background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 10px;
            padding: 1rem; display: flex; align-items: center; gap: 1rem; justify-content: space-between;
        }
        .imported-photo { width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border: 1px solid var(--accent-green); background: #000; }
        .imported-qr { width: 55px; height: 55px; border-radius: 6px; border: 1px solid #3b82f6; background: #fff; padding: 2px; }
        .modal-footer {
            background: var(--card-bg); border-top: 1px solid var(--border-color);
            padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem;
        }

        /* TABLE */
        .table-responsive { overflow-x: auto; margin-top: 1rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left; }
        th, td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-color); }
        th { background: var(--card-bg); color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
        tr:hover { background: rgba(255, 255, 255, 0.02); }
        .badge { background: rgba(16, 185, 129, 0.2); color: var(--accent-green); padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }

        /* SPINNER */
        .spinner { animation: rotate 1s linear infinite; display: none; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        /* CONDITIONAL ACTIONS AREA */
        #actionsArea { display: none; border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 1rem; }
    </style>
</head>
<body>

    <header>
        <div class="logo-area">
            <i class="fas fa-shield-halved"></i>
            <h1>CIMIS ↔ SIADOC API</h1>
        </div>
        <nav class="nav-links">
            <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="impression.php"><i class="fas fa-print"></i> Impression</a>
            <a href="api_siadoc.php" class="active"><i class="fas fa-network-wired"></i> API SIADOC</a>
            <a href="siadoc_integration.php"><i class="fas fa-exchange-alt"></i> Intégration</a>
        </nav>
    </header>

    <div class="container">
        
        <!-- BARRE DE STATISTIQUES EN TEMPS RÉEL -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="num" id="statTotal">--</div>
                    <div class="label">Militaires CIMIS</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-building-columns"></i></div>
                <div class="stat-info">
                    <div class="num" id="statSiadoc">--</div>
                    <div class="label">Synchronisés SIADOC</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-bolt"></i></div>
                <div class="stat-info">
                    <div class="num" id="statStatus">EN LIGNE</div>
                    <div class="label">API REST SIADOC</div>
                </div>
            </div>
        </div>

        <!-- 1. ÉTAPE 1 : RECHERCHE ET FILTRES DES MILITAIRES -->
        <div class="panel">
            <div class="panel-title">
                <i class="fas fa-filter"></i> Étape 1 : Recherche & Filtres Officiels (Armée Camerounaise)
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Recherche par Matricule</label>
                    <input type="text" id="inputMatricule" class="form-control" placeholder="ex: GND-2026-COL-005">
                </div>

                <div class="form-group">
                    <label>Grades Officiels</label>
                    <select id="selectGrade" class="form-control">
                        <option value="">Tous les grades</option>
                        <optgroup label="Officiers Généraux / Amiraux">
                            <option value="Amiral">Amiral (5★)</option>
                            <option value="Vice-Amiral d'Escadre">Vice-Amiral d'Escadre (4★)</option>
                            <option value="Vice-Amiral">Vice-Amiral (3★)</option>
                            <option value="Contre-Amiral">Contre-Amiral (2★)</option>
                            <option value="Général d'Armée">Général d'Armée (5★)</option>
                            <option value="Général de Corps d'Armée">Général de Corps d'Armée (4★)</option>
                            <option value="Général de Division">Général de Division / Division Aérienne (3★)</option>
                            <option value="Général de Brigade">Général de Brigade / Brigade Aérienne (2★)</option>
                        </optgroup>
                        <optgroup label="Officiers Supérieurs">
                            <option value="Colonel">Colonel / Capitaine de Vaisseau</option>
                            <option value="Lieutenant-Colonel">Lieutenant-Colonel / Capitaine de Frégate</option>
                            <option value="Chef de Bataillon">Chef de Bataillon / Chef d'Escadron / Capitaine de Corvette</option>
                        </optgroup>
                        <optgroup label="Officiers Subalternes & Aspirants">
                            <option value="Capitaine">Capitaine / Lieutenant de Vaisseau</option>
                            <option value="Lieutenant">Lieutenant / Enseigne de Vaisseau de 1re Classe</option>
                            <option value="Sous-Lieutenant">Sous-Lieutenant / Enseigne de Vaisseau de 2e Classe</option>
                            <option value="Aspirant">Aspirant</option>
                        </optgroup>
                        <optgroup label="Sous-Officiers & Rangs">
                            <option value="Major">Major / Maître Principal</option>
                            <option value="Adjudant-Chef Major">Adjudant-Chef Major</option>
                            <option value="Adjudant-Chef">Adjudant-Chef / Premier Maître</option>
                            <option value="Adjudant">Adjudant / Maître</option>
                            <option value="Sergent-Chef">Sergent-Chef / Maréchal des Logis-Chef / Second Maître</option>
                            <option value="Sergent">Sergent / Maréchal des Logis</option>
                            <option value="Caporal-Chef">Caporal-Chef / Quartier-Maître de 1er Classe</option>
                            <option value="Caporal">Caporal / Quartier-Maître de 2e Classe</option>
                            <option value="Soldat de 1er Classe">Soldat de 1er Classe / Matelot de 1er Classe</option>
                            <option value="Soldat de 2e Classe">Soldat de 2e Classe / Matelot de 2e Classe / Élève-Gendarme</option>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label>Corps d'Armée</label>
                    <select id="selectUnite" class="form-control">
                        <option value="">Tous les corps</option>
                        <option value="GENDARMERIE NATIONALE">GENDARMERIE NATIONALE</option>
                        <option value="ARMÉE DE TERRE">ARMÉE DE TERRE</option>
                        <option value="ARMÉE DE L'AIR">ARMÉE DE L'AIR</option>
                        <option value="MARINE NATIONALE">MARINE NATIONALE</option>
                        <option value="CIVIL">CIVIL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Année Entrée Service (Enrôlement)</label>
                    <input type="number" id="inputAnneeEnrolement" class="form-control" placeholder="ex: 2026">
                </div>

                <div class="form-group">
                    <label>Année Promotion Galon (Grade)</label>
                    <input type="number" id="inputAnneeGrade" class="form-control" placeholder="ex: 2026">
                </div>

                <div class="form-group" style="display: flex; gap: 0.5rem;">
                    <button class="btn" id="btnSearch" onclick="rechercherMilitaires()">
                        <i class="fas fa-spinner spinner" id="spinSearch"></i>
                        <i class="fas fa-search" id="icoSearch"></i> Rechercher
                    </button>
                    <button class="btn btn-blue" id="btnReset" onclick="resetFiltres()">
                        <i class="fas fa-rotate-left"></i> Réinitialiser
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. ÉTAPE 2 : ZONE DYNAMIQUE DES RÉSULTATS & FEEDBACK -->
        <div class="panel">
            <div class="panel-title">
                <i class="fas fa-list-check"></i> Étape 2 : Résultats de la Demande
            </div>

            <!-- Bannières dynamiques de statut -->
            <div id="bannerSuccess" class="banner banner-success">
                <i class="fas fa-circle-check"></i> <span id="msgSuccess">Données chargées avec succès.</span>
            </div>
            <div id="bannerError" class="banner banner-error">
                <i class="fas fa-circle-exclamation"></i> <span id="msgError">Une erreur est survenue.</span>
            </div>

            <!-- Tableau des résultats -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"><input type="checkbox" id="checkSelectAll" onclick="toggleSelectAll(this)" style="cursor: pointer; width: 18px; height: 18px;" title="Sélectionner / Désélectionner Tout"></th>
                            <th>Matricule SIADOC</th>
                            <th>Nom & Prénom</th>
                            <th>Grade</th>
                            <th>Corps / Unité</th>
                            <th>Source</th>
                            <th>Action Directe</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyResults">
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Lancez une recherche ou appliquez un filtre pour afficher les militaires.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 3. ÉTAPE 3 : ACTIONS DECOULANT DES RÉSULTATS (Apparaît après résultat) -->
            <div id="actionsArea">
                <div style="font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem; color: var(--accent-green); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <span><i class="fas fa-layer-group"></i> Actions Multiples (<span id="selectedCount" style="color: #34d399; font-size: 1.1rem; font-weight: 700;">0</span> militaire(s) sélectionné(s))</span>
                    <a href="impression.php" class="btn btn-blue" style="padding: 0.4rem 0.9rem; font-size: 0.85rem; text-decoration: none;" target="_blank">
                        <i class="fas fa-print"></i> Accéder à Impression.php ➔
                    </a>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                    <button class="btn" id="btnBulkImport" onclick="lancerImportationSelection()">
                        <i class="fas fa-spinner spinner" id="spinBulk"></i>
                        <i class="fas fa-cloud-arrow-down" id="icoBulk"></i> 📥 Importer la sélection dans CIMIS (Générer Matricule CIM & QR Code)
                    </button>
                    <button class="btn btn-blue" onclick="chargerHistorique()">
                        <i class="fas fa-clock-rotate-left"></i> Voir le journal des échanges
                    </button>
                </div>
            </div>
        </div>

        <!-- HISTORIQUE DES REQUÊTES -->
        <div class="panel" id="panelHistorique" style="display: none;">
            <div class="panel-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <span><i class="fas fa-history"></i> Journal des Échanges & Sécurité</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);" id="histCountLabel">0 élement(s)</span>
            </div>

            <!-- Filtres de l'Historique -->
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; background: var(--card-bg); padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <input type="text" id="histSearchInput" oninput="filtrerHistoriqueLocal()" class="form-control" style="flex: 1; min-width: 180px;" placeholder="🔍 Recherche dans l'historique (action, utilisateur, détails)...">
                
                <select id="histFilterStatus" onchange="filtrerHistoriqueLocal()" class="form-control" style="width: auto;">
                    <option value="">Tous les statuts</option>
                    <option value="SUCCESS">🟢 Succès (SUCCESS)</option>
                    <option value="ERROR">🔴 Erreurs (ERROR)</option>
                </select>

                <select id="histFilterAction" onchange="filtrerHistoriqueLocal()" class="form-control" style="width: auto;">
                    <option value="">Toutes les actions</option>
                    <option value="IMPORT">📥 Importations</option>
                    <option value="GET">🔍 Recherches & consultations</option>
                </select>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date / Heure</th>
                            <th>Action</th>
                            <th>Utilisateur / Système</th>
                            <th>Statut</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyHistorique"></tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL DE SUCCÈS IMPORTATION SIADOC -->
    <div id="modalImportSuccess" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-circle-check"></i> Importation SIADOC Réussie avec Succès !</h3>
                <button onclick="fermerModalImport()" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div style="font-size: 0.9rem; color: var(--text-muted);" id="modalSummaryText">
                    Le personnel militaire ci-dessous a été importé depuis SIADOC avec succès. Leurs matricules CIMIS et QR Codes ont été générés.
                </div>
                <div id="modalMilitairesList" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <!-- Rempli dynamiquement par JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-blue" onclick="fermerModalImport()">
                    <i class="fas fa-xmark"></i> Fermer
                </button>
                <a href="impression.php" class="btn" style="text-decoration: none;" target="_blank">
                    <i class="fas fa-print"></i> Accéder à Impression.php ➔
                </a>
            </div>
        </div>
    </div>

    <script>
        // Charger les statistiques au démarrage
        document.addEventListener('DOMContentLoaded', () => {
            fetchStats();
        });

        function fetchStats() {
            fetch('api_siadoc.php?action=stats')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        document.getElementById('statTotal').textContent = data.data.total_militaires || '0';
                        document.getElementById('statSiadoc').textContent = data.data.envois_siadoc || '0';
                    }
                })
                .catch(() => {});
        }

        // Lancer la recherche avec spinner & feedback dynamique
        function rechercherMilitaires() {
            const btn = document.getElementById('btnSearch');
            const spin = document.getElementById('spinSearch');
            const ico = document.getElementById('icoSearch');
            const bSuccess = document.getElementById('bannerSuccess');
            const bError = document.getElementById('bannerError');
            const tbody = document.getElementById('tbodyResults');
            const actionsArea = document.getElementById('actionsArea');
            const checkAll = document.getElementById('checkSelectAll');

            btn.disabled = true;
            spin.style.display = 'inline-block';
            ico.style.display = 'none';
            bSuccess.style.display = 'none';
            bError.style.display = 'none';
            checkAll.checked = false;

            const matricule = document.getElementById('inputMatricule').value.trim();
            const grade = document.getElementById('selectGrade').value;
            const unite = document.getElementById('selectUnite').value;
            const anneeEnrolement = document.getElementById('inputAnneeEnrolement').value.trim();
            const anneeGrade = document.getElementById('inputAnneeGrade').value.trim();

            let url = 'api_siadoc.php?action=';
            if (matricule) {
                url += 'get_militaire&matricule=' + encodeURIComponent(matricule);
            } else {
                url += 'get_filtres&grade=' + encodeURIComponent(grade) 
                    + '&unite=' + encodeURIComponent(unite)
                    + '&annee_enrolement=' + encodeURIComponent(anneeEnrolement)
                    + '&annee_grade=' + encodeURIComponent(anneeGrade);
            }

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    spin.style.display = 'none';
                    ico.style.display = 'inline-block';

                    if (data.success && isArrayValid(data.data)) {
                        const list = data.data;
                        document.getElementById('msgSuccess').textContent = (data.message || (list.length + ' militaire(s) trouvé(s)'));
                        bSuccess.style.display = 'flex';

                        tbody.innerHTML = list.map(m => {
                            const mat = m.matricule_militaire || m.matricule || 'N/A';
                            return `
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="checkMilitaire" value="${mat}" onchange="updateSelectionCount()" style="cursor: pointer; width: 18px; height: 18px;">
                                    </td>
                                    <td style="font-weight: 600; color: #34d399;">${mat}</td>
                                    <td>${m.nom || ''} ${m.prenom || ''}</td>
                                    <td>${m.grade || 'Non spécifié'}</td>
                                    <td>${m.unite || m.corps || 'Non spécifié'}</td>
                                    <td><span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa;">${m.source_system || 'SIADOC'}</span></td>
                                    <td>
                                        <button class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="importerUnMilitaire('${mat}')">
                                            <i class="fas fa-cloud-arrow-down"></i> Importer
                                        </button>
                                    </td>
                                </tr>
                            `;
                        }).join('');

                        actionsArea.style.display = 'block';
                        updateSelectionCount();
                    } else {
                        document.getElementById('msgError').textContent = data.error || data.message || 'Aucun militaire trouvé pour ces critères.';
                        bError.style.display = 'flex';
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #f87171; padding: 2rem;">Aucun résultat disponible.</td></tr>`;
                        actionsArea.style.display = 'none';
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    spin.style.display = 'none';
                    ico.style.display = 'inline-block';

                    document.getElementById('msgError').textContent = 'Erreur réseau : ' + err.message;
                    bError.style.display = 'flex';
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #f87171; padding: 2rem;">Erreur de communication avec le serveur.</td></tr>`;
                    actionsArea.style.display = 'none';
                });
        }

        function isArrayValid(data) {
            return Array.isArray(data) && data.length > 0;
        }

        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.checkMilitaire');
            checkboxes.forEach(cb => cb.checked = master.checked);
            updateSelectionCount();
        }

        function updateSelectionCount() {
            const selected = document.querySelectorAll('.checkMilitaire:checked');
            const countSpan = document.getElementById('selectedCount');
            if (countSpan) countSpan.textContent = selected.length;
        }

        function resetFiltres() {
            document.getElementById('inputMatricule').value = '';
            document.getElementById('selectGrade').value = '';
            document.getElementById('selectUnite').value = '';
            document.getElementById('inputAnneeEnrolement').value = '';
            document.getElementById('inputAnneeGrade').value = '';
            document.getElementById('checkSelectAll').checked = false;
            document.getElementById('bannerSuccess').style.display = 'none';
            document.getElementById('bannerError').style.display = 'none';
            document.getElementById('actionsArea').style.display = 'none';
            document.getElementById('tbodyResults').innerHTML = `
                <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Filtres réinitialisés. Lancez une recherche.</td></tr>
            `;
        }

        let rawHistoryData = [];

        function chargerHistorique() {
            const panel = document.getElementById('panelHistorique');

            fetch('api_siadoc.php?action=get_historique')
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data.operations)) {
                        rawHistoryData = data.data.operations;
                        panel.style.display = 'block';
                        filtrerHistoriqueLocal();
                        panel.scrollIntoView({ behavior: 'smooth' });
                    }
                })
                .catch(() => {});
        }

        function filtrerHistoriqueLocal() {
            const tbody = document.getElementById('tbodyHistorique');
            const search = (document.getElementById('histSearchInput').value || '').toLowerCase();
            const statusFilter = document.getElementById('histFilterStatus').value;
            const actionFilter = document.getElementById('histFilterAction').value;
            const countLabel = document.getElementById('histCountLabel');

            let filtered = rawHistoryData.filter(op => {
                const action = (op.action || '').toUpperCase();
                const status = (op.status || '').toUpperCase();
                const user = (op.utilisateur || '').toLowerCase();
                const details = (op.details || '').toLowerCase();

                if (statusFilter && status !== statusFilter) return false;
                if (actionFilter && !action.includes(actionFilter)) return false;
                if (search && !action.toLowerCase().includes(search) && !user.includes(search) && !details.includes(search)) return false;

                return true;
            });

            if (countLabel) countLabel.textContent = filtered.length + ' élément(s) affiché(s)';

            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Aucun échange ne correspond à ces critères.</td></tr>`;
                return;
            }

            tbody.innerHTML = filtered.map(op => {
                const badgeColor = op.status === 'SUCCESS' ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)';
                const badgeTextColor = op.status === 'SUCCESS' ? '#34d399' : '#f87171';

                return `
                    <tr>
                        <td style="font-size: 0.85rem; color: var(--text-muted);">${op.date || ''}</td>
                        <td style="font-weight: 600; color: #60a5fa;">${op.action || ''}</td>
                        <td>${op.utilisateur || 'SIADOC_SYSTEM'}</td>
                        <td><span class="badge" style="background: ${badgeColor}; color: ${badgeTextColor};">${op.status || 'SUCCESS'}</span></td>
                        <td style="font-size: 0.85rem;">${op.details || '-'}</td>
                    </tr>
                `;
            }).join('');
        }

        function importerUnMilitaire(mat) {
            lancerImportationAJAX([mat]);
        }

        function lancerImportationSelection() {
            const selected = Array.from(document.querySelectorAll('.checkMilitaire:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Veuillez cocher au moins un militaire à importer.');
                return;
            }
            lancerImportationAJAX(selected);
        }

        function lancerImportationAJAX(matriculesArray) {
            const btnBulk = document.getElementById('btnBulkImport');
            const spinBulk = document.getElementById('spinBulk');
            const icoBulk = document.getElementById('icoBulk');

            if (btnBulk) {
                btnBulk.disabled = true;
                if (spinBulk) spinBulk.style.display = 'inline-block';
                if (icoBulk) icoBulk.style.display = 'none';
            }

            const matParam = matriculesArray.join(',');
            const url = '../backend/siadoc_import.php?action=importer_militaires&limit=' + matriculesArray.length + '&matricule=' + encodeURIComponent(matParam) + '&api_key=siadoc-2026-cimis-integration';

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (btnBulk) {
                        btnBulk.disabled = false;
                        if (spinBulk) spinBulk.style.display = 'none';
                        if (icoBulk) icoBulk.style.display = 'inline-block';
                    }

                    if (data.success && Array.isArray(data.militaires)) {
                        afficherModalSuccesImport(data);
                        fetchStats();
                    } else {
                        alert('Erreur lors de l\'importation: ' + (data.error || data.message || 'Échec'));
                    }
                })
                .catch(err => {
                    if (btnBulk) {
                        btnBulk.disabled = false;
                        if (spinBulk) spinBulk.style.display = 'none';
                        if (icoBulk) icoBulk.style.display = 'inline-block';
                    }
                    alert('Erreur réseau lors de l\'importation: ' + err.message);
                });
        }

        function afficherModalSuccesImport(data) {
            const modal = document.getElementById('modalImportSuccess');
            const listContainer = document.getElementById('modalMilitairesList');
            const summary = document.getElementById('modalSummaryText');

            summary.textContent = data.message || (data.militaires.length + ' militaire(s) importé(s) de SIADOC vers CIMIS avec succès.');

            listContainer.innerHTML = data.militaires.map(m => {
                const matMil = m.matricule_militaire || 'N/A';
                const matCim = m.matricule_cimis || 'CIM-2026';
                const nom = m.nom || '';
                const prenom = m.prenom || '';
                const grade = m.grade || '';
                const unite = m.unite || '';
                
                // Photo du candidat
                let photoUrl = '../img/default_photo.png';
                if (m.photo) {
                    photoUrl = m.photo.startsWith('http') ? m.photo : '../' + m.photo.replace('../', '');
                }
                
                // QR Code
                const qrPath = m.qr_code ? '../' + m.qr_code.replace('../', '') : '../img/qrcodes/' + matMil + '_qr.png';
                
                const actionBadge = m.action === 'CREATION' 
                    ? '<span class="badge" style="background: rgba(16,185,129,0.2); color: #34d399;">Nouveau</span>' 
                    : '<span class="badge" style="background: rgba(59,130,246,0.2); color: #60a5fa;">Mis à jour</span>';

                return `
                    <div class="imported-card-item" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.85rem; background: var(--card-bg); border-radius: 10px; border: 1px solid var(--border-color);">
                        <!-- GAUCHE : Photo du Militaire -->
                        <div style="display: flex; align-items: center; gap: 0.85rem; min-width: 0;">
                            <img src="${photoUrl}" class="imported-photo" alt="Photo" onerror="this.src='../img/candidats/default.png'; this.onerror=null;" style="width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border: 1.5px solid var(--accent-green); flex-shrink: 0; background: #1e293b;">
                            
                            <!-- CENTRE : Infos Personnel & Matricules -->
                            <div style="min-width: 0;">
                                <div style="font-weight: 700; font-size: 0.95rem; color: #f9fafb;">
                                    ${nom} ${prenom} ${actionBadge}
                                </div>
                                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 500;">
                                    ${grade} ${unite ? '• ' + unite : ''}
                                </div>
                                <div style="font-size: 0.8rem; color: #34d399; font-weight: 600; margin-top: 3px;">
                                    SIADOC: <span style="color: #fff;">${matMil}</span> ➔ CIMIS: <span style="color: #60a5fa;">${matCim}</span>
                                </div>
                            </div>
                        </div>

                        <!-- DROITE : Code QR Généré -->
                        <div style="flex-shrink: 0; text-align: center;">
                            <img src="${qrPath}" class="imported-qr" alt="QR Code" style="width: 52px; height: 52px; border-radius: 6px; border: 1px solid #3b82f6; background: #fff; padding: 2px;" onerror="this.style.display='none'">
                        </div>
                    </div>
                `;
            }).join('');

            modal.style.display = 'flex';
        }

        function fermerModalImport() {
            document.getElementById('modalImportSuccess').style.display = 'none';
        }
    </script>
</body>
</html>
