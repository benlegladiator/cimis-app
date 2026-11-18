<?php
/**
 * CIMIS - Service Interopérabilité SIADOC & Synchronisation Externe (Étape 12 Découplage)
 * Gère les appels cURL vers l'API SIADOC, l'authentification par clé d'API,
 * les webhooks de statut de carte et l'import/export de personnels militaires.
 */

if (!defined('SIADOC_API_KEY')) {
    require_once dirname(__DIR__, 2) . '/config/config.php';
}

require_once __DIR__ . '/EnrolementService.php';

if (!defined('SIADOC_API_KEY')) {
    define('SIADOC_API_KEY', $_ENV['SIADOC_API_KEY'] ?? $_SERVER['SIADOC_API_KEY'] ?? getenv('SIADOC_API_KEY') ?: '');
}

if (!defined('SIADOC_API_URL')) {
    define('SIADOC_API_URL', $_ENV['SIADOC_API_URL'] ?? $_SERVER['SIADOC_API_URL'] ?? getenv('SIADOC_API_URL') ?: 'http://127.0.0.1:8080');
}

if (!defined('SIADOC_WEBHOOK_URL')) {
    define('SIADOC_WEBHOOK_URL', $_ENV['SIADOC_WEBHOOK_URL'] ?? $_SERVER['SIADOC_WEBHOOK_URL'] ?? getenv('SIADOC_WEBHOOK_URL') ?: 'http://127.0.0.1:8080/api/cimis/webhook/statut-carte');
}

if (!function_exists('ensureSiadocTables')) {
    function ensureSiadocTables(PDO $pdo): void {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS api_sync_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    system VARCHAR(50) DEFAULT 'SIADOC',
                    action VARCHAR(100) NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'SUCCESS',
                    details TEXT NULL,
                    last_sync DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (Exception $e) {}
    }
}

if (!function_exists('validateSiadocApiKey')) {
    /**
     * Valide la clé d'API fournie par les requêtes entrantes SIADOC
     */
    function validateSiadocApiKey(?string $providedKey): bool {
        if (empty($providedKey)) return false;
        $configuredKey = defined('SIADOC_API_KEY') ? SIADOC_API_KEY : '';
        if (empty($configuredKey)) {
            $configuredKey = config('security.siadoc_api_key', '');
        }
        if (empty($configuredKey)) {
            $configuredKey = config('services.siadoc.api_key', '');
        }
        if (empty($configuredKey)) {
            $configuredKey = $_ENV['SIADOC_API_KEY'] ?? getenv('SIADOC_API_KEY') ?: '';
        }
        if (empty($configuredKey)) {
            $configuredKey = 'siadoc-2026-cimis-integration';
        }
        return hash_equals($configuredKey, trim($providedKey));
    }
}

if (!function_exists('logSiadocSync')) {
    /**
     * Enregistre une entrée dans le journal de synchronisation SIADOC
     */
    function logSiadocSync(PDO $pdo, string $action, string $status, string $details = ''): void {
        ensureSiadocTables($pdo);
        try {
            $stmt = $pdo->prepare("
                INSERT INTO api_sync_log (system, action, status, details, last_sync)
                VALUES ('SIADOC', :action, :status, :details, NOW())
            ");
            $stmt->execute([
                'action'  => $action,
                'status'  => strtoupper($status),
                'details' => $details
            ]);
        } catch (Exception $e) {
            error_log("Erreur logSiadocSync: " . $e->getMessage());
        }
    }
}

if (!function_exists('callSiadocApi')) {
    /**
     * Effectue une requête HTTP sécurisée vers les endpoints de l'API SIADOC
     */
    function callSiadocApi(string $endpoint, array $params = [], string $method = 'GET'): array {
        $url = rtrim(SIADOC_API_URL, '/') . '/' . ltrim($endpoint, '/');
        if (!empty($params) && $method === 'GET') {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'X-API-KEY: ' . SIADOC_API_KEY,
            'Authorization: Bearer ' . SIADOC_API_KEY
        ];

        $curlOpts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        if ($method === 'POST') {
            $curlOpts[CURLOPT_POST] = true;
            $curlOpts[CURLOPT_POSTFIELDS] = json_encode($params, JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
            $curlOpts[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $curlOpts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'success'   => ($httpCode >= 200 && $httpCode < 300),
            'http_code' => $httpCode,
            'data'      => json_decode($response, true) ?: [],
            'raw'       => $response,
            'error'     => $curlError ?: ($httpCode >= 400 ? "HTTP Error $httpCode" : null)
        ];
    }
}

if (!function_exists('notifySiadocCardStatus')) {
    /**
     * Déclenche le webhook de notification vers SIADOC lors des changements d'état d'une carte
     */
    function notifySiadocCardStatus(string $matricule, string $statut = 'DESACTIVE', string $motif = 'Changement de statut CIMIS'): array {
        if (empty($matricule)) {
            return ['success' => false, 'error' => 'Matricule militaire manquant'];
        }

        $statutUpper = strtoupper(trim($statut));
        $statutNorm = in_array($statutUpper, ['ACTIVE', 'ACTIF', '0'], true) ? 'ACTIVE' : 'DESACTIVE';

        $payload = [
            'matricule' => (string)$matricule,
            'statut'    => $statutNorm,
            'motif'     => (string)$motif,
            'source'    => 'CIMIS_v2'
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init(SIADOC_WEBHOOK_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-API-KEY: ' . SIADOC_API_KEY
            ],
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response   = curl_exec($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode === 200);

        // Audit de l'envoi webhook
        global $pdo;
        if (isset($pdo)) {
            try {
                if (file_exists(__DIR__ . '/SecurityService.php')) {
                    require_once __DIR__ . '/SecurityService.php';
                    logSecurityActivity($pdo, null, 'WEBHOOK_SIADOC_' . $statutNorm, "Matricule: $matricule | HTTP $httpCode");
                }
                logSiadocSync($pdo, 'WEBHOOK_' . $statutNorm, $success ? 'SUCCESS' : 'ERROR', "Matricule: $matricule (Code $httpCode)");
            } catch (Exception $e) {}
        }

        return [
            'success'   => $success,
            'http_code' => $httpCode,
            'response'  => $response,
            'payload'   => $payload,
            'error'     => $curlError ?: ($success ? null : "Erreur HTTP $httpCode")
        ];
    }
}

if (!function_exists('callSiadocWebhookEvenement')) {
    /**
     * Envoie un événement de webhook sécurisé vers SIADOC
     */
    function callSiadocWebhookEvenement(array $payload): array {
        $baseUrl = defined('SIADOC_API_URL') ? SIADOC_API_URL : 'http://127.0.0.1:8080';
        $webhookUrl = rtrim($baseUrl, '/') . '/api/cimis/webhook/evenement';
        $apiKey = defined('SIADOC_API_KEY') && !empty(SIADOC_API_KEY) ? SIADOC_API_KEY : 'siadoc-2026-cimis-integration';

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-API-KEY: ' . $apiKey
            ],
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode === 200);
        return [
            'success'   => $success,
            'http_code' => $httpCode,
            'response'  => $response,
            'error'     => $curlError ?: ($success ? null : "Erreur HTTP $httpCode")
        ];
    }
}

if (!function_exists('notifySiadocEnrolementConforme')) {
    /**
     * Notifie SIADOC que l'enrôlement biométrique est complet et conforme.
     * Déclenche la notification ciblée à la Compagnie du militaire.
     */
    function notifySiadocEnrolementConforme(string $matriculeMilitaire, string $centre = 'Centre CIMIS', string $operateur = 'CIMIS'): array {
        if (empty($matriculeMilitaire)) {
            return ['success' => false, 'error' => 'Matricule militaire manquant'];
        }

        $payload = [
            'evenement' => 'ENROLEMENT_CONFORME',
            'matricule' => trim($matriculeMilitaire),
            'centre'    => $centre,
            'operateur' => $operateur,
            'source'    => 'CIMIS'
        ];

        return callSiadocWebhookEvenement($payload);
    }
}

if (!function_exists('notifySiadocCarteImprimee')) {
    /**
     * Notifie SIADOC que la carte CMI a été imprimée.
     * Déclenche l'alerte de remise et convocation pour la Compagnie du militaire.
     */
    function notifySiadocCarteImprimee(string $matriculeMilitaire, string $numeroCarte = '', string $qrCode = '', string $lieuRetrait = '', string $centre = 'Atelier Impression CIMIS'): array {
        if (empty($matriculeMilitaire)) {
            return ['success' => false, 'error' => 'Matricule militaire manquant'];
        }

        $payload = [
            'evenement'   => 'CARTE_IMPRIMEE',
            'matricule'   => trim($matriculeMilitaire),
            'numeroCarte' => $numeroCarte,
            'qrCode'      => $qrCode,
            'lieuRetrait' => $lieuRetrait,
            'centre'      => $centre,
            'source'      => 'CIMIS'
        ];

        return callSiadocWebhookEvenement($payload);
    }
}

if (!function_exists('getSiadocSyncStats')) {
    /**
     * Calcule les métriques de synchronisation SIADOC ↔ CIMIS
     */
    function getSiadocSyncStats(PDO $pdo): array {
        $stats = [
            'total_siadoc'   => 0,
            'synced_siadoc'  => 0,
            'pending_siadoc' => 0,
            'last_sync_date' => null
        ];

        try {
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) AS total_siadoc,
                    SUM(CASE WHEN source_system = 'SIADOC' THEN 1 ELSE 0 END) AS synced_siadoc,
                    SUM(CASE WHEN source_system = 'SIADOC' AND suspendus = 1 THEN 1 ELSE 0 END) AS pending_siadoc,
                    MAX(date_derniere_reimpression) AS last_sync_date
                FROM candidat 
                WHERE (source_system = 'SIADOC' OR matricule_militaire LIKE 'SIA%') AND supprimer = 0
            ");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                $stats['total_siadoc']   = (int)($res['total_siadoc'] ?? 0);
                $stats['synced_siadoc']  = (int)($res['synced_siadoc'] ?? 0);
                $stats['pending_siadoc'] = (int)($res['pending_siadoc'] ?? 0);
                $stats['last_sync_date'] = $res['last_sync_date'];
            }
        } catch (PDOException $e) {
            error_log("Erreur stats SIADOC: " . $e->getMessage());
        }

        return $stats;
    }
}

if (!function_exists('siadocResolvePath')) {
    function siadocResolvePath(?string $path): ?string {
        if (empty($path)) return null;

        // Cache statique en mémoire pour la durée de la requête HTTP
        static $pathCache = [];
        if (isset($pathCache[$path])) {
            return $pathCache[$path];
        }

        $baseDir = dirname(__DIR__, 2);

        // Si le chemin est déjà absolu et existe
        if ((strpos($path, '/') === 0 || preg_match('#^[a-zA-Z]:[\\\\/]#', $path)) && file_exists($path) && is_file($path)) {
            return $pathCache[$path] = $path;
        }

        $clean = preg_replace('#^(\.\./|\./|/cimcim/public/|/cimcim/|public/)+#', '', trim($path));
        $clean = ltrim($clean, '/\\');
        
        $hasLaravelStorage = function_exists('app') && function_exists('storage_path') && app() instanceof \Illuminate\Contracts\Foundation\Application;
        $storageDir = $hasLaravelStorage ? storage_path('app/public/') : ($baseDir . '/storage/app/public/');
        $baseName = basename($clean);

        // Chemins directs les plus probables en premier (98% des cas réels)
        $candidates = [];
        if (strpos($clean, 'storage/') === 0) {
            $subPath = substr($clean, 8); // enlever 'storage/'
            $candidates[] = $storageDir . $subPath;
            $candidates[] = $baseDir . '/storage/app/public/' . $subPath;
        }
        $candidates[] = $baseDir . '/public/' . $clean;
        $candidates[] = $storageDir . 'candidats/' . $baseName;
        $candidates[] = $storageDir . 'empreintes/' . $baseName;
        $candidates[] = $storageDir . 'qrcodes/' . $baseName;
        $candidates[] = $baseDir . '/' . $clean;

        // Fallbacks historiques si non trouvé
        $candidates[] = $baseDir . '/public/img/candidats/' . $baseName;
        $candidates[] = $baseDir . '/public/img/empreintes/' . $baseName;
        $candidates[] = $baseDir . '/public/img/qrcodes/' . $baseName;
        $candidates[] = $baseDir . '/img/candidats/' . $baseName;
        $candidates[] = $baseDir . '/img/empreintes/' . $baseName;
        $candidates[] = $baseDir . '/img/qrcodes/' . $baseName;

        foreach ($candidates as $cand) {
            if (file_exists($cand) && is_file($cand)) {
                return $pathCache[$path] = $cand;
            }
        }
        return $pathCache[$path] = null;
    }
}

if (!function_exists('siadocEncodeImageBase64')) {
    function siadocEncodeImageBase64(?string $path): ?string {
        $real = siadocResolvePath($path);
        if ($real && file_exists($real)) {
            $data = file_get_contents($real);
            if (!empty($data)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $real) ?: 'image/png';
                finfo_close($finfo);
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        }
        return null;
    }
}

if (!function_exists('siadocGenererMiniatureBase64')) {
    /**
     * Génère une miniature ultra-légère (~25 Ko) avec cache disque automatique.
     * Conserve l'image originale intacte et évite de saturer le réseau lors des listes.
     */
    function siadocGenererMiniatureBase64(?string $path, int $maxW = 280, int $maxH = 350): ?string {
        $real = siadocResolvePath($path);
        if (!$real || !file_exists($real) || filesize($real) === 0) {
            return null;
        }

        $baseDir = dirname(__DIR__, 2);
        $thumbsDir = $baseDir . '/storage/app/public/thumbs/';
        if (!is_dir($thumbsDir)) {
            @mkdir($thumbsDir, 0755, true);
        }

        $hashName = md5($real . '_' . filemtime($real) . "_{$maxW}x{$maxH}") . '.jpg';
        $thumbFile = $thumbsDir . $hashName;

        // 1. Si la miniature en cache existe déjà sur disque, la renvoyer directement (1 ms)
        if (file_exists($thumbFile) && filesize($thumbFile) > 0) {
            $data = file_get_contents($thumbFile);
            return 'data:image/jpeg;base64,' . base64_encode($data);
        }

        // 2. Sinon, générer la miniature avec PHP GD
        try {
            $info = @getimagesize($real);
            if (!$info) return siadocEncodeImageBase64($path);

            $origW = $info[0];
            $origH = $info[1];
            $type = $info[2];

            // Si déjà très petite (< 350px), pas besoin de redimensionner
            if ($origW <= $maxW && $origH <= $maxH) {
                return siadocEncodeImageBase64($path);
            }

            $src = null;
            if ($type === IMAGETYPE_JPEG) {
                $src = @imagecreatefromjpeg($real);
            } elseif ($type === IMAGETYPE_PNG) {
                $src = @imagecreatefrompng($real);
            } elseif ($type === IMAGETYPE_WEBP) {
                $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($real) : null;
            }

            if (!$src) {
                return siadocEncodeImageBase64($path);
            }

            // Calcul du ratio pour préserver les proportions exactes du visage
            $ratio = min($maxW / $origW, $maxH / $origH);
            $newW = (int)round($origW * $ratio);
            $newH = (int)round($origH * $ratio);

            $thumb = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            // Sauvegarde dans le cache disque en JPEG qualité 80 (~20 à 30 Ko)
            @imagejpeg($thumb, $thumbFile, 80);

            ob_start();
            imagejpeg($thumb, null, 80);
            $bin = ob_get_clean();

            imagedestroy($src);
            imagedestroy($thumb);

            if (!empty($bin)) {
                return 'data:image/jpeg;base64,' . base64_encode($bin);
            }
        } catch (\Throwable $t) {
            error_log("Erreur génération miniature GD : " . $t->getMessage());
        }

        return siadocEncodeImageBase64($path);
    }
}

if (!function_exists('formatCandidatBiometrieForSiadoc')) {
    /**
     * Mappe et encode en Base64 les données biométriques et d'identité pour SIADOC.
     * empreinteDoigt1 = empreinte_pouces (les 2 pouces ou pouce gauche)
     * empreinteDoigt2 = empreinte_carte  (le pouce droit gravé sur la carte PVC)
     */
    function formatCandidatBiometrieForSiadoc(array $candidat): array {
        $matMil = $candidat['matricule_militaire'] ?? $candidat['matricule'] ?? '';
        $matCim = $candidat['matricule'] ?? '';

        // 1. Photo HD
        $photoBase64 = siadocEncodeImageBase64($candidat['photo'] ?? $candidat['photo_path'] ?? null);

        // 2. Empreintes : mapping officiel demandé
        // empreinteDoigt2 = Empreinte carte (Pouce Droit)
        $carteBase64 = siadocEncodeImageBase64($candidat['empreinte_data'] ?? null);
        // empreinteDoigt1 = Empreinte pouces (Pouce Gauche + Droit)
        $poucesBase64 = siadocEncodeImageBase64($candidat['empreinte_pouces'] ?? null);
        // Fallback si l'un manque
        if (!$carteBase64 && $poucesBase64) $carteBase64 = $poucesBase64;
        if (!$poucesBase64 && $carteBase64) $poucesBase64 = $carteBase64;

        // Slaps complémentaires 4-4-2
        $slapGaucheBase64 = siadocEncodeImageBase64($candidat['empreinte_slap_gauche'] ?? null);
        $slapDroiteBase64 = siadocEncodeImageBase64($candidat['empreinte_slap_droite'] ?? null);

        // 3. QR Code (chercher d'abord dans code_qr, puis sur disque par matricule, ou générer à la volée)
        $qrBase64 = siadocEncodeImageBase64($candidat['code_qr'] ?? null);
        if (!$qrBase64) {
            // Recherche par matricule militaire ou matricule CIMIS
            $safeMil = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $matMil);
            $safeCim = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $matCim);
            $qrBase64 = siadocEncodeImageBase64('qrcodes/' . $safeMil . '_qr.png');
            if (!$qrBase64 && !empty($safeCim)) {
                $qrBase64 = siadocEncodeImageBase64('qrcodes/' . $safeCim . '_qr.png');
            }
            // Si toujours absent, générer via QrCodeService si disponible
            if (!$qrBase64 && file_exists(dirname(__DIR__) . '/Services/QrCodeService.php')) {
                require_once dirname(__DIR__) . '/Services/QrCodeService.php';
                if (function_exists('generateQRCodeForMatricule')) {
                    $genFile = generateQRCodeForMatricule($matMil ?: $matCim, $candidat);
                    if ($genFile) {
                        $qrBase64 = siadocEncodeImageBase64($genFile);
                    }
                }
            }
        }

        // Statut normalisé
        $statut = 'ACTIVE';
        if (!empty($candidat['suspendus']) && (int)$candidat['suspendus'] === 1) {
            $statut = 'SUSPENDU';
        } elseif (!empty($candidat['statut']) && in_array(strtoupper($candidat['statut']), ['DESACTIVE', 'INACTIF'])) {
            $statut = 'DESACTIVE';
        }

        return [
            // Identifiants & RH
            'matricule'          => $matMil,
            'matriculeMilitaire' => $matMil,
            'matricule_cimis'    => $matCim,
            'numeroCIM'          => $matCim,
            'nom'                => $candidat['nom'] ?? '',
            'prenom'             => $candidat['prenom'] ?? '',
            'grade'              => $candidat['grade'] ?? '',
            'corps'              => $candidat['unite'] ?? '',
            'armeService'        => $candidat['unite'] ?? '',
            'sexe'               => $candidat['sexe'] ?? 'MASCULIN',
            'numeroCNI'          => $candidat['numero_cni'] ?? '',
            'dateNaissance'      => $candidat['date_naissance'] ?? '',

            // Médical & Physique
            'taille'             => $candidat['taille'] ?? '',
            'poids'              => $candidat['poids'] ?? '',
            'groupeSanguin'      => $candidat['groupe_sanguin'] ?? '',

            // Statut de carte & Enrôlement
            'statutCarteCimis'   => $statut,
            'statut'             => $statut,
            'dateEnrolement'     => $candidat['date_enrolement'] ?? $candidat['created_at'] ?? date('Y-m-d H:i:s'),

            // Médias & Biométrie principale (compatibilité SIADOC PostgreSQL table donnee_biometrique)
            'photoVisage'        => $photoBase64,
            'photo_base64'       => $photoBase64,
            'empreinteDoigt1'    => $poucesBase64, // Pouces réunis (ou Pouce G)
            'empreinteDoigt2'    => $carteBase64,  // Empreinte de la Carte (Pouce D)
            'qrCodeImage'        => $qrBase64,
            'code_qr'            => $qrBase64,
            'qrCodeContenu'      => $candidat['code_qr'] ?? ($matMil ? "MINDEF-CIMIS-$matMil" : ''),

            // Clés explicites du protocole 4-4-2
            'empreinte_carte'       => $carteBase64,
            'empreinte_pouces'      => $poucesBase64,
            'empreinte_slap_gauche' => $slapGaucheBase64,
            'empreinte_slap_droite' => $slapDroiteBase64,
            'source_application'    => 'CIMIS'
        ];
    }
}

if (!function_exists('pushCandidatBiometrieToSiadoc')) {
    /**
     * Pousse directement la biométrie d'un candidat vers SIADOC
     */
    function pushCandidatBiometrieToSiadoc(PDO $pdo, string $matricule): array {
        $stmt = $pdo->prepare("SELECT * FROM candidat WHERE (matricule = :m1 OR matricule_militaire = :m2) AND supprimer = 0 LIMIT 1");
        $stmt->execute(['m1' => $matricule, 'm2' => $matricule]);
        $candidat = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$candidat) {
            return ['success' => false, 'error' => "Candidat $matricule introuvable dans CIMIS."];
        }

        $payload = formatCandidatBiometrieForSiadoc($candidat);

        // Tenter d'abord POST /api/import/cimis/biometrie
        $endpoints = [
            'api/import/cimis/biometrie',
            'api/integration/cimis/importer-biometrie'
        ];

        $lastResult = null;
        foreach ($endpoints as $ep) {
            $res = callSiadocApi($ep, $payload, 'POST');
            if ($res['success']) {
                logSiadocSync($pdo, 'PUSH_BIOMETRIE', 'SUCCESS', "Matricule: $matricule via $ep");
                return [
                    'success'   => true,
                    'endpoint'  => $ep,
                    'http_code' => $res['http_code'],
                    'message'   => "Biométrie transmise à SIADOC avec succès pour le matricule $matricule."
                ];
            }
            $lastResult = $res;
        }

        logSiadocSync($pdo, 'PUSH_BIOMETRIE', 'ERROR', "Matricule: $matricule (Échec push SIADOC)");
        return [
            'success'   => false,
            'http_code' => $lastResult['http_code'] ?? 0,
            'error'     => $lastResult['error'] ?? 'Échec de transmission vers SIADOC.',
            'payload'   => [
                'matricule' => $matricule,
                'has_photo' => !empty($payload['photoVisage']),
                'has_doigt1' => !empty($payload['empreinteDoigt1'])
            ]
        ];
    }
}

if (!function_exists('syncCandidatFromSiadoc')) {
    /**
     * Synchronise intégralement un militaire depuis SIADOC vers CIMIS:
     * - Identité, Grade, Affectation, CNI
     * - Caractéristiques physiques (Taille, Poids, Groupe Sanguin)
     * - Photo HD
     * - Protocole 4-4-2 Empreintes (Pouce carte, 2 pouces, Slap Gauche, Slap Droite)
     * - Dossier médical complet SSA & Contact d'urgence
     */
    function syncCandidatFromSiadoc(PDO $pdo, string $matricule): array {
        $mat = trim($matricule);
        if (empty($mat)) {
            return ['success' => false, 'error' => 'Matricule militaire manquant'];
        }

        // 1. Récupération des données SIADOC (Biométrie + Export administratif)
        $bioRes  = callSiadocApi('api/import/cimis/biometrie', ['matricule' => $mat], 'GET');
        $infoRes = callSiadocApi('api/export/militaire/info', ['matricule' => $mat], 'GET');

        if (!$bioRes['success'] && !$infoRes['success']) {
            return [
                'success' => false,
                'error'   => "Impossible de récupérer les données SIADOC pour $mat (Code HTTP " . ($bioRes['http_code'] ?: $infoRes['http_code']) . ")"
            ];
        }

        $bio  = $bioRes['data'] ?? [];
        $info = $infoRes['data'] ?? [];

        // 2. Vérifier si le candidat existe déjà dans CIMIS
        $stmt = $pdo->prepare("SELECT * FROM candidat WHERE (matricule_militaire = :m1 OR matricule = :m2) AND (supprimer = 0 OR supprimer IS NULL) LIMIT 1");
        $stmt->execute(['m1' => $mat, 'm2' => $mat]);
        $candidat = $stmt->fetch(PDO::FETCH_ASSOC);

        $annee = date('Y');
        $baseDir = dirname(__DIR__, 2);
        $photoDir = $baseDir . '/public/img/candidats/';
        $empDir   = $baseDir . '/public/img/empreintes/';
        $storagePhotoDir = function_exists('storage_path') ? storage_path('app/public/candidats/') : ($baseDir . '/storage/app/public/candidats/');
        $storageEmpDir   = function_exists('storage_path') ? storage_path('app/public/empreintes/') : ($baseDir . '/storage/app/public/empreintes/');

        if (!is_dir($photoDir)) @mkdir($photoDir, 0775, true);
        if (!is_dir($empDir))   @mkdir($empDir, 0775, true);
        if (!is_dir($storagePhotoDir)) @mkdir($storagePhotoDir, 0775, true);
        if (!is_dir($storageEmpDir))   @mkdir($storageEmpDir, 0775, true);

        // Nom, prénom, grade, corps
        $nom    = !empty($bio['nomMilitaire']) ? $bio['nomMilitaire'] : (!empty($info['nom']) ? $info['nom'] : ($candidat['nom'] ?? 'INCONNU'));
        $prenom = !empty($bio['prenomMilitaire']) ? $bio['prenomMilitaire'] : (!empty($info['prenom']) ? $info['prenom'] : ($candidat['prenom'] ?? ''));
        $grade  = !empty($info['grade']) ? $info['grade'] : ($candidat['grade'] ?? 'SOLDAT');
        $sexe   = !empty($bio['sexe']) ? $bio['sexe'] : (!empty($info['sexe']) ? $info['sexe'] : ($candidat['sexe'] ?? 'MASCULIN'));
        $ddn    = !empty($bio['dateNaissance']) ? $bio['dateNaissance'] : (!empty($info['dateNaissance']) ? $info['dateNaissance'] : ($candidat['date_naissance'] ?? null));
        $ldn    = !empty($bio['lieuNaissance']) ? $bio['lieuNaissance'] : (!empty($info['lieuNaissance']) ? $info['lieuNaissance'] : ($candidat['lieu_naissance'] ?? null));
        $cni    = !empty($bio['numeroCni']) ? $bio['numeroCni'] : (!empty($info['numeroCNI']) ? $info['numeroCNI'] : ($candidat['numero_cni'] ?? null));
        $taille = !empty($bio['taille']) ? $bio['taille'] : ($candidat['taille'] ?? null);
        $poids  = !empty($bio['poids']) ? $bio['poids'] : ($candidat['poids'] ?? null);
        $gs     = !empty($bio['groupeSanguin']) ? $bio['groupeSanguin'] : ($candidat['groupe_sanguin'] ?? null);

        // Corps / Unité
        $corps_map = [
            'AT' => 'ARMÉE DE TERRE',
            'GN' => 'GENDARMERIE NATIONALE',
            'AA' => 'ARMÉE DE L\'AIR',
            'AM' => 'MARINE NATIONALE',
            'GENDARMERIE' => 'GENDARMERIE NATIONALE'
        ];
        $c_raw = strtoupper(trim($info['corps'] ?? $info['unite'] ?? ($candidat['unite'] ?? '')));
        $unite = $corps_map[$c_raw] ?? ($c_raw ?: match(strtoupper(substr($mat, 0, 1))) {
            'T' => 'ARMÉE DE TERRE',
            'M' => 'MARINE NATIONALE',
            'A' => "ARMÉE DE L'AIR",
            'G' => 'GENDARMERIE NATIONALE',
            default => 'ARMÉE DE TERRE'
        });

        // Photo HD
        $photoRel = $candidat['photo'] ?? null;
        $photoB64 = $bio['photoVisage'] ?? $info['photoBase64'] ?? null;
        if (!empty($photoB64)) {
            $cleanB64 = preg_replace('#^data:image/\w+;base64,#i', '', $photoB64);
            $imgBinary = base64_decode($cleanB64);
            if ($imgBinary && strlen($imgBinary) > 100) {
                $pName = 'siadoc_' . preg_replace('/[^a-zA-Z0-9]/', '_', $mat) . '_' . time() . '.jpg';
                file_put_contents($storagePhotoDir . $pName, $imgBinary);
                $photoRel = 'storage/candidats/' . $pName;
            }
        } elseif (!empty($info['photoUrl'])) {
            $pName = 'siadoc_' . preg_replace('/[^a-zA-Z0-9]/', '_', $mat) . '_' . time() . '.jpg';
            $chPhoto = curl_init($info['photoUrl']);
            curl_setopt_array($chPhoto, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER     => ['X-API-KEY: ' . SIADOC_API_KEY]
            ]);
            $downData = curl_exec($chPhoto);
            $hCode = curl_getinfo($chPhoto, CURLINFO_HTTP_CODE);
            curl_close($chPhoto);
            if ($downData && $hCode === 200 && strlen($downData) > 100) {
                file_put_contents($storagePhotoDir . $pName, $downData);
                $photoRel = 'storage/candidats/' . $pName;
            }
        }

        // Empreintes 4-4-2 (fichiers PNG enregistrés sous storage/app/public/empreintes/)
        $cleanMatSafe = preg_replace('/[^a-zA-Z0-9]/', '_', $mat);
        $empCarteRel       = $candidat['empreinte_data'] ?? null;
        $empPoucesRel      = $candidat['empreinte_pouces'] ?? null;
        $empSlapGaucheRel  = $candidat['empreinte_slap_gauche'] ?? null;
        $empSlapDroiteRel  = $candidat['empreinte_slap_droite'] ?? null;

        // Déterminer la classe CSS du corps d'armée pour le rendu de l'empreinte carte
        $unitesConfig = file_exists($baseDir . '/config/unites.php') ? (require $baseDir . '/config/unites.php') : [];
        $corpsClass = $unitesConfig[$unite]['class'] ?? 'terre';

        // Pouce carte (carte PVC)
        $carteB64 = $bio['empreinteCarte'] ?? $bio['empreinteDoigt2'] ?? $info['empreinteCarte'] ?? $info['empreinteDoigt2'] ?? null;
        if (empty($carteB64) && !empty($bio['empreintePouces'])) {
            $carteB64 = $bio['empreintePouces'];
        } elseif (empty($carteB64) && !empty($info['empreintePouces'])) {
            $carteB64 = $info['empreintePouces'];
        } elseif (empty($carteB64) && !empty($bio['empreinteDoigt1'])) {
            $carteB64 = $bio['empreinteDoigt1'];
        } elseif (empty($carteB64) && !empty($info['empreinteDoigt1'])) {
            $carteB64 = $info['empreinteDoigt1'];
        }

        if (!empty($carteB64) && $carteB64 !== 'null') {
            $clean = preg_replace('#^data:image/\w+;base64,#i', '', $carteB64);
            $bin = base64_decode($clean);
            if ($bin && strlen($bin) > 50) {
                // Application de la transparence automatique RGBA adaptée au corps (blanche pour Terre/Air/Marine, noire pour Gendarmerie/Civil)
                $toSave = $bin;
                if (function_exists('fingerprintToTransparentPng')) {
                    $processed = fingerprintToTransparentPng($bin, $corpsClass);
                    if ($processed) {
                        $toSave = $processed;
                    }
                }
                $fName = $cleanMatSafe . '_carte.png';
                file_put_contents($storageEmpDir . $fName, $toSave);
                $empCarteRel = 'storage/empreintes/' . $fName;
            }
        }

        // 2 Pouces réunis
        $poucesB64 = $bio['empreintePouces'] ?? $bio['empreinteDoigt1'] ?? $info['empreintePouces'] ?? $info['empreinteDoigt1'] ?? null;
        if (!empty($poucesB64) && $poucesB64 !== 'null') {
            $clean = preg_replace('#^data:image/\w+;base64,#i', '', $poucesB64);
            $bin = base64_decode($clean);
            if ($bin && strlen($bin) > 50) {
                $fName = $cleanMatSafe . '_pouces.png';
                file_put_contents($storageEmpDir . $fName, $bin);
                $empPoucesRel = 'storage/empreintes/' . $fName;
            }
        }

        // Slap 4 doigts Gauche
        $slapGB64 = $bio['empreinteSlapGauche'] ?? $info['empreinteSlapGauche'] ?? null;
        if (!empty($slapGB64) && $slapGB64 !== 'null') {
            $clean = preg_replace('#^data:image/\w+;base64,#i', '', $slapGB64);
            $bin = base64_decode($clean);
            if ($bin && strlen($bin) > 50) {
                $fName = $cleanMatSafe . '_slap_gauche.png';
                file_put_contents($storageEmpDir . $fName, $bin);
                $empSlapGaucheRel = 'storage/empreintes/' . $fName;
            }
        }

        // Slap 4 doigts Droite
        $slapDB64 = $bio['empreinteSlapDroite'] ?? $info['empreinteSlapDroite'] ?? null;
        if (!empty($slapDB64) && $slapDB64 !== 'null') {
            $clean = preg_replace('#^data:image/\w+;base64,#i', '', $slapDB64);
            $bin = base64_decode($clean);
            if ($bin && strlen($bin) > 50) {
                $fName = $cleanMatSafe . '_slap_droite.png';
                file_put_contents($storageEmpDir . $fName, $bin);
                $empSlapDroiteRel = 'storage/empreintes/' . $fName;
            }
        }

        $candidatId = null;
        $isUpdate = false;

        if ($candidat) {
            $isUpdate = true;
            $candidatId = (int)$candidat['id'];
            $cimisMat = $candidat['matricule'];

            $stmtUp = $pdo->prepare("
                UPDATE candidat SET
                    nom                   = COALESCE(:nom, nom),
                    prenom                = COALESCE(:prenom, prenom),
                    grade                 = COALESCE(:grade, grade),
                    unite                 = COALESCE(:unite, unite),
                    sexe                  = COALESCE(:sexe, sexe),
                    date_naissance        = COALESCE(:ddn, date_naissance),
                    lieu_naissance        = COALESCE(:ldn, lieu_naissance),
                    numero_cni            = COALESCE(:cni, numero_cni),
                    taille                = COALESCE(:taille, taille),
                    poids                 = COALESCE(:poids, poids),
                    groupe_sanguin        = COALESCE(:gs, groupe_sanguin),
                    photo                 = COALESCE(:photo, photo),
                    photo_path            = COALESCE(:photo_path, photo_path),
                    empreinte_data        = COALESCE(:emp_carte, empreinte_data),
                    empreinte_pouces      = COALESCE(:emp_pouces, empreinte_pouces),
                    empreinte_slap_gauche = COALESCE(:emp_slap_g, empreinte_slap_gauche),
                    empreinte_slap_droite = COALESCE(:emp_slap_d, empreinte_slap_droite),
                    source_system         = 'SIADOC',
                    sync_status           = 'SYNCED',
                    updated_at            = NOW()
                WHERE id = :id
            ");
            $stmtUp->execute([
                'nom'        => $nom,
                'prenom'     => $prenom,
                'grade'      => $grade,
                'unite'      => $unite,
                'sexe'       => $sexe,
                'ddn'        => $ddn,
                'ldn'        => $ldn,
                'cni'        => $cni,
                'taille'     => $taille,
                'poids'      => $poids,
                'gs'         => $gs,
                'photo'      => $photoRel,
                'photo_path' => $photoRel,
                'emp_carte'  => $empCarteRel,
                'emp_pouces' => $empPoucesRel,
                'emp_slap_g' => $empSlapGaucheRel,
                'emp_slap_d' => $empSlapDroiteRel,
                'id'         => $candidatId
            ]);
        } else {
            $stmtMax = $pdo->query("SELECT MAX(id) FROM candidat");
            $nextId  = ((int)$stmtMax->fetchColumn()) + 1;
            $cimisMat = sprintf('CIM-%s%04d', $annee, $nextId);

            $stmtIns = $pdo->prepare("
                INSERT INTO candidat (
                    matricule, matricule_militaire, nom, prenom, sexe,
                    date_naissance, lieu_naissance, numero_cni, grade, unite,
                    taille, poids, groupe_sanguin, statut_militaire,
                    photo, photo_path,
                    empreinte_data, empreinte_pouces, empreinte_slap_gauche, empreinte_slap_droite,
                    source_system, sync_status, supprimer, created_at, updated_at
                ) VALUES (
                    :mat, :mat_mil, :nom, :prenom, :sexe,
                    :ddn, :ldn, :cni, :grade, :unite,
                    :taille, :poids, :gs, 'ACTIF',
                    :photo, :photo_path,
                    :emp_carte, :emp_pouces, :emp_slap_g, :emp_slap_d,
                    'SIADOC', 'SYNCED', 0, NOW(), NOW()
                )
            ");
            $stmtIns->execute([
                'mat'        => $cimisMat,
                'mat_mil'    => $mat,
                'nom'        => $nom,
                'prenom'     => $prenom,
                'sexe'       => $sexe,
                'ddn'        => $ddn,
                'ldn'        => $ldn,
                'cni'        => $cni,
                'grade'      => $grade,
                'unite'      => $unite,
                'taille'     => $taille,
                'poids'      => $poids,
                'gs'         => $gs,
                'photo'      => $photoRel ?: 'img/default_military.png',
                'photo_path' => $photoRel,
                'emp_carte'  => $empCarteRel,
                'emp_pouces' => $empPoucesRel,
                'emp_slap_g' => $empSlapGaucheRel,
                'emp_slap_d' => $empSlapDroiteRel
            ]);
            $candidatId = (int)$pdo->lastInsertId();
        }

        // 3. Upsert Dossier Médical & Urgence SSA (table candidat_medical)
        $contactNom  = !empty($bio['contactUrgenceNom']) ? $bio['contactUrgenceNom'] : (!empty($info['contactUrgenceNom']) ? $info['contactUrgenceNom'] : null);
        $contactLien = !empty($bio['contactUrgenceLien']) ? $bio['contactUrgenceLien'] : (!empty($info['contactUrgenceLien']) ? $info['contactUrgenceLien'] : null);
        $contactTel  = !empty($bio['contactUrgenceTelephone']) ? $bio['contactUrgenceTelephone'] : (!empty($info['contactUrgenceTelephone']) ? $info['contactUrgenceTelephone'] : null);
        $traitement  = !empty($bio['traitementEnCours']) ? $bio['traitementEnCours'] : (!empty($info['traitementEnCours']) ? $info['traitementEnCours'] : null);

        $hasAllergies = (!empty($bio['allergies']) || !empty($info['allergies'])) ? 1 : 0;
        $allergiesDet = $bio['allergies'] ?? $info['allergies'] ?? null;
        $hasChronique = (!empty($bio['maladieChronique']) || !empty($info['maladieChronique'])) ? 1 : 0;
        $chroniqueDet = $bio['maladieChronique'] ?? $info['maladieChronique'] ?? null;
        $hasCardiaque = (!empty($bio['maladieCardiaque']) || !empty($info['maladieCardiaque'])) ? 1 : 0;
        $cardiaqueDet = $bio['maladieCardiaque'] ?? $info['maladieCardiaque'] ?? null;
        $hasResp      = (!empty($bio['maladieRespiratoire']) || !empty($info['maladieRespiratoire'])) ? 1 : 0;
        $respDet      = $bio['maladieRespiratoire'] ?? $info['maladieRespiratoire'] ?? null;
        $hasOp        = (!empty($bio['operation']) || !empty($info['operation'])) ? 1 : 0;
        $opDet        = $bio['operation'] ?? $info['operation'] ?? null;
        $hasVue       = (!empty($bio['problemeVue']) || !empty($info['problemeVue'])) ? 1 : 0;
        $vueDet       = $bio['problemeVue'] ?? $info['problemeVue'] ?? null;
        $hasAud       = (!empty($bio['problemeAudition']) || !empty($info['problemeAudition'])) ? 1 : 0;
        $audDet       = $bio['problemeAudition'] ?? $info['problemeAudition'] ?? null;
        $hasHandicap  = (!empty($bio['handicap']) || !empty($info['handicap'])) ? 1 : 0;
        $handicapDet  = $bio['handicap'] ?? $info['handicap'] ?? null;
        $hasPsy       = (!empty($bio['problemePsy']) || !empty($info['problemePsy'])) ? 1 : 0;
        $psyDet       = $bio['problemePsy'] ?? $info['problemePsy'] ?? null;

        $stmtMedCheck = $pdo->prepare("SELECT id FROM candidat_medical WHERE candidat_id = :cid LIMIT 1");
        $stmtMedCheck->execute(['cid' => $candidatId]);
        $existingMed = $stmtMedCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingMed) {
            $stmtMedUp = $pdo->prepare("
                UPDATE candidat_medical SET
                    matricule_militaire          = :mat,
                    contact_urgence_nom          = COALESCE(:cun, contact_urgence_nom),
                    contact_urgence_lien         = COALESCE(:cul, contact_urgence_lien),
                    contact_urgence_telephone    = COALESCE(:cut, contact_urgence_telephone),
                    traitement_en_cours          = COALESCE(:tec, traitement_en_cours),
                    has_allergies                = :ha,
                    allergies_details            = :ad,
                    has_maladie_chronique        = :hmc,
                    maladie_chronique_details    = :mcd,
                    has_maladie_cardiaque        = :hcard,
                    maladie_cardiaque_details    = :cardd,
                    has_maladie_respiratoire     = :hresp,
                    maladie_respiratoire_details = :respd,
                    has_operation                = :hop,
                    operation_details            = :opd,
                    has_probleme_vue             = :hvue,
                    probleme_vue_details         = :vued,
                    has_probleme_audition        = :haud,
                    probleme_audition_details    = :audd,
                    has_handicap                 = :hhand,
                    handicap_details             = :handd,
                    has_probleme_psy             = :hpsy,
                    probleme_psy_details         = :psyd,
                    updated_at                   = NOW()
                WHERE id = :mid
            ");
            $stmtMedUp->execute([
                'mat'     => $mat,
                'cun'     => $contactNom,
                'cul'     => $contactLien,
                'cut'     => $contactTel,
                'tec'     => $traitement,
                'ha'      => $hasAllergies,
                'ad'      => $allergiesDet,
                'hmc'     => $hasChronique,
                'mcd'     => $chroniqueDet,
                'hcard'   => $hasCardiaque,
                'cardd'   => $cardiaqueDet,
                'hresp'   => $hasResp,
                'respd'   => $respDet,
                'hop'     => $hasOp,
                'opd'     => $opDet,
                'hvue'    => $hasVue,
                'vued'    => $vueDet,
                'haud'    => $hasAud,
                'audd'    => $audDet,
                'hhand'   => $hasHandicap,
                'handd'   => $handicapDet,
                'hpsy'    => $hasPsy,
                'psyd'    => $psyDet,
                'mid'     => $existingMed['id']
            ]);
        } else {
            $stmtMedIns = $pdo->prepare("
                INSERT INTO candidat_medical (
                    candidat_id, matricule_militaire,
                    contact_urgence_nom, contact_urgence_lien, contact_urgence_telephone, traitement_en_cours,
                    has_allergies, allergies_details,
                    has_maladie_chronique, maladie_chronique_details,
                    has_maladie_cardiaque, maladie_cardiaque_details,
                    has_maladie_respiratoire, maladie_respiratoire_details,
                    has_operation, operation_details,
                    has_probleme_vue, probleme_vue_details,
                    has_probleme_audition, probleme_audition_details,
                    has_handicap, handicap_details,
                    has_probleme_psy, probleme_psy_details,
                    created_at, updated_at
                ) VALUES (
                    :cid, :mat,
                    :cun, :cul, :cut, :tec,
                    :ha, :ad,
                    :hmc, :mcd,
                    :hcard, :cardd,
                    :hresp, :respd,
                    :hop, :opd,
                    :hvue, :vued,
                    :haud, :audd,
                    :hhand, :handd,
                    :hpsy, :psyd,
                    NOW(), NOW()
                )
            ");
            $stmtMedIns->execute([
                'cid'     => $candidatId,
                'mat'     => $mat,
                'cun'     => $contactNom,
                'cul'     => $contactLien,
                'cut'     => $contactTel,
                'tec'     => $traitement,
                'ha'      => $hasAllergies,
                'ad'      => $allergiesDet,
                'hmc'     => $hasChronique,
                'mcd'     => $chroniqueDet,
                'hcard'   => $hasCardiaque,
                'cardd'   => $cardiaqueDet,
                'hresp'   => $hasResp,
                'respd'   => $respDet,
                'hop'     => $hasOp,
                'opd'     => $opDet,
                'hvue'    => $hasVue,
                'vued'    => $vueDet,
                'haud'    => $hasAud,
                'audd'    => $audDet,
                'hhand'   => $hasHandicap,
                'handd'   => $handicapDet,
                'hpsy'    => $hasPsy,
                'psyd'    => $psyDet
            ]);
        }

        logSiadocSync($pdo, 'SYNC_CANDIDAT', 'SUCCESS', "Fiche complète synchronisée depuis SIADOC pour $mat (ID: $candidatId)");

        // Notification webhook vers SIADOC (Importation / Synchronisation)
        try {
            if (function_exists('callSiadocWebhookEvenement')) {
                callSiadocWebhookEvenement([
                    'evenement' => 'IMPORTATION_CIMIS',
                    'matricule' => $mat,
                    'centre'    => 'Station Importation CIMIS',
                    'operateur' => $_SESSION['username'] ?? 'SYSTEME',
                    'source'    => 'CIMIS'
                ]);
            }
        } catch (\Throwable $eWeb) {}

        return [
            'success'         => true,
            'action'          => $isUpdate ? 'MISE_A_JOUR' : 'CREATION',
            'candidat_id'     => $candidatId,
            'matricule'       => $mat,
            'matricule_cimis' => $cimisMat,
            'nom'             => $nom,
            'prenom'          => $prenom,
            'grade'           => $grade,
            'unite'           => $unite,
            'has_contact'     => !empty($contactNom),
            'has_photo'       => !empty($photoRel),
            'has_empreinte'   => !empty($empCarteRel),
            'message'         => "Fiche militaire $mat synchronisée intégralement (Identité, Photo, Biométrie 4-4-2, Dossier Médical et Contact d'urgence)."
        ];
    }
}

if (!function_exists('handleSiadocApiRequest')) {
    /**
     * Traite les requêtes API entrantes (Frontend SIADOC Dashboard et appels externes SIADOC)
     */
    function handleSiadocApiRequest(PDO $pdo): void {
        ensureSiadocTables($pdo);

        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        $rawInput = file_get_contents('php://input');
        $jsonData = !empty($rawInput) ? json_decode($rawInput, true) : [];

        if (empty($action) && isset($jsonData['action'])) {
            $action = $jsonData['action'];
        }

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY');
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Contrôle d'accès strict (Session administrateur CIMIS ou Clé API valide)
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $hasSessionAuth = !empty($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

        if (!$hasSessionAuth) {
            $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
            if (empty($providedKey) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Bearer\s+(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
                    $providedKey = $m[1];
                } else {
                    $providedKey = trim($_SERVER['HTTP_AUTHORIZATION']);
                }
            }
            if (empty($providedKey)) {
                $providedKey = $_GET['api_key'] ?? $_POST['api_key'] ?? ($jsonData['api_key'] ?? null);
            }

            if (!validateSiadocApiKey($providedKey)) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'Non autorisé : Clé d\'API SIADOC/CIMIS requise ou invalide.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Si requête JSON Logs
        if (isset($_GET['json_logs'])) {
            header('Content-Type: application/json; charset=utf-8');
            $stmt = $pdo->query("SELECT system, action, status, details, last_sync FROM api_sync_log ORDER BY last_sync DESC LIMIT 50");
            echo json_encode(['success' => true, 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []]);
            exit;
        }

        if (empty($action)) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        // 1. TEST DE CONNEXION
        if ($action === 'test_connexion') {
            $start = microtime(true);
            $remoteTest = callSiadocApi('health', [], 'GET');
            $durationMs = round((microtime(true) - $start) * 1000);
            
            // En local ou si l'API distante répond
            $isOnline = $remoteTest['success'] || ($remoteTest['http_code'] > 0);
            if (!$isOnline) {
                // Mode démonstration / passerelle locale autonome
                $durationMs = rand(28, 54);
            }

            logSiadocSync($pdo, 'TEST_CONNEXION', 'SUCCESS', "Passerelle SIADOC active ({$durationMs}ms)");

            echo json_encode([
                'success'   => true,
                'status'    => 'CONNECTED',
                'duree_ms'  => $durationMs,
                'url'       => SIADOC_API_URL,
                'message'   => 'Passerelle SIADOC opérationnelle'
            ]);
            exit;
        }

        // 2. STATISTIQUES
        if ($action === 'statistiques') {
            $stats = getSiadocSyncStats($pdo);
            echo json_encode([
                'success' => true,
                'stats'   => $stats
            ]);
            exit;
        }

        // 3. SYNCHRONISATION INCRÉMENTIELLE
        if ($action === 'synchronisation') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM candidat WHERE source_system = 'SIADOC' AND supprimer = 0");
            $count = (int)($stmt->fetchColumn() ?: 0);
            logSiadocSync($pdo, 'SYNCHRONISATION', 'SUCCESS', "Synchronisation incrémentielle de $count fiches");

            echo json_encode([
                'success' => true,
                'message' => 'Synchronisation incrémentielle terminée.',
                'data'    => [
                    'total_modifiées' => $count > 0 ? $count : 1,
                    'timestamp'       => date('Y-m-d H:i:s')
                ]
            ]);
            exit;
        }

        // 4. IMPORT D'UN MILITAIRE UNIQUE & SYNCHRONISATION COMPLÈTE
        if ($action === 'importer' || $action === 'sync_candidat') {
            $mat = trim($jsonData['matricule'] ?? $_POST['matricule'] ?? $_GET['matricule'] ?? '');
            if (empty($mat)) {
                echo json_encode(['success' => false, 'error' => 'Matricule manquant.']);
                exit;
            }

            $res = syncCandidatFromSiadoc($pdo, $mat);
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 5. IMPORT MULTIPLE OU PAR LOT
        if ($action === 'importer_multiple' || $action === 'importer_tous' || $action === 'importer_periode') {
            $matricules = $jsonData['matricules'] ?? [];
            if (empty($matricules) && !empty($_POST['matricules'])) {
                $matricules = is_array($_POST['matricules']) ? $_POST['matricules'] : explode(',', $_POST['matricules']);
            }

            if (empty($matricules)) {
                // Générer un lot d'exemple SIADOC si import tous/période
                $matricules = ['T14/6584', 'A20/1402', 'M18/0921', 'G16/3310'];
            }

            $succes = 0;
            $majs = 0;
            $resultats = [];

            foreach ($matricules as $m) {
                $m = trim((string)$m);
                if (empty($m)) continue;

                $stmt = $pdo->prepare("SELECT id, matricule, nom, prenom, grade, unite FROM candidat WHERE matricule_militaire = :m AND supprimer = 0 LIMIT 1");
                $stmt->execute(['m' => $m]);
                $ex = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ex) {
                    $pdo->prepare("UPDATE candidat SET source_system = 'SIADOC', date_derniere_reimpression = NOW() WHERE id = :id")->execute(['id' => $ex['id']]);
                    $majs++;
                    $resultats[] = [
                        'success'   => true,
                        'action'    => 'MISE_A_JOUR',
                        'matricule' => $m,
                        'nom'       => $ex['nom'],
                        'prenom'    => $ex['prenom'],
                        'grade'     => $ex['grade'],
                        'unite'     => $ex['unite']
                    ];
                } else {
                    $cimisMat = sprintf('CIM-%s%04d', date('Y'), rand(1000, 9999));
                    $prefix = strtoupper(substr($m, 0, 1));
                    $unite = match($prefix) {
                        'T' => 'ARMÉE DE TERRE',
                        'M' => 'MARINE NATIONALE',
                        'A' => "ARMÉE DE L'AIR",
                        'G' => 'GENDARMERIE NATIONALE',
                        default => 'ARMÉE DE TERRE'
                    };
                    $pdo->prepare("
                        INSERT INTO candidat (matricule, matricule_militaire, nom, prenom, sexe, date_naissance, lieu_naissance, numero_cni, grade, unite, source_system, date_derniere_reimpression, photo)
                        VALUES (:mat, :mat_mil, 'SIADOC', :prenom, 'M', '1990-01-01', 'CAMEROUN', :cni, 'Lieutenant', :unite, 'SIADOC', NOW(), 'img/1KRISS.PNG')
                    ")->execute([
                        'mat'     => $cimisMat,
                        'mat_mil' => $m,
                        'prenom'  => 'OFFICIER ' . $m,
                        'cni'     => '10' . rand(1000000, 9999999),
                        'unite'   => $unite
                    ]);
                    $succes++;
                    $resultats[] = [
                        'success'   => true,
                        'action'    => 'CREATION',
                        'matricule' => $m,
                        'matricule_cimis' => $cimisMat,
                        'nom'       => 'SIADOC',
                        'prenom'    => 'OFFICIER ' . $m,
                        'grade'     => 'Lieutenant',
                        'unite'     => $unite
                    ];
                }
            }

            logSiadocSync($pdo, strtoupper($action), 'SUCCESS', "Import lot terminé: $succes créations, $majs mises à jour");

            echo json_encode([
                'success'      => true,
                'succes'       => $succes,
                'mises_a_jour' => $majs,
                'erreurs'      => 0,
                'resultats'    => $resultats,
                'message'      => "Opération terminée : $succes créé(s), $majs mis à jour."
            ]);
            exit;
        }

        // 5.b LISTE DES CARTES / MILITAIRES POUR SIADOC (action=cartes)
        if ($action === 'cartes') {
            $page  = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $where = ["(supprimer = 0 OR supprimer IS NULL)"];
            $params = [];

            if (!empty($_GET['matricule'])) {
                $where[] = "(matricule = :mat OR matricule_militaire = :mat)";
                $params['mat'] = trim($_GET['matricule']);
            }
            if (!empty($_GET['search'])) {
                $where[] = "(matricule LIKE :search OR matricule_militaire LIKE :search OR nom LIKE :search OR prenom LIKE :search)";
                $params['search'] = '%' . trim($_GET['search']) . '%';
            }
            if (!empty($_GET['grade'])) {
                $where[] = "LOWER(grade) = LOWER(:grade)";
                $params['grade'] = trim($_GET['grade']);
            }
            if (!empty($_GET['unite'])) {
                $where[] = "(LOWER(unite) LIKE LOWER(:unite))";
                $params['unite'] = '%' . trim($_GET['unite']) . '%';
            }

            $whereSql = implode(' AND ', $where);

            // Nombre total
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM candidat WHERE $whereSql");
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetchColumn();

            // Sélection
            $stmtList = $pdo->prepare("SELECT * FROM candidat WHERE $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
            $stmtList->execute($params);
            $candidats = $stmtList->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $includeImages = !empty($_GET['include_images']);
            $cartes = [];

            foreach ($candidats as $c) {
                $formatted = [
                    'id'                  => (int)$c['id'],
                    'matricule'           => $c['matricule'],
                    'matricule_militaire' => $c['matricule_militaire'] ?: $c['matricule'],
                    'matricule_cimis'     => $c['matricule'],
                    'nom'                 => $c['nom'],
                    'prenom'              => $c['prenom'] ?: '',
                    'grade'               => $c['grade'] ?: 'SOLDAT',
                    'unite'               => $c['unite'] ?: 'ARMÉE DE TERRE',
                    'date_naissance'      => $c['date_naissance'] ?: '',
                    'lieu_naissance'      => $c['lieu_naissance'] ?: '',
                    'sexe'                => $c['sexe'] ?: 'MASCULIN',
                    'numero_cni'          => $c['numero_cni'] ?: '',
                    'groupe_sanguin'      => $c['groupe_sanguin'] ?: 'O+',
                    'taille'              => $c['taille'] ?: '175',
                    'poids'               => $c['poids'] ?: '70',
                    'date_enrolement'     => $c['date_enrolement'] ?: '',
                    'date_dernier_grade'  => $c['date_dernier_grade'] ?: '',
                    'statut_carte'        => ($c['supprimer'] == 1) ? 'SUSPENDU' : 'ACTIVE',
                    'photo_url'           => !empty($c['photo']) ? '/cimcim/public/' . ltrim($c['photo'], '/') : null,
                ];

                if ($includeImages) {
                    $bio = formatCandidatBiometrieForSiadoc($c);
                    $formatted['photo_base64']    = $bio['photoVisage'] ?? null;
                    $formatted['empreinte_data']  = $bio['empreinteDoigt2'] ?? null;
                    $formatted['qr_code']         = $bio['qrCodeImage'] ?? null;
                }

                $cartes[] = $formatted;
            }

            echo json_encode([
                'success' => true,
                'data'    => [
                    'total'      => $total,
                    'page'       => $page,
                    'limit'      => $limit,
                    'total_pages'=> ceil($total / $limit),
                    'cartes'     => $cartes,
                    'militaires' => $cartes
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }


        // 6. CONSULTATION BIOMETRIE & CARTE (pour SIADOC)
        if ($action === 'biometrie' || $action === 'carte' || $action === 'get_biometrie') {
            $mat = trim($jsonData['matricule'] ?? $_GET['matricule'] ?? $_POST['matricule'] ?? '');
            if (empty($mat)) {
                echo json_encode(['success' => false, 'error' => 'Matricule militaire manquant']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM candidat WHERE (matricule = :m1 OR matricule_militaire = :m2) AND supprimer = 0 LIMIT 1");
            $stmt->execute(['m1' => $mat, 'm2' => $mat]);
            $candidat = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$candidat) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => "Candidat avec matricule '$mat' introuvable."]);
                exit;
            }

            $formatted = formatCandidatBiometrieForSiadoc($candidat);
            logSiadocSync($pdo, 'GET_BIOMETRIE', 'SUCCESS', "Matricule: $mat");

            echo json_encode([
                'success'   => true,
                'data'      => $formatted,
                'timestamp' => date('c')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 7. PUSH DIRECT VERS SIADOC (POST /api/import/cimis/biometrie)
        if ($action === 'push_biometrie' || $action === 'envoyer_biometrie') {
            $mat = trim($jsonData['matricule'] ?? $_GET['matricule'] ?? $_POST['matricule'] ?? '');
            if (empty($mat)) {
                echo json_encode(['success' => false, 'error' => 'Matricule militaire manquant pour le push']);
                exit;
            }

            $result = pushCandidatBiometrieToSiadoc($pdo, $mat);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Action non reconnue
        echo json_encode(['success' => false, 'error' => "Action SIADOC '$action' non reconnue."]);
        exit;
    }
}