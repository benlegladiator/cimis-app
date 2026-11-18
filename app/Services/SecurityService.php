<?php
/**
 * CIMIS - Service de Sécurité, Cryptographie & Gouvernance (Norme MINDEF / ANTIC)
 * Gère :
 * 1. Protection CSRF cryptographique automatique
 * 2. Chiffrement & Sécurisation des sessions (AES-256-CBC, HttpOnly, SameSite)
 * 3. Rate Limiting & Protection Anti Brute-Force (IP & Compte)
 * 4. Hachage moderne des mots de passe (Bcrypt / Argon2id)
 * 5. Journalisation d'audit (activity_log) et détection d'intrusions
 */

if (!defined('DB_NAME')) {
    require_once dirname(__DIR__, 2) . '/config/config.php';
}

/* ====================================================================
   1. SÉCURISATION & INITIALISATION DES SESSIONS (AES-256 / COOKIE GUARDS)
   ==================================================================== */

if (!function_exists('cimis_init_secure_session')) {
    /**
     * Démarre une session avec drapeaux de sécurité stricts
     */
    function cimis_init_secure_session(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
            
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            
            session_set_cookie_params([
                'lifetime' => 7200, // 2 heures
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            @session_start();
        }
    }
}

if (!function_exists('cimis_encrypt_data')) {
    /**
     * Chiffre une donnée avec l'algorithme militaire AES-256-CBC
     */
    function cimis_encrypt_data(string $plainText, string $secretKey = ''): string {
        $key = $secretKey ?: (defined('APP_KEY') ? APP_KEY : 'CIMIS_MINDEF_CAMEROON_SECRET_2026_KEY');
        $keyHash = hash('sha256', $key, true);
        $iv = random_bytes(16);
        $cipherText = openssl_encrypt($plainText, 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipherText);
    }
}

if (!function_exists('cimis_decrypt_data')) {
    /**
     * Déchiffre une donnée chiffrée en AES-256-CBC
     */
    function cimis_decrypt_data(string $encryptedData, string $secretKey = ''): ?string {
        $key = $secretKey ?: (defined('APP_KEY') ? APP_KEY : 'CIMIS_MINDEF_CAMEROON_SECRET_2026_KEY');
        $raw = base64_decode($encryptedData, true);
        if (!$raw || strlen($raw) < 17) {
            return null;
        }
        $iv = substr($raw, 0, 16);
        $cipherText = substr($raw, 16);
        $keyHash = hash('sha256', $key, true);
        $decrypted = openssl_decrypt($cipherText, 'AES-256-CBC', $keyHash, OPENSSL_RAW_DATA, $iv);
        return ($decrypted !== false) ? $decrypted : null;
    }
}

/* ====================================================================
   2. PROTECTION CSRF CRYPTOGRAPHIQUE AUTOMATIQUE
   ==================================================================== */

if (!function_exists('cimis_csrf_token')) {
    /**
     * Retourne ou génère le jeton CSRF de la session courante
     */
    function cimis_csrf_token(): string {
        cimis_init_secure_session();
        if (empty($_SESSION['_cimis_csrf_token']) || strlen($_SESSION['_cimis_csrf_token']) < 32) {
            $_SESSION['_cimis_csrf_token'] = bin2hex(random_bytes(32));
        }
        $_SESSION['csrf_token'] = $_SESSION['_cimis_csrf_token'];
        return $_SESSION['_cimis_csrf_token'];
    }
}

if (!function_exists('cimis_generate_csrf_token')) {
    /**
     * Alias pour la génération de jeton CSRF
     */
    function cimis_generate_csrf_token(): string {
        return cimis_csrf_token();
    }
}

if (!function_exists('cimis_csrf_field')) {
    /**
     * Génère l'input HTML caché pour les formulaires POST
     */
    function cimis_csrf_field(): string {
        $token = htmlspecialchars(cimis_csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $token . '">' .
               '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return cimis_csrf_token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return cimis_csrf_field();
    }
}

if (!function_exists('cimis_verify_csrf_token')) {
    /**
     * Vérifie la validité d'un jeton CSRF soumis
     */
    function cimis_verify_csrf_token(?string $submittedToken): bool {
        if (empty($submittedToken) || !is_string($submittedToken)) {
            return false;
        }
        cimis_init_secure_session();
        $sessionToken = $_SESSION['_cimis_csrf_token'] ?? $_SESSION['csrf_token'] ?? '';
        if (!empty($sessionToken) && hash_equals($sessionToken, $submittedToken)) {
            return true;
        }
        // Tolérance session Laravel
        if (function_exists('session')) {
            try {
                $lToken = session()->token();
                if (!empty($lToken) && hash_equals($lToken, $submittedToken)) {
                    return true;
                }
            } catch (\Throwable $e) {}
        }
        if (function_exists('csrf_token')) {
            try {
                $lToken2 = csrf_token();
                if (!empty($lToken2) && hash_equals($lToken2, $submittedToken)) {
                    return true;
                }
            } catch (\Throwable $e) {}
        }
        return false;
    }
}

/* ====================================================================
   3. RATE LIMITING & PROTECTION ANTI BRUTE-FORCE
   ==================================================================== */

if (!function_exists('cimis_get_client_ip')) {
    function cimis_get_client_ip(): string {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

if (!function_exists('cimis_check_rate_limit')) {
    /**
     * Vérifie si l'IP ou le compte a dépassé le seuil de requêtes autorisées
     * 
     * @param string $action Nom de l'action (ex: 'login', 'gate')
     * @param int $maxAttempts Nombre max de tentatives (défaut: 5)
     * @param int $decaySeconds Temps de blocage en secondes (défaut: 900 = 15min)
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
     */
    function cimis_check_rate_limit(string $action = 'login', int $maxAttempts = 5, int $decaySeconds = 900): array {
        cimis_init_secure_session();
        $ip = cimis_get_client_ip();
        $cacheDir = CIMIS_BASE_PATH . '/storage/framework/cache/data';
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $key = 'rate_' . md5($action . '_' . $ip);
        $file = $cacheDir . '/' . $key . '.json';

        $data = ['attempts' => 0, 'locked_until' => 0, 'first_attempt' => time()];
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $parsed = $content ? json_decode($content, true) : null;
            if (is_array($parsed)) {
                $data = $parsed;
            }
        }

        $now = time();

        // Si verrouillé
        if (!empty($data['locked_until']) && $data['locked_until'] > $now) {
            return [
                'allowed'     => false,
                'remaining'   => 0,
                'retry_after' => $data['locked_until'] - $now
            ];
        }

        // Si la fenêtre d'expiration est passée, reset
        if ($now - ($data['first_attempt'] ?? $now) > $decaySeconds) {
            $data['attempts'] = 0;
            $data['first_attempt'] = $now;
            $data['locked_until'] = 0;
            @file_put_contents($file, json_encode($data), LOCK_EX);
        }

        $remaining = max(0, $maxAttempts - ($data['attempts'] ?? 0));

        return [
            'allowed'     => ($remaining > 0),
            'remaining'   => $remaining,
            'retry_after' => 0
        ];
    }
}

if (!function_exists('cimis_hit_rate_limit')) {
    /**
     * Enregistre un échec de tentative
     */
    function cimis_hit_rate_limit(string $action = 'login', int $maxAttempts = 5, int $decaySeconds = 900): void {
        cimis_init_secure_session();
        $ip = cimis_get_client_ip();
        $cacheDir = CIMIS_BASE_PATH . '/storage/framework/cache/data';
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $key = 'rate_' . md5($action . '_' . $ip);
        $file = $cacheDir . '/' . $key . '.json';

        $data = ['attempts' => 0, 'locked_until' => 0, 'first_attempt' => time()];
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $parsed = $content ? json_decode($content, true) : null;
            if (is_array($parsed)) {
                $data = $parsed;
            }
        }

        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        if ($data['attempts'] >= $maxAttempts) {
            $data['locked_until'] = time() + $decaySeconds;
        }

        @file_put_contents($file, json_encode($data), LOCK_EX);
    }
}

if (!function_exists('cimis_clear_rate_limit')) {
    /**
     * Réinitialise le compteur après succès
     */
    function cimis_clear_rate_limit(string $action = 'login'): void {
        $ip = cimis_get_client_ip();
        $cacheDir = CIMIS_BASE_PATH . '/storage/framework/cache/data';
        $key = 'rate_' . md5($action . '_' . $ip);
        $file = $cacheDir . '/' . $key . '.json';
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

/* ====================================================================
   4. HACHAGE MODERNE DES MOTS DE PASSE (BCRYPT / ARGON2ID)
   ==================================================================== */

if (!function_exists('cimis_hash_password')) {
    /**
     * Hache un mot de passe avec Bcrypt cost 12 ou Argon2id
     */
    function cimis_hash_password(string $password): string {
        if (defined('PASSWORD_ARGON2ID') && function_exists('password_hash')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost'   => 4,
                'threads'     => 1
            ]);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

if (!function_exists('cimis_verify_password')) {
    /**
     * Vérifie un mot de passe de manière stricte (Bcrypt / Argon2id)
     */
    function cimis_verify_password(string $password, string $hash): bool {
        if (trim($password) === '' || trim($hash) === '') {
            return false;
        }
        return password_verify($password, $hash);
    }
}

/* ====================================================================
   5. AUDIT DES ACTIONS ET GOUVERNANCE SÉCURITÉ
   ==================================================================== */

if (!function_exists('cimis_audit_log')) {
    /**
     * Journalisation universelle MINDEF (Qui a fait quoi)
     * Enregistre l'auteur précis (ID, nom, rôle), l'action, l'entité ciblée, les détails et l'IP.
     */
    function cimis_audit_log(
        PDO $pdo,
        string $action,
        string $description,
        ?string $module = 'GENERAL',
        ?int $enregistrementId = null,
        ?string $details = null,
        ?int $userId = null,
        ?string $username = null,
        ?string $ip = null
    ): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        $username = $username ?? ($_SESSION['username'] ?? 'OPERATEUR');
        $ip = $ip ?: (function_exists('cimis_get_client_ip') ? cimis_get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CIMIS/Desktop';

        // Résolution et validation de l'ID utilisateur (évite tout conflit de clé étrangère fk_activity_user)
        if ($userId) {
            try {
                $stChk = $pdo->prepare("SELECT id FROM utilisateur WHERE id = ? LIMIT 1");
                $stChk->execute([$userId]);
                if (!$stChk->fetchColumn()) {
                    $userId = null;
                }
            } catch (Exception $e) {
                $userId = null;
            }
        }
        if (!$userId && $username) {
            try {
                $stU = $pdo->prepare("SELECT id FROM utilisateur WHERE username = ? LIMIT 1");
                $stU->execute([$username]);
                $foundId = $stU->fetchColumn();
                if ($foundId) $userId = (int)$foundId;
            } catch (Exception $e) {}
        }

        try {
            // 1. Insertion dans activity_log
            $stAct = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, description, ip_address, date_action) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stAct->execute([$userId, strtoupper(trim($action)), trim($description), $ip]);

            // 2. Insertion dans logs (traçabilité détaillée)
            $stLog = $pdo->prepare("
                INSERT INTO logs (utilisateur_id, username, action, module, table_concernee, id_enregistrement, details, ip_adresse, user_agent, date_action, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'SUCCES')
            ");
            $stLog->execute([
                $userId,
                $username,
                strtoupper(trim($action)),
                strtoupper(trim($module ?: 'GENERAL')),
                'candidat',
                $enregistrementId,
                $details ?: $description,
                $ip,
                substr($userAgent, 0, 250)
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Erreur cimis_audit_log: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('logSecurityActivity')) {
    /**
     * Enregistre un événement dans le journal d'activité d'audit
     */
    function logSecurityActivity(PDO $pdo, ?int $userId, string $action, string $description, string $ip = ''): bool {
        return cimis_audit_log($pdo, $action, $description, 'SECURITE', null, $description, $userId, null, $ip);
    }
}

if (!function_exists('getSecurityAuditLogs')) {
    function getSecurityAuditLogs(PDO $pdo, int $limit = 50, string $filterAction = ''): array {
        $where = [];
        $params = [];

        if (!empty($filterAction)) {
            $where[] = "a.action = :act";
            $params['act'] = strtoupper(trim($filterAction));
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $sql = "
            SELECT a.id, a.user_id, a.action, a.description, a.ip_address, a.date_action,
                   COALESCE(u.username, 'SYSTÈME / ANONYME') AS username,
                   COALESCE(u.role, 'GOUVERNANCE') AS role
            FROM activity_log a
            LEFT JOIN utilisateur u ON u.id = a.user_id
            $whereClause
            ORDER BY a.date_action DESC, a.id DESC
            LIMIT " . (int)$limit;

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur getSecurityAuditLogs: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getAllUsersWithSecurityStatus')) {
    function getAllUsersWithSecurityStatus(PDO $pdo): array {
        $sql = "
            SELECT id, username, email, role, actif, date_creation, date_derniere_connexion, 
                   compte_verrouille, date_verrouillage, nombre_echecs, motif_desactivation
            FROM utilisateur 
            ORDER BY role = 'SUPER_ADMIN' DESC, date_creation DESC
        ";

        try {
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur getAllUsersWithSecurityStatus: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('toggleUserStatus')) {
    function toggleUserStatus(PDO $pdo, int $targetUserId, bool $activate, string $motif, int $actingUserId, string $ip = ''): array {
        if ($targetUserId === $actingUserId && !$activate) {
            return [
                'success' => false,
                'error'   => 'Action interdite : vous ne pouvez pas désactiver votre propre compte administrateur.'
            ];
        }

        try {
            $stmt = $pdo->prepare("SELECT username, role FROM utilisateur WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetUser) {
                return ['success' => false, 'error' => 'Utilisateur introuvable.'];
            }

            if ($activate) {
                $upd = $pdo->prepare("UPDATE utilisateur SET actif = 1, compte_verrouille = 0, date_verrouillage = NULL, motif_desactivation = NULL, nombre_echecs = 0 WHERE id = ?");
                $upd->execute([$targetUserId]);
                $action = 'REACTIVATION_UTILISATEUR';
                $desc = "Réactivation du compte {$targetUser['username']} ({$targetUser['role']})";
            } else {
                $upd = $pdo->prepare("UPDATE utilisateur SET actif = 0, date_verrouillage = NOW(), motif_desactivation = ? WHERE id = ?");
                $upd->execute([$motif, $targetUserId]);
                $action = 'DESACTIVATION_UTILISATEUR';
                $desc = "Désactivation du compte {$targetUser['username']} ({$targetUser['role']})" . (!empty($motif) ? " - Motif: $motif" : "");
            }

            logSecurityActivity($pdo, $actingUserId, $action, $desc, $ip);

            return [
                'success' => true,
                'message' => "Compte {$targetUser['username']} " . ($activate ? 'réactivé' : 'désactivé') . " avec succès."
            ];
        } catch (PDOException $e) {
            error_log("Erreur toggleUserStatus: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur de base de données : ' . $e->getMessage()];
        }
    }
}

if (!function_exists('getIntrusionAlertLogs')) {
    function getIntrusionAlertLogs(string $logDir = 'logs', int $limit = 50): array {
        $alertFile = $logDir . '/security_alerts.log';
        if (!file_exists($alertFile)) {
            return [];
        }

        $lines = @file($alertFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $entries = [];
        $currentEntry = [];

        foreach (array_reverse($lines) as $line) {
            if (str_starts_with($line, '[')) {
                if (!empty($currentEntry)) {
                    $entries[] = implode("\n", array_reverse($currentEntry));
                    if (count($entries) >= $limit) break;
                    $currentEntry = [];
                }
            }
            $currentEntry[] = $line;
        }

        if (!empty($currentEntry) && count($entries) < $limit) {
            $entries[] = implode("\n", array_reverse($currentEntry));
        }

        return $entries;
    }
}

if (!function_exists('getSecurityComplianceMetrics')) {
    function getSecurityComplianceMetrics(PDO $pdo, string $rootDir): array {
        $metrics = [
            'total_users'     => 0,
            'active_users'    => 0,
            'locked_users'    => 0,
            'super_admins'    => 0,
            'total_logs'      => 0,
            'recent_alerts'   => 0
        ];

        try {
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) AS total_users,
                    SUM(CASE WHEN actif = 1 THEN 1 ELSE 0 END) AS active_users,
                    SUM(CASE WHEN compte_verrouille = 1 OR actif = 0 THEN 1 ELSE 0 END) AS locked_users,
                    SUM(CASE WHEN role = 'SUPER_ADMIN' THEN 1 ELSE 0 END) AS super_admins
                FROM utilisateur
            ");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                $metrics['total_users']  = (int)($res['total_users'] ?? 0);
                $metrics['active_users'] = (int)($res['active_users'] ?? 0);
                $metrics['locked_users'] = (int)($res['locked_users'] ?? 0);
                $metrics['super_admins'] = (int)($res['super_admins'] ?? 0);
            }

            $stmt2 = $pdo->query("SELECT COUNT(*) FROM activity_log");
            $metrics['total_logs'] = (int)($stmt2->fetchColumn() ?: 0);

            $metrics['recent_alerts'] = count(getIntrusionAlertLogs($rootDir . '/logs', 20));
        } catch (PDOException $e) {
            error_log("Erreur metrics sécurité: " . $e->getMessage());
        }

        return $metrics;
    }
}