<?php
require_once 'config.php';
require_once 'qrcode_generator.php';

// Photos disponibles
$photos = [
    'img/1KRISS.PNG',
    'img/1YANNICK.PNG', 
    'img/ONANA.PNG',
    'img/YANNICK.PNG',
    'img/KRISS.PNG',
    'img/GRACE.PNG',
    'img/ben.PNG'
];

// Grades complets par unité (basés sur le système existant)
$grades_by_unite = [
    'GENDARMERIE NATIONALE' => [
        // OFFICIERS GÉNÉRAUX (4)
        'Général d\'Armée',
        'Général de Corps d\'Armée',
        'Général de Division',
        'Général de Brigade',
        
        // OFFICIERS SUPÉRIEURS (3)
        'Colonel',
        'Lieutenant-Colonel',
        'Chef d\'Escadron (C/E)',
        
        // OFFICIERS SUBALTERNES (3)
        'Capitaine',
        'Lieutenant',
        'Sous-Lieutenant',
        
        // ASPIRANTS (1)
        'Aspirant',
        
        // SOUS-OFFICIERS (3)
        'Adjudant-Chef Major',
        'Adjudant-Chef',
        'Adjudant',
        
        // SOUS-OFFICIERS SUBALTERNES (3)
        'Maréchal des Logis-Chef (MDL/C)',
        'Maréchal des Logis',
        'Gendarme-Major',
        'Gendarme',
        'Élève-Gendarme'
    ],
    'ARMÉE DE TERRE' => [
        // OFFICIERS GÉNÉRAUX (4)
        'Général d\'Armée',
        'Général de Corps d\'Armée',
        'Général de Division',
        'Général de Brigade',
        
        // OFFICIERS SUPÉRIEURS (3)
        'Colonel',
        'Lieutenant-Colonel',
        'Chef de Bataillon',
        
        // OFFICIERS SUBALTERNES (3)
        'Capitaine',
        'Lieutenant',
        'Sous-Lieutenant',
        
        // ASPIRANTS (1)
        'Aspirant',
        
        // SOUS-OFFICIERS (4)
        'Adjudant-Chef Major',
        'Adjudant-Chef',
        'Adjudant',
        'Major',
        
        // SOUS-OFFICIERS SUBALTERNES (4)
        'Sergent-Chef',
        'Sergent',
        'Caporal-Chef',
        'Caporal',
        
        // MILITAIRES DU RANG (2)
        'Soldat de 1ère Classe',
        'Soldat de 2ème Classe'
    ],
    'MARINE NATIONALE' => [
        // OFFICIERS GÉNÉRAUX (4)
        'Amiral d\'Escadre',
        'Vice-Amiral d\'Escadre',
        'Contre-Amiral',
        'Vice-Amiral',
        
        // OFFICIERS SUPÉRIEURS (3)
        'Capitaine de Vaisseau',
        'Capitaine de Frégate',
        'Capitaine de Corvette',
        
        // OFFICIERS SUBALTERNES (3)
        'Lieutenant de Vaisseau',
        'Enseigne de Vaisseau de 1ère Classe',
        'Enseigne de Vaisseau de 2ème Classe',
        
        // ASPIRANTS ET ÉLÈVES OFFICIERS (1)
        'Aspirant',
        
        // OFFICIERS MARINERS (SOUS-OFFICIERS) (5)
        'Maître Principal',
        'Premier Maître',
        'Maître',
        'Second Maître',
        'Quartier-Maître de 1ère Classe',
        'Quartier-Maître de 2ème Classe',
        'Matelot de 1ère Classe',
        'Matelot de 2ème Classe',
        'Matelot'
    ],
    'ARMÉE DE L\'AIR' => [
        // OFFICIERS GÉNÉRAUX (4)
        'Général d\'Armée Aérienne',
        'Général de Corps Aérien',
        'Général de Division Aérienne',
        'Général de Brigade Aérienne',
        
        // OFFICIERS SUPÉRIEURS (3)
        'Colonel',
        'Lieutenant-Colonel',
        'Commandant',
        
        // OFFICIERS SUBALTERNES (3)
        'Capitaine',
        'Lieutenant',
        'Sous-Lieutenant',
        
        // ASPIRANTS (1)
        'Aspirant',
        
        // SOUS-OFFICIERS (4)
        'Adjudant-Chef Major',
        'Adjudant-Chef',
        'Adjudant',
        'Major',
        
        // SOUS-OFFICIERS SUBALTERNES (4)
        'Sergent-Chef',
        'Sergent',
        'Caporal-Chef',
        'Caporal',
        
        // MILITAIRES DU RANG (2)
        'Soldat de 1ère Classe',
        'Soldat de 2ème Classe'
    ]
];

// Noms africains variés
$noms = [
    'KOUAMÉ', 'TOURÉ', 'KONAN', 'KOUADIO', 'YAO', 'KOFFI', 'KOUAKOU', 'GBANÉ',
    'BAMBA', 'DIALLO', 'BA', 'TRAORÉ', 'SANGARÉ', 'CISSÉ', 'CAMARA', 'KEÏTA',
    'TOURÉ', 'DIAKITÉ', 'FOFANA', 'COULIBALY', 'DOUMBIA', 'KANTÉ', 'SISSOKO',
    'NGUESSAN', 'ASSI', 'KOUASSI', 'ALLASSAN', 'MAMADOU', 'IBRAHIMA',
    'MOUSSA', 'OUMAR', 'ABDOULAYE', 'SÉKOU', 'LAMINE', 'CHEICK',
    'MOHAMED', 'BOUBACAR', 'ALI', 'BACHIR', 'YACOUBA', 'SOULEYMANE',
    'MAÏGA', 'DICKO', 'DRABO', 'SAGARA', 'SAMAKÉ', 'DIAWARA',
    'ONANA', 'ETOGA', 'FOKOU', 'KAMGA', 'ZONGO', 'OUÉDRAOGO',
    'TAPSOBA', 'SOMDA', 'YAMEOGO', 'KABORÉ', 'SANKARA', 'COMPAORÉ',
    'NKOU', 'TCHUENTE', 'MBAPPI', 'KAMDEM', 'NGUEGUIM', 'OUMAROU',
    'BASSOLE', 'TCHAMBA', 'FOUDA', 'MBIAO', 'KOUAMÉ', 'ASSAMOI',
    'KOUASSI', 'KOFFI', 'YAO', 'GBANÉ', 'KOUAKOU', 'KONAN', 'TOURÉ'
];

// Prénoms africains variés
$prenoms = [
    'MAMADOU', 'IBRAHIMA', 'MOUSSA', 'OUMAR', 'ABDOULAYE', 'SÉKOU', 'LAMINE',
    'CHEICK', 'MOHAMED', 'BOUBACAR', 'ALI', 'BACHIR', 'YACOUBA', 'SOULEYMANE',
    'ADAMA', 'BINTOU', 'AÏSSATOU', 'MARIAM', 'FATOU', 'ASTOU', 'KHADIDJA',
    'AMINATA', 'AÏCHA', 'FATIMATA', 'OUMOU', 'ASSAN', 'MAMADOU', 'IBRAHIMA',
    'JEAN-CLAUDE', 'PIERRE-ANDRÉ', 'JEAN-MARC', 'MARIE-CLAUDE', 'MICHEL',
    'PAUL', 'JEAN', 'PETER', 'ROGER', 'ALAIN', 'PATRICE', 'GEORGES'
];

// Lieux de naissance africains
$lieux = [
    'YAOUNDÉ', 'DOUALA', 'BOUAKE', 'ABIDJAN', 'BAMAKO', 'OUAGADOUGOU',
    'DAKAR', 'THIÈS', 'KAOLACK', 'SAINT-LOUIS', 'LOME', 'KARA',
    'COTONOU', 'PORTO-NOVO', 'PARAKOU', 'NIAMEY', 'ZINDER', 'MARADI',
    'CONAKRY', 'KANKAN', 'KISSIDOUGOU', 'FREETOWN', 'MONROVIA',
    'ACCRA', 'KUMASI', 'TAMALE', 'OUAGADOUGOU', 'BOBO-DIOULASSO'
];

// Fonction pour générer un matricule militaire selon l'unité
function generateMatriculeMilitaire($unite, $grade) {
    $prefixes = [
        'GENDARMERIE NATIONALE' => 'GND',
        'ARMÉE DE TERRE' => 'AT',
        'MARINE NATIONALE' => 'MN',
        'ARMÉE DE L\'AIR' => 'AA'
    ];
    
    $prefix = $prefixes[$unite] ?? 'MIL';
    $numero = rand(10000, 99999);
    return $prefix . '/' . $numero;
}

echo "<h2>GÉNÉRATION DES MILITAIRES</h2>";

try {
    $pdo->beginTransaction();
    
    $total_militaires = 0;
    $matricule_counter = 2000; // Commencer à CIM-2000 pour éviter les conflits
    
    foreach ($grades_by_unite as $unite => $grades) {
        echo "<h3>Unité: $unite</h3>";
        
        foreach ($grades as $grade) {
            // Créer un militaire pour chaque grade avec informations variées
            $nom = $noms[array_rand($noms)];
            $prenom = $prenoms[array_rand($prenoms)];
            $matricule = 'CIM-' . $matricule_counter++;
            $matricule_militaire = generateMatriculeMilitaire($unite, $grade);
            $photo = $photos[array_rand($photos)];
            $lieu_naissance = $lieux[array_rand($lieux)];
            
            // Générer le code QR basé sur le matricule militaire
            $code_qr = generateQRCodeForMatricule($matricule_militaire);
            
            // Générer une date de naissance aléatoire (1970-1995)
            $annee_naissance = rand(1970, 1995);
            $mois_naissance = rand(1, 12);
            $jour_naissance = rand(1, 28);
            $date_naissance = sprintf("%04d-%02d-%02d", $annee_naissance, $mois_naissance, $jour_naissance);
            
            // Générer une date de délivrance CNI aléatoire (2015-2023)
            $annee_cni = rand(2015, 2023);
            $mois_cni = rand(1, 12);
            $jour_cni = rand(1, 28);
            $date_delivrance_cni = sprintf("%04d-%02d-%02d", $annee_cni, $mois_cni, $jour_cni);
            
            // Lieu délivrance CNI
            $lieu_delivrance_cni = $lieux[array_rand($lieux)];
            
            // Année de promotion (2018-2023)
            $annee_promotion = rand(2018, 2023);
            
            // Générer un numéro CNI aléatoire
            $numero_cni = '';
            for ($i = 0; $i < 12; $i++) {
                $numero_cni .= rand(0, 9);
            }
            
            // Vérifier si le matricule existe déjà
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM candidat WHERE matricule = ?");
            $check_stmt->execute([$matricule]);
            
            if ($check_stmt->fetchColumn() == 0) {
                // Insérer le nouveau militaire
                $insert_stmt = $pdo->prepare("
                    INSERT INTO candidat (matricule, matricule_militaire, nom, prenom, grade, unite, photo, date_naissance, lieu_naissance, 
                    numero_cni, date_enrolement, suspendus, statut_militaire, source_system, supprimer, code_qr)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insert_stmt->execute([
                    $matricule,
                    $matricule_militaire,
                    $nom,
                    $prenom,
                    $grade,
                    $unite,
                    $photo,
                    $date_naissance,
                    $lieu_naissance,
                    $numero_cni,
                    $date_naissance, // Utiliser date_naissance comme date_enrolement
                    0, // Non suspendu
                    'ACTIF', // Statut militaire
                    'CIMIS', // Source système
                    0, // Non supprimé
                    $code_qr // Code QR généré
                ]);
                
                echo "✅ Créé: $matricule - $nom $prenom - $grade - $unite<br>";
                echo "   🎖️ Matricule militaire: $matricule_militaire<br>";
                echo "   📍 Né le: $date_naissance à $lieu_naissance<br>";
                echo "   🆔 CNI: $numero_cni<br>";
                echo "   🔲 QR Code: $code_qr<br>";
                echo "   📅 Promotion: $annee_promotion<br><br>";
                $total_militaires++;
            } else {
                echo "⚠️ Existe déjà: $matricule<br>";
            }
        }
    }
    
    $pdo->commit();
    
    echo "<h2>RÉSUMÉ</h2>";
    echo "<strong>Total militaires créés:</strong> $total_militaires<br>";
    echo "<strong>Opération terminée avec succès!</strong>";
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo "Erreur: " . $e->getMessage();
}
?>
