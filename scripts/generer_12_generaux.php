<?php
// Script pour générer 12 généraux (4 par corps d'armée) dans le système CIMIS

require_once '../backend/config.php';
require_once '../backend/phpqrcode/qrlib.php';

// Noms africains authentiques pour les généraux
$noms_africains = [
    'MBOCK', 'NGUESSAN', 'KOUAME', 'DIOP', 'TRAORE', 'BAH',
    'OUATTARA', 'KONE', 'TOURE', 'DIALLO', 'SOW', 'CAMARA'
];

$prenoms_africains = [
    'Abdoulaye', 'Bakary', 'Cheikh', 'Mamadou', 'Ibrahim', 'Oumar',
    'Youssef', 'Mohamed', 'Ali', 'Hassan', 'Seydou', 'Lamine'
];

$villes_cameroun = [
    'YAOUNDE', 'DOUALA', 'GAROUA', 'MAROUA', 'BAFOUSSAM', 'BERTOUA',
    'EDEA', 'KUMBA', 'NKONGSAMBA', 'BAMENDA', 'LIMBE', 'BUEA'
];

// Grades de généraux par corps
$grades_generaux = [
    'ARMÉE DE TERRE' => [
        ['grade' => 'GENERAL D ARMEE', 'ordre' => 1],
        ['grade' => 'GENERAL DE CORPS D ARMEE', 'ordre' => 2],
        ['grade' => 'GENERAL DE DIVISION', 'ordre' => 3],
        ['grade' => 'GENERAL DE BRIGADE', 'ordre' => 4]
    ],
    'MARINE NATIONALE' => [
        ['grade' => 'AMIRAL', 'ordre' => 1],
        ['grade' => 'VICE AMIRAL D ESCADRE', 'ordre' => 2],
        ['grade' => 'VICE AMIRAL', 'ordre' => 3],
        ['grade' => 'CONTRE AMIRAL', 'ordre' => 4]
    ],
    'ARMÉE DE L\'AIR' => [
        ['grade' => 'GENERAL D ARMEE AERIENNE', 'ordre' => 1],
        ['grade' => 'GENERAL DE CORPS D ARMEE AERIENNE', 'ordre' => 2],
        ['grade' => 'GENERAL DE DIVISION AERIENNE', 'ordre' => 3],
        ['grade' => 'GENERAL DE BRIGADE AERIENNE', 'ordre' => 4]
    ],
    'GENDARMERIE' => [
        ['grade' => 'GENERAL D ARMEE GENDARMERIE', 'ordre' => 1],
        ['grade' => 'GENERAL DE CORPS D ARMEE GENDARMERIE', 'ordre' => 2],
        ['grade' => 'GENERAL DE DIVISION GENDARMERIE', 'ordre' => 3],
        ['grade' => 'GENERAL DE GENDARMERIE', 'ordre' => 4]
    ]
];

try {
    echo "🎯 GÉNÉRATION DES 12 GÉNÉRAUX CIMIS\n";
    echo "===================================\n\n";
    
    $generaux_crees = [];
    $compteur_total = 0;
    
    foreach ($grades_generaux as $corps => $grades) {
        echo "📋 Corps: $corps\n";
        echo str_repeat("-", 40) . "\n";
        
        foreach ($grades as $index => $grade_info) {
            $compteur_total++;
            
            // Sélectionner un nom et prénom aléatoire
            $nom = $noms_africains[array_rand($noms_africains)];
            $prenom = $prenoms_africains[array_rand($prenoms_africains)];
            $lieu_naissance = $villes_cameroun[array_rand($villes_cameroun)];
            
            // Générer une date de naissance réaliste (45-65 ans pour les généraux)
            $annee_naissance = rand(1959, 1979);
            $mois_naissance = rand(1, 12);
            $jour_naissance = rand(1, 28);
            $date_naissance = sprintf("%04d-%02d-%02d", $annee_naissance, $mois_naissance, $jour_naissance);
            
            // Générer les matricules
            $matricule_cim = 'CIM-' . str_pad($compteur_total + 50000, 5, '0', STR_PAD_LEFT);
            $matricule_militaire = 'GEN/' . date('Y') . '/' . str_pad($compteur_total, 3, '0', STR_PAD_LEFT);
            
            // Données du général
            $general_data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'date_naissance' => $date_naissance,
                'lieu_naissance' => $lieu_naissance,
                'sexe' => 'MASCULIN',
                'matricule' => $matricule_cim,
                'matricule_militaire' => $matricule_militaire,
                'unite' => $corps,
                'grade' => $grade_info['grade'],
                'date_enrolement' => date('Y-m-d', strtotime('-20 years')),
                'statut_militaire' => 'ACTIF',
                'supprimer' => 0,
                'supprimer_par' => 'ADMIN_CIMIS',
                'date_suppression' => date('Y-m-d H:i:s')
            ];
            
            // Vérifier si le matricule existe déjà
            $check_sql = "SELECT id FROM candidat WHERE matricule = :matricule OR matricule_militaire = :matricule_militaire";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([
                ':matricule' => $general_data['matricule'],
                ':matricule_militaire' => $general_data['matricule_militaire']
            ]);
            
            if ($check_stmt->rowCount() > 0) {
                echo "⚠️ Matricule existant, génération d'un nouveau...\n";
                $general_data['matricule'] = 'CIM-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
                $general_data['matricule_militaire'] = 'GEN/' . date('Y') . '/' . rand(100, 999);
            }
            
            // Insérer le général dans la base de données
            $sql = "INSERT INTO candidat (
                nom, prenom, date_naissance, lieu_naissance, sexe, matricule, matricule_militaire,
                unite, grade, date_enrolement, statut_militaire, supprimer, supprimer_par, date_suppression
            ) VALUES (
                :nom, :prenom, :date_naissance, :lieu_naissance, :sexe, :matricule, :matricule_militaire,
                :unite, :grade, :date_enrolement, :statut_militaire, :supprimer, :supprimer_par, :date_suppression
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($general_data);
            
            $id_general = $pdo->lastInsertId();
            
            // Générer le code QR
            
            // Données pour le QR code
            $qr_data = [
                'matricule' => $general_data['matricule'],
                'nom' => $general_data['nom'],
                'prenom' => $general_data['prenom'],
                'grade' => $general_data['grade'],
                'unite' => $general_data['unite'],
                'timestamp' => time(),
                'signature' => hash('sha256', $general_data['matricule'] . 'CIMIS2026')
            ];
            
            // Sauvegarder le QR code
            $qr_filename = 'qrcodes/qr_' . $general_data['matricule'] . '.png';
            $qr_path = '../img/' . $qr_filename;
            
            // Créer le répertoire si nécessaire
            if (!is_dir('../img/qrcodes')) {
                mkdir('../img/qrcodes', 0755, true);
            }
            
            // Générer le QR code avec PHP QR Code
            QRcode::png(json_encode($qr_data), $qr_path, QR_ECLEVEL_M, 8);
            
            // Mettre à jour le chemin du QR code dans la base de données
            $update_sql = "UPDATE candidat SET code_qr = :code_qr WHERE id = :id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                ':code_qr' => $qr_filename,
                ':id' => $id_general
            ]);
            
            // Générer la photo du général
            $photo_filename = $general_data['matricule'] . '_' . time() . '.png';
            $photo_path = '../img/candidats/' . $photo_filename;
            
            // Créer une image par défaut pour le général (fond bleu marine avec texte)
            $width = 200;
            $height = 250;
            $image = imagecreatetruecolor($width, $height);
            
            // Couleurs
            $bg_color = imagecolorallocate($image, 0, 50, 100); // Bleu marine
            $text_color = imagecolorallocate($image, 255, 255, 255); // Blanc
            $border_color = imagecolorallocate($image, 200, 200, 200); // Gris clair
            
            // Remplir le fond
            imagefill($image, 0, 0, $bg_color);
            
            // Ajouter une bordure
            imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);
            
            // Ajouter le texte (grade et nom)
            $font_size = 3;
            $grade_text = strtoupper(substr($general_data['grade'], 0, 15));
            $name_text = strtoupper($general_data['nom'] . ' ' . $general_data['prenom']);
            
            // Centrer le texte
            $grade_x = ($width - strlen($grade_text) * imagefontwidth($font_size)) / 2;
            $grade_y = 50;
            
            $name_x = ($width - strlen($name_text) * imagefontwidth($font_size)) / 2;
            $name_y = 100;
            
            imagestring($image, $font_size, $grade_x, $grade_y, $grade_text, $text_color);
            imagestring($image, $font_size, $name_x, $name_y, $name_text, $text_color);
            
            // Ajouter le matricule
            $matricule_text = $general_data['matricule'];
            $matricule_x = ($width - strlen($matricule_text) * imagefontwidth($font_size)) / 2;
            $matricule_y = 150;
            imagestring($image, $font_size, $matricule_x, $matricule_y, $matricule_text, $text_color);
            
            // Ajouter "GENERAL" au bas
            $general_text = "GENERAL";
            $general_x = ($width - strlen($general_text) * imagefontwidth($font_size)) / 2;
            $general_y = 200;
            imagestring($image, $font_size, $general_x, $general_y, $general_text, $text_color);
            
            // Sauvegarder l'image
            imagepng($image, $photo_path);
            imagedestroy($image);
            
            // Mettre à jour le chemin de la photo dans la base de données
            $photo_update_sql = "UPDATE candidat SET photo = :photo WHERE id = :id";
            $photo_update_stmt = $pdo->prepare($photo_update_sql);
            $photo_update_stmt->execute([
                ':photo' => $photo_filename,
                ':id' => $id_general
            ]);
            
            echo "🎫 Général créé avec succès\n";
            echo "✅ Général créé:\n";
            echo "   🎖️  Grade: " . $grade_info['grade'] . "\n";
            echo "   👤 Nom: $nom $prenom\n";
            echo "   📅 Naissance: $date_naissance ($lieu_naissance)\n";
            echo "   🏷️  Matricule CIM: $matricule_cim\n";
            echo "   🎫 Matricule militaire: $matricule_militaire\n";
            echo "   🏢 Corps: $corps\n";
            echo "   🔲 QR Code: $qr_filename\n";
            echo "   🆔 ID: $id_general\n";
            echo "\n";
            
            $generaux_crees[] = [
                'id' => $id_general,
                'nom' => $nom,
                'prenom' => $prenom,
                'grade' => $grade_info['grade'],
                'corps' => $corps,
                'matricule' => $matricule_cim
            ];
        }
        echo "\n";
    }
    
    // Résumé final
    echo "🎉 RÉSUMÉ DE LA GÉNÉRATION\n";
    echo "==========================\n";
    echo "📊 Total des généraux créés: " . count($generaux_crees) . "\n";
    echo "📋 Répartition par corps:\n";
    
    $corps_count = [];
    foreach ($generaux_crees as $general) {
        $corps_count[$general['corps']][] = $general;
    }
    
    foreach ($corps_count as $corps => $generaux) {
        echo "   🏢 $corps: " . count($generaux) . " généraux\n";
        foreach ($generaux as $general) {
            echo "      🎖️  " . $general['grade'] . " - " . $general['nom'] . " " . $general['prenom'] . "\n";
        }
    }
    
    echo "\n🔗 Actions disponibles:\n";
    echo "   📱 Visualiser les cartes via: impression.php\n";
    echo "   🔍 Rechercher par grade ou corps\n";
    echo "   🎯 Imprimer les cartes militaires\n";
    echo "   📊 Consulter les statistiques\n";
    
    echo "\n✨ Les 12 généraux sont maintenant intégrés dans le système CIMIS!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la génération: " . $e->getMessage() . "\n";
    echo "📍 Détails: " . $e->getTraceAsString() . "\n";
}
?>
