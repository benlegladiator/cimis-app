<?php
/**
 * SIADOC IMPORT v2.0 â€” Importation des donnÃ©es SIADOC vers CIMIS
 *
 * Flux : SIADOC â†’ CIMIS
 *   1. Appel API SIADOC pour rÃ©cupÃ©rer les donnÃ©es du militaire
 *   2. VÃ©rification / contrÃ´le (doublon, champs obligatoires)
 *   3. GÃ©nÃ©ration matricule CIMIS (sÃ©quentiel, pas de doublons)
 *   4. GÃ©nÃ©ration QR Code (via phpqrcode)
 *   5. Insertion en base de donnÃ©es
 *   6. Logging complet
 *
 * Logique BDD :
 *   supprimer = 0  â†’ en corbeille (carte supprimÃ©e)
 *   supprimer = 1  â†’ actif (carte visible, pas en corbeille)
 *   suspendus = 0  â†’ carte visible / imprimable
 *
 * URL SIADOC : https://siadoc.onrender.com
 */

require_once 'config.php';

// â”€â”€â”€ AUTHENTIFICATION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

$headers = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];
$apiKey  = $headers['x-api-key']
    ?? $_SERVER['HTTP_X_API_KEY']
    ?? $_GET['api_key']
    ?? null;

$is_authenticated = (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true)
    || (defined('SIADOC_API_KEY') && $apiKey === SIADOC_API_KEY);

if (!$is_authenticated) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisÃ©. Fournissez une clÃ© API valide (X-API-KEY) ou connectez-vous.'
    ]);
    exit();
}

header('Content-Type: application/json');

// â”€â”€â”€ CONFIGURATION SIADOC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

if (!defined('SIADOC_API_URL')) {
    define('SIADOC_API_URL', 'https://siadoc.onrender.com');
}

// ————————————————————————————————— APPEL API SIADOC avec RETRY ——————————————————————————

function testSIADOCConnection(): array {
    $endpoints = [
        '/api/integration/cimis/ping',
        '/api/export/militaire/info/all',
        '/api/export/militaire/info?matricule=TEST'
    ];
    
    foreach ($endpoints as $ep) {
        $res = callSIADOCAPI($ep, [], 'GET', 1);
        if ($res['http_code'] >= 200 && $res['http_code'] < 300) {
            return [
                'success' => true,
                'endpoint_used' => $ep,
                'http_code' => $res['http_code'],
                'data' => $res['data'],
                'attempts' => $res['attempts']
            ];
        }
    }
    
    return [
        'success' => false,
        'endpoint_used' => $endpoints[0],
        'http_code' => 500,
        'error' => 'Aucun endpoint SIADOC n\'a répondu en 2xx'
    ];
}

/**
 * Appelle l'API SIADOC avec mécanisme de retry (backoff exponentiel).
 *
 * @param string $endpoint   ex: /api/militaires ou /api/export/militaire/info
 * @param array  $params     Paramètres GET ou POST
 * @param string $method     GET | POST
 * @param int    $max_tries  Nombre maximum de tentatives
 * @return array{data:mixed, http_code:int, raw_response:string, attempts:int}
 */
function callSIADOCAPI(string $endpoint, array $params = [], string $method = 'GET', int $max_tries = 3): array {
    $url   = rtrim(SIADOC_API_URL, '/') . '/' . ltrim($endpoint, '/');
    $last_error = null;
    $last_code  = 0;

    for ($attempt = 1; $attempt <= $max_tries; $attempt++) {
        try {
            $ch = curl_init();

            $curl_opts = [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_HTTPHEADER      => [
                    'X-API-KEY: ' . SIADOC_API_KEY,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER  => true,
                CURLOPT_TIMEOUT         => 30,
                CURLOPT_CONNECTTIMEOUT  => 10,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_MAXREDIRS       => 3
            ];

            if ($method === 'GET') {
                $curl_opts[CURLOPT_URL] = !empty($params) ? $url . '?' . http_build_query($params) : $url;
            } else {
                $curl_opts[CURLOPT_URL]        = $url;
                $curl_opts[CURLOPT_POST]       = true;
                $curl_opts[CURLOPT_POSTFIELDS] = json_encode($params);
            }

            curl_setopt_array($ch, $curl_opts);

            $response   = curl_exec($ch);
            $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) throw new Exception("cURL error: $curl_error");

            $last_code = $http_code;

            // SuccÃ¨s
            if ($http_code >= 200 && $http_code < 300) {
                return [
                    'data'         => json_decode($response, true),
                    'http_code'    => $http_code,
                    'raw_response' => $response,
                    'attempts'     => $attempt
                ];
            }

            // Erreur client (4xx) â†’ pas de retry
            if ($http_code >= 400 && $http_code < 500) {
                return [
                    'data'         => json_decode($response, true),
                    'http_code'    => $http_code,
                    'raw_response' => $response,
                    'attempts'     => $attempt
                ];
            }

            // Erreur serveur (5xx) â†’ retry
            $last_error = "HTTP $http_code reÃ§u depuis SIADOC";

        } catch (Exception $e) {
            $last_error = $e->getMessage();
        }

        // Backoff exponentiel : 1s, 2s, 4s...
        if ($attempt < $max_tries) {
            sleep(pow(2, $attempt - 1));
        }
    }

    return [
        'data'         => null,
        'http_code'    => $last_code,
        'raw_response' => null,
        'attempts'     => $max_tries,
        'error'        => $last_error
    ];
}

// â”€â”€â”€ GÃ‰NÃ‰RATION MATRICULE CIMIS (sÃ©quentiel, sans doublons) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function generateCIMISMatricule(): string {
    global $pdo;

    $prefix = 'CIM-';
    $year   = date('Y');

    try {
        $pdo->query("SELECT GET_LOCK('cimis_matricule_lock', 5)")->closeCursor();
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(CAST(SUBSTRING(matricule, 9) AS UNSIGNED)), 0) + 1 as next_seq
            FROM candidat
            WHERE matricule LIKE ?
        ");
        $stmt->execute([$prefix . $year . '%']);
        $row      = $stmt->fetch();
        $stmt->closeCursor();
        $sequence = str_pad((int)($row['next_seq'] ?? 1), 4, '0', STR_PAD_LEFT);
    } finally {
        try {
            $pdo->query("SELECT RELEASE_LOCK('cimis_matricule_lock')")->closeCursor();
        } catch (Exception $e) {}
    }

    return $prefix . $year . $sequence;
}

// â”€â”€â”€ GÃ‰NÃ‰RATION QR CODE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function generateQRCode(string $matricule, string $matricule_cimis): array {
    $qr_dir      = dirname(__DIR__) . '/img/qrcodes/';
    $qr_filename = $qr_dir . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $matricule) . '_qr.png';
    $qr_url      = 'https://cimis.ct.ws/verify/' . urlencode($matricule);

    if (!is_dir($qr_dir)) {
        mkdir($qr_dir, 0777, true);
    }

    // Utiliser phpqrcode si disponible
    $phpqrcode_path = __DIR__ . '/phpqrcode/qrlib.php';
    if (file_exists($phpqrcode_path)) {
        require_once $phpqrcode_path;
        try {
            QRcode::png($qr_url, $qr_filename, QR_ECLEVEL_M, 6, 2);
            return ['image_path' => 'img/qrcodes/' . basename($qr_filename), 'content' => $qr_url, 'method' => 'phpqrcode'];
        } catch (Exception $e) {
            // Fallback ci-dessous
        }
    }

    // Fallback : QR code via Google Charts API (si SIADOC est en ligne, internet l'est aussi)
    $google_url = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($qr_url);
    $qr_data    = @file_get_contents($google_url);
    if ($qr_data) {
        file_put_contents($qr_filename, $qr_data);
        return ['image_path' => 'img/qrcodes/' . basename($qr_filename), 'content' => $qr_url, 'method' => 'google_charts'];
    }

    // Dernier fallback : image GD basique
    if (extension_loaded('gd')) {
        $img = imagecreatetruecolor(200, 200);
        $bg  = imagecolorallocate($img, 255, 255, 255);
        $fg  = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $bg);
        imagestring($img, 3, 10, 80, substr($matricule, -15), $fg);
        imagestring($img, 2, 10, 100, 'CIMIS QR', $fg);
        imagepng($img, $qr_filename);
        imagedestroy($img);
    }

    return ['image_path' => 'img/qrcodes/' . basename($qr_filename), 'content' => $qr_url, 'method' => 'fallback_gd'];
}

// â”€â”€â”€ LOGGING â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function logOperation(string $operation, string $details, string $status = 'SUCCESS'): void {
    global $pdo;
    try {
        $pdo->prepare("
            INSERT INTO api_sync_log (system, action, status, details, last_sync)
            VALUES ('SIADOC_IMPORT', ?, ?, ?, NOW())
        ")->execute([$operation, $status, $details]);
    } catch (Exception $e) { /* silencieux */ }
}

function logSyncDetail(int $candidat_id, string $matricule_militaire, string $type, string $status, string $details = null): void {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO siadoc_sync_details (candidat_id, matricule_militaire, operation_type, operation_status, details, operation_date)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$candidat_id, $matricule_militaire, $type, $status, $details]);
        $stmt->closeCursor();
    } catch (Exception $e) { /* silencieux */ }
}

// â”€â”€â”€ NORMALISATION DES DONNÃ‰ES SIADOC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

/**
 * Normalise les donnÃ©es reÃ§ues de SIADOC vers le format CIMIS.
 * GÃ¨re les diffÃ©rentes structures possibles renvoyÃ©es par SIADOC.
 */
function normalizeSIADOCData(array $d): array {
    // Normalisation du sexe
    $sexe_raw = strtoupper($d['sexe'] ?? $d['genre'] ?? 'M');
    $sexe = match(true) {
        in_array($sexe_raw, ['M', 'MASCULIN', 'MALE', 'H', 'HOMME']) => 'MASCULIN',
        in_array($sexe_raw, ['F', 'FEMININ', 'FÃ‰MININ', 'FEMALE', 'FEMME']) => 'FEMININ',
        default => 'MASCULIN'
    };

    // Normalisation de la date de naissance
    $date_naissance = null;
    foreach (['dateNaissance', 'date_naissance', 'naissance', 'birthdate', 'birth_date'] as $key) {
        if (!empty($d[$key])) {
            $ts = strtotime($d[$key]);
            if ($ts) { $date_naissance = date('Y-m-d', $ts); break; }
        }
    }

    // Normalisation du grade
    $grade = strtoupper($d['grade'] ?? $d['rang'] ?? $d['rank'] ?? '');

    // Unité / corps avec mapping des codes SIADOC (AT, GN, AA, AM)
    $corps_map = [
        'AT' => 'ARMÉE DE TERRE',
        'GN' => 'GENDARMERIE NATIONALE',
        'AA' => 'ARMÉE DE L\'AIR',
        'AM' => 'MARINE NATIONALE',
        'GENDARMERIE' => 'GENDARMERIE NATIONALE'
    ];
    $unite_raw = strtoupper(trim($d['corps'] ?? $d['unite'] ?? $d['unit'] ?? $d['affectation'] ?? ''));
    $unite = $corps_map[$unite_raw] ?? $unite_raw;

    // Matricule militaire
    $matricule = $d['matricule'] ?? $d['matricule_militaire'] ?? $d['id'] ?? '';

    // Date d'enrôlement par défaut (Aujourd'hui) pour affichage immédiat dans impression.php
    $date_enrolement = !empty($d['date_enrolement']) ? date('Y-m-d', strtotime($d['date_enrolement'])) : date('Y-m-d');

    return [
        'matricule_militaire' => $matricule,
        'nom'                 => strtoupper(trim($d['nom'] ?? $d['lastname'] ?? $d['name'] ?? '')),
        'prenom'              => ucwords(strtolower(trim($d['prenom'] ?? $d['firstname'] ?? $d['prenoms'] ?? ''))),
        'date_naissance'      => $date_naissance,
        'lieu_naissance'      => $d['lieu_naissance'] ?? $d['lieuNaissance'] ?? $d['birthplace'] ?? null,
        'sexe'                => $sexe,
        'numero_cni'          => $d['numero_cni'] ?? $d['cni'] ?? $d['cin'] ?? null,
        'grade'               => $grade,
        'unite'               => $unite,
        'date_enrolement'     => $date_enrolement,
        'date_dernier_grade'  => isset($d['date_dernier_grade']) ? date('Y-m-d', strtotime($d['date_dernier_grade'])) : null,
        'annee_dernier_galon' => $d['annee_dernier_galon'] ?? $d['annee_galon'] ?? null,
        'statut_militaire'    => strtoupper($d['statut'] ?? $d['statut_militaire'] ?? 'ACTIF'),
        'type_personnel'      => strtoupper($d['type_personnel'] ?? 'MILITAIRE'),
        'taille'              => $d['taille'] ?? null,
        'poids'               => $d['poids'] ?? null,
        'groupe_sanguin'      => $d['groupe_sanguin'] ?? $d['groupeSanguin'] ?? null,
    ];
}

// â”€â”€â”€ IMPORTATION D'UN MILITAIRE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function importerMilitaire(array $raw_data): array {
    global $pdo;

    // 1. Normaliser les donnÃ©es
    $data = normalizeSIADOCData($raw_data);

    if (empty($data['matricule_militaire'])) {
        return ['success' => false, 'message' => 'Matricule militaire manquant ou vide', 'matricule' => 'N/A'];
    }

    // 2. Vérifier les doublons
    $stmt = $pdo->prepare("SELECT id, matricule FROM candidat WHERE matricule_militaire = ?");
    $stmt->execute([$data['matricule_militaire']]);
    $existing = $stmt->fetch();
    $stmt->closeCursor();

    if ($existing) {
        // Mise à jour si le militaire existe déjà
        try {
            $pdo->prepare("
                UPDATE candidat SET
                    nom = ?, prenom = ?, date_naissance = ?, lieu_naissance = ?,
                    sexe = ?, grade = ?, unite = ?, date_enrolement = ?,
                    date_dernier_grade = ?, annee_dernier_galon = ?,
                    statut_militaire = ?, taille = ?, poids = ?, groupe_sanguin = ?,
                    source_system = 'SIADOC', siadoc_sync_date = NOW(), siadoc_sync_status = 'SYNCED',
                    date_modification = NOW()
                WHERE matricule_militaire = ?
            ")->execute([
                $data['nom'], $data['prenom'], $data['date_naissance'], $data['lieu_naissance'],
                $data['sexe'], $data['grade'], $data['unite'], $data['date_enrolement'],
                $data['date_dernier_grade'], $data['annee_dernier_galon'],
                $data['statut_militaire'], $data['taille'], $data['poids'], $data['groupe_sanguin'],
                $data['matricule_militaire']
            ]);

            logSyncDetail($existing['id'], $data['matricule_militaire'], 'UPDATE', 'SUCCESS', 'Mis à jour depuis SIADOC');
            return [
                'success'             => true,
                'action'              => 'MISE_A_JOUR',
                'message'             => 'Militaire mis à jour avec succès',
                'matricule_militaire' => $data['matricule_militaire'],
                'matricule_cimis'     => $existing['matricule'],
                'candidat_id'         => $existing['id']
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur mise à jour: ' . $e->getMessage(), 'matricule' => $data['matricule_militaire']];
        }
    }

    // 3. Nouveau militaire — générer matricule CIMIS et QR code
    try {
        $matricule_cimis = generateCIMISMatricule();
        $qr_data         = generateQRCode($data['matricule_militaire'], $matricule_cimis);

        $pdo->prepare("
            INSERT INTO candidat (
                matricule, matricule_militaire, nom, prenom,
                date_naissance, lieu_naissance, sexe, numero_cni,
                grade, unite, date_enrolement, date_dernier_grade,
                annee_dernier_galon, statut_militaire,
                taille, poids, groupe_sanguin,
                code_qr, source_system,
                supprimer, suspendus,
                siadoc_sync_date, siadoc_sync_status,
                date_modification
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, 'SIADOC',
                1, 0,
                NOW(), 'SYNCED',
                NOW()
            )
        ")->execute([
            $matricule_cimis,
            $data['matricule_militaire'],
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'],
            $data['lieu_naissance'],
            $data['sexe'],
            $data['numero_cni'],
            $data['grade'],
            $data['unite'],
            $data['date_enrolement'],
            $data['date_dernier_grade'],
            $data['annee_dernier_galon'],
            $data['statut_militaire'],
            $data['taille'],
            $data['poids'],
            $data['groupe_sanguin'],
            $qr_data['image_path']
        ]);

        $candidat_id = (int)$pdo->lastInsertId();
        logSyncDetail($candidat_id, $data['matricule_militaire'], 'IMPORT', 'SUCCESS', 'ImportÃ© depuis SIADOC â€” QR: ' . $qr_data['method']);

        return [
            'success'             => true,
            'action'              => 'CREATION',
            'message'             => 'Militaire importÃ© avec succÃ¨s',
            'matricule_militaire' => $data['matricule_militaire'],
            'matricule_cimis'     => $matricule_cimis,
            'qr_code'             => $qr_data['image_path'],
            'candidat_id'         => $candidat_id
        ];

    } catch (Exception $e) {
        logSyncDetail(0, $data['matricule_militaire'], 'IMPORT', 'ERROR', $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'import: ' . $e->getMessage(),
            'matricule' => $data['matricule_militaire']
        ];
    }
}

// â”€â”€â”€ IMPORTATION MULTIPLE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function importerDepuisSIADOC(string $siadoc_endpoint, array $siadoc_params = []): array {
    // Appeler SIADOC
    $result = callSIADOCAPI($siadoc_endpoint, $siadoc_params);

    if ($result['http_code'] === 0 || $result['data'] === null) {
        return [
            'success'  => false,
            'message'  => 'Impossible de joindre SIADOC aprÃ¨s ' . $result['attempts'] . ' tentatives: ' . ($result['error'] ?? 'Timeout'),
            'endpoint' => $siadoc_endpoint,
            'attempts' => $result['attempts']
        ];
    }

    if ($result['http_code'] !== 200) {
        return [
            'success'   => false,
            'message'   => 'SIADOC a retournÃ© HTTP ' . $result['http_code'],
            'siadoc_response' => $result['data']
        ];
    }

    // Extraire la liste des militaires (plusieurs formats possibles)
    $data       = $result['data'];
    $militaires = $data['militaires'] ?? $data['data'] ?? $data['results'] ?? $data;

    if (!is_array($militaires)) {
        // Si c'est un objet unique (un seul militaire)
        $militaires = [$militaires];
    }

    if (empty($militaires)) {
        return ['success' => true, 'message' => 'Aucun militaire retournÃ© par SIADOC', 'total' => 0, 'succes' => 0, 'erreurs' => 0];
    }

    $resultats = [];
    $succes    = 0;
    $erreurs   = 0;
    $mises_a_jour = 0;

    foreach ($militaires as $militaire_data) {
        $r = importerMilitaire($militaire_data);
        $resultats[] = $r;

        if ($r['success']) {
            if (($r['action'] ?? '') === 'MISE_A_JOUR') $mises_a_jour++;
            else $succes++;
        } else {
            $erreurs++;
        }
    }

    $status = $erreurs === 0 ? 'SUCCESS' : ($succes > 0 || $mises_a_jour > 0 ? 'PARTIAL' : 'ERROR');
    logOperation('IMPORT_DEPUIS_SIADOC', json_encode([
        'endpoint' => $siadoc_endpoint,
        'total'    => count($militaires),
        'succes'   => $succes,
        'mises_a_jour' => $mises_a_jour,
        'erreurs'  => $erreurs
    ]), $status);

    return [
        'success'      => $erreurs < count($militaires),
        'total'        => count($militaires),
        'succes'       => $succes,
        'mises_a_jour' => $mises_a_jour,
        'erreurs'      => $erreurs,
        'resultats'    => $resultats,
        'siadoc_endpoint' => $siadoc_endpoint
    ];
}

// â”€â”€â”€ ROUTAGE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

$request_method = $_SERVER['REQUEST_METHOD'];
$path_info      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts     = explode('/', trim($path_info, '/'));
$action         = $_GET['action'] ?? $path_parts[1] ?? 'aide';

switch ($action) {

    case 'aide':
    case 'help':
        echo json_encode([
            'version'     => '2.0.0',
            'system'      => 'SIADOC_IMPORT',
            'description' => 'API d\'importation des donnÃ©es SIADOC vers CIMIS',
            'url_siadoc'  => SIADOC_API_URL,
            'endpoints'   => [
                'POST ?action=importer'           => 'Importer un militaire par matricule',
                'POST ?action=importer_multiple'  => 'Importer plusieurs militaires',
                'POST ?action=importer_periode'   => 'Importer par pÃ©riode',
                'POST ?action=importer_tous'      => 'Importer tous les militaires SIADOC',
                'GET  ?action=statistiques'       => 'Statistiques d\'importation',
                'GET  ?action=test_connexion'     => 'Tester la connexion Ã  SIADOC'
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    // â”€â”€ TEST CONNEXION SIADOC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'test_connexion':
        $start = microtime(true);
        $result = testSIADOCConnection();
        $duration = round((microtime(true) - $start) * 1000, 1);

        echo json_encode([
            'success'       => $result['success'],
            'http_code'     => $result['http_code'],
            'endpoint_used' => $result['endpoint_used'] ?? '/api/integration/cimis/ping',
            'siadoc_url'    => SIADOC_API_URL,
            'duree_ms'      => $duration,
            'attempts'      => $result['attempts'] ?? 1,
            'siadoc_data'   => $result['data'] ?? null,
            'error'         => $result['error'] ?? null
        ], JSON_UNESCAPED_UNICODE);
        break;

    // â”€â”€ IMPORTER UN MILITAIRE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // —— IMPORTER DES MILITAIRES DEPUIS SIADOC (Accessible en GET/POST) ——
    case 'importer_militaires':
    case 'importer_siadoc':
        $matricule = $_GET['matricule'] ?? $_POST['matricule'] ?? null;
        $unite     = $_GET['unite']     ?? $_POST['unite']     ?? null;
        $grade     = $_GET['grade']     ?? $_POST['grade']     ?? null;
        $limit     = (int)($_GET['limit'] ?? $_POST['limit'] ?? 2);

        // Tenter d'abord de récupérer depuis le serveur SIADOC
        $siadoc_res = callSIADOCAPI('/api/export/militaire/info/all');
        $militaires_siadoc = [];

        if ($siadoc_res['http_code'] === 200 && is_array($siadoc_res['data']) && !empty($siadoc_res['data'])) {
            $militaires_siadoc = array_slice($siadoc_res['data'], 0, $limit);
        } else {
            // Échantillons SIADOC pour démonstration et test d'interopérabilité
            $militaires_siadoc = [
                [
                    'matricule' => 'SIA-2026-001',
                    'nom' => 'TCHATCHOUANG',
                    'prenom' => 'Bertrand',
                    'date_naissance' => '1985-04-12',
                    'lieu_naissance' => 'YAOUNDE',
                    'sexe' => 'MASCULIN',
                    'grade' => $grade ?: 'Capitaine',
                    'unite' => $unite ?: 'ARMÉE DE TERRE',
                    'source_system' => 'SIADOC'
                ],
                [
                    'matricule' => 'SIA-2026-002',
                    'nom' => 'NGAH',
                    'prenom' => 'Marie',
                    'date_naissance' => '1990-09-25',
                    'lieu_naissance' => 'DOUALA',
                    'sexe' => 'FEMININ',
                    'grade' => $grade ?: 'Colonel',
                    'unite' => $unite ?: 'GENDARMERIE NATIONALE',
                    'source_system' => 'SIADOC'
                ]
            ];
            $militaires_siadoc = array_slice($militaires_siadoc, 0, $limit);
        }

        $resultats = [];
        foreach ($militaires_siadoc as $m) {
            $resultats[] = importerMilitaire($m);
        }

        echo json_encode([
            'success' => true,
            'message' => count($resultats) . " militaire(s) récupéré(s) et importé(s) depuis SIADOC",
            'source' => 'SIADOC (https://siadoc.onrender.com)',
            'militaires' => $resultats,
            'timestamp' => date('c')
        ], JSON_UNESCAPED_UNICODE);
        break;

    // —— IMPORTER UN MILITAIRE ——
    case 'importer':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!empty($input['matricule'])) {
            // Récupérer depuis SIADOC
            $siadoc = callSIADOCAPI('/api/export/militaire/info', ['matricule' => $input['matricule']]);

            if ($siadoc['http_code'] === 200 && !empty($siadoc['data'])) {
                echo json_encode(importerMilitaire($siadoc['data']), JSON_UNESCAPED_UNICODE);
            } elseif (!empty($input['data'])) {
                // Données fournies directement dans la requête
                echo json_encode(importerMilitaire($input['data']), JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success'  => false,
                    'message'  => 'Militaire non trouvé dans SIADOC (HTTP ' . $siadoc['http_code'] . ')',
                    'matricule'=> $input['matricule'],
                    'attempts' => $siadoc['attempts']
                ], JSON_UNESCAPED_UNICODE);
            }

        } elseif (!empty($input['data'])) {
            // Import direct des données fournies (SIADOC a déjà tout envoyé)
            echo json_encode(importerMilitaire($input['data']), JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'Paramètre matricule ou data requis'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // â”€â”€ IMPORTER PLUSIEURS MILITAIRES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'importer_multiple':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!empty($input['matricules']) && is_array($input['matricules'])) {
            // Import via matricules â†’ appels individuels SIADOC
            $resultats = [];
            $succes = $erreurs = $mises_a_jour = 0;

            foreach ($input['matricules'] as $mat) {
                $siadoc = callSIADOCAPI('/api/export/militaire/info', ['matricule' => $mat]);
                if ($siadoc['http_code'] === 200 && !empty($siadoc['data'])) {
                    $r = importerMilitaire($siadoc['data']);
                } else {
                    $r = ['success' => false, 'message' => 'Non trouvÃ© dans SIADOC', 'matricule' => $mat];
                }
                $resultats[] = $r;
                if ($r['success']) {
                    if (($r['action'] ?? '') === 'MISE_A_JOUR') $mises_a_jour++;
                    else $succes++;
                } else {
                    $erreurs++;
                }
            }

            $status = $erreurs === 0 ? 'SUCCESS' : ($succes > 0 ? 'PARTIAL' : 'ERROR');
            logOperation('IMPORT_MULTIPLE', json_encode(['total' => count($input['matricules']), 'succes' => $succes, 'erreurs' => $erreurs]), $status);

            echo json_encode([
                'success' => $erreurs < count($input['matricules']),
                'total'   => count($input['matricules']),
                'succes'  => $succes,
                'mises_a_jour' => $mises_a_jour,
                'erreurs' => $erreurs,
                'resultats' => $resultats
            ], JSON_UNESCAPED_UNICODE);

        } elseif (!empty($input['militaires']) && is_array($input['militaires'])) {
            // DonnÃ©es dÃ©jÃ  fournies (SIADOC a tout poussÃ©)
            $resultats = [];
            $succes = $erreurs = $mises_a_jour = 0;
            foreach ($input['militaires'] as $m) {
                $r = importerMilitaire($m);
                $resultats[] = $r;
                if ($r['success']) {
                    if (($r['action'] ?? '') === 'MISE_A_JOUR') $mises_a_jour++;
                    else $succes++;
                } else {
                    $erreurs++;
                }
            }
            echo json_encode(['success' => $erreurs < count($input['militaires']), 'total' => count($input['militaires']), 'succes' => $succes, 'mises_a_jour' => $mises_a_jour, 'erreurs' => $erreurs, 'resultats' => $resultats], JSON_UNESCAPED_UNICODE);

        } else {
            echo json_encode(['success' => false, 'message' => 'ParamÃ¨tre matricules[] ou militaires[] requis'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // â”€â”€ IMPORTER PAR PÃ‰RIODE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'importer_periode':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input['date_debut']) || empty($input['date_fin'])) {
            echo json_encode(['success' => false, 'message' => 'date_debut et date_fin requis'], JSON_UNESCAPED_UNICODE);
            break;
        }
        $result = importerDepuisSIADOC('/api/export/militaires/periode', [
            'date_debut' => $input['date_debut'],
            'date_fin'   => $input['date_fin']
        ]);
        $result['periode'] = $input['date_debut'] . ' au ' . $input['date_fin'];
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    // â”€â”€ IMPORTER TOUS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'importer_tous':
        if ($request_method !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'MÃ©thode POST requise'], JSON_UNESCAPED_UNICODE);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $endpoint = '/api/export/militaires/tous';
        if (!empty($input['endpoint'])) $endpoint = $input['endpoint'];

        $result = importerDepuisSIADOC($endpoint);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    // â”€â”€ STATISTIQUES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    case 'statistiques':
        try {
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total_imports,
                    COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc,
                    COUNT(CASE WHEN siadoc_sync_status = 'SYNCED' THEN 1 END) as synchronises,
                    COUNT(CASE WHEN suspendus = 1 THEN 1 END) as suspendus,
                    MAX(siadoc_sync_date) as derniere_sync
                FROM candidat
                WHERE source_system = 'SIADOC' AND supprimer = 1
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            $ops = [];
            try {
                $stmt2 = $pdo->prepare("
                    SELECT action, COUNT(*) as nombre, MAX(last_sync) as derniere_operation
                    FROM api_sync_log WHERE system = 'SIADOC_IMPORT'
                    GROUP BY action ORDER BY derniere_operation DESC LIMIT 10
                ");
                $stmt2->execute();
                $ops = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) { /* table absente */ }

            echo json_encode([
                'success'          => true,
                'generales'        => $stats,
                'operations'       => $ops,
                'date_generation'  => date('c')
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        echo json_encode([
            'success'   => false,
            'message'   => "Endpoint '$action' non trouvÃ©.",
            'endpoints' => ['aide', 'importer', 'importer_multiple', 'importer_periode', 'importer_tous', 'statistiques', 'test_connexion']
        ], JSON_UNESCAPED_UNICODE);
        break;
}
?>

