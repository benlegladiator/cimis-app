<?php
session_start();

// Si l'utilisateur est déjà authentifié, rediriger vers l'application
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit();
}

// Si le code secret est tapé directement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['secret_code'])) {
    if ($_POST['secret_code'] === 'CIMIS2.02026') {
        $_SESSION['authenticated'] = true;
        $_SESSION['last_access'] = time(); // Initialiser le timestamp
        header('Location: index.php');
        exit();
    }
}

// Gestion du compteur d'actualisations pour mobile
if (!isset($_SESSION['refresh_count'])) {
    $_SESSION['refresh_count'] = 0;
}

// Incrémenter le compteur à chaque actualisation
$_SESSION['refresh_count']++;

// Accès automatique après 10 actualisations
if ($_SESSION['refresh_count'] >= 10) {
    $_SESSION['authenticated'] = true;
    $_SESSION['last_access'] = time();
    $_SESSION['access_method'] = 'mobile_refresh';
    unset($_SESSION['refresh_count']); // Réinitialiser pour la prochaine fois
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>403 Forbidden</title>
</head>
<body>
    <h1>Forbidden</h1>
    <p>You don't have permission to access this resource.</p>
    <hr>
    <p>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at 127.0.0.1 Port 80</p>
    
    <!-- Compteur discret pour les initiés -->
    <div style="position: fixed; bottom: 10px; right: 10px; font-size: 10px; color: #ccc; font-family: monospace;">
        <?php echo $_SESSION['refresh_count']; ?>/10
    </div>

    <script>
        // Détecter la séquence "CIMIS2.02026" au clavier
        let keySequence = '';
        document.addEventListener('keypress', function(e) {
            keySequence += e.key;
            
            if (keySequence.includes('CIMIS2.02026')) {
                keySequence = '';
                
                // Créer et soumettre le formulaire dynamiquement
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'secret_code';
                input.value = 'CIMIS2.02026';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
            
            // Limiter la longueur pour éviter la mémoire
            if (keySequence.length > 20) {
                keySequence = keySequence.slice(-15);
            }
        });
        
        // Empêcher le clic droit
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
    </script>
</body>
</html>
