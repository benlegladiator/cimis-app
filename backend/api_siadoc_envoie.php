<?php
/**
 * API SIADOC ENVOIE - VERSION CONSOLIDÃ‰E v2.0
 * Expose les donnÃ©es CIMIS pour SIADOC
 * 
 * Logique BDD :
 *   supprimer = 0  â†’ actif (pas en corbeille)
 *   supprimer = 1  â†’ en corbeille (supprimÃ©)
 *   suspendus = 0  â†’ peut voir/imprimer sa carte
 *   suspendus = 1  â†’ suspendu, carte non visible/imprimable
 *
 * URL SIADOC : https://siadoc.onrender.com
 * ClÃ© API    : siadoc-2026-cimis-integration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// â”€â”€â”€ AUTHENTIFICATION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function verifyApiKey() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    // Normaliser les headers (certains serveurs les passent en minuscules)
    $headers_lower = array_change_key_case($headers, CASE_LOWER);

    $api_key = $headers_lower['x-api-key']
        ?? $headers_lower['authorization']
        ?? $_SERVER['HTTP_X_API_KEY']
        ?? $_GET['api_key']
        ?? $_POST['api_key']
        ?? null;

    // Enlever le prÃ©fixe "Bearer " si prÃ©sent
    if ($api_key && str_starts_with($api_key, 'Bearer ')) {
        $api_key = substr($api_key, 7);
    }

    if (!$api_key || $api_key !== SIADOC_API_KEY) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error'   => 'ClÃ© API invalide ou manquante',
            'code'    => 'INVALID_API_KEY'
        ]);
        exit();
    }
}

// â”€â”€â”€ UTILITAIRES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function encodeImageToBase64($image_path) {
    if ($image_path && file_exists($image_path)) {
        $image_data = file_get_contents($image_path);
        $mime_type  = mime_content_type($image_path) ?: 'image/png';
        return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
    }
    return null;
}

function resolveImagePath($relative_path) {
    if (!$relative_path) return null;
    // Essayer plusieurs chemins possibles
    $candidates = [
        $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($relative_path, '/'),
        dirname(__DIR__) . '/' . ltrim($relative_path, '/'),
        __DIR__ . '/../' . ltrim($relative_path, '/')
    ];
    foreach ($candidates as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
}

function sendResponse($data, $message = null, $http_code = 200) {
    http_response_code($http_code);
    $response = [
        'success'   => true,
        'data'      => $data,
        'timestamp' => date('c')
    ];
    if ($message) $response['message'] = $message;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

function sendError($message, $http_code = 400, $code = null) {
    http_response_code($http_code);
    $resp = [
        'success'   => false,
        'error'     => $message,
        'timestamp' => date('c')
    ];
    if ($code) $resp['code'] = $code;
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
}

function logAPIAccess($action, $details = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO api_sync_log (system, action, status, details, last_sync)
            VALUES ('CIMIS_EXPORT', ?, 'SUCCESS', ?, NOW())
        ");
        $stmt->execute([$action, $details ? json_encode($details) : null]);
    } catch (Exception $e) {
        // Silencieux — ne pas bloquer la réponse si le log échoue
    }
}

// â”€â”€â”€ COLONNES SQL COMMUNES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

define('SQL_COLONNES_CARTE', "
    c.id,
    c.matricule,
    c.matricule_militaire,
    c.nom,
    c.prenom,
    c.date_naissance,
    c.lieu_naissance,
    c.sexe,
    c.numero_cni,
    c.date_enrolement,
    c.date_dernier_grade,
    c.annee_dernier_galon,
    c.grade,
    c.unite,
    c.photo,
    c.taille,
    c.poids,
    c.groupe_sanguin,
    c.code_qr,
    c.empreinte_data,
    c.source_system,
    c.type_personnel,
    c.statut_militaire,
    c.categorie_civil,
    c.suspendus,
    c.date_modification
");

// â”€â”€â”€ AUTHENTIFICATION (obligatoire pour tous les endpoints sauf OPTIONS) â”€â”€â”€â”€â”€â”€

verifyApiKey();

// â”€â”€â”€ ROUTAGE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

$request_method = $_SERVER['REQUEST_METHOD'];
$path_info      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts     = explode('/', trim($path_info, '/'));

// Support double mode : ?action=xxx  OU  /chemin/action
$action = $_GET['action'] ?? $path_parts[1] ?? 'aide';

switch ($action) {

    // â”€â”€ AIDE / DOCUMENTATION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'aide':
    case 'help':
        sendResponse([
            'version'     => '2.0.0',
            'system'      => 'CIMIS',
            'description' => 'API d\'exposition des donnÃ©es CIMIS pour SIADOC',
            'endpoints'   => [
                'GET  ?action=aide'                               => 'Documentation des endpoints',
                'GET  ?action=carte&matricule=XXX'               => 'RÃ©cupÃ©rer une carte par matricule',
                'GET  ?action=cartes'                            => 'RÃ©cupÃ©rer toutes les cartes actives',
                'GET  ?action=cartes&grade=Capitaine'           => 'Filtrer par grade',
                'GET  ?action=cartes&unite=AT'                  => 'Filtrer par unitÃ©',
                'GET  ?action=cartes&sexe=MASCULIN'             => 'Filtrer par sexe',
                'GET  ?action=cartes&annee_galon=2025'          => 'Filtrer par annÃ©e de dernier galon',
                'GET  ?action=cartes&annee_entree=2014'         => 'Filtrer par annÃ©e entrÃ©e service',
                'GET  ?action=cartes&periode_entree=2014-2016'  => 'Filtrer par pÃ©riode d\'entrÃ©e',
                'GET  ?action=cartes&age_min=25&age_max=45'     => 'Filtrer par tranche d\'Ã¢ge',
                'GET  ?action=cartes&statut_militaire=ACTIF'    => 'Filtrer par statut militaire',
                'GET  ?action=cartes&include_images=true'       => 'Inclure photos/QR en base64',
                'GET  ?action=cartes&page=2&limit=50'           => 'Pagination',
                'GET  ?action=cartes&matricules=T14/xxx,M15/yyy'=> 'Plusieurs matricules (max 100)',
                'GET  ?action=statistiques'                     => 'Statistiques globales',
                'GET  ?action=biometrie&matricule=XXX'          => 'DonnÃ©es biomÃ©triques',
                'GET  ?action=recherche&q=terme'                => 'Recherche texte libre',
                'GET  ?action=synchronisation'                  => 'Sync incrÃ©mentielle',
                'GET  ?action=sante'                            => 'Health check',
                'POST ?action=webhook'                          => 'Recevoir notifications SIADOC',
            ],
            'logique_bdd' => [
                'actif'         => 'supprimer = 1 (actif)',
                'corbeille'     => 'supprimer = 1 (supprimÃ© logiquement)',
                'peut_voir'     => 'suspendus = 0 (carte visible et imprimable)',
                'suspendu'      => 'suspendus = 1 (carte non affichable)',
            ],
            'auth' => 'Header X-API-KEY ou ?api_key= requis pour toutes les requÃªtes'
        ]);
        break;

    // â”€â”€ SANTÃ‰ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'sante':
    case 'health':
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM candidat WHERE supprimer = 1");
            $count = $stmt->fetch()['total'];
            sendResponse([
                'status'        => 'operationnel',
                'database'      => 'connectee',
                'total_actifs'  => $count,
                'version'       => '2.0.0',
                'timestamp'     => date('c')
            ], 'API CIMIS opÃ©rationnelle');
        } catch (PDOException $e) {
            sendError('Base de donnÃ©es inaccessible: ' . $e->getMessage(), 503, 'DB_ERROR');
        }
        break;

    // â”€â”€ CARTE INDIVIDUELLE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'carte':
        $matricule = $_GET['matricule'] ?? $path_parts[2] ?? null;
        if (!$matricule) {
            sendError('ParamÃ¨tre matricule requis', 400, 'MISSING_PARAM');
            break;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT " . SQL_COLONNES_CARTE . "
                FROM candidat c
                WHERE (c.matricule = ? OR c.matricule_militaire = ?)
                  AND c.supprimer = 1
            ");
            $stmt->execute([$matricule, $matricule]);
            $carte = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$carte) {
                sendError('Carte non trouvÃ©e pour le matricule: ' . $matricule, 404, 'NOT_FOUND');
                break;
            }

            // Images
            $carte['peut_voir_carte'] = ($carte['suspendus'] == 0);
            if ($carte['photo']) {
                $path = resolveImagePath($carte['photo']);
                $carte['photo_base64'] = $path ? encodeImageToBase64($path) : null;
            }
            if ($carte['code_qr']) {
                $path = resolveImagePath($carte['code_qr']);
                $carte['qr_code_base64'] = $path ? encodeImageToBase64($path) : null;
            }
            $carte['url_verification'] = 'https://cimis.ct.ws/verify/' . urlencode($carte['matricule_militaire'] ?? $carte['matricule']);

            logAPIAccess('GET_CARTE', ['matricule' => $matricule]);
            sendResponse($carte, 'Carte rÃ©cupÃ©rÃ©e avec succÃ¨s');

        } catch (PDOException $e) {
            sendError('Erreur base de donnÃ©es: ' . $e->getMessage(), 500, 'DB_ERROR');
        }
        break;

    // â”€â”€ LISTE DES CARTES (avec filtres avancÃ©s) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'cartes':
        // Support ?matricules=XXX,YYY (batch)
        if (isset($_GET['matricules'])) {
            $matricules = array_filter(array_map('trim', explode(',', $_GET['matricules'])));
            if (empty($matricules)) {
                sendError('Aucun matricule valide fourni', 400, 'MISSING_PARAM');
                break;
            }
            $matricules = array_slice($matricules, 0, 100);
            $placeholders = implode(',', array_fill(0, count($matricules), '?'));

            try {
                $stmt = $pdo->prepare("
                    SELECT " . SQL_COLONNES_CARTE . "
                    FROM candidat c
                    WHERE c.supprimer = 1
                      AND c.matricule_militaire IN ($placeholders)
                    ORDER BY c.nom, c.prenom
                ");
                $stmt->execute($matricules);
                $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (isset($_GET['include_images']) && $_GET['include_images'] === 'true') {
                    foreach ($resultats as &$r) {
                        if ($r['photo'])   $r['photo_base64']   = encodeImageToBase64(resolveImagePath($r['photo']));
                        if ($r['code_qr']) $r['qr_code_base64'] = encodeImageToBase64(resolveImagePath($r['code_qr']));
                    }
                    unset($r);
                }

                sendResponse([
                    'militaires'          => $resultats,
                    'matricules_recherches'=> $matricules,
                    'total_trouves'       => count($resultats),
                    'total_demandes'      => count($matricules),
                    'non_trouves'         => array_values(array_diff($matricules, array_column($resultats, 'matricule_militaire')))
                ]);
            } catch (PDOException $e) {
                sendError('Erreur base de donnÃ©es: ' . $e->getMessage(), 500, 'DB_ERROR');
            }
            break;
        }

        // RequÃªte standard avec filtres
        try {
            $sql    = "SELECT " . SQL_COLONNES_CARTE . " FROM candidat c WHERE c.supprimer = 1";
            $params = [];

            // Filtre matricule (recherche dans matricule_militaire uniquement)
            if (!empty($_GET['matricule'])) {
                $sql .= " AND c.matricule_militaire = ?";
                $params[] = $_GET['matricule'];
            }

            // Filtre grade (accepte tableau ou valeur unique)
            if (!empty($_GET['grade'])) {
                $grades = is_array($_GET['grade']) ? $_GET['grade'] : [$_GET['grade']];
                $sql .= " AND c.grade IN (" . implode(',', array_fill(0, count($grades), '?')) . ")";
                $params = array_merge($params, $grades);
            }

            // Filtre unitÃ©
            if (!empty($_GET['unite'])) {
                $unites = is_array($_GET['unite']) ? $_GET['unite'] : [$_GET['unite']];
                $sql .= " AND c.unite IN (" . implode(',', array_fill(0, count($unites), '?')) . ")";
                $params = array_merge($params, $unites);
            }

            // Filtre statut militaire
            if (!empty($_GET['statut_militaire'])) {
                $statuts = is_array($_GET['statut_militaire']) ? $_GET['statut_militaire'] : [$_GET['statut_militaire']];
                $sql .= " AND c.statut_militaire IN (" . implode(',', array_fill(0, count($statuts), '?')) . ")";
                $params = array_merge($params, $statuts);
            }

            // Filtre sexe
            if (!empty($_GET['sexe'])) {
                $sql .= " AND c.sexe = ?";
                $params[] = strtoupper($_GET['sexe']);
            }

            // Filtre annÃ©e dernier galon
            if (!empty($_GET['annee_galon'])) {
                $sql .= " AND YEAR(c.annee_dernier_galon) = ?";
                $params[] = (int)$_GET['annee_galon'];
            }

            // Filtre annÃ©e entrÃ©e en service
            if (!empty($_GET['annee_entree'])) {
                $annee = preg_replace('/[^0-9]/', '', $_GET['annee_entree']);
                $sql .= " AND c.date_enrolement LIKE ?";
                $params[] = $annee . '%';
            }

            // Filtre pÃ©riode d'entrÃ©e (ex: 2014-2016)
            if (!empty($_GET['periode_entree'])) {
                $periodes = explode('-', $_GET['periode_entree']);
                if (count($periodes) === 2) {
                    $sql .= " AND YEAR(c.date_enrolement) BETWEEN ? AND ?";
                    $params[] = (int)$periodes[0];
                    $params[] = (int)$periodes[1];
                }
            }

            // Filtre âge min (compatible ANSI SQL/MySQL/PostgreSQL)
            if (!empty($_GET['age_min'])) {
                $sql .= " AND (YEAR(CURRENT_DATE) - YEAR(c.date_naissance)) >= ?";
                $params[] = (int)$_GET['age_min'];
            }

            // Filtre âge max (compatible ANSI SQL/MySQL/PostgreSQL)
            if (!empty($_GET['age_max'])) {
                $sql .= " AND (YEAR(CURRENT_DATE) - YEAR(c.date_naissance)) <= ?";
                $params[] = (int)$_GET['age_max'];
            }

            // Filtre pÃ©riode d'enrÃ´lement (ex: 2023-01-01,2023-12-31)
            if (!empty($_GET['periode'])) {
                $periodes = explode(',', $_GET['periode']);
                if (count($periodes) === 2) {
                    $sql .= " AND c.date_enrolement BETWEEN ? AND ?";
                    $params[] = trim($periodes[0]);
                    $params[] = trim($periodes[1]);
                }
            }

            // Filtre source systÃ¨me
            if (!empty($_GET['source'])) {
                $sql .= " AND c.source_system = ?";
                $params[] = strtoupper($_GET['source']);
            }

            // Filtre suspendus (par dÃ©faut on retourne tous actifs, suspendus inclus)
            if (isset($_GET['suspendus'])) {
                $sql .= " AND c.suspendus = ?";
                $params[] = (int)$_GET['suspendus'];
            }

            // Comptage total (sans LIMIT/OFFSET)
            $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM candidat c WHERE c.supprimer = 1" .
                substr($sql, strpos($sql, 'WHERE c.supprimer = 1') + strlen('WHERE c.supprimer = 1')));
            $count_stmt->execute($params);
            $total = (int)$count_stmt->fetch()['total'];

            // Tri et pagination
            $sql  .= " ORDER BY c.nom, c.prenom";
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(500, max(1, (int)($_GET['limit'] ?? 100)));
            $offset = ($page - 1) * $limit;
            $sql  .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt  = $pdo->prepare($sql);
            $stmt->execute($params);
            $cartes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Images en base64 si demandÃ©es
            if (isset($_GET['include_images']) && $_GET['include_images'] === 'true') {
                foreach ($cartes as &$carte) {
                    if ($carte['photo'])   $carte['photo_base64']   = encodeImageToBase64(resolveImagePath($carte['photo']));
                    if ($carte['code_qr']) $carte['qr_code_base64'] = encodeImageToBase64(resolveImagePath($carte['code_qr']));
                }
                unset($carte);
            }

            // Ajouter champ synthÃ©tique peut_voir_carte
            foreach ($cartes as &$c) {
                $c['peut_voir_carte'] = ($c['suspendus'] == 0);
            }
            unset($c);

            logAPIAccess('GET_CARTES', ['total' => $total, 'filtres' => array_keys($_GET)]);
            sendResponse([
                'cartes'           => $cartes,
                'pagination'       => [
                    'page'  => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int)ceil($total / $limit)
                ],
                'filtres_appliques' => [
                    'matricule'       => $_GET['matricule']       ?? null,
                    'grade'           => $_GET['grade']           ?? null,
                    'unite'           => $_GET['unite']           ?? null,
                    'annee_galon'     => $_GET['annee_galon']     ?? null,
                    'annee_entree'    => $_GET['annee_entree']    ?? null,
                    'periode_entree'  => $_GET['periode_entree']  ?? null,
                    'age_min'         => $_GET['age_min']         ?? null,
                    'age_max'         => $_GET['age_max']         ?? null,
                    'statut_militaire'=> $_GET['statut_militaire']?? null,
                    'sexe'            => $_GET['sexe']            ?? null,
                    'suspendus'       => $_GET['suspendus']       ?? null,
                ]
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de donnÃ©es: ' . $e->getMessage(), 500, 'DB_ERROR');
        }
        break;

    // â”€â”€ STATISTIQUES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'statistiques':
        try {
            $stmt = $pdo->query("
                SELECT
                    COUNT(*) as total_actifs,
                    COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc,
                    COUNT(CASE WHEN source_system = 'CIMIS'  THEN 1 END) as venus_de_cimis,
                    COUNT(CASE WHEN suspendus = 1            THEN 1 END) as suspendus,
                    COUNT(CASE WHEN suspendus = 0            THEN 1 END) as actifs_visibles,
                    COUNT(CASE WHEN code_qr IS NOT NULL      THEN 1 END) as avec_qr_code,
                    COUNT(CASE WHEN empreinte_data IS NOT NULL THEN 1 END) as avec_biometrie,
                    COUNT(CASE WHEN photo IS NOT NULL        THEN 1 END) as avec_photo,
                    COUNT(DISTINCT unite)                                 as unites_differentes,
                    COUNT(DISTINCT grade)                                 as grades_differents,
                    MAX(date_modification)                                as derniere_modification
                FROM candidat
                WHERE supprimer = 1
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Par unitÃ©
            $stmt = $pdo->query("
                SELECT unite, COUNT(*) as effectif,
                       COUNT(CASE WHEN suspendus=0 THEN 1 END) as actifs
                FROM candidat WHERE supprimer = 1
                GROUP BY unite ORDER BY effectif DESC LIMIT 20
            ");
            $par_unite = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Par grade
            $stmt = $pdo->query("
                SELECT grade, COUNT(*) as effectif
                FROM candidat WHERE supprimer = 1
                GROUP BY grade ORDER BY effectif DESC LIMIT 20
            ");
            $par_grade = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Par source
            $stmt = $pdo->query("
                SELECT source_system, COUNT(*) as effectif,
                       MAX(date_modification) as derniere_maj
                FROM candidat WHERE supprimer = 1
                GROUP BY source_system
            ");
            $par_source = $stmt->fetchAll(PDO::FETCH_ASSOC);

            sendResponse([
                'generales'       => $stats,
                'par_unite'       => $par_unite,
                'par_grade'       => $par_grade,
                'par_source'      => $par_source,
                'date_generation' => date('c')
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de donnÃ©es: ' . $e->getMessage(), 500, 'DB_ERROR');
        }
        break;

    // â”€â”€ BIOMÃ‰TRIE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'biometrie':
        $matricule = $_GET['matricule'] ?? $path_parts[2] ?? null;
        if (!$matricule) {
            sendError('ParamÃ¨tre matricule requis', 400, 'MISSING_PARAM');
            break;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.photo,
                    c.code_qr,
                    c.empreinte_data,
                    c.taille,
                    c.poids,
                    c.groupe_sanguin,
                    c.suspendus
                FROM candidat c
                WHERE (c.matricule = ? OR c.matricule_militaire = ?)
                  AND c.supprimer = 1
            ");
            $stmt->execute([$matricule, $matricule]);
            $bio = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bio) {
                sendError('Carte non trouvÃ©e', 404, 'NOT_FOUND');
                break;
            }

            if ($bio['photo'])   $bio['photo_base64']   = encodeImageToBase64(resolveImagePath($bio['photo']));
            if ($bio['code_qr']) $bio['qr_code_base64'] = encodeImageToBase64(resolveImagePath($bio['code_qr']));
            $bio['peut_voir_carte'] = ($bio['suspendus'] == 0);
            $bio['url_verification'] = 'https://cimis.ct.ws/verify/' . urlencode($bio['matricule_militaire'] ?? $bio['matricule']);

            sendResponse($bio, 'DonnÃ©es biomÃ©triques rÃ©cupÃ©rÃ©es avec succÃ¨s');

        } catch (PDOException $e) {
            sendError('Erreur base de donnÃ©es: ' . $e->getMessage(), 500, 'DB_ERROR');
        }
        break;

    // â”€â”€ RECHERCHE TEXTE LIBRE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'recherche':
        $terme = trim($_GET['q'] ?? '');
        if (strlen($terme) < 2) {
            sendError('Terme de recherche requis (minimum 2 caractÃ¨res)', 400, 'MISSING_PARAM');
            break;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT
                    c.id, c.matricule, c.matricule_militaire,
                    c.nom, c.prenom, c.grade, c.unite,
                    c.date_enrolement, c.suspendus, c.source_system
                FROM candidat c
                WHERE c.supprimer = 1
                  AND (
                      c.matricule          LIKE ? OR
                      c.matricule_militaire LIKE ? OR
                      c.nom                LIKE ? OR
                      c.prenom             LIKE ? OR
                      c.unite              LIKE ? OR
                      c.grade              LIKE ?
                  )
                ORDER BY c.nom, c.prenom
                LIMIT 50
            ");
            $s = '%' . $terme . '%';
            $stmt->execute([$s, $s, $s, $s, $s, $s]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($resultats as &$r) {
                $r['peut_voir_carte'] = ($r['suspendus'] == 0);
            }
            unset($r);

            sendResponse([
                'resultats'     => $resultats,
                'terme_recherche' => $terme,
                'total_trouve'  => count($resultats)
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de donnÃ©es: ' . $e->getMessage(), 500, 'DB_ERROR');
        }
        break;

    // â”€â”€ SYNCHRONISATION INCRÃ‰MENTIELLE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'synchronisation':
        try {
            // RÃ©cupÃ©rer la date de la derniÃ¨re sync rÃ©ussie
            $last_sync = null;
            try {
                $stmt = $pdo->prepare("
                    SELECT last_sync FROM api_sync_log
                    WHERE system = 'SIADOC_SYNC' AND status = 'SUCCESS'
                    ORDER BY last_sync DESC LIMIT 1
                ");
                $stmt->execute();
                $row = $stmt->fetch();
                if ($row) $last_sync = $row['last_sync'];
            } catch (Exception $e) { /* table peut ne pas exister encore */ }

            $sql    = "SELECT " . SQL_COLONNES_CARTE . " FROM candidat c WHERE c.supprimer = 1";
            $params = [];

            if ($last_sync) {
                $sql .= " AND (c.date_modification > ? OR c.date_modification IS NULL)";
                $params[] = $last_sync;
            }
            $sql .= " ORDER BY c.date_modification DESC LIMIT 1000";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $cartes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cartes as &$c) {
                $c['peut_voir_carte'] = ($c['suspendus'] == 0);
            }
            unset($c);

            // Logger la synchronisation
            try {
                $log_stmt = $pdo->prepare("
                    INSERT INTO api_sync_log (system, action, status, details, last_sync)
                    VALUES ('SIADOC_SYNC', 'EXPORT', 'SUCCESS', ?, NOW())
                ");
                $log_stmt->execute([json_encode(['total' => count($cartes)])]);
            } catch (Exception $e) { /* silencieux */ }

            sendResponse([
                'cartes_modifiees'  => $cartes,
                'derniere_sync'     => $last_sync,
                'date_sync_actuelle'=> date('c'),
                'total_modifiees'   => count($cartes)
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de donnÃ©es: ' . $e->getMessage(), 500, 'DB_ERROR');
        }
        break;

    // â”€â”€ WEBHOOK â€” Recevoir les notifications SIADOC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'webhook':
        if ($request_method !== 'POST') {
            sendError('MÃ©thode POST requise pour le webhook', 405, 'METHOD_NOT_ALLOWED');
            break;
        }

        $raw_input = file_get_contents('php://input');
        $input     = json_decode($raw_input, true);

        if (!$input) {
            sendError('Corps de requÃªte JSON invalide', 400, 'INVALID_JSON');
            break;
        }

        // VÃ©rification de la signature HMAC (si prÃ©sente)
        $headers_all = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];
        $signature   = $headers_all['x-siadoc-signature'] ?? null;
        if ($signature) {
            $expected = hash_hmac('sha256', $raw_input, SIADOC_API_KEY);
            if (!hash_equals($expected, $signature)) {
                sendError('Signature webhook invalide', 401, 'INVALID_SIGNATURE');
                break;
            }
        }

        $event_type = $input['type'] ?? $input['event'] ?? null;
        $data       = $input['data'] ?? $input;

        // Logger le webhook reÃ§u
        try {
            $pdo->prepare("
                INSERT INTO api_sync_log (system, action, status, details, last_sync)
                VALUES ('SIADOC_WEBHOOK', ?, 'RECEIVED', ?, NOW())
            ")->execute([$event_type ?? 'UNKNOWN', json_encode($data)]);
        } catch (Exception $e) { /* silencieux */ }

        // Traiter selon le type d'Ã©vÃ©nement
        switch ($event_type) {
            case 'MILITAIRE_CREE':
            case 'MILITAIRE_MIS_A_JOUR':
                // Notification que SIADOC a une mise Ã  jour â€” dÃ©clencher une sync
                sendResponse([
                    'recu'        => true,
                    'event'       => $event_type,
                    'action'      => 'SYNC_PLANIFIEE',
                    'matricule'   => $data['matricule'] ?? null,
                    'message'     => 'Ã‰vÃ©nement reÃ§u â€” synchronisation planifiÃ©e'
                ]);
                break;

            case 'PING':
                sendResponse(['recu' => true, 'pong' => true, 'timestamp' => date('c')]);
                break;

            default:
                sendResponse([
                    'recu'    => true,
                    'event'   => $event_type,
                    'message' => 'Ã‰vÃ©nement reÃ§u et enregistrÃ©'
                ]);
                break;
        }
        break;

    // â”€â”€ DEFAULT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    default:
        sendError(
            "Endpoint '$action' non trouvÃ©. Consultez ?action=aide pour la liste des endpoints.",
            404,
            'NOT_FOUND'
        );
        break;
}
?>

