<?php
// Carte/confection_carte.php - Moteur de rendu portable des cartes PVC

// Fonction pour obtenir une photo par défaut aléatoire
function getDefaultPhoto() {
    $default_photos = [
        'img/1ONANA.PNG',
        'img/1YANNICK.PNG', 
        'img/GRACE.PNG',
        'img/KRISS.PNG',
        'img/ONANA.PNG',
        'img/YANNICK.PNG'
    ];
    
    // Sélectionner une photo aléatoirement
    $random_index = array_rand($default_photos);
    return $default_photos[$random_index];
}

// Fonction pour afficher le sexe correctement
function afficherSexe($sexe) {
    // Si le sexe est déjà en format complet, le retourner tel quel
    if (in_array(strtoupper($sexe), ['MASCULIN', 'FEMININ'])) {
        return strtoupper($sexe);
    }
    
    // Si c'est juste "M" ou "F", convertir en format complet
    switch (strtoupper($sexe)) {
        case 'M':
            return 'MASCULIN';
        case 'F':
            return 'FEMININ';
        default:
            return strtoupper($sexe); // Retourner le texte original par défaut
    }
}

// Fonction pour déterminer si un grade est un officier
// Fonction pour déterminer si un grade est un officier
function estOfficier($grade) {
    $grade_normalise = strtolower(trim($grade));
    // Normaliser les accents
    $grade_normalise = str_replace(
        ['é','è','ê','ë','à','â','ä','î','ï','ô','ö','ù','û'],
        ['e','e','e','e','a','a','a','i','i','o','o','u','u'],
        $grade_normalise
    );

    // Liste des grades d'officiers (seulement officiers généraux, supérieurs, subalternes, aspirants)
    $grades_officiers = [
        // OFFICIERS GENERAUX
        'general d armee','general d armee aerienne',
        'general de corps d armee','general de corps d armee aerienne',
        'general de division','general de division aerienne',
        'general de brigade','general de brigade aerienne',
        'general de gendarmerie',
        'amiral d escadre','vice amiral d escadre','vice amiral','contre amiral',

        // OFFICIERS SUPERIEURS
        'colonel','lieutenant colonel','chef d escadron','chef de bataillon','commandant',
        'capitaine de vaisseau','capitaine de fregate','capitaine de corvette',

        // OFFICIERS SUBALTERNES
        'capitaine','lieutenant','sous lieutenant',
        'lieutenant de vaisseau','enseigne de vaisseau de 1ere classe','enseigne de vaisseau de 2eme classe',

        // ASPIRANTS
        'aspirant'
    ];

    return in_array($grade_normalise, $grades_officiers);
}

// Fonction pour obtenir la signature selon le grade
function getSignature($grade) {
    return estOfficier($grade) ? 'JOSEPH BETI ASSOMO - Ministre de la Défense'
                               : 'GOUFAN A RIM - Directeur des Ressources Humaines';
}


// Fonction pour obtenir l'image du grade
function getGradeImage($grade) {
    // --- SYSTÈME UNIFIÉ DE GALONS (images existantes dans img/galons/) ---
    $grade_images = [
        // OFFICIERS GENERAUX/AMIRAUX (4 images - mêmes étoiles pour tous corps)
        'general d armee' => 'img/galons/general_arme.png',
        'general d armee aerienne' => 'img/galons/general_arme.png',
        'general d armee aerienne' => 'img/galons/general_arme.png',
        'amiral' => 'img/galons/general_arme.png',
        // Variantes avec apostrophes (depuis enrolement.js)
        'general d\'armee' => 'img/galons/general_arme.png',
        'general d\'armee aerienne' => 'img/galons/general_arme.png',
        'general d\'armee aerienne' => 'img/galons/general_arme.png',
        
        'general de corps d armee' => 'img/galons/generale_corps.png',
        'general de corps d armee aerienne' => 'img/galons/generale_corps.png',
        'general de corps d armee aérienne' => 'img/galons/generale_corps.png',
        // Variantes avec apostrophes (depuis enrolement.js)
        'general de corps d\'armee' => 'img/galons/generale_corps.png',
        'general de corps d\'armee aerienne' => 'img/galons/generale_corps.png',
        'general de corps d\'armee aérienne' => 'img/galons/generale_corps.png',
        'general de corps aérien' => 'img/galons/generale_corps.png',
        'general de division aérienne' => 'img/galons/generale_division.png',
        'general de brigade aérienne' => 'img/galons/generale_brigade.png',
        'amiral d\'escadre' => 'img/galons/generale_corps.png',
        'vice amiral d\'escadre' => 'img/galons/generale_corps.png',
        
        // OFFICIERS SUPERIEURS avec apostrophes
        'chef d\'escadron' => 'img/galons/commandant.png',
        'chef de bataillon' => 'img/galons/commandant.png',
        'capitaine de vaisseau' => 'img/galons/colonel.png',
        'capitaine de frégate' => 'img/galons/colonel.png',
        'capitaine de corvette' => 'img/galons/colonel.png',
        'lieutenant de vaisseau' => 'img/galons/lieutenant_colonel.png',
        'lieutenant colonel' => 'img/galons/lieutenant_colonel.png',
        'commandant' => 'img/galons/commandant.png',
        
        // OFFICIERS SUBALTERNES avec apostrophes
        'sous lieutenant' => 'img/galons/sous_lieutenant.png',
        'lieutenant' => 'img/galons/lieutenant.png',
        'enseigne de vaisseau de 1ere classe' => 'img/galons/lieutenant.png',
        'enseigne de vaisseau de 2eme classe' => 'img/galons/sous_lieutenant.png',
        
        // SOUS-OFFICIERS avec apostrophes
        'adjudant chef major' => 'img/galons/adjudant_chef_major.png',
        'adjudant chef' => 'img/galons/adjudant_chef.png',
        'marechal des logis chef' => 'img/galons/sergent_chef.png',
        'marechal des logis' => 'img/galons/sergent.png',
        'gendarme major' => 'img/galons/gendarme_major.png',
        'sergent chef' => 'img/galons/sergent_chef.png',
        'caporal chef' => 'img/galons/caporal_che.png',
        'maitre principal major' => 'img/galons/adjudant_chef_major.png',
        'maitre principal' => 'img/galons/adjudant_chef.png',
        'premier maitre' => 'img/galons/adjudant_chef.png',
        'second maitre' => 'img/galons/adjudant.png',
        'quartier maitre de 1ere classe' => 'img/galons/caporal_che.png',
        'quartier maitre de 2eme classe' => 'img/galons/caporal.png',
        
        // MILITAIRES DU RANG avec apostrophes
        'soldat de 1ere classe' => 'img/galons/soldat_1er_classe.png',
        'soldat de 2eme classe' => 'img/galons/soldat_2eme_classe.png',
        'matelot de 1ere classe' => 'img/galons/soldat_1er_classe.png',
        'matelot de 2eme classe' => 'img/galons/soldat_2eme_classe.png',
        'eleve gendarme' => 'img/galons/soldat_2eme_classe.png',
        
        'general de division' => 'img/galons/generale_division.png',
        'general de division aerienne' => 'img/galons/generale_division.png',
        'vice amiral' => 'img/galons/generale_division.png',
        
        'general de brigade' => 'img/galons/generale_brigade.png',
        'general de brigade aerienne' => 'img/galons/generale_brigade.png',
        'contre amiral' => 'img/galons/generale_brigade.png',
        'general de gendarmerie' => 'img/galons/generale_brigade.png',
        
        // GÉNÉRAUX DE GENDARMERIE (grades spécifiques)
        'general d armee gendarmerie' => 'img/galons/general_arme.png',
        'general de corps d armee gendarmerie' => 'img/galons/generale_corps.png',
        'general de division gendarmerie' => 'img/galons/generale_division.png',
        
        // OFFICIERS SUPERIEURS (3 images)
        'colonel' => 'img/galons/colonel.png',
        'capitaine de vaisseau' => 'img/galons/colonel.png',
        
        'lieutenant colonel' => 'img/galons/lieutenant_colonel.png',
        'capitaine de fregate' => 'img/galons/lieutenant_colonel.png',
        
        'chef de bataillon' => 'img/galons/commandant.png',
        'commandant' => 'img/galons/commandant.png',
        'chef d escadron' => 'img/galons/commandant.png',
        'capitaine de corvette' => 'img/galons/commandant.png',
        
        // OFFICIERS SUBALTERNES (4 images)
        'capitaine' => 'img/galons/capitaine.png',
        'lieutenant de vaisseau' => 'img/galons/capitaine.png',
        
        'lieutenant' => 'img/galons/lieutenant.png',
        
        'sous lieutenant' => 'img/galons/sous_lieutenant.png',
        
        'aspirant' => 'img/galons/aspirant.png',
        
        // SOUS OFFICIERS SUPERIEURS (3 images)
        'adjudant chef major' => 'img/galons/adjudant_chef_major.png',
        'maitre principal major' => 'img/galons/adjudant_chef_major.png', // Marine Nationale et Armée de l'Air
        
        'adjudant chef' => 'img/galons/adjudant_chef.png',
        'maitre principal' => 'img/galons/adjudant_chef.png',
        
        'adjudant' => 'img/galons/adjudant.png',
        'premier maitre' => 'img/galons/adjudant.png',
        
        // SOUS OFFICIERS SUBALTERNES (2 images)
        'sergent chef' => 'img/galons/sergent_chef.png',
        'marechal des logis chef' => 'img/galons/sergent_chef.png',
        'maitre' => 'img/galons/sergent_chef.png',
        
        'sergent' => 'img/galons/sergent.png',
        'marechal des logis' => 'img/galons/sergent.png',
        'second maitre' => 'img/galons/sergent.png',
        
        // MILITAIRES DE RANG (4 images)
        'gendarme major' => 'img/galons/gendarme_major.png',
        'gendarme-major' => 'img/galons/gendarme_major.png',
        'caporal chef' => 'img/galons/caporal_che.png',
        'quartier maitre de 1ere classe' => 'img/galons/caporal_che.png',
        
        'caporal' => 'img/galons/caporal.png',
        'gendarme' => 'img/galons/gendarme.png',
        'quartier maitre de 2eme classe' => 'img/galons/caporal.png',
        
        'soldat de 1ere classe' => 'img/galons/soldat_1er_classe.png',
        'matelot de 1ere classe' => 'img/galons/soldat_1er_classe.png',
        'aviateur de 1ere classe' => 'img/galons/soldat_1er_classe.png',
        'gendarme de 1ere classe' => 'img/galons/soldat_1er_classe.png',
        
        // SANS GALON (soldat, aviateur, matelot, gendarme de 2eme classe)
        'soldat de 2eme classe' => '',
        'matelot de 2eme classe' => '',
        'aviateur de 2eme classe' => '',
        'gendarme de 2eme classe' => '',
        
        // ÉLÈVES (PAS DE GALON)
        'eleve officier 1ere annee' => '',
        'eleve officier 2eme annee' => '',
        'eleve gendarme' => '',
        
        // GRADES AVEC ABRÉVIATIONS (SYSTÈME MODIFIER_CANDIDAT.PHP)
        'general d armee (ga)' => 'img/galons/general_arme.png',
        'general de corps d armee (gca)' => 'img/galons/generale_corps.png',
        'general de division (gd)' => 'img/galons/generale_division.png',
        'general de brigade (gb)' => 'img/galons/generale_brigade.png',
        'colonel (col)' => 'img/galons/colonel.png',
        'lieutenant colonel (lcl)' => 'img/galons/lieutenant_colonel.png',
        'chef d escadron (c/e)' => 'img/galons/commandant.png',
        'chef d\'escadron (c/e)' => 'img/galons/commandant.png',
        'capitaine (cne)' => 'img/galons/capitaine.png',
        'lieutenant (lt)' => 'img/galons/lieutenant.png',
        'sous lieutenant (s/lt)' => 'img/galons/sous_lieutenant.png',
        'adjudant chef major (acm)' => 'img/galons/adjudant_chef_major.png',
        'adjudant chef (a/c)' => 'img/galons/adjudant_chef.png',
        'adjudant (adjt)' => 'img/galons/adjudant.png',
        'marechal des logis chef (mdl/c)' => 'img/galons/sergent_chef.png',
        'marechal des logis (mdl)' => 'img/galons/sergent.png',
        'gendarme major' => 'img/galons/gendarme_major.png',
        'gendarme' => 'img/galons/gendarme.png',
    ];
    
    // Normalisation améliorée pour gérer les accents et caractères spéciaux
    $grade_normalise = strtolower(trim($grade));
    $grade_normalise = str_replace('_', ' ', $grade_normalise); // Remplacer underscores par espaces
    $grade_normalise = str_replace('-', ' ', $grade_normalise); // Remplacer tirets par espaces
    
    // Gestion des accents (conversion vers ASCII)
    $grade_normalise = str_replace(['é', 'è', 'ê', 'ë'], ['e', 'e', 'e', 'e'], $grade_normalise);
    $grade_normalise = str_replace(['à', 'â', 'ä'], ['a', 'a', 'a'], $grade_normalise);
    $grade_normalise = str_replace(['î', 'ï'], ['i', 'i'], $grade_normalise);
    $grade_normalise = str_replace(['ô', 'ö'], ['o', 'o'], $grade_normalise);
    $grade_normalise = str_replace(['ù', 'û', 'ü'], ['u', 'u'], $grade_normalise);
    $grade_normalise = str_replace(['ç'], ['c'], $grade_normalise);
    
    // Nettoyage des espaces multiples
    $grade_normalise = preg_replace('/\s+/', ' ', $grade_normalise);
    
    // DEBUG : Afficher le grade normalisé pour debug
    if (function_exists('error_log')) {
        error_log("DEBUG getGradeImage: grade='$grade' -> grade_normalise='$grade_normalise'");
    }
    
    // Retourner l'image correspondante ou vide si pas de galon
    $result = $grade_images[$grade_normalise] ?? '';
    
    // Ajouter le préfixe ../ pour compatibilité avec Frontend/
    if (!empty($result)) {
        $result = '../' . $result;
    }
    
    // DEBUG : Afficher le résultat
    if (function_exists('error_log')) {
        error_log("DEBUG getGradeImage: result='$result'");
    }
    
    return $result;
}

// Fonction pour formater les noms longs sur les cartes (maxi 2 noms ou 2 prénoms)
function formatNomComplet($nom, $prenom) {
    // Séparer les noms et prénoms
    $noms = explode(' ', trim($nom ?? ''));
    $prenoms = explode(' ', trim($prenom ?? ''));
    
    // Garder maximum 2 noms
    if (count($noms) > 2) {
        $noms = array_slice($noms, 0, 2);
    }
    
    // Garder maximum 2 prénoms
    if (count($prenoms) > 2) {
        // Garder les 2 premiers prénoms
        $prenoms = array_slice($prenoms, 0, 2);
    }
    
    // Combiner nom et prénom formatés
    $nom_formate = implode(' ', $noms);
    $prenom_formate = implode(' ', $prenoms);
    
    return trim($nom_formate . ' ' . $prenom_formate);
}

// Fonction pour formater le grade sans abréviations pour l'affichage (UNIQUEMENT MAJUSCULES)
function formatGradeDisplay($grade) {
    $grade_normalise = strtoupper(trim($grade));
    
    // Supprimer les accents et convertir en majuscules
    $grade_normalise = str_replace(['É', 'È', 'Ê', 'Ë', 'é', 'è', 'ê', 'ë'], 'E', $grade_normalise);
    $grade_normalise = str_replace(['À', 'Â', 'Ä', 'à', 'â', 'ä'], 'A', $grade_normalise);
    $grade_normalise = str_replace(['Î', 'Ï', 'î', 'ï'], 'I', $grade_normalise);
    $grade_normalise = str_replace(['Ô', 'Ö', 'ô', 'ö'], 'O', $grade_normalise);
    $grade_normalise = str_replace(['Ù', 'Û', 'Ü', 'ù', 'û', 'ü'], 'U', $grade_normalise);
    $grade_normalise = str_replace(['Ç', 'ç'], 'C', $grade_normalise);
    
    // Remplacer les tirets et underscores par des espaces
    $grade_normalise = str_replace(['-', '_'], ' ', $grade_normalise);
    
    // Nettoyer les espaces multiples
    $grade_normalise = preg_replace('/\s+/', ' ', $grade_normalise);
    
    // Mapping des grades avec abréviations vers grades sans abréviations
    $grade_mapping = [
        // ARMÉE DE TERRE
        'GÉNÉRAL D ARMÉE (GA)' => 'GENERAL D ARMEE',
        'GÉNÉRAL DE CORPS D ARMÉE (GCA)' => 'GENERAL DE CORPS D ARMEE',
        'GÉNÉRAL DE DIVISION (GD)' => 'GENERAL DE DIVISION',
        'GÉNÉRAL DE BRIGADE (GB)' => 'GENERAL DE BRIGADE',
        'COLONEL (COL)' => 'COLONEL',
        'LIEUTENANT-COLONEL (LCL)' => 'LIEUTENANT COLONEL',
        'COMMANDANT / CHEF DE BATAILLON (Cdt)' => 'CHEF DE BATAILLON',
        'CAPITAINE (Cne)' => 'CAPITAINE',
        'LIEUTENANT (Lt)' => 'LIEUTENANT',
        'SOUS-LIEUTENANT (S/Lt)' => 'SOUS LIEUTENANT',
        'ASPIRANT' => 'ASPIRANT',
        'ADJUDANT-CHEF MAJOR (ACM)' => 'ADJUDANT CHEF MAJOR',
        'ADJUDANT-CHEF (A/C)' => 'ADJUDANT CHEF',
        'ADJUDANT (ADJT)' => 'ADJUDANT',
        'SERGENT-CHEF (SGT/C)' => 'SERGENT CHEF',
        'SERGENT (SGT)' => 'SERGENT',
        'CAPORAL-CHEF (CPL/C)' => 'CAPORAL CHEF',
        'CAPORAL (CPL)' => 'CAPORAL',
        'SOLDAT DE 1E CLASSE' => 'SOLDAT DE 1ERE CLASSE',
        'SOLDAT DE 2E CLASSE' => 'SOLDAT DE 2EME CLASSE',
        'ÉLÈVE OFFICIER 2ᵉ ANNÉE' => 'ELEVE OFFICIER 2EME ANNEE',
        'ÉLÈVE OFFICIER 1ʳᵉ ANNÉE' => 'ELEVE OFFICIER 1ERE ANNEE',
        
        // MARINE NATIONALE
        'AMIRAL D ESCADRE' => 'AMIRAL',
        'VICE-AMIRAL D ESCADRE (VAE)' => 'VICE AMIRAL D ESCADRE',
        'VICE-AMIRAL (VA)' => 'VICE AMIRAL',
        'CONTRE-AMIRAL (CA)' => 'CONTRE AMIRAL',
        'CAPITAINE DE VAISSEAU (CV)' => 'CAPITAINE DE VAISSEAU',
        'CAPITAINE DE FRÉGATE (CF)' => 'CAPITAINE DE FREGATE',
        'CAPITAINE DE CORVETTE (CC)' => 'CAPITAINE DE CORVETTE',
        'LIEUTENANT DE VAISSEAU (LV)' => 'LIEUTENANT DE VAISSEAU',
        'ENSEIGNE DE VAISSEAU DE 1E CLASSE (EV1)' => 'ENSEIGNE DE VAISSEAU 1ERE CLASSE',
        'ENSEIGNE DE VAISSEAU DE 2E CLASSE (EV2)' => 'ENSEIGNE DE VAISSEAU 2EME CLASSE',
        'MAJOR' => 'MAITRE PRINCIPAL MAJOR',
        'MAÎTRE PRINCIPAL (MP)' => 'MAITRE PRINCIPAL',
        'PREMIER MAÎTRE (PM)' => 'PREMIER MAITRE',
        'MAÎTRE (MTR)' => 'MAITRE',
        'SECOND MAÎTRE (SM)' => 'SECOND MAITRE',
        'QUARTIER-MAÎTRE DE 1E CLASSE (QM1)' => 'QUARTIER MAITRE DE 1ERE CLASSE',
        'QUARTIER-MAÎTRE DE 2E CLASSE (QM2)' => 'QUARTIER MAITRE DE 2EME CLASSE',
        'MATELOT DE 1E CLASSE' => 'MATELOT DE 1ERE CLASSE',
        'MATELOT' => 'MATELOT DE 2EME CLASSE',
        
        // ARMÉE DE L'AIR
        'GÉNÉRAL D ARMÉE AÉRIENNE' => 'GENERAL D ARMEE AERIENNE',
        'GÉNÉRAL DE CORPS AÉRIEN' => 'GENERAL DE CORPS AERIEN',
        'GÉNÉRAL DE DIVISION AÉRIENNE' => 'GENERAL DE DIVISION AERIENNE',
        'GÉNÉRAL DE BRIGADE AÉRIENNE' => 'GENERAL DE BRIGADE AERIENNE',
        'COLONEL (COL)' => 'COLONEL',
        'LIEUTENANT-COLONEL (LCL)' => 'LIEUTENANT COLONEL',
        'COMMANDANT (Cdt)' => 'CHEF DE BATAILLON',
        'CAPITAINE (Cne)' => 'CAPITAINE',
        'LIEUTENANT (Lt)' => 'LIEUTENANT',
        'SOUS-LIEUTENANT (S/Lt)' => 'SOUS LIEUTENANT',
        'ASPIRANT' => 'ASPIRANT',
        'ADJUDANT-CHEF' => 'ADJUDANT CHEF',
        'ADJUDANT' => 'ADJUDANT',
        'SERGENT-CHEF' => 'SERGENT CHEF',
        'SERGENT' => 'SERGENT',
        'CAPORAL-CHEF' => 'CAPORAL CHEF',
        'CAPORAL' => 'CAPORAL',
        'AVIATEUR DE 1E CLASSE' => 'AVIATEUR DE 1ERE CLASSE',
        'AVIATEUR DE 2E CLASSE' => 'AVIATEUR DE 2EME CLASSE',
        
        // GENDARMERIE NATIONALE
        'GÉNÉRAL D ARMÉE (GA)' => 'GENERAL D ARMEE',
        'GÉNÉRAL DE CORPS D ARMÉE (GCA)' => 'GENERAL DE CORPS D ARMEE',
        'GÉNÉRAL DE DIVISION (GD)' => 'GENERAL DE DIVISION',
        'GÉNÉRAL DE BRIGADE (GB)' => 'GENERAL DE BRIGADE',
        'GÉNÉRAL DE GENDARMERIE' => 'GENERAL DE GENDARMERIE',
        'COLONEL (COL)' => 'COLONEL',
        'LIEUTENANT-COLONEL (LCL)' => 'LIEUTENANT COLONEL',
        'CHEF D ESCADRON (C/E)' => 'CHEF D ESCADRON',
        'CAPITAINE (Cne)' => 'CAPITAINE',
        'LIEUTENANT (Lt)' => 'LIEUTENANT',
        'SOUS-LIEUTENANT (S/Lt)' => 'SOUS LIEUTENANT',
        'ASPIRANT' => 'ASPIRANT',
        'ADJUDANT-CHEF MAJOR (ACM)' => 'ADJUDANT CHEF MAJOR',
        'ADJUDANT-CHEF (A/C)' => 'ADJUDANT CHEF',
        'ADJUDANT (ADJT)' => 'ADJUDANT',
        'MARECHAL DES LOGIS-CHEF (MDL/C)' => 'MARECHAL DES LOGIS CHEF',
        'MARECHAL DES LOGIS (MDL)' => 'MARECHAL DES LOGIS',
        'MARÉCHAL DES LOGIS-CHEF (MDL/C)' => 'MARECHAL DES LOGIS CHEF',
        'MARÉCHAL DES LOGIS (MDL)' => 'MARECHAL DES LOGIS',
        'GENDARME-MAJOR' => 'GENDARME MAJOR',
        'GENDARME' => 'GENDARME',
        'ÉLÈVE-GENDARME' => 'ELEVE GENDARME',
        'GENDARME_MAJOR (GM)' => 'GENDARME MAJOR',
        'GENDARME (GEND)' => 'GENDARME',
        'MARECHAL DES LOGIS CHEF' => 'MARECHAL DES LOGIS CHEF',
        'MARECHAL DES LOGIS' => 'MARECHAL DES LOGIS',
        'GENDARME_DE_1ERE_CLASSE' => 'GENDARME DE 1ERE CLASSE',
        'GENDARME_DE_2EME_CLASSE' => 'GENDARME DE 2EME CLASSE',
        'ELEVE_GENDARME (E/G)' => 'ELEVE GENDARME'
    ];
    
    // Si le grade est déjà dans le mapping, le retourner
    if (isset($grade_mapping[$grade_normalise])) {
        return $grade_mapping[$grade_normalise];
    }
    
    // Sinon, supprimer les abréviations entre parenthèses
    $grade_sans_abrev = preg_replace('/\s*\([^)]*\)/', '', $grade_normalise);
    
    return $grade_sans_abrev;
}

function renderRecto($candidat, $config, $unite, $fond_image, $logo_unit) {
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card <?php echo $config['class']; ?>">
            <!-- Arrière-plan de la carte -->
            <img src="<?php echo $fond_image; ?>" class="card-bg" alt="Fond militaire recto">
            
            <!-- Motifs de guilloches -->
            <div class="guilloche-pattern"></div>
            
            <!-- Éléments holographiques simulés -->
            <!-- Étoile holographique au centre -->
            <div class="holographic-element center-star"></div>
            <!-- Petit carré holographique en bas à droite -->
            <div class="holographic-element bottom-right-square"></div>
            
            <!-- Filigrane de sécurité -->
            <div class="security-watermark">CIMIS</div>
            
            <!-- ============================================= -->
            <!-- SECTION ENTÊTE (25% de la hauteur) -->
            <!-- ============================================= -->
            <div class="card-header">
                <div class="header-separator"></div>
                
                <!-- Section gauche - Texte français -->
                <div class="header-left">
                    <div class="header-text">
                        <div class="republique">REPUBLIQUE DU CAMEROUN</div>
                        <div class="devise">Paix - Travail - Patrie</div>
                        <div class="ministere">MINISTERE DE LA DÉFENSE</div>
                    </div>
                </div>
                
                <!-- Section milieu - Logo -->
                <div class="header-center">
                    <img src="../img/cimis.png" class="header-logo" alt="Logo Armée">
                </div>
                
                <!-- Section droite - Texte anglais -->
                <div class="header-right">
                    <div class="header-text">
                        <div class="republique">REPUBLIC OF CAMEROON</div>
                        <div class="devise">Peace - Work - Fatherland</div>
                        <div class="ministere">MINISTRY OF DÉFENCE</div>
                    </div>
                </div>
                
                <div class="header-separator"></div>
            </div>
            
            <!-- Bande séparatrice camerounaise -->
            <div class="cameroun-flag"></div>
            
            <!-- ============================================= -->
            <!-- SECTION CORPS (68% de la hauteur) -->
            <!-- ============================================= -->
            <div class="card-body">
                <div class="body-separator"></div>
                
                <!-- Hologramme du Cameroun -->
                <div class="card-hologram">
                    <img src="../img/cameroun.png" class="hologram-image" alt="Hologramme Cameroun">
                </div>
                
                <!-- Conteneur principal pour les 4 blocs en ligne -->
                <div class="card-content-horizontal" style="margin-top: <?php echo ($unite === 'CIVIL') ? '-25px' : '-15px'; ?>;">
                    
                    <!-- 1er bloc - Photo (25%) -->
                    <div class="card-photo-block" style="margin-top: 18px;">
                        <div class="card-photo-container">
                            <?php 
                            // Gestion du chemin de la photo
                            if (!empty($candidat['photo'])) {
                                // Si le chemin est déjà complet, l'utiliser directement
                                if (file_exists('../' . $candidat['photo'])) {
                                    $photo_path = '../' . $candidat['photo'];
                                } 
                                // Si le chemin est relatif, le compléter
                                elseif (file_exists('../img/candidats/' . basename($candidat['photo']))) {
                                    $photo_path = '../img/candidats/' . basename($candidat['photo']);
                                }
                                // Sinon, essayer avec le nom de fichier seul
                                else {
                                    $filename = basename($candidat['photo']);
                                    $photo_path = '../img/candidats/' . $filename;
                                }
                            } else {
                                $photo_path = '';
                            }
                            
                            if (file_exists($photo_path)) {
                                echo '<img src="' . htmlspecialchars($photo_path) . '" class="candidate-photo" alt="Photo du personnel">';
                            } else {
                                // Utiliser une photo par défaut aléatoire si la photo n'existe pas
                                $default_photo = getDefaultPhoto();
                                if ($default_photo && file_exists('../' . $default_photo)) {
                                    echo '<img src="../' . htmlspecialchars($default_photo) . '" class="candidate-photo" alt="Photo par défaut">';
                                } else {
                                    // Afficher un placeholder si aucune photo par défaut n'est disponible
                                    echo '<div class="candidate-photo-placeholder" style="width: 100px; height: 130px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border-radius: 5px; border: 2px solid #00ff00;">
                                                <i class="fa-solid fa-user"></i>
                                              </div>';
                                }
                            }
                        ?>
                        </div>
                    </div>
                    
                    <!-- 2ème bloc - Labels et Valeurs combinés (65%) -->
                    <div class="card-info-block<?php echo estOfficier($candidat['grade']) ? ' officier' : ''; ?>">
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Nom/Name</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars(strtoupper($candidat['nom'] ?? '')); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Prénom/First Name</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars($candidat['prenom'] ?? ''); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Date Naissance/Birth Date</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars($candidat['date_naissance'] ?? ''); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Lieu Naissance/Birth Place</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars($candidat['lieu_naissance'] ?? ''); ?></span>
                        </div>
                        <?php if ($unite !== 'CIVIL' && !estOfficier($candidat['grade'])): ?>
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Matricule/Service Number</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars($candidat['matricule_militaire'] ?? ''); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($unite !== 'CIVIL'): ?>
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Corps/Branch</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars($candidat['unite'] ?? ''); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Grade/Rank</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars(formatGradeDisplay($candidat['grade'] ?? '')); ?></span>
                        </div>
                        <?php else: ?>
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Catégorie/Category</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars($candidat['categorie_civil'] ?? ''); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label" style="font-size: 20%;">Fonction/Function</span>
                            <span class="value" style="font-size: 20%;"><?php echo htmlspecialchars(formatGradeDisplay($candidat['grade'] ?? '')); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    </div>
                
            </div>
            
            <!-- Image du grade positionnée à x=90% y=50% z-index=3 -->
            <?php if ($candidat['unite'] !== 'CIVIL'): ?>
            <div class="grade-image-container">
                <img src="<?php echo getGradeImage($candidat['grade']); ?>" class="grade-image" alt="Grade">
            </div>
            <?php endif; ?>
            
            <!-- Ligne blanche de séparation principale -->
            <div class="separator-line"></div>
            
            <!-- ============================================= -->
            <!-- SECTION PIED (8% de la hauteur) -->
            <!-- ============================================= -->
            <div class="card-footer">
                <div class="footer-separator"></div>
                <div class="footer-text">
                    <?php if ($unite === 'CIVIL'): ?>
                        Carte réservée exclusivement au personnel civil du Ministère de la Défense<br>
                        Card reserved exclusively for civilian personnel of the Ministry of Defence
                    <?php else: ?>
                        Carte réservée exclusivement aux personnels militaires officiers, sous-officiers et militaires du rang<br>
                        Card reserved exclusively for military personnel officers, non-commissioned officers and enlisted personnel
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function renderVerso($candidat, $config, $unite, $fond_image, $logo_unit) {
    ob_start(); ?>
    <div class="card-subsection">
        <div class="id-card">
            <!-- Arrière-plan -->
            <img src="<?php echo $fond_image; ?>" class="card-bg" alt="Fond militaire verso">
            
            <!-- Motifs de guilloches -->
            <div class="guilloche-pattern"></div>
            
            <!-- Contenu verso -->
            <div class="card-verso-content">
                <!-- Header verso (18%) -->
                <div class="card-verso-header">
                    <div class="verso-title-english">
                        <?php if ($candidat['unite'] === 'CIVIL'): ?>
                            CIVIL  CARD        N°   <strong><?php echo htmlspecialchars($candidat['matricule'] ?? ''); ?></strong>
                        <?php else: ?>
                            MILITARY  CARD        N°   <strong><?php echo htmlspecialchars($candidat['matricule'] ?? ''); ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Bande verte de séparation -->
                <div class="verso-green-separator"></div>
                
                <!-- Body (77%) - 3 blocs -->
                <div class="card-verso-body">
                    <!-- Conteneur pour les 3 blocs -->
                    <div class="verso-content-horizontal">
                        
                        <!-- 1er bloc - Labels -->
                        <div class="verso-labels-block">
                            <div class="verso-label-row">
                                <span class="verso-label" style="font-size: 20%;">Valide depuis le/Valid since...</span>
                            </div>
                            <div class="verso-label-row">
                                <span class="verso-label" style="font-size: 20%;">Numero CNI/National ID number</span>
                            </div>
                            <div class="verso-label-row">
                                <span class="verso-label" style="font-size: 20%;">Taille (cm)/Height (cm)</span>
                            </div>
                            <div class="verso-label-row">
                                <span class="verso-label" style="font-size: 20%;">Sexe/Sex</span>
                            </div>
                            <div class="verso-label-row">
                                <span class="verso-label" style="font-size: 20%;">Groupe sanguin/Blood group</span>
                            </div>
                        </div>
                        
                        <!-- 2ème bloc - Valeurs -->
                        <div class="verso-values-block">
                            <div class="verso-value-row">
                                <span class="verso-value" style="font-size: 20%;"><?php echo !empty($candidat['date_enrolement']) ? date('d/m/Y', strtotime($candidat['date_enrolement'])) : 'N/A'; ?></span>
                            </div>
                            <div class="verso-value-row">
                                <span class="verso-value" style="font-size: 20%;"><?php echo !empty($candidat['numero_cni']) ? htmlspecialchars($candidat['numero_cni']) : 'N/A'; ?></span>
                            </div>
                            <div class="verso-value-row">
                                <span class="verso-value" style="font-size: 20%;"><?php echo !empty($candidat['taille']) ? htmlspecialchars($candidat['taille']) : 'N/A'; ?></span>
                            </div>
                            <div class="verso-value-row">
                                <span class="verso-value" style="font-size: 20%;"><?php echo afficherSexe($candidat['sexe'] ?? ''); ?></span>
                            </div>
                            <div class="verso-value-row">
                                <span class="verso-value" style="font-size: 20%;"><?php echo !empty($candidat['groupe_sanguin']) ? htmlspecialchars($candidat['groupe_sanguin']) : 'N/A'; ?></span>
                            </div>
                        </div>
                        
                        <!-- 3ème bloc - Logo du corps -->
                        <div class="verso-logo-block">
                            <img src="<?php echo $logo_unit; ?>" class="verso-corps-logo" alt="Logo du corps">
                        </div>
                        
                    </div>
                    
                    <!-- Ligne du bas avec empreinte, QR code et signature -->
                    <div class="verso-bottom-row">
                        <div class="verso-fingerprint">
                            <div class="fingerprint-placeholder">
                                <i class="fa-solid fa-fingerprint"></i>
                            </div>
                            <span class="fingerprint-text">empreinte digitale</span>
                        </div>
                        
                        <?php if (!empty($candidat['code_qr']) && file_exists($candidat['code_qr'])): ?>
                        <div class="verso-qr" style="margin-top: -15px;">
                            <div class="qr-secure" style="padding: 0.5mm; position: relative;">
                                <img src="<?php echo $candidat['code_qr']; ?>" class="qr-code-image" alt="QR Code">
                                <!-- Badge sécurité -->
                                <div style="
                                    position: absolute;
                                    top: -3mm;
                                    right: -3mm;
                                    background: #d4af37;
                                    color: #000;
                                    border-radius: 50%;
                                    width: 6mm;
                                    height: 6mm;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 3mm;
                                    font-weight: bold;
                                ">
                                    🔒
                                </div>
                            </div>
                            <span class="qr-text">QR Code</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="verso-signature" style="left: 55%; top: 15px; transform: translateX(-50%);">
                            <div class="signature-text signature-yellow" style="
                                font-family: 'Brush Script MT', cursive;
                                font-size: 10px;
                                font-style: italic;
                                color: #FFD700;
                                text-transform: none;
                                margin-bottom: 12px;
                                line-height: 1.2;
                            "><?php 
                                if (estOfficier($candidat['grade'])) {
                                    echo 'J. Beti Assomo';
                                } else {
                                    echo 'G. a Rim';
                                }
                            ?></div>
                            <div class="signature-text signature-white" style="
                                margin-top: 6px;
                                line-height: 1.1;
                                font-size: 7px;
                                font-weight: bold;
                            "><?php 
                                if (estOfficier($candidat['grade'])) {
                                    echo htmlspecialchars(getSignature($candidat['grade']));
                                } else {
                                    echo 'Directeur des Resources Humaines';
                                }
                            ?></div>
                            <!-- Nombre d'impressions discret dans un coin -->
                            <div style="
                                position: absolute;
                                bottom: 2px;
                                right: 2px;
                                font-size: 4px;
                                color: rgba(0, 0, 0, 0.3);
                                font-family: monospace;
                            ">
                                <?php echo ($candidat['nb_reimpressions'] ?? 0) + 1; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bande rouge de séparation entre body et footer -->
                <div class="verso-red-separator"></div>
                
                <!-- Footer (5%) -->
                <div class="card-verso-footer">
                    <!-- Hologramme CAMEROUN en arrière-plan -->
                    <div class="verso-footer-hologram">
                        CAMEROUN CAMEROON
                    </div>
                    
                    <!-- Texte Ministre au premier plan -->
                    <div class="verso-footer-ministre">
                        Ministere de la Défense / Ministry of Defence
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function renderCarte($candidat) {
    $config_unites = include __DIR__ . '/config_unites.php';
    $unite = $candidat['unite'] ?? 'ARMÉE DE TERRE';
    $config = $config_unites[$unite] ?? $config_unites['ARMÉE DE TERRE'];

    $fond_image = file_exists('../' . $config['fond']) ? '../' . $config['fond'] : '../img/default_fond.png';
    $logo_unit = !empty($config['logo']) && file_exists('../' . $config['logo']) ? '../' . $config['logo'] : '';

    ob_start(); ?>
    <div class="carte-militaire-container">
        <div class="cards-row">
            <?php echo renderRecto($candidat, $config, $unite, $fond_image, $logo_unit); ?>
            <?php echo renderVerso($candidat, $config, $unite, $fond_image, $logo_unit); ?>
        </div>
    </div>
    <script src="../js/carte.js"></script>
    <?php return ob_get_clean();
}

// Fonction pour rendre une carte avec un fond uniforme (mode preview)
// Cette fonction ignore le fond défini dans config_unites et force un fond personnalisé
function renderCarteUniforme($candidat, $fond_uniforme) {
    $unite = $candidat['unite'] ?? 'ARMÉE DE TERRE';
    
    // Utiliser le fond uniforme fourni au lieu du fond de l'unité
    $fond_image = file_exists('../' . $fond_uniforme) ? '../' . $fond_uniforme : '../img/default_fond.png';
    
    // Récupérer la config pour le logo (on garde le logo de l'unité)
    $config_unites = include __DIR__ . '/config_unites.php';
    $config = $config_unites[$unite] ?? $config_unites['ARMÉE DE TERRE'];
    $logo_unit = !empty($config['logo']) && file_exists('../' . $config['logo']) ? '../' . $config['logo'] : '';

    ob_start(); ?>
    <div class="carte-militaire-container">
        <div class="cards-row">
            <?php echo renderRecto($candidat, $config, $unite, $fond_image, $logo_unit); ?>
            <?php echo renderVerso($candidat, $config, $unite, $fond_image, $logo_unit); ?>
        </div>
    </div>
    <script src="../js/carte.js"></script>
    <?php return ob_get_clean();
}
?>
