<?php
// API REST pour l'échange de données entre CIMIS et GESMIL2.0
// Endpoint: http://127.0.0.1/cim/api_gesmil.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../backend/config.php';

// Configuration de sécurité
define('API_KEY', 'GESMIL2.0-CIMIS-2026-KEY'); // Clé à partager avec GESMIL2.0

// Vérification de la clé API
function verifyApiKey() {
    $headers = getallheaders();
    $api_key = $headers['Authorization'] ?? $headers['authorization'] ?? $_GET['api_key'] ?? $_POST['api_key'] ?? null;
    
    if (!$api_key || $api_key !== API_KEY) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Clé API invalide',
            'code' => 'UNAUTHORIZED'
        ]);
        exit();
    }
}

// Logger pour l'API
function logApiCall($endpoint, $method, $data = []) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'endpoint' => $endpoint,
        'method' => $method,
        'data' => $data
    ];
    file_put_contents('logs/api_gesmil.log', json_encode($log) . "\n", FILE_APPEND);
}

// Récupérer tous les candidats CIMIS
function getCandidats($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, matricule, matricule_militaire, nom, prenom, date_naissance, sexe,
                numero_cni, taille, poids, groupe_sanguin, type_personnel, unite,
                grade, matricule_militaire, annee_dernier_galon, date_enrolement,
                date_dernier_grade, photo_path, code_qr, statut, date_creation
            FROM candidat 
            ORDER BY date_creation DESC
        ");
        $stmt->execute();
        $candidats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nettoyer les chemins des fichiers
        foreach ($candidats as &$candidat) {
            $candidat['photo_path'] = $candidat['photo_path'] ? 
                'http://127.0.0.1/cim/' . $candidat['photo_path'] : null;
            $candidat['code_qr'] = $candidat['code_qr'] ? 
                'http://127.0.0.1/cim/' . $candidat['code_qr'] : null;
        }
        
        return $candidats;
    } catch(PDOException $e) {
        throw new Exception("Erreur récupération candidats: " . $e->getMessage());
    }
}

// Récupérer un candidat spécifique
function getCandidat($pdo, $matricule) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, matricule, matricule_militaire, nom, prenom, date_naissance, sexe,
                numero_cni, taille, poids, groupe_sanguin, type_personnel, unite,
                grade, matricule_militaire, annee_dernier_galon, date_enrolement,
                date_dernier_grade, photo_path, code_qr, statut, date_creation
            FROM candidat 
            WHERE matricule = :matricule OR matricule_militaire = :matricule
        ");
        $stmt->execute(['matricule' => $matricule]);
        $candidat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($candidat) {
            $candidat['photo_path'] = $candidat['photo_path'] ? 
                'http://127.0.0.1/cim/' . $candidat['photo_path'] : null;
            $candidat['code_qr'] = $candidat['code_qr'] ? 
                'http://127.0.0.1/cim/' . $candidat['code_qr'] : null;
        }
        
        return $candidat;
    } catch(PDOException $e) {
        throw new Exception("Erreur récupération candidat: " . $e->getMessage());
    }
}

// Récupérer tous les militaires (type_personnel = 'MILITAIRE')
function getAllMilitaires($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, matricule, matricule_militaire, nom, prenom, date_naissance, sexe,
                numero_cni, taille, poids, groupe_sanguin, type_personnel, unite,
                grade, matricule_militaire, annee_dernier_galon, date_enrolement,
                date_dernier_grade, photo_path, code_qr, statut, date_creation
            FROM candidat 
            WHERE type_personnel = 'MILITAIRE'
            ORDER BY nom, prenom
        ");
        $stmt->execute();
        $militaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nettoyer les chemins des fichiers
        foreach ($militaires as &$militaire) {
            $militaire['photo_path'] = $militaire['photo_path'] ? 
                'http://127.0.0.1/cim/' . $militaire['photo_path'] : null;
            $militaire['code_qr'] = $militaire['code_qr'] ? 
                'http://127.0.0.1/cim/' . $militaire['code_qr'] : null;
        }
        
        return $militaires;
    } catch(PDOException $e) {
        throw new Exception("Erreur récupération militaires: " . $e->getMessage());
    }
}

// Récupérer les militaires par période d'entrée en service
function getMilitairesByPeriode($pdo, $annee_debut, $annee_fin) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id, matricule, matricule_militaire, nom, prenom, date_naissance, sexe,
                numero_cni, taille, poids, groupe_sanguin, type_personnel, unite,
                grade, matricule_militaire, annee_dernier_galon, date_enrolement,
                date_dernier_grade, photo_path, code_qr, statut, date_creation
            FROM candidat 
            WHERE type_personnel = 'MILITAIRE'
            AND YEAR(date_enrolement) BETWEEN :annee_debut AND :annee_fin
            ORDER BY date_enrolement, nom, prenom
        ");
        $stmt->execute([
            'annee_debut' => $annee_debut,
            'annee_fin' => $annee_fin
        ]);
        $militaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nettoyer les chemins des fichiers
        foreach ($militaires as &$militaire) {
            $militaire['photo_path'] = $militaire['photo_path'] ? 
                'http://127.0.0.1/cim/' . $militaire['photo_path'] : null;
            $militaire['code_qr'] = $militaire['code_qr'] ? 
                'http://127.0.0.1/cim/' . $militaire['code_qr'] : null;
        }
        
        return $militaires;
    } catch(PDOException $e) {
        throw new Exception("Erreur récupération militaires par période: " . $e->getMessage());
    }
}

// Créer/Mettre à jour un candidat depuis GESMIL2.0
function createOrUpdateCandidat($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        // Vérifier si le candidat existe déjà
        $stmt = $pdo->prepare("
            SELECT id FROM candidat 
            WHERE matricule_militaire = :matricule_militaire 
            OR (nom = :nom AND prenom = :prenom AND date_naissance = :date_naissance)
        ");
        $stmt->execute([
            'matricule_militaire' => $data['matricule_militaire'] ?? '',
            'nom' => strtoupper($data['nom'] ?? ''),
            'prenom' => strtoupper($data['prenom'] ?? ''),
            'date_naissance' => $data['date_naissance'] ?? ''
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Mise à jour
            $stmt = $pdo->prepare("
                UPDATE candidat SET
                    nom = :nom,
                    prenom = :prenom,
                    date_naissance = :date_naissance,
                    sexe = :sexe,
                    numero_cni = :numero_cni,
                    taille = :taille,
                    poids = :poids,
                    groupe_sanguin = :groupe_sanguin,
                    type_personnel = :type_personnel,
                    unite = :unite,
                    grade = :grade,
                    matricule_militaire = :matricule_militaire,
                    annee_dernier_galon = :annee_dernier_galon,
                    date_enrolement = :date_enrolement,
                    date_dernier_grade = :date_dernier_grade,
                    statut = 'ACTIVE',
                    date_maj = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $existing['id'],
                'nom' => strtoupper($data['nom'] ?? ''),
                'prenom' => strtoupper($data['prenom'] ?? ''),
                'date_naissance' => $data['date_naissance'] ?? null,
                'sexe' => $data['sexe'] ?? '',
                'numero_cni' => $data['numero_cni'] ?? '',
                'taille' => $data['taille'] ?? '',
                'poids' => $data['poids'] ?? '',
                'groupe_sanguin' => $data['groupe_sanguin'] ?? '',
                'type_personnel' => $data['type_personnel'] ?? 'MILITAIRE',
                'unite' => $data['unite'] ?? '',
                'grade' => $data['grade'] ?? '',
                'matricule_militaire' => $data['matricule_militaire'] ?? '',
                'annee_dernier_galon' => $data['annee_dernier_galon'] ?? null,
                'date_enrolement' => $data['date_enrolement'] ?? date('Y-m-d'),
                'date_dernier_grade' => $data['date_dernier_grade'] ?? null
            ]);
            
            $result = [
                'action' => 'updated',
                'matricule_cimis' => getMatriculeById($pdo, $existing['id']),
                'message' => 'Candidat mis à jour avec succès'
            ];
            
        } else {
            // Création
            $matricule = generateMatriculeCIMIS($pdo);
            
            $stmt = $pdo->prepare("
                INSERT INTO candidat (
                    matricule, matricule_militaire, nom, prenom, date_naissance, sexe,
                    numero_cni, taille, poids, groupe_sanguin, type_personnel, unite,
                    grade, annee_dernier_galon, date_enrolement, date_dernier_grade,
                    statut, date_creation
                ) VALUES (
                    :matricule, :matricule_militaire, :nom, :prenom, :date_naissance, :sexe,
                    :numero_cni, :taille, :poids, :groupe_sanguin, :type_personnel, :unite,
                    :grade, :annee_dernier_galon, :date_enrolement, :date_dernier_grade,
                    :statut, NOW()
                )
            ");
            
            $stmt->execute([
                'matricule' => $matricule,
                'matricule_militaire' => $data['matricule_militaire'] ?? '',
                'nom' => strtoupper($data['nom'] ?? ''),
                'prenom' => strtoupper($data['prenom'] ?? ''),
                'date_naissance' => $data['date_naissance'] ?? null,
                'sexe' => $data['sexe'] ?? '',
                'numero_cni' => $data['numero_cni'] ?? '',
                'taille' => $data['taille'] ?? '',
                'poids' => $data['poids'] ?? '',
                'groupe_sanguin' => $data['groupe_sanguin'] ?? '',
                'type_personnel' => $data['type_personnel'] ?? 'MILITAIRE',
                'unite' => $data['unite'] ?? '',
                'grade' => $data['grade'] ?? '',
                'annee_dernier_galon' => $data['annee_dernier_galon'] ?? null,
                'date_enrolement' => $data['date_enrolement'] ?? date('Y-m-d'),
                'date_dernier_grade' => $data['date_dernier_grade'] ?? null,
                'statut' => 'ACTIVE'
            ]);
            
            $result = [
                'action' => 'created',
                'matricule_cimis' => $matricule,
                'message' => 'Candidat créé avec succès'
            ];
        }
        
        $pdo->commit();
        return $result;
        
    } catch(PDOException $e) {
        $pdo->rollback();
        throw new Exception("Erreur création/mise à jour candidat: " . $e->getMessage());
    }
}

// Générer un matricule CIMIS unique
function generateMatriculeCIMIS($pdo) {
    do {
        $matricule = 'CIM-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE matricule = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } while ($result['count'] > 0);
    
    return $matricule;
}

// Récupérer le matricule par ID
function getMatriculeById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT matricule FROM candidat WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['matricule'] ?? null;
}

// Supprimer un candidat
function deleteCandidat($pdo, $matricule) {
    try {
        $stmt = $pdo->prepare("DELETE FROM candidat WHERE matricule = :matricule OR matricule_militaire = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        
        return [
            'deleted' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Candidat supprimé avec succès' : 'Candidat non trouvé'
        ];
    } catch(PDOException $e) {
        throw new Exception("Erreur suppression candidat: " . $e->getMessage());
    }
}

// Router les requêtes API
try {
    verifyApiKey();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $endpoint = $_GET['endpoint'] ?? '';
    
    logApiCall($endpoint, $method, $_REQUEST);
    
    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'candidats':
                    $candidats = getCandidats($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $candidats,
                        'count' => count($candidats),
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                case 'candidat':
                    $matricule = $_GET['matricule'] ?? '';
                    if (!$matricule) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Matricule requis']);
                        break;
                    }
                    
                    $candidat = getCandidat($pdo, $matricule);
                    if ($candidat) {
                        echo json_encode([
                            'success' => true,
                            'data' => $candidat,
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Candidat non trouvé']);
                    }
                    break;
                    
                case 'militaires':
                    $militaires = getAllMilitaires($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $militaires,
                        'count' => count($militaires),
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                case 'militaires-periode':
                    $annee_debut = $_GET['annee_debut'] ?? '';
                    $annee_fin = $_GET['annee_fin'] ?? '';
                    
                    if (!$annee_debut || !$annee_fin) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Année de début et de fin requises']);
                        break;
                    }
                    
                    if (!is_numeric($annee_debut) || !is_numeric($annee_fin)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Les années doivent être numériques']);
                        break;
                    }
                    
                    $militaires = getMilitairesByPeriode($pdo, $annee_debut, $annee_fin);
                    echo json_encode([
                        'success' => true,
                        'data' => $militaires,
                        'count' => count($militaires),
                        'periode' => "$annee_debut-$annee_fin",
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                case 'health':
                    echo json_encode([
                        'success' => true,
                        'status' => 'API CIMIS opérationnelle',
                        'version' => '1.0.0',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'database' => 'Connectée'
                    ]);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Endpoint non trouvé']);
            }
            break;
            
        case 'POST':
            switch ($endpoint) {
                case 'candidat':
                    $json_input = file_get_contents('php://input');
                    $data = json_decode($json_input, true);
                    
                    if (!$data) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'JSON invalide']);
                        break;
                    }
                    
                    $result = createOrUpdateCandidat($pdo, $data);
                    echo json_encode([
                        'success' => true,
                        'data' => $result,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Endpoint non trouvé']);
            }
            break;
            
        case 'DELETE':
            switch ($endpoint) {
                case 'candidat':
                    $matricule = $_GET['matricule'] ?? '';
                    if (!$matricule) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Matricule requis']);
                        break;
                    }
                    
                    $result = deleteCandidat($pdo, $matricule);
                    echo json_encode([
                        'success' => $result['deleted'],
                        'data' => $result,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Endpoint non trouvé']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
