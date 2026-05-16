<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Accès non autorisé']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
$templatesDir = __DIR__ . '/../templates';
$list = [];
if (is_dir($templatesDir)) {
    $files = glob($templatesDir . '/*.json');
    foreach($files as $f) {
        $content = @file_get_contents($f);
        $data = @json_decode($content, true);
        $list[] = [
            'file' => basename($f),
            'meta' => $data['meta'] ?? null,
            'template' => $data['template'] ?? null,
            'modified' => date('c', filemtime($f))
        ];
    }
}
echo json_encode(['success'=>true,'templates'=>$list]);
exit;
?>