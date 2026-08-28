<?php
/**
 * GESTIONNAIRE FILE D'ATTENTE IMPRESSION REÇUS A4
 */
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if (!isset($_SESSION['pending_receipts'])) {
    $_SESSION['pending_receipts'] = [];
}

$action = $_REQUEST['action'] ?? 'get';

if ($action === 'add') {
    $ids_raw = $_REQUEST['ids'] ?? $_REQUEST['id'] ?? null;
    if ($ids_raw) {
        $ids = is_array($ids_raw) ? $ids_raw : explode(',', $ids_raw);
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id > 0 && !in_array($id, $_SESSION['pending_receipts'])) {
                $_SESSION['pending_receipts'][] = $id;
            }
        }
    }
    echo json_encode([
        'success' => true,
        'count'   => count($_SESSION['pending_receipts']),
        'ids'     => $_SESSION['pending_receipts']
    ]);
    exit;
}

if ($action === 'clear') {
    $_SESSION['pending_receipts'] = [];
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

// Action 'get' par défaut
echo json_encode([
    'success' => true,
    'count'   => count($_SESSION['pending_receipts']),
    'ids'     => $_SESSION['pending_receipts']
]);
