<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Rediriger vers la porte de sécurité
    header('Location: security_gate.php');
    exit();
}

// Si authentifié, continuer vers le vrai index
header('Location: dashboard.php');
exit();
?>
