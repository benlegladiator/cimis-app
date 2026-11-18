<?php
/**
 * CIMIS - Service de Gestion Sécurisée des Signatures Officielles MINDEF & DRH
 * Ministère de la Défense - République du Cameroun
 *
 * Fonctionnalités de sécurité :
 * - Authentification et vérification de mot de passe Super Administrateur en temps réel
 * - Validation stricte du type MIME, extension, magic bytes et en-tête PNG (\x89PNG\r\n\x1a\n)
 * - Décodage et assainissement intégral via GD (élimination des malwares/scripts dans EXIF/chunks)
 * - Préservation ou suppression automatique du fond blanc (pour signatures scannées)
 * - Génération automatique synchronisée de toutes les variantes militaires (Gold, Transparent, Blanc, Noir, Bleu)
 * - Sauvegarde versionnée automatique avec horodatage avant tout écrasement
 * - Possibilité de restauration/rollback en un clic
 * - Traçabilité et audit renforcé dans les tables logs et activity_log
 */

namespace App\Services;

use PDO;
use Exception;

class SignatureService
{
    private static string $signaturesDir = '';
    private static string $archivesDir = '';
    private static string $configFile = '';

    private static function initPaths(): void
    {
        $base = defined('CIMIS_ROOT') ? CIMIS_ROOT : dirname(__DIR__, 2);
        self::$signaturesDir = $base . '/public/img/signatures';
        self::$archivesDir   = self::$signaturesDir . '/archives';
        self::$configFile    = $base . '/config/signatures.json';

        if (!file_exists(self::$signaturesDir)) {
            @mkdir(self::$signaturesDir, 0755, true);
        }
        if (!file_exists(self::$archivesDir)) {
            @mkdir(self::$archivesDir, 0755, true);
        }
    }

    /**
     * Retourne la configuration courante des signataires
     */
    public static function getConfig(): array
    {
        self::initPaths();
        if (file_exists(self::$configFile)) {
            $data = @json_decode(file_get_contents(self::$configFile), true);
            if (is_array($data) && isset($data['mindef'], $data['drh'])) {
                return $data;
            }
        }

        $default = [
            'mindef' => [
                'name' => 'JOSEPH BETI ASSOMO',
                'title' => 'Ministre Délégué à la Présidence chargé de la Défense',
                'role_appele' => 'Officiers',
                'base_file' => 'SignatureMindef.png',
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => 'SYSTEME'
            ],
            'drh' => [
                'name' => 'DIRECTEUR DES RESSOURCES HUMAINES',
                'title' => 'Directeur des Ressources Humaines du MINDEF',
                'role_appele' => 'Sous-officiers et Militaires du Rang',
                'base_file' => 'SignatureDrh.png',
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => 'SYSTEME'
            ]
        ];
        @file_put_contents(self::$configFile, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $default;
    }

    /**
     * Sauvegarde la configuration
     */
    public static function saveConfig(array $config): bool
    {
        self::initPaths();
        return (bool)@file_put_contents(
            self::$configFile,
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Récupère les métadonnées complètes pour l'affichage de la page de gestion
     */
    public static function getSignaturesDetails(): array
    {
        self::initPaths();
        $config = self::getConfig();
        $details = [];

        foreach (['mindef', 'drh'] as $type) {
            $prefix = ($type === 'mindef') ? 'SignatureMindef' : 'SignatureDrh';
            $mainPath = self::$signaturesDir . '/' . $prefix . '.png';
            $goldPath = self::$signaturesDir . '/' . $prefix . '_gold.png';
            $transpPath = self::$signaturesDir . '/' . $prefix . '_transp.png';

            $fileExists = file_exists($mainPath);
            $goldExists = file_exists($goldPath);

            $fileSize = $fileExists ? filesize($mainPath) : 0;
            $fileTime = $fileExists ? filemtime($mainPath) : 0;
            $sha256 = $fileExists ? hash_file('sha256', $mainPath) : 'NON_DEFINI';

            $dimensions = [0, 0];
            if ($fileExists) {
                $imgInfo = @getimagesize($mainPath);
                if ($imgInfo) {
                    $dimensions = [$imgInfo[0], $imgInfo[1]];
                }
            }

            $details[$type] = [
                'type' => $type,
                'prefix' => $prefix,
                'name' => $config[$type]['name'] ?? '',
                'title' => $config[$type]['title'] ?? '',
                'role_appele' => $config[$type]['role_appele'] ?? '',
                'updated_at' => $config[$type]['updated_at'] ?? ($fileTime ? date('Y-m-d H:i:s', $fileTime) : 'N/A'),
                'updated_by' => $config[$type]['updated_by'] ?? 'N/A',
                'file_exists' => $fileExists,
                'file_size' => $fileSize,
                'file_size_formatted' => $fileSize > 0 ? round($fileSize / 1024, 2) . ' Ko' : '0 Ko',
                'dimensions' => $dimensions[0] . ' x ' . $dimensions[1] . ' px',
                'sha256' => $sha256,
                'url_main' => '/cimcim/public/img/signatures/' . $prefix . '.png?v=' . ($fileTime ?: time()),
                'url_gold' => '/cimcim/public/img/signatures/' . $prefix . '_gold.png?v=' . ($fileTime ?: time()),
                'url_transp' => '/cimcim/public/img/signatures/' . $prefix . '_transp.png?v=' . ($fileTime ?: time()),
            ];
        }

        return $details;
    }

    /**
     * Liste des archives de sauvegardes disponibles
     */
    public static function getArchivesList(): array
    {
        self::initPaths();
        $archives = [];
        if (!is_dir(self::$archivesDir)) {
            return $archives;
        }

        $files = scandir(self::$archivesDir);
        foreach ($files as $f) {
            if ($f === '.' || $f === '..' || !str_ends_with(strtolower($f), '.png')) {
                continue;
            }
            // Exemple : SignatureMindef_backup_20261117_114500_base.png
            $filePath = self::$archivesDir . '/' . $f;
            $mtime = filemtime($filePath);
            $type = str_contains($f, 'SignatureMindef') ? 'mindef' : (str_contains($f, 'SignatureDrh') ? 'drh' : 'inconnu');

            $archives[] = [
                'filename' => $f,
                'path' => $filePath,
                'type' => $type,
                'label_type' => ($type === 'mindef') ? 'MINDEF (Ministre)' : 'DRH (Directeur RH)',
                'size_formatted' => round(filesize($filePath) / 1024, 2) . ' Ko',
                'date' => date('d/m/Y à H:i:s', $mtime),
                'timestamp' => $mtime,
                'url' => '/cimcim/public/img/signatures/archives/' . $f . '?v=' . $mtime
            ];
        }

        // Trier par date décroissante
        usort($archives, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        return $archives;
    }

    /**
     * Validation stricte du fichier PNG téléversé
     */
    public static function validatePngUpload(array $file): array
    {
        if (empty($file) || !isset($file['error'])) {
            return ['ok' => false, 'error' => "Aucun fichier n'a été transmis."];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la taille maximale autorisée.",
                UPLOAD_ERR_PARTIAL => "Le fichier n'a été que partiellement téléversé.",
                UPLOAD_ERR_NO_FILE => "Aucun fichier sélectionné.",
                default => "Erreur technique lors de la réception du fichier (Code: {$file['error']})."
            };
            return ['ok' => false, 'error' => $msg];
        }

        // 1. Taille maximale (3 Mo)
        if ($file['size'] > 3 * 1024 * 1024) {
            return ['ok' => false, 'error' => "Le fichier est trop volumineux (Maximum 3 Mo autorisé)."];
        }

        // 2. Extension stricte .png
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'png') {
            return ['ok' => false, 'error' => "Format interdit : Seuls les fichiers avec l'extension '.png' sont acceptés."];
        }

        // 3. Vérification des Magic Bytes (Signature binaire standard PNG : 89 50 4E 47 0D 0A 1A 0A)
        $handle = @fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            return ['ok' => false, 'error' => "Impossible de lire le fichier téléversé pour analyse de sécurité."];
        }
        $header = fread($handle, 8);
        fclose($handle);
        $expectedHeader = "\x89PNG\r\n\x1a\n";
        if ($header !== $expectedHeader) {
            return ['ok' => false, 'error' => "Sécurité : Les octets magiques du fichier ne correspondent pas à un fichier PNG authentique."];
        }

        // 4. Analyse MIME type réel via finfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if ($mime !== 'image/png') {
                return ['ok' => false, 'error' => "Sécurité : Type MIME détecté invalide ($mime). Seul 'image/png' est autorisé."];
            }
        }

        // 5. Validation getimagesize
        $imgInfo = @getimagesize($file['tmp_name']);
        if (!$imgInfo || ($imgInfo[2] ?? 0) !== IMAGETYPE_PNG) {
            return ['ok' => false, 'error' => "Le fichier transmis n'est pas une image PNG valide ou est corrompu."];
        }

        $w = $imgInfo[0];
        $h = $imgInfo[1];
        if ($w < 80 || $h < 30) {
            return ['ok' => false, 'error' => "La résolution de l'image est trop petite ({$w}x{$h} px). Minimum recommandé : 200x80 px."];
        }
        if ($w > 5000 || $h > 3000) {
            return ['ok' => false, 'error' => "La résolution de l'image est excessive ({$w}x{$h} px)."];
        }

        return ['ok' => true, 'width' => $w, 'height' => $h];
    }

    /**
     * Traite l'image reçue : assainit le contenu, supprime le fond si demandé, et génère toutes les variantes.
     */
    public static function processAndDeploySignature(
        string $tmpPath,
        string $type,
        bool $autoRemoveWhiteBackground = true
    ): array {
        self::initPaths();
        $prefix = ($type === 'mindef') ? 'SignatureMindef' : 'SignatureDrh';

        // 1. Sauvegarder les fichiers actuels avant écrasement (Archivage versionné)
        $timestamp = date('Ymd_His');
        $variants = ['', '_gold', '_transp', '_white', '_black', '_blue'];
        foreach ($variants as $v) {
            $currentFile = self::$signaturesDir . '/' . $prefix . $v . '.png';
            if (file_exists($currentFile)) {
                $archiveFile = self::$archivesDir . '/' . $prefix . '_backup_' . $timestamp . ($v ?: '_base') . '.png';
                @copy($currentFile, $archiveFile);
            }
        }

        // 2. Charger l'image avec GD pour assainissement complet (strippe tout code injecté dans les métadonnées)
        $src = @imagecreatefrompng($tmpPath);
        if (!$src) {
            throw new Exception("Impossible de décoder l'image PNG via le moteur graphique GD.");
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // A. Image de base nettoyée avec transparence
        $clean = imagecreatetruecolor($w, $h);
        imagealphablending($clean, false);
        imagesavealpha($clean, true);
        $transparent = imagecolorallocatealpha($clean, 0, 0, 0, 127);
        imagefilledrectangle($clean, 0, 0, $w, $h, $transparent);

        // B. Image dorée (Gold)
        $gold = imagecreatetruecolor($w, $h);
        imagealphablending($gold, false);
        imagesavealpha($gold, true);
        imagefilledrectangle($gold, 0, 0, $w, $h, $transparent);

        // C. Image blanche (White)
        $white = imagecreatetruecolor($w, $h);
        imagealphablending($white, false);
        imagesavealpha($white, true);
        imagefilledrectangle($white, 0, 0, $w, $h, $transparent);

        // D. Image noire (Black)
        $black = imagecreatetruecolor($w, $h);
        imagealphablending($black, false);
        imagesavealpha($black, true);
        imagefilledrectangle($black, 0, 0, $w, $h, $transparent);

        // E. Image bleue (Blue)
        $blue = imagecreatetruecolor($w, $h);
        imagealphablending($blue, false);
        imagesavealpha($blue, true);
        imagefilledrectangle($blue, 0, 0, $w, $h, $transparent);

        // Couleurs de référence MINDEF
        $colorGoldR = 212; $colorGoldG = 175; $colorGoldB = 55;   // #d4af37
        $colorBlueR = 2;   $colorBlueG = 132; $colorBlueB = 199;  // #0284c7

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgba = imagecolorat($src, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $alpha = ($rgba >> 24) & 0x7F;

                // Détection de fond blanc ou clair (papier scanné)
                if ($autoRemoveWhiteBackground) {
                    $brightness = ($r + $g + $b) / 3;
                    if ($brightness > 220 && $alpha < 40) {
                        // Pixel considéré comme fond de page blanche -> convertir en transparent
                        $alpha = 127;
                    }
                }

                if ($alpha < 125) {
                    // Calcul de l'intensité du trait (opacité relative)
                    $traitAlpha = $alpha;

                    // Pixel propre
                    $pixelClean = imagecolorallocatealpha($clean, $r, $g, $b, $traitAlpha);
                    imagesetpixel($clean, $x, $y, $pixelClean);

                    // Pixel Gold
                    $pixelGold = imagecolorallocatealpha($gold, $colorGoldR, $colorGoldG, $colorGoldB, $traitAlpha);
                    imagesetpixel($gold, $x, $y, $pixelGold);

                    // Pixel White
                    $pixelWhite = imagecolorallocatealpha($white, 255, 255, 255, $traitAlpha);
                    imagesetpixel($white, $x, $y, $pixelWhite);

                    // Pixel Black
                    $pixelBlack = imagecolorallocatealpha($black, 0, 0, 0, $traitAlpha);
                    imagesetpixel($black, $x, $y, $pixelBlack);

                    // Pixel Blue
                    $pixelBlue = imagecolorallocatealpha($blue, $colorBlueR, $colorBlueG, $colorBlueB, $traitAlpha);
                    imagesetpixel($blue, $x, $y, $pixelBlue);
                }
            }
        }

        // Sauvegarder toutes les déclinaisons officielles sur le disque
        imagepng($clean, self::$signaturesDir . '/' . $prefix . '.png', 6);
        imagepng($clean, self::$signaturesDir . '/' . $prefix . '_transp.png', 6);
        imagepng($gold,  self::$signaturesDir . '/' . $prefix . '_gold.png', 6);
        imagepng($white, self::$signaturesDir . '/' . $prefix . '_white.png', 6);
        imagepng($black, self::$signaturesDir . '/' . $prefix . '_black.png', 6);
        imagepng($blue,  self::$signaturesDir . '/' . $prefix . '_blue.png', 6);

        // Libérer les ressources mémoires GD
        imagedestroy($src);
        imagedestroy($clean);
        imagedestroy($gold);
        imagedestroy($white);
        imagedestroy($black);
        imagedestroy($blue);

        return [
            'success' => true,
            'timestamp' => $timestamp,
            'dimensions' => "{$w} x {$h} px",
            'sha256' => hash_file('sha256', self::$signaturesDir . '/' . $prefix . '.png')
        ];
    }

    /**
     * Restauration d'une signature depuis une archive
     */
    public static function restoreArchive(string $archiveFilename, string $type): bool
    {
        self::initPaths();
        $sourcePath = self::$archivesDir . '/' . basename($archiveFilename);
        if (!file_exists($sourcePath)) {
            throw new Exception("L'archive spécifiée est introuvable sur le disque.");
        }

        $prefix = ($type === 'mindef') ? 'SignatureMindef' : 'SignatureDrh';

        // Sauvegarder l'état actuel avant restauration
        $timestamp = date('Ymd_His');
        $current = self::$signaturesDir . '/' . $prefix . '.png';
        if (file_exists($current)) {
            @copy($current, self::$archivesDir . '/' . $prefix . '_before_restore_' . $timestamp . '.png');
        }

        // Remplacer et regénérer les variantes
        self::processAndDeploySignature($sourcePath, $type, false);
        return true;
    }

    /**
     * Vérification du mot de passe de l'administrateur courant
     */
    public static function verifySuperAdminPassword(PDO $pdo, int $userId, string $password): bool
    {
        if (empty($password)) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT password, role FROM utilisateur WHERE id = ? AND actif = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['role'] !== 'SUPER_ADMIN') {
            return false;
        }

        return password_verify($password, $user['password']);
    }

    /**
     * Enregistre l'audit de l'action dans MySQL
     */
    public static function logAudit(
        PDO $pdo,
        int $userId,
        string $username,
        string $action,
        string $details,
        string $ip,
        string $userAgent
    ): void {
        try {
            $validUserId = null;
            if ($userId > 0) {
                $chk = $pdo->prepare("SELECT id FROM utilisateur WHERE id = ?");
                $chk->execute([$userId]);
                if ($chk->fetch()) {
                    $validUserId = $userId;
                }
            }

            // Table logs
            $stmt = $pdo->prepare("
                INSERT INTO logs (utilisateur_id, username, action, module, details, ip_adresse, user_agent, statut, date_action)
                VALUES (?, ?, ?, 'SECURITE_SIGNATURES', ?, ?, ?, 'SUCCES', NOW())
            ");
            $stmt->execute([$validUserId, $username, $action, $details, $ip, $userAgent]);

            // Table activity_log
            if ($validUserId !== null) {
                $stmt2 = $pdo->prepare("
                    INSERT INTO activity_log (user_id, action, description, ip_address, date_action)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt2->execute([$validUserId, $action, $details, $ip]);
            }
        } catch (\Throwable $e) {
            error_log("Erreur enregistrement log signature: " . $e->getMessage());
        }
    }
}
