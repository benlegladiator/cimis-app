<?php
require_once 'config.php';
require_once 'qrcode_generator.php';

echo "<h2>GÉNÉRATION DES CODES QR POUR MILITAIRES EXISTANTS</h2>";

try {
    $pdo->beginTransaction();
    
    // Récupérer tous les militaires sans code QR
    $stmt = $pdo->prepare("SELECT id, matricule, matricule_militaire, nom, prenom, unite, grade FROM candidat WHERE (code_qr IS NULL OR code_qr = '') AND matricule_militaire IS NOT NULL AND matricule_militaire != '' ORDER BY unite, grade");
    $stmt->execute();
    $militaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_qr_generated = 0;
    $total_errors = 0;
    
    if (empty($militaires)) {
        echo "<p style='color: #4facfe;'>✅ Tous les militaires ont déjà des codes QR!</p>";
    } else {
        echo "<h3>Militaires trouvés sans code QR : " . count($militaires) . "</h3>";
        
        foreach ($militaires as $militaire) {
            try {
                // Générer le code QR basé sur le matricule militaire
                $matricule_militaire = $militaire['matricule_militaire'];
                $code_qr = generateQRCodeForMatricule($matricule_militaire);
                
                // Mettre à jour la base de données
                $update_stmt = $pdo->prepare("UPDATE candidat SET code_qr = ? WHERE id = ?");
                $update_stmt->execute([$code_qr, $militaire['id']]);
                
                echo "✅ QR généré pour: <strong>{$militaire['matricule']}</strong> - {$militaire['nom']} {$militaire['prenom']} ({$militaire['unite']})<br>";
                echo "   🎖️ Matricule: $matricule_militaire<br>";
                echo "   🔲 QR Code: $code_qr<br><br>";
                
                $total_qr_generated++;
                
            } catch (Exception $e) {
                echo "❌ Erreur pour {$militaire['matricule']}: " . $e->getMessage() . "<br>";
                $total_errors++;
            }
        }
    }
    
    // Vérifier les militaires sans matricule militaire
    $stmt_no_matricule = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE (matricule_militaire IS NULL OR matricule_militaire = '') AND unite != 'CIVIL'");
    $stmt_no_matricule->execute();
    $no_matricule_count = $stmt_no_matricule->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($no_matricule_count > 0) {
        echo "<h3 style='color: #fa709a;'>⚠️ Militaires sans matricule militaire: $no_matricule_count</h3>";
        
        // Générer des matricules militaires pour ceux qui n'en ont pas
        $stmt_missing = $pdo->prepare("SELECT id, matricule, nom, prenom, unite, grade FROM candidat WHERE (matricule_militaire IS NULL OR matricule_militaire = '') AND unite != 'CIVIL' ORDER BY unite, grade");
        $stmt_missing->execute();
        $missing_militaires = $stmt_missing->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($missing_militaires as $militaire) {
            try {
                // Générer un matricule militaire selon l'unité
                $prefixes = [
                    'GENDARMERIE NATIONALE' => 'GND',
                    'ARMÉE DE TERRE' => 'AT',
                    'MARINE NATIONALE' => 'MN',
                    'ARMÉE DE L\'AIR' => 'AA'
                ];
                
                $unite = $militaire['unite'];
                $prefix = $prefixes[$unite] ?? 'MIL';
                $numero = rand(10000, 99999);
                $matricule_militaire = $prefix . '/' . $numero;
                
                // Générer le code QR
                $code_qr = generateQRCodeForMatricule($matricule_militaire);
                
                // Mettre à jour la base de données
                $update_stmt = $pdo->prepare("UPDATE candidat SET matricule_militaire = ?, code_qr = ? WHERE id = ?");
                $update_stmt->execute([$matricule_militaire, $code_qr, $militaire['id']]);
                
                echo "🔧 Matricule et QR générés pour: <strong>{$militaire['matricule']}</strong> - {$militaire['nom']} {$militaire['prenom']} ({$militaire['unite']})<br>";
                echo "   🎖️ Nouveau matricule: $matricule_militaire<br>";
                echo "   🔲 QR Code: $code_qr<br><br>";
                
                $total_qr_generated++;
                
            } catch (Exception $e) {
                echo "❌ Erreur pour {$militaire['matricule']}: " . $e->getMessage() . "<br>";
                $total_errors++;
            }
        }
    }
    
    $pdo->commit();
    
    echo "<h2>RÉSUMÉ</h2>";
    echo "<strong>Total codes QR générés:</strong> $total_qr_generated<br>";
    echo "<strong>Erreurs:</strong> $total_errors<br>";
    echo "<strong>Opération terminée avec succès!</strong><br>";
    
    // Vérifier le répertoire qrcodes
    $qrcodes_dir = __DIR__ . '/../img/qrcodes/';
    if (is_dir($qrcodes_dir)) {
        $files = glob($qrcodes_dir . '*_qr.png');
        echo "<strong>Fichiers QR dans img/qrcodes/:</strong> " . count($files) . "<br>";
    }
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "Erreur: " . $e->getMessage();
}
?>
