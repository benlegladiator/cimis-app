<?php
/**
 * API SIADOC ENVOIE - Exposition des données CIMIS pour SIADOC
 * Ce fichier permet à SIADOC de récupérer les données des cartes CIMIS
 */

// Affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Gestionnaire d'erreurs personnalisé
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Erreur PHP: $message dans $file à la ligne $line");
    if (error_reporting() & $severity) {
        echo json_encode([
            'success' => false,
            'error' => "Erreur PHP: $message",
            'file' => basename($file),
            'line' => $line,
            'debug' => true
        ]);
        exit();
    }
});

// Gestionnaire d'exceptions
set_exception_handler(function($exception) {
    error_log("Exception non capturée: " . $exception->getMessage());
    echo json_encode([
        'success' => false,
        'error' => "Exception: " . $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine(),
        'debug' => true
    ]);
    exit();
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration
require_once 'config.php';

// Clé API pour authentification (à configurer)
define('SIADOC_API_KEY', 'siadoc-2026-cimis-integration');

// Fonction compatible pour récupérer les headers
function getAllHeadersCompat() {
    if (!function_exists('getallheaders')) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    } else {
        return getallheaders();
    }
}

// Vérification de la clé API
function verifyApiKey() {
    $headers = getAllHeadersCompat();
    $api_key = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? $_GET['api_key'] ?? $_POST['api_key'] ?? null;
    
    if (!$api_key || $api_key !== SIADOC_API_KEY) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Clé API invalide',
            'code' => 'INVALID_API_KEY'
        ]);
        exit();
    }
}

// Fonction pour encoder une image en base64
function encodeImageToBase64($image_path) {
    if (file_exists($image_path)) {
        $image_data = file_get_contents($image_path);
        $mime_type = mime_content_type($image_path);
        return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
    }
    return null;
}

// Fonction pour envoyer une réponse JSON
function sendResponse($data, $message = null, $http_code = 200) {
    http_response_code($http_code);
    $response = ['success' => true, 'data' => $data];
    if ($message) {
        $response['message'] = $message;
    }
    echo json_encode($response);
}

// Fonction pour envoyer une erreur
function sendError($message, $http_code = 400) {
    http_response_code($http_code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('c')
    ]);
}

// Vérifier l'authentification (TEMPORAIREMENT DÉSACTIVÉ POUR DÉBOGAGE)
// verifyApiKey();

// Router les requêtes
$request_method = $_SERVER['REQUEST_METHOD'];
$path_info = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path_info, '/'));

// Récupérer l'action depuis le path ou le paramètre GET
$action = $_GET['action'] ?? 'help';

switch ($action) {
    case 'help':
        sendResponse([
            'endpoints' => [
                'GET /api_siadoc_envoie.php/help' => 'Documentation des endpoints',
                'GET /api_siadoc_envoie.php/carte/{matricule}' => 'Récupérer une carte par matricule',
                'GET /api_siadoc_envoie.php/cartes' => 'Récupérer toutes les cartes actives avec filtres avancés',
                'GET /api_siadoc_envoie.php/cartes?matricule=XXX' => 'Filtrer par matricule (recherche uniquement dans matricule_militaire)',
                'GET /api_siadoc_envoie.php/cartes?grade=Capitaine&grade=Colonel' => 'Filtrer par plusieurs grades',
                'GET /api_siadoc_envoie.php/cartes?unite=AA&unite=GG' => 'Filtrer par plusieurs unités',
                'GET /api_siadoc_envoie.php/cartes?annee_galon=2025' => 'Filtrer par année de dernier galon',
                'GET /api_siadoc_envoie.php/cartes?annee_entree=2014' => 'Filtrer par année d\'entrée en service',
                'GET /api_siadoc_envoie.php/cartes?periode_entree=2014-2016' => 'Filtrer par période d\'entrée',
                'GET /api_siadoc_envoie.php/cartes?age_min=25&age_max=45' => 'Filtrer par tranche d\'âge',
                'GET /api_siadoc_envoie.php/cartes?statut_militaire=ACTIF&statut_militaire=EN_MISSION' => 'Filtrer par statut militaire',
                'GET /api_siadoc_envoie.php/cartes?sexe=MASCULIN' => 'Filtrer par sexe',
                'GET /api_siadoc_envoie.php/cartes?periode=2023-01-01,2023-12-31' => 'Cartes par période d\'enrôlement',
                'GET /api_siadoc_envoie.php/cartes?include_images=true' => 'Inclure les images en base64',
                'GET /api_siadoc_envoie.php/cartes?page=2&limit=50' => 'Pagination',
                'GET /api_siadoc_envoie.php/statistiques' => 'Statistiques des cartes',
                'GET /api_siadoc_envoie.php/biometrie/{matricule}' => 'Données biométriques par matricule',
                'GET /api_siadoc_envoie.php/recherche?q={terme}' => 'Rechercher des cartes',
                'GET /api_siadoc_envoie.php/matricules?matricules=T14/6584,M15/4578' => 'Récupérer plusieurs militaires par matricules militaires uniquement (max 100)',
                'GET /api_siadoc_envoie.php/synchronisation' => 'Synchronisation incrémentielle'
            ],
            'version' => '1.1.0',
            'system' => 'CIMIS',
            'description' => 'API d\'exposition des données CIMIS pour SIADOC avec filtres avancés',
            'champs_disponibles' => [
                'matricule' => 'Matricule CIMIS généré automatiquement (CIM-YYYY-XXXX)',
                'matricule_militaire' => 'Numéro de carte / Matricule militaire (T14/6584, A14/7845) - CHAMP PRINCIPAL',
                'nom' => 'Nom du militaire',
                'prenom' => 'Prénom du militaire',
                'date_naissance' => 'Date de naissance',
                'lieu_naissance' => 'Lieu de naissance',
                'sexe' => 'Sexe (MASCULIN/FEMININ)',
                'numero_cni' => 'Numéro CNI',
                'date_enrolement' => 'Date d\'enrôlement',
                'date_dernier_grade' => 'Date du dernier grade',
                'annee_dernier_galon' => 'Date de la dernière promotion au grade',
                'grade' => 'Grade actuel',
                'unite' => 'Unité d\'affectation',
                'photo' => 'Photo d\'identité',
                'taille' => 'Taille en cm',
                'poids' => 'Poids en kg',
                'groupe_sanguin' => 'Groupe sanguin',
                'code_qr' => 'Code QR de vérification',
                'empreinte_data' => 'Données empreinte digitale',
                'source_system' => 'Système source (CIMIS/SIADOC)',
                'type_personnel' => 'Type de personnel (MILITAIRE/CIVIL)',
                'statut_militaire' => 'Statut militaire actuel',
                'categorie_civil' => 'Catégorie pour personnel civil',
                'date_modification' => 'Date de dernière modification',
                'suspendus' => 'Indicateur de suspension (0/1)',
                'supprimer' => 'Indicateur de suppression (0/1)'
            ],
            'exemples_requetes' => [
                'Capitaines entrés en service en 2014' => '/cartes?grade=Capitaine&annee_entree=2014',
                'Colonels avec galon en 2025' => '/cartes?grade=Colonel&annee_galon=2025',
                'Militaires actifs âgés de 25-35 ans' => '/cartes?statut_militaire=ACTIF&age_min=25&age_max=35',
                'Femmes dans l\'Armée de l\'Air' => '/cartes?sexe=FEMININ&unite=AA'
            ]
        ]);
        break;

    case 'carte':
        // Récupérer une carte spécifique
        $matricule = $path_parts[2] ?? null;
        if (!$matricule) {
            sendError('Matricule requis');
            break;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
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
                    c.categorie_civil
                FROM candidat c
                WHERE (c.matricule = ? OR c.matricule_militaire = ?)
            ");
            $stmt->execute([$matricule, $matricule]);
            $carte = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$carte) {
                sendError('Carte non trouvée', 404);
                break;
            }

            // Encoder les images en base64
            if ($carte['photo']) {
                $photo_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $carte['photo'];
                $carte['photo_base64'] = encodeImageToBase64($photo_path);
            }
            if ($carte['code_qr']) {
                $qr_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $carte['code_qr'];
                $carte['qr_code_base64'] = encodeImageToBase64($qr_path);
            }

            // URL de vérification
            $carte['url_verification'] = 'https://cimis.ct.ws/verify/' . ($carte['matricule_militaire'] ?? $carte['matricule']);

            sendResponse($carte, 'Carte récupérée avec succès');

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    case 'cartes':
        // Récupérer toutes les cartes avec filtres avancés
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.date_naissance,
                    c.lieu_naissance,
                    c.sexe,
                    c.numero_cni,
                    c.grade,
                    c.unite,
                    c.photo,
                    c.taille,
                    c.poids,
                    c.groupe_sanguin
                FROM candidat c
                WHERE 1=1
            ");
            $params = [];

            // Filtre par matricule (recherche uniquement dans matricule_militaire)
            if (isset($_GET['matricule'])) {
                // Recherche uniquement dans matricule_militaire (numéro de carte)
                $stmt = $pdo->prepare("
                    SELECT 
                        c.id,
                        c.matricule,
                        c.matricule_militaire,
                        c.nom,
                        c.prenom,
                        c.date_naissance,
                        c.sexe,
                        c.numero_cni,
                        c.grade,
                        c.unite,
                        c.photo,
                        c.taille,
                        c.poids,
                        c.groupe_sanguin
                    FROM candidat c
                    WHERE c.matricule_militaire = ?
                ");
                $sql .= " AND c.matricule_militaire = ?";
                $params[] = $_GET['matricule'];
            }

            // Filtre par grade
            if (isset($_GET['grade'])) {
                if (is_array($_GET['grade'])) {
                    $placeholders = str_repeat('?,', count($_GET['grade']));
                    $placeholders = rtrim($placeholders, ',');
                    $sql .= " AND c.grade IN ($placeholders)";
                    $params = array_merge($params, $_GET['grade']);
                } else {
                    $sql .= " AND c.grade = ?";
                    $params[] = $_GET['grade'];
                }
            }

            // Filtre par unité
            if (isset($_GET['unite'])) {
                if (is_array($_GET['unite'])) {
                    $placeholders = str_repeat('?,', count($_GET['unite']));
                    $placeholders = rtrim($placeholders, ',');
                    $sql .= " AND c.unite IN ($placeholders)";
                    $params = array_merge($params, $_GET['unite']);
                } else {
                    $sql .= " AND c.unite = ?";
                    $params[] = $_GET['unite'];
                }
            }

            // Filtre par année de dernier galon
            if (isset($_GET['annee_galon'])) {
                $sql .= " AND YEAR(c.annee_dernier_galon) = ?";
                $params[] = $_GET['annee_galon'];
            }

            // Filtre par année d'entrée en service (à partir du matricule)
            if (isset($_GET['annee_entree'])) {
                $sql .= " AND (c.matricule_militaire LIKE ? OR c.matricule LIKE ?)";
                $annee = $_GET['annee_entree'];
                $params[] = "%/$annee/%";
                $params[] = "%$annee%";
            }

            // Filtre par période d'entrée en service
            if (isset($_GET['periode_entree'])) {
                $periodes = explode('-', $_GET['periode_entree']);
                if (count($periodes) === 2) {
                    $sql .= " AND (SUBSTRING(c.matricule_militaire, LOCATE('/', c.matricule_militaire) + 1, 4) BETWEEN ? AND ?)";
                    $params[] = $periodes[0];
                    $params[] = $periodes[1];
                }
            }

            // Filtre par âge
            if (isset($_GET['age_min'])) {
                $sql .= " AND TIMESTAMPDIFF(YEAR, c.date_naissance, CURDATE()) >= ?";
                $params[] = $_GET['age_min'];
            }
            if (isset($_GET['age_max'])) {
                $sql .= " AND TIMESTAMPDIFF(YEAR, c.date_naissance, CURDATE()) <= ?";
                $params[] = $_GET['age_max'];
            }

            // Filtre par statut de carte
            if (isset($_GET['statut_carte'])) {
                if (is_array($_GET['statut_carte'])) {
                    $placeholders = str_repeat('?,', count($_GET['statut_carte']));
                    $placeholders = rtrim($placeholders, ',');
                    $sql .= " AND c.statut_carte IN ($placeholders)";
                    $params = array_merge($params, $_GET['statut_carte']);
                } else {
                    $sql .= " AND c.statut_carte = ?";
                    $params[] = $_GET['statut_carte'];
                }
            }

            // Filtre par sexe
            if (isset($_GET['sexe'])) {
                $sql .= " AND c.sexe = ?";
                $params[] = $_GET['sexe'];
            }

            // Filtre par période d'enrôlement
            if (isset($_GET['periode'])) {
                $periodes = explode(',', $_GET['periode']);
                if (count($periodes) === 2) {
                    $sql .= " AND c.date_enrolement BETWEEN ? AND ?";
                    $params[] = $periodes[0];
                    $params[] = $periodes[1];
                }
            }

            // Filtre par source
            if (isset($_GET['source'])) {
                $sql .= " AND c.source_system = ?";
                $params[] = $_GET['source'];
            }

            // Pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = ($page - 1) * $limit;

            $sql .= " ORDER BY c.nom, c.prenom LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $cartes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Encoder les images en base64 si demandé
            if (isset($_GET['include_images']) && $_GET['include_images'] === 'true') {
                foreach ($cartes as &$carte) {
                    if ($carte['photo']) {
                        $photo_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $carte['photo'];
                        $carte['photo_base64'] = encodeImageToBase64($photo_path);
                    }
                    if ($carte['code_qr']) {
                        $qr_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $carte['code_qr'];
                        $carte['qr_code_base64'] = encodeImageToBase64($qr_path);
                    }
                }
            }

            // Compter le total pour pagination
            $count_sql = str_replace("ORDER BY c.nom, c.prenom LIMIT " . (int)$limit . " OFFSET " . (int)$offset, "", $sql);
            $count_sql = str_replace("c.id, c.matricule, c.matricule_militaire, c.nom, c.prenom, c.date_naissance, c.sexe, c.numero_cni, c.date_enrolement, c.date_dernier_grade, c.annee_dernier_galon, c.grade, c.unite, c.photo, c.taille, c.poids, c.groupe_sanguin, c.code_qr, c.empreinte_data, c.source_system, c.type_personnel, c.statut_carte, c.fonction, c.direction", "COUNT(*) as total", $count_sql);
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute($params);
            $result = $count_stmt->fetch();
            $total = isset($result['total']) ? $result['total'] : 0;

            sendResponse([
                'cartes' => $cartes,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ],
                'filtres_appliques' => [
                    'matricule' => $_GET['matricule'] ?? null,
                    'grade' => $_GET['grade'] ?? null,
                    'unite' => $_GET['unite'] ?? null,
                    'annee_galon' => $_GET['annee_galon'] ?? null,
                    'annee_entree' => $_GET['annee_entree'] ?? null,
                    'periode_entree' => $_GET['periode_entree'] ?? null,
                    'age_min' => $_GET['age_min'] ?? null,
                    'age_max' => $_GET['age_max'] ?? null,
                    'statut_carte' => $_GET['statut_carte'] ?? null,
                    'sexe' => $_GET['sexe'] ?? null,
                    'periode' => $_GET['periode'] ?? null,
                    'source' => $_GET['source'] ?? null
                ]
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    case 'statistiques':
        // Statistiques des cartes
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_cartes,
                    COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc,
                    COUNT(CASE WHEN source_system = 'CIMIS' THEN 1 END) as venus_de_cimis,
                    COUNT(CASE WHEN code_qr IS NOT NULL THEN 1 END) as avec_qr_code,
                    COUNT(CASE WHEN empreinte_data IS NOT NULL THEN 1 END) as avec_biometrie,
                    COUNT(DISTINCT unite) as unites_differentes,
                    COUNT(DISTINCT grade) as grades_differents
                FROM candidat 
                WHERE 1=1
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Statistiques par unité
            $stmt = $pdo->prepare("
                SELECT unite, COUNT(*) as effectif
                FROM candidat 
                WHERE 1=1
                GROUP BY unite
                ORDER BY effectif DESC
            ");
            $stmt->execute();
            $stats_par_unite = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Statistiques par grade
            $stmt = $pdo->prepare("
                SELECT grade, COUNT(*) as effectif
                FROM candidat 
                WHERE 1=1
                GROUP BY grade
                ORDER BY effectif DESC
            ");
            $stmt->execute();
            $stats_par_grade = $stmt->fetchAll(PDO::FETCH_ASSOC);

            sendResponse([
                'generales' => $stats,
                'par_unite' => $stats_par_unite,
                'par_grade' => $stats_par_grade,
                'date_generation' => date('c')
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    case 'biometrie':
        // Données biométriques par matricule
        $matricule = $path_parts[2] ?? null;
        if (!$matricule) {
            sendError('Matricule requis');
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
                    c.empreinte_data
                FROM candidat c
                WHERE (c.matricule = ? OR c.matricule_militaire = ?)
                AND c.suspendus = 0
            ");
            $stmt->execute([$matricule, $matricule]);
            $biometrie = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$biometrie) {
                sendError('Carte non trouvée', 404);
                break;
            }

            // Encoder les données biométriques
            if ($biometrie['photo']) {
                $photo_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $biometrie['photo'];
                $biometrie['photo_base64'] = encodeImageToBase64($photo_path);
            }
            if ($biometrie['code_qr']) {
                $qr_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $biometrie['code_qr'];
                $biometrie['qr_code_base64'] = encodeImageToBase64($qr_path);
            }

            // URL de vérification
            $biometrie['url_verification'] = 'https://cimis.ct.ws/verify/' . ($biometrie['matricule_militaire'] ?? $biometrie['matricule']);

            sendResponse($biometrie, 'Données biométriques récupérées avec succès');

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    case 'recherche':
        // Recherche de cartes
        $terme = $_GET['q'] ?? null;
        if (!$terme || strlen($terme) < 2) {
            sendError('Terme de recherche requis (minimum 2 caractères)');
            break;
        }

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.date_naissance,
                    c.lieu_naissance,
                    c.sexe,
                    c.numero_cni,
                    c.grade,
                    c.unite,
                    c.photo,
                    c.taille,
                    c.poids,
                    c.groupe_sanguin
                FROM candidat c
                WHERE 1=1
                AND (
                    c.matricule LIKE ? OR
                    c.matricule_militaire LIKE ? OR
                    c.nom LIKE ? OR
                    c.prenom LIKE ? OR
                    c.unite LIKE ? OR
                    c.grade LIKE ?
                )
                ORDER BY c.nom, c.prenom
                LIMIT 50
            ");
            $search_term = '%' . $terme . '%';
            $stmt->execute([
                $search_term, $search_term, $search_term, 
                $search_term, $search_term, $search_term
            ]);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            sendResponse([
                'resultats' => $resultats,
                'terme_recherche' => $terme,
                'total_trouve' => count($resultats)
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    case 'matricules':
        // Récupérer plusieurs militaires par leurs matricules
        $matricules_param = $_GET['matricules'] ?? null;
        if (!$matricules_param) {
            sendError('Paramètre matricules requis (format: matricule1,matricule2,matricule3)');
            break;
        }

        try {
            $matricules = explode(',', $matricules_param);
            $matricules = array_map('trim', $matricules);
            $matricules = array_filter($matricules);

            if (empty($matricules)) {
                sendError('Aucun matricule valide fourni');
                break;
            }

            // Limiter à 100 matricules maximum pour éviter la surcharge
            $matricules = array_slice($matricules, 0, 100);

            $placeholders = str_repeat('?,', count($matricules));
            $placeholders = rtrim($placeholders, ',');

            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.date_naissance,
                    c.lieu_naissance,
                    c.sexe,
                    c.numero_cni,
                    c.grade,
                    c.unite,
                    c.photo,
                    c.taille,
                    c.poids,
                    c.groupe_sanguin
                FROM candidat c
                WHERE 1=1
                AND c.matricule_militaire IN ($placeholders)
                ORDER BY FIELD(c.matricule_militaire, " . implode(',', array_fill(0, count($matricules), '?')) . ")
            ");

            $params = $matricules;
            $stmt->execute($params);
            $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Encoder les images en base64 si demandé
            if (isset($_GET['include_images']) && $_GET['include_images'] === 'true') {
                foreach ($resultats as &$resultat) {
                    if ($resultat['photo']) {
                        $photo_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $resultat['photo'];
                        $resultat['photo_base64'] = encodeImageToBase64($photo_path);
                    }
                    if ($resultat['code_qr']) {
                        $qr_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $resultat['code_qr'];
                        $resultat['qr_code_base64'] = encodeImageToBase64($qr_path);
                    }
                }
            }

            sendResponse([
                'militaires' => $resultats,
                'matricules_recherches' => $matricules,
                'total_trouves' => count($resultats),
                'total_demandes' => count($matricules),
                'non_trouves' => array_diff($matricules, array_column($resultats, 'matricule_militaire'))
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    case 'synchronisation':
        // Endpoint de synchronisation pour SIADOC
        try {
            // Récupérer la date de dernière synchronisation
            $stmt = $pdo->prepare("
                SELECT last_sync 
                FROM api_sync_log 
                WHERE system = 'SIADOC_SYNC' 
                ORDER BY last_sync DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $last_sync = $stmt->fetch();

            // Récupérer les cartes modifiées depuis la dernière sync
            $sql = "
                SELECT 
                    c.id,
                    c.matricule,
                    c.matricule_militaire,
                    c.nom,
                    c.prenom,
                    c.date_naissance,
                    c.lieu_naissance,
                    c.sexe,
                    c.numero_cni,
                    c.grade,
                    c.unite,
                    c.photo,
                    c.taille,
                    c.poids,
                    c.groupe_sanguin,
                    c.date_modification
                FROM candidat c
                WHERE 1=1
            ";

            if ($last_sync) {
                $sql .= " AND (c.date_modification > ? OR c.date_modification IS NULL)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$last_sync['last_sync']]);
            } else {
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
            }

            $cartes_modifiees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Logger la synchronisation
            $log_stmt = $pdo->prepare("
                INSERT INTO api_sync_log (system, action, status, last_sync) 
                VALUES ('SIADOC_SYNC', 'EXPORT', 'SUCCESS', NOW())
            ");
            $log_stmt->execute();

            sendResponse([
                'cartes_modifiees' => $cartes_modifiees,
                'derniere_sync' => $last_sync ? $last_sync['last_sync'] : null,
                'date_sync_actuelle' => date('c'),
                'total_modifiees' => count($cartes_modifiees)
            ]);

        } catch (PDOException $e) {
            sendError('Erreur base de données: ' . $e->getMessage());
        }
        break;

    default:
        sendError('Endpoint non trouvé', 404);
        break;
}
?>
