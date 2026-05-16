<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Accès non autorisé']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$templatesDir = __DIR__ . '/../templates';
$assetsDir = __DIR__ . '/../img/templates';
if (!is_dir($templatesDir)) mkdir($templatesDir, 0755, true);
if (!is_dir($assetsDir)) mkdir($assetsDir, 0755, true);

$name = trim($_POST['name'] ?? 'template');
$corps = trim($_POST['corps'] ?? 'Terre');
$templateJson = $_POST['template'] ?? null;

if (!$templateJson) {
    echo json_encode(['success'=>false,'message'=>'Template manquant']);
    exit;
}

// Decode to validate
$templateData = json_decode($templateJson, true);
if ($templateData === null) {
    echo json_encode(['success'=>false,'message'=>'JSON invalide']);
    exit;
}

// Handle uploads: bg, logo (optional)
$uploaded = [];
$allowed = ['image/png','image/jpeg','image/jpg','image/webp'];
foreach(['bg','logo'] as $field) {
    if (!empty($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES[$field]['tmp_name'];
        $mime = mime_content_type($tmp);
        if (!in_array($mime, $allowed)) continue;
        $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
        $safe = preg_replace('/[^a-z0-9_\-\.]/i','_', basename($_FILES[$field]['name']));
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '_' . $safe;
        $dest = $assetsDir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($tmp, $dest)) {
            $rel = 'img/templates/' . $filename;
            $uploaded[$field] = $rel;
        }
    }
}

// If uploads provided, inject into template data under assets
if (!empty($uploaded)) {
    $templateData['assets'] = array_merge($templateData['assets'] ?? [], $uploaded);
}

// Build filename (slug + timestamp)
$slug = preg_replace('/[^a-z0-9\-]/i','_', strtolower($name));
$filename = $slug . '_' . time() . '.json';
$path = $templatesDir . DIRECTORY_SEPARATOR . $filename;

file_put_contents($path, json_encode([
    'meta' => ['name'=>$name,'corps'=>$corps,'created_by'=>$_SESSION['username'] ?? 'system','created_at'=>date('c')],
    'template' => $templateData
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode(['success'=>true,'message'=>'Template sauvegardé','file'=>$filename,'path'=>'templates/'.$filename]);
exit;
?>