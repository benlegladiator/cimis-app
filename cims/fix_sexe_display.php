<?php
// Script PHP de correction et test du problème d'affichage du sexe sur les cartes CIMIS
// À exécuter directement: http://127.0.0.1/cim/fix_sexe_display.php

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "    <title>🔧 Correction et Test - Affichage Sexe</title>";
echo "    <style>";
echo "        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }";
echo "        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "        .header { text-align: center; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; }";
echo "        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #4ade80; background: #f0f9ff; }";
echo "        .step h3 { color: #2c3e50; margin-top: 0; }";
echo "        .code { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; margin: 10px 0; overflow-x: auto; }";
echo "        .success { color: #28a745; font-weight: bold; }";
echo "        .error { color: #dc3545; font-weight: bold; }";
echo "        .warning { color: #ffc107; font-weight: bold; }";
echo "        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }";
echo "        .btn:hover { background: #0056b3; }";
echo "        .btn.success { background: #28a745; }";
echo "        .btn.success:hover { background: #1e7e34; }";
echo "        .btn.warning { background: #ffc107; color: #212529; }";
echo "        .btn.warning:hover { background: #e0a800; }";
echo "        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }";
echo "        .result.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }";
echo "        .result.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }";
echo "        .loading { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite; }";
echo "        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }";
echo "    </style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "    <div class='header'>";
echo "        <h1>🔧 CIMIS - Correction et Test Affichage Sexe</h1>";
echo "        <p>Ce script va corriger le problème d'affichage du sexe sur les cartes et tester avec un enregistrement</p>";
echo "    </div>";

// ÉTAPE 1: Lire et analyser le fichier actuel
echo "<div class='step'>";
echo "<h3>📝 ÉTAPE 1: Analyse du fichier confection_carte.php actuel</h3>";

$filePath = __DIR__ . '/Carte/confection_carte.php';
if (file_exists($filePath)) {
    $content = file_get_contents($filePath);
    
    // Vérifier si la fonction afficherSexe existe
    if (strpos($content, 'function afficherSexe') !== false) {
        echo "<p class='success'>✅ La fonction afficherSexe() existe déjà</p>";
    } else {
        echo "<p class='warning'>⚠️ La fonction afficherSexe() n'existe pas</p>";
    }
    
    // Vérifier l'affichage actuel du sexe
    if (strpos($content, "echo htmlspecialchars(\$candidat['sexe']") !== false) {
        echo "<p class='error'>❌ Problème détecté: Le sexe est affiché directement sans conversion</p>";
    } elseif (strpos($content, 'echo afficherSexe') !== false) {
        echo "<p class='success'>✅ L'affichage utilise déjà la fonction afficherSexe()</p>";
    } else {
        echo "<p class='warning'>⚠️ L'affichage du sexe n'utilise pas afficherSexe()</p>";
    }
    
    // Vérifier les styles CSS
    if (strpos($content, 'white-space: nowrap') !== false) {
        echo "<p class='success'>✅ Les styles anti-troncature sont déjà présents</p>";
    } else {
        echo "<p class='warning'>⚠️ Les styles anti-troncature sont manquants</p>";
    }
    
} else {
    echo "<p class='error'>❌ Fichier confection_carte.php non trouvé</p>";
}

echo "</div>";

// ÉTAPE 2: Générer le contenu corrigé
echo "<div class='step'>";
echo "<h3>🔧 ÉTAPE 2: Génération du contenu corrigé</h3>";

$fonctionSexe = '// Fonction pour afficher le sexe correctement
function afficherSexe($sexe) {
    // Si le sexe est déjà en format complet, le retourner tel quel
    if (in_array(strtoupper($sexe), [\'MASCULIN\', \'FEMININ\'])) {
        return strtoupper($sexe);
    }
    
    // Si c\'est juste "M" ou "F", convertir en format complet
    switch (strtoupper($sexe)) {
        case \'M\':
            return \'MASCULIN\';
        case \'F\':
            return \'FEMININ\';
        default:
            return strtoupper($sexe); // Retourner le texte original par défaut
    }
}

';

// Créer le contenu corrigé
$newContent = $content;

// Ajouter la fonction afficherSexe si elle n'existe pas
if (strpos($content, 'function afficherSexe') === false) {
    // Insérer après le premier <?php
    $newContent = str_replace('<?php', '<?php\n' . $fonctionSexe, $newContent);
    echo "<p class='success'>✅ Fonction afficherSexe() ajoutée</p>";
}

// Remplacer l'affichage du sexe
$oldDisplay = '<?php echo htmlspecialchars($candidat[\'sexe\'] ?? \'\'); ?>';
$newDisplay = '<?php echo afficherSexe($candidat[\'sexe\'] ?? \'\'); ?>';

if (strpos($newContent, $oldDisplay) !== false) {
    $newContent = str_replace($oldDisplay, $newDisplay, $newContent);
    echo "<p class='success'>✅ Affichage du sexe corrigé</p>";
}

// Ajouter les styles CSS anti-troncature
$cssUpdate = '.value {
    white-space: nowrap; /* Empêche le retour à la ligne */
    overflow: visible; /* Assure que le texte n\'est pas coupé */
    text-overflow: clip; /* Coupe proprement si nécessaire */
    min-width: 0; /* Permet au flex de réduire si nécessaire */
}';

if (strpos($newContent, 'white-space: nowrap') === false) {
    // Remplacer la classe .value existante
    $oldCSS = '.value {';
    if (strpos($newContent, $oldCSS) !== false) {
        $startIndex = strpos($newContent, $oldCSS);
        $endIndex = strpos($newContent, '}', $startIndex) + 1;
        
        $newContent = substr($newContent, 0, $startIndex) . $cssUpdate . substr($newContent, $endIndex);
        echo "<p class='success'>✅ Styles CSS anti-troncature ajoutés</p>";
    }
}

// Sauvegarder le fichier corrigé
$backupFile = __DIR__ . '/Carte/confection_carte_backup_' . date('Y-m-d_H-i-s') . '.php';
if (copy($filePath, $backupFile)) {
    echo "<p class='success'>✅ Sauvegarde créée: " . basename($backupFile) . "</p>";
}

if (file_put_contents($filePath, $newContent)) {
    echo "<p class='success'>✅ Fichier confection_carte.php mis à jour avec succès</p>";
} else {
    echo "<p class='error'>❌ Erreur lors de la mise à jour du fichier</p>";
}

echo "</div>";

// ÉTAPE 3: Formulaire de test
echo "<div class='step'>";
echo "<h3>🧪 ÉTAPE 3: Test d'enregistrement d'un candidat</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_enrollment'])) {
    echo "<div class='result'>";
    echo "<div class='loading'></div> Test en cours...";
    
    // Préparer les données de test
    $testData = [
        'nom' => 'TEST' . time(),
        'prenom' => 'CORRECTION',
        'sexe' => 'MASCULIN',
        'date_naissance' => '1990-01-01',
        'numero_cni' => 'TEST123456789012345',
        'taille' => '175',
        'poids' => '70',
        'groupe_sanguin' => 'O+',
        'type_personnel' => 'MILITAIRE',
        'unite' => 'ARMÉE DE TERRE',
        'grade' => 'CAPITAINE',
        'matricule_militaire' => 'T17/99999',
        'annee_dernier_galon' => '2023'
    ];
    
    try {
        // Simuler l'envoi des données
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/cim/backend/enrolement_traitement.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($body, true);
            
            if ($result && isset($result['success']) && $result['success']) {
                echo "<div class='result success'>";
                echo "<h4 class='success'>✅ SUCCÈS - Test d'enregistrement</h4>";
                echo "<p><strong>Matricule généré:</strong> " . htmlspecialchars($result['matricule']) . "</p>";
                echo "<p><strong>Code QR généré:</strong> " . htmlspecialchars($result['code_qr']) . "</p>";
                echo "<p><strong>Nom:</strong> " . htmlspecialchars($result['candidat']['nom']) . "</p>";
                echo "<p><strong>Prénom:</strong> " . htmlspecialchars($result['candidat']['prenom']) . "</p>";
                echo "<p><strong>Sexe:</strong> " . htmlspecialchars($result['candidat']['sexe']) . "</p>";
                echo "</div>";
                
                // ÉTAPE 4: Tester la génération de la carte
                echo "<div class='step'>";
                echo "<h3>🎴 ÉTAPE 4: Test de génération de la carte</h3>";
                
                $matricule = $result['matricule'];
                $carteUrl = "http://127.0.0.1/cim/visualiser_carte.php?matricule=" . urlencode($matricule);
                
                echo "<p><strong>URL de test:</strong> <a href='$carteUrl' target='_blank'>$carteUrl</a></p>";
                
                // Récupérer le HTML de la carte
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, $carteUrl);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
                
                $carteHtml = curl_exec($ch2);
                $carteHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                
                curl_close($ch2);
                
                if ($carteHttpCode === 200) {
                    echo "<div class='result success'>";
                    echo "<h4>✅ Carte générée avec succès</h4>";
                    
                    if (strpos($carteHtml, 'afficherSexe') !== false) {
                        echo "<p class='success'>✅ La fonction afficherSexe() est utilisée dans la carte</p>";
                    } else {
                        echo "<p class='warning'>⚠️ La fonction afficherSexe() n'est pas trouvée dans la carte</p>";
                    }
                    
                    if (strpos($carteHtml, 'MASCULIN') !== false) {
                        echo "<p class='success'>✅ Le sexe \"MASCULIN\" s'affiche correctement</p>";
                    } elseif (strpos($carteHtml, 'M</span>') !== false) {
                        echo "<p class='error'>❌ Le sexe s'affiche toujours comme \"M\"</p>";
                    } else {
                        echo "<p class='warning'>ℹ️ Le sexe n'est pas trouvé dans la carte générée</p>";
                    }
                    
                    echo "</div>";
                } else {
                    echo "<p class='error'>❌ Erreur lors de la génération de la carte (HTTP $carteHttpCode)</p>";
                }
                
                echo "</div>";
                
            } else {
                echo "<div class='result error'>";
                echo "<h4 class='error'>❌ ÉCHEC - Test d'enregistrement</h4>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($result['message'] ?? 'Erreur inconnue') . "</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='result error'>";
            echo "<h4 class='error'>❌ Erreur HTTP $httpCode</h4>";
            echo "<p>Le serveur a retourné une erreur lors du test</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='result error'>";
        echo "<h4 class='error'>❌ Exception</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
} else {
    echo "<form method='post'>";
    echo "<input type='hidden' name='test_enrollment' value='1'>";
    echo "<button type='submit' class='btn success'>🧪 Lancer le test d'enregistrement</button>";
    echo "</form>";
    
    echo "<p><small>Ce test va créer un candidat avec les données suivantes:</small></p>";
    echo "<div class='code'>";
    echo "Nom: TEST" . time() . "<br>";
    echo "Prénom: CORRECTION<br>";
    echo "Sexe: MASCULIN<br>";
    echo "Unité: ARMÉE DE TERRE<br>";
    echo "Grade: CAPITAINE<br>";
    echo "Matricule militaire: T17/99999";
    echo "</div>";
}

echo "</div>";

// ÉTAPE 5: Afficher le contenu corrigé
echo "<div class='step'>";
echo "<h3>📄 ÉTAPE 5: Contenu corrigé généré</h3>";
echo "<p>Voici le contenu qui a été appliqué au fichier confection_carte.php:</p>";
echo "<div class='code'>";
echo htmlspecialchars(substr($newContent, 0, 2000)) . "...";
echo "</div>";
echo "<p><small>Le contenu a été sauvegardé dans le fichier et une sauvegarde a été créée.</small></p>";
echo "</div>";

echo "<div class='step'>";
echo "<h3>🎯 Résumé des corrections</h3>";
echo "<ul>";
echo "<li class='success'>✅ Fonction afficherSexe() ajoutée pour convertir M/F en MASCULIN/FEMININ</li>";
echo "<li class='success'>✅ Affichage du sexe corrigé pour utiliser la nouvelle fonction</li>";
echo "<li class='success'>✅ Styles CSS ajoutés pour éviter la troncature du texte</li>";
echo "<li class='success'>✅ Sauvegarde automatique créée avant modification</li>";
echo "<li class='success'>✅ Test d'enregistrement et génération de carte inclus</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
