<?php
/**
 * SIADOC SYNC CRON v2.0 â€” Synchronisation automatique SIADOC â†’ CIMIS
 *
 * Usage :
 *   php siadoc_sync_cron.php                  # Sync incrÃ©mentielle (par dÃ©faut)
 *   php siadoc_sync_cron.php --tous           # Import de tous les militaires
 *   php siadoc_sync_cron.php --dry-run        # Simulation (aucune Ã©criture)
 *   php siadoc_sync_cron.php --limit=100      # Limiter Ã  N militaires
 *   php siadoc_sync_cron.php --verbose        # Afficher plus de dÃ©tails
 *
 * Exemple CRON (toutes les 30 minutes) :
 *   * /30 * * * * php /chemin/vers/cimcim/scripts/siadoc_sync_cron.php >> /chemin/logs/siadoc_sync.log 2>&1
 *
 * Logique BDD :
 *   supprimer = 0  â†’ actif (pas en corbeille)
 *   suspendus = 0  â†’ carte visible / imprimable
 */

// â”€â”€â”€ CONFIGURATION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

define('CIMCIM_ROOT', dirname(__DIR__));
define('SIADOC_CRON_VERSION', '2.0.0');

// Charger la configuration CIMIS
require_once CIMCIM_ROOT . '/backend/config.php';

if (!defined('SIADOC_API_URL')) {
    define('SIADOC_API_URL', 'https://siadoc.onrender.com');
}

// RÃ©pertoire des logs
$log_dir = CIMCIM_ROOT . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/siadoc_sync.log';

// â”€â”€â”€ ARGUMENTS CLI â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

$args     = $argv ?? [];
$dry_run  = in_array('--dry-run', $args);
$tous     = in_array('--tous',    $args);
$verbose  = in_array('--verbose', $args);
$limit    = 500;

foreach ($args as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int)$m[1];
    }
}

// â”€â”€â”€ LOGGING â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function cron_log(string $level, string $message): void {
    global $log_file, $verbose;
    $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
    file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
    if ($verbose || $level === 'error' || $level === 'info') {
        echo $line;
    }
}

function cron_db_log(string $action, string $status, string $details): void {
    global $pdo;
    try {
        $pdo->prepare("
            INSERT INTO api_sync_log (system, action, status, details, last_sync)
            VALUES ('SIADOC_CRON', ?, ?, ?, NOW())
        ")->execute([$action, $status, $details]);
    } catch (Exception $e) {
        cron_log('warn', 'Impossible de logger en BDD: ' . $e->getMessage());
    }
}

// â”€â”€â”€ VERROU (Ã©vite les exÃ©cutions simultanÃ©es) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

$lock_file = sys_get_temp_dir() . '/cimis_siadoc_sync.lock';

function acquireLock(string $lock_file): bool {
    if (file_exists($lock_file)) {
        $pid = (int)file_get_contents($lock_file);
        // VÃ©rifier si le processus est encore actif (Unix seulement)
        if (function_exists('posix_kill') && $pid > 0 && posix_kill($pid, 0)) {
            return false; // Processus actif â€” ne pas lancer
        }
        // Processus mort ou Windows â€” supprimer le verrou
        unlink($lock_file);
    }
    file_put_contents($lock_file, getmypid());
    return true;
}

function releaseLock(string $lock_file): void {
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}

// â”€â”€â”€ APPEL API SIADOC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function siadoc_api(string $endpoint, array $params = [], string $method = 'GET', int $max_tries = 3): array {
    $url = rtrim(SIADOC_API_URL, '/') . '/' . ltrim($endpoint, '/');
    $last_error = '';

    for ($i = 1; $i <= $max_tries; $i++) {
        try {
            $ch = curl_init();
            $opts = [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_HTTPHEADER      => ['X-API-KEY: ' . SIADOC_API_KEY, 'Content-Type: application/json'],
                CURLOPT_SSL_VERIFYPEER  => true,
                CURLOPT_TIMEOUT         => 60,
                CURLOPT_CONNECTTIMEOUT  => 15,
                CURLOPT_FOLLOWLOCATION  => true
            ];
            if ($method === 'GET') {
                $opts[CURLOPT_URL] = !empty($params) ? $url . '?' . http_build_query($params) : $url;
            } else {
                $opts[CURLOPT_URL]        = $url;
                $opts[CURLOPT_POST]       = true;
                $opts[CURLOPT_POSTFIELDS] = json_encode($params);
            }
            curl_setopt_array($ch, $opts);
            $resp   = curl_exec($ch);
            $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr   = curl_error($ch);
            curl_close($ch);

            if ($cerr) throw new Exception($cerr);
            if ($code >= 200 && $code < 300) {
                return ['ok' => true, 'code' => $code, 'data' => json_decode($resp, true), 'attempts' => $i];
            }
            if ($code >= 400 && $code < 500) {
                return ['ok' => false, 'code' => $code, 'data' => json_decode($resp, true), 'attempts' => $i];
            }
            $last_error = "HTTP $code";
        } catch (Exception $e) {
            $last_error = $e->getMessage();
        }
        if ($i < $max_tries) sleep(pow(2, $i - 1));
    }

    return ['ok' => false, 'code' => 0, 'data' => null, 'error' => $last_error, 'attempts' => $max_tries];
}

// â”€â”€â”€ IMPORT D'UN MILITAIRE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function cron_importer(array $raw, bool $dry_run): array {
    global $pdo;

    // Normaliser
    $sexe_raw = strtoupper($raw['sexe'] ?? $raw['genre'] ?? 'M');
    $sexe = match(true) {
        in_array($sexe_raw, ['M', 'MASCULIN', 'MALE', 'H', 'HOMME']) => 'MASCULIN',
        in_array($sexe_raw, ['F', 'FEMININ', 'FEMALE', 'FEMME'])     => 'FEMININ',
        default => 'MASCULIN'
    };

    $mat   = $raw['matricule'] ?? $raw['matricule_militaire'] ?? '';
    $nom   = strtoupper(trim($raw['nom'] ?? ''));
    $prenom = ucwords(strtolower(trim($raw['prenom'] ?? $raw['prenoms'] ?? '')));
    $grade  = strtoupper($raw['grade'] ?? '');
    $unite  = $raw['corps'] ?? $raw['unite'] ?? $raw['affectation'] ?? '';

    $dn = null;
    foreach (['dateNaissance', 'date_naissance', 'naissance'] as $k) {
        if (!empty($raw[$k])) { $dn = date('Y-m-d', strtotime($raw[$k])); break; }
    }

    if (empty($mat)) {
        return ['ok' => false, 'action' => 'SKIP', 'reason' => 'Matricule vide'];
    }

    if ($dry_run) {
        return ['ok' => true, 'action' => 'DRY_RUN', 'matricule' => $mat, 'nom' => $nom, 'prenom' => $prenom];
    }

    // Doublon ?
    $stmt = $pdo->prepare("SELECT id, matricule FROM candidat WHERE matricule_militaire = ?");
    $stmt->execute([$mat]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Mise Ã  jour
        $pdo->prepare("
            UPDATE candidat SET
                nom = ?, prenom = ?, date_naissance = ?, sexe = ?,
                grade = ?, unite = ?,
                source_system = 'SIADOC',
                siadoc_sync_date = NOW(), siadoc_sync_status = 'SYNCED',
                date_modification = NOW()
            WHERE matricule_militaire = ?
        ")->execute([$nom, $prenom, $dn, $sexe, $grade, $unite, $mat]);

        return ['ok' => true, 'action' => 'UPDATE', 'matricule' => $mat, 'id' => $existing['id']];
    }

    // Nouveau â€” gÃ©nÃ©rer matricule CIMIS
    $prefix = 'CIM-';
    $year   = date('Y');
    $pdo->exec("SELECT GET_LOCK('cimis_matricule_lock', 5)");
    try {
        $s = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(matricule, 9) AS UNSIGNED)), 0) + 1 FROM candidat WHERE matricule LIKE ?");
        $s->execute([$prefix . $year . '%']);
        $seq = str_pad((int)$s->fetchColumn(), 4, '0', STR_PAD_LEFT);
    } finally {
        $pdo->exec("SELECT RELEASE_LOCK('cimis_matricule_lock')");
    }
    $mat_cimis = $prefix . $year . $seq;

    // QR code simple
    $qr_dir  = CIMCIM_ROOT . '/img/qrcodes/';
    $qr_file = $qr_dir . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $mat) . '_qr.png';
    if (!is_dir($qr_dir)) mkdir($qr_dir, 0777, true);

    $qr_path_db = 'img/qrcodes/' . basename($qr_file);
    $phpqr       = CIMCIM_ROOT . '/backend/phpqrcode/qrlib.php';
    if (file_exists($phpqr)) {
        require_once $phpqr;
        try {
            QRcode::png('https://cimis.ct.ws/verify/' . urlencode($mat), $qr_file, QR_ECLEVEL_M, 5, 2);
        } catch (Exception $e) { $qr_path_db = null; }
    } else {
        $qr_path_db = null;
    }

    $pdo->prepare("
        INSERT INTO candidat (
            matricule, matricule_militaire, nom, prenom,
            date_naissance, sexe, grade, unite,
            code_qr, source_system,
            supprimer, suspendus,
            siadoc_sync_date, siadoc_sync_status,
            date_modification
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'SIADOC', 1, 0, NOW(), 'SYNCED', NOW())
    ")->execute([$mat_cimis, $mat, $nom, $prenom, $dn, $sexe, $grade, $unite, $qr_path_db]);

    $id = (int)$pdo->lastInsertId();
    return ['ok' => true, 'action' => 'CREATE', 'matricule' => $mat, 'matricule_cimis' => $mat_cimis, 'id' => $id];
}

// â”€â”€â”€ RÃ‰CUPÃ‰RATION DE LA DERNIÃˆRE SYNC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function getLastSyncDate(): ?string {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT last_sync FROM api_sync_log
            WHERE system = 'SIADOC_CRON' AND status IN ('SUCCESS', 'PARTIAL')
            ORDER BY last_sync DESC LIMIT 1
        ");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $row['last_sync'] : null;
    } catch (Exception $e) {
        return null;
    }
}

// â”€â”€â”€ PROGRAMME PRINCIPAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

cron_log('info', 'â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•');
cron_log('info', "CIMIS SIADOC CRON v" . SIADOC_CRON_VERSION . " â€” " . date('Y-m-d H:i:s'));
cron_log('info', 'URL SIADOC : ' . SIADOC_API_URL);
cron_log('info', 'Mode       : ' . ($dry_run ? 'DRY-RUN (simulation)' : ($tous ? 'IMPORT COMPLET' : 'INCRÃ‰MENTIEL')));
cron_log('info', 'Limite     : ' . $limit);
cron_log('info', 'â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•');

// AcquÃ©rir le verrou
if (!acquireLock($lock_file)) {
    cron_log('warn', 'Une autre instance est dÃ©jÃ  en cours d\'exÃ©cution. Abandon.');
    exit(0);
}

register_shutdown_function('releaseLock', $lock_file);

$start_time = microtime(true);
$total      = 0;
$created    = 0;
$updated    = 0;
$errors     = 0;
$skipped    = 0;

try {
    // â”€â”€ 1. DÃ©terminer l'endpoint SIADOC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    if ($tous) {
        cron_log('info', 'Import complet : récupération de tous les militaires SIADOC...');
        $endpoint = '/api/export/militaire/info/all';
        $params   = [];
    } else {
        $last_sync = getLastSyncDate();
        cron_log('info', 'Sync incrémentielle depuis : ' . ($last_sync ?? 'début (première sync)'));
        $endpoint = '/api/export/militaire/info/all';
        $params   = [];
    }

    // ── 2. Appeler l'API SIADOC ───────────────────────────────────────────────

    cron_log('info', "Appel SIADOC : $endpoint");
    $result = siadoc_api($endpoint, $params);

    if (!$result['ok']) {
        cron_log('warn', 'SIADOC retourne une erreur (HTTP ' . $result['code'] . '): ' . ($result['error'] ?? json_encode($result['data'])));
        cron_log('info', 'Tentative avec endpoint alternatif : /api/export/militaire/info');
        $result = siadoc_api('/api/export/militaire/info', []);
    }

    if (!$result['ok']) {
        $msg = 'Impossible de contacter SIADOC aprÃ¨s ' . $result['attempts'] . ' tentatives';
        cron_log('error', $msg . ': ' . ($result['error'] ?? 'HTTP ' . $result['code']));
        cron_db_log('SYNC_CRON', 'ERROR', json_encode(['message' => $msg, 'http_code' => $result['code']]));
        exit(1);
    }

    cron_log('info', 'SIADOC rÃ©pondu en ' . $result['attempts'] . ' tentative(s) â€” HTTP ' . $result['code']);

    // â”€â”€ 3. Extraire la liste des militaires â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    $data       = $result['data'];
    $militaires = $data['militaires'] ?? $data['data'] ?? $data['results'] ?? [];

    if (!is_array($militaires)) {
        $militaires = is_array($data) && !isset($data['militaires']) ? $data : [$data];
    }

    // Filtrer les entrÃ©es nulles
    $militaires = array_filter($militaires, fn($m) => is_array($m) && !empty($m));
    $total      = count($militaires);

    cron_log('info', "$total militaire(s) reÃ§u(s) depuis SIADOC");

    if ($total === 0) {
        cron_log('info', 'Aucun militaire Ã  synchroniser.');
        cron_db_log('SYNC_CRON', 'SUCCESS', json_encode(['message' => 'Aucune mise Ã  jour', 'total' => 0]));
        exit(0);
    }

    // â”€â”€ 4. Importer chaque militaire â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    $batch_size = 50;
    $processed  = 0;

    foreach (array_chunk($militaires, $batch_size) as $batch_num => $batch) {
        foreach ($batch as $m) {
            try {
                $r = cron_importer($m, $dry_run);

                if ($r['ok']) {
                    switch ($r['action']) {
                        case 'CREATE':   $created++; cron_log('debug', "CRÃ‰Ã‰  : {$r['matricule']} â†’ {$r['matricule_cimis']}"); break;
                        case 'UPDATE':   $updated++; cron_log('debug', "MÃ€J   : {$r['matricule']}"); break;
                        case 'DRY_RUN':  $created++; cron_log('debug', "[DRY] : {$r['matricule']} {$r['nom']} {$r['prenom']}"); break;
                        case 'SKIP':     $skipped++; cron_log('debug', "SKIP  : " . ($r['reason'] ?? '')); break;
                    }
                } else {
                    $errors++;
                    $mat = $m['matricule'] ?? $m['matricule_militaire'] ?? '?';
                    cron_log('warn', "ERREUR : $mat â€” " . ($r['reason'] ?? 'Inconnu'));
                }
            } catch (Exception $e) {
                $errors++;
                $mat = $m['matricule'] ?? $m['matricule_militaire'] ?? '?';
                cron_log('error', "EXCEPTION sur $mat : " . $e->getMessage());
            }
            $processed++;
        }

        // Rapport intermÃ©diaire tous les 50
        cron_log('info', "Progression : $processed/$total â€” crÃ©Ã©s=$created, mises_Ã _jour=$updated, erreurs=$errors");
    }

    // â”€â”€ 5. Rapport final â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    $duration = round(microtime(true) - $start_time, 2);
    $status   = $errors === 0 ? 'SUCCESS' : ($created + $updated > 0 ? 'PARTIAL' : 'ERROR');

    $summary = [
        'total'      => $total,
        'created'    => $created,
        'updated'    => $updated,
        'errors'     => $errors,
        'skipped'    => $skipped,
        'duration_s' => $duration,
        'dry_run'    => $dry_run,
        'mode'       => $tous ? 'COMPLET' : 'INCREMENTAL'
    ];

    cron_log('info', 'â”€â”€â”€ RAPPORT FINAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€');
    cron_log('info', "Statut      : $status");
    cron_log('info', "Total       : $total");
    cron_log('info', "CrÃ©Ã©s       : $created");
    cron_log('info', "Mis Ã  jour  : $updated");
    cron_log('info', "Erreurs     : $errors");
    cron_log('info', "IgnorÃ©s     : $skipped");
    cron_log('info', "DurÃ©e       : {$duration}s");
    cron_log('info', 'â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€');

    if (!$dry_run) {
        cron_db_log('SYNC_CRON', $status, json_encode($summary));
    }

    exit($status === 'ERROR' ? 1 : 0);

} catch (Exception $e) {
    $msg = 'ERREUR CRITIQUE : ' . $e->getMessage();
    cron_log('error', $msg);
    cron_db_log('SYNC_CRON', 'ERROR', json_encode(['message' => $msg]));
    releaseLock($lock_file);
    exit(1);
}
?>

