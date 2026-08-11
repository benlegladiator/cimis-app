<?php
session_start();

// Détruire complètement la session
session_destroy();
session_unset();

// Rediriger vers la page d'accès
header('Location: ../index.php');
exit;
?>
