<?php
// Endpoint dynamique de service de photo pour CIMIS & SIADOC
// Garantit TOUJOURS un retour HTTP 200 OK avec une image valide (aucun 404)

require_once __DIR__ . '/config.php';

$id = $_GET['id'] ?? null;
$matricule = $_GET['matricule'] ?? null;

$photo_content = null;
$mime_type = 'image/png';

if ($id || $matricule) {
    try {
        global $pdo;
        if ($id) {
            $stmt = $pdo->prepare("SELECT photo FROM candidat WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("SELECT photo FROM candidat WHERE matricule = ? OR matricule_militaire = ?");
            $stmt->execute([$matricule, $matricule]);
        }
        $candidat = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($candidat['photo'])) {
            $rel = ltrim(str_replace('../', '', $candidat['photo']), '/');
            $candidates_paths = [
                $_SERVER['DOCUMENT_ROOT'] . '/' . $rel,
                dirname(__DIR__) . '/' . $rel,
                __DIR__ . '/../' . $rel
            ];
            foreach ($candidates_paths as $p) {
                if (file_exists($p) && is_file($p)) {
                    $photo_content = file_get_contents($p);
                    $mime_type = mime_content_type($p) ?: 'image/jpeg';
                    break;
                }
            }
        }
    } catch (Exception $e) {}
}

// Fallback sur une photo par défaut engagée dans le dépôt Git
if (!$photo_content) {
    $default_paths = [
        __DIR__ . '/../img/1KRISS.PNG',
        __DIR__ . '/../img/1ONANA.PNG',
        __DIR__ . '/../img/1YANNICK.PNG',
        __DIR__ . '/../img/ben.PNG',
        __DIR__ . '/../img/GRACE.PNG'
    ];
    foreach ($default_paths as $dp) {
        if (file_exists($dp)) {
            $photo_content = file_get_contents($dp);
            $mime_type = mime_content_type($dp) ?: 'image/png';
            break;
        }
    }
}

// Fallback ultime GD image si aucun fichier sur disque
if (!$photo_content && extension_loaded('gd')) {
    $img = imagecreatetruecolor(200, 240);
    $bg  = imagecolorallocate($img, 15, 23, 42);
    $fg  = imagecolorallocate($img, 16, 185, 129);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 4, 30, 110, "CIMIS MILITAIRE", $fg);
    ob_start();
    imagepng($img);
    $photo_content = ob_get_clean();
    imagedestroy($img);
    $mime_type = 'image/png';
}

header('Content-Type: ' . $mime_type);
header('Cache-Control: public, max-age=86400');
header('Access-Control-Allow-Origin: *');
echo $photo_content;
exit();
