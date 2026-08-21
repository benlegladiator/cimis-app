<?php
// Carte/confection_carte.php - Moteur de rendu portable des cartes PVC

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
function estOfficier($grade) {
    $grade_normalise = strtolower(trim($grade));
    
    // Liste des grades d'officiers
    $grades_officiers = [
        'general d armee',
        'general d armee aerienne', 
        'amiral',
        'general de corps d armee',
        'general de corps d armee aerienne',
        'vice amiral d escadre',
        'general de division',
        'general de division aerienne',
        'vice amiral',
        'general de brigade',
        'general de brigade aerienne',
        'contre amiral',
        'general de gendarmerie',
        'colonel',
        'capitaine de vaisseau',
        'lieutenant colonel',
        'capitaine de fregate',
        'chef de bataillon',
        'commandant',
        'chef d escadron',
        'capitaine de corvette',
        'capitaine',
        'lieutenant de vaisseau',
        'lieutenant',
        'enseigne de vaisseau 1ere classe',
        'sous lieutenant',
        'enseigne de vaisseau 2eme classe',
        'aspirant'
    ];
    
    // Vérifier si le grade normalisé est dans la liste des officiers
    return in_array($grade_normalise, $grades_officiers);
}

// Fonction pour obtenir la signature selon le grade
function getSignature($grade) {
    return estOfficier($grade) ? 'Joseph Beti Assomo' : 'Goufan a Rim';
}

// Fonction pour obtenir l'image du grade
function getGradeImage($grade) {
    // --- SYSTÈME UNIFIÉ DE GALONS (images existantes dans img/galons/) ---
    $grade_images = [
        // OFFICIERS GENERAUX/AMIRAUX (4 images - mêmes étoiles pour tous corps)
        'general d armee' => 'img/galons/general_armé.png',
        'general d armee aerienne' => 'img/galons/general_armé.png',
        'amiral' => 'img/galons/general_armé.png',
        
        'general de corps d armee' => 'img/galons/generale_corps.png',
        'general de corps d armee aerienne' => 'img/galons/generale_corps.png',
        'vice amiral d escadre' => 'img/galons/generale_corps.png',
        
        'general de division' => 'img/galons/generale_division.png',
        'general de division aerienne' => 'img/galons/generale_division.png',
        'vice amiral' => 'img/galons/generale_division.png',
        
        'general de brigade' => 'img/galons/generale_brigade.png',
        'general de brigade aerienne' => 'img/galons/generale_brigade.png',
        'contre amiral' => 'img/galons/generale_brigade.png',
        'general de gendarmerie' => 'img/galons/generale_brigade.png',
        
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
        'enseigne de vaisseau 1ere classe' => 'img/galons/lieutenant.png',
        
        'sous lieutenant' => 'img/galons/sous_lieutenant.png',
        'enseigne de vaisseau 2eme classe' => 'img/galons/sous_lieutenant.png',
        
        'aspirant' => 'img/galons/aspirant.png',
        
        // SOUS OFFICIERS SUPERIEURS (3 images)
        'adjudant chef major' => 'img/galons/adjudant_chef_major.png',
        'maitre principal major' => 'img/galons/adjudant_chef_major.png',
        
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
        'gendarme major' => 'img/galons/sergent1.png',
        'caporal chef' => 'img/galons/caporal_chel.png',
        'quartier maitre de 1ere classe' => 'img/galons/caporal_chel.png',
        
        'caporal' => 'img/galons/caporal.png',
        'gendarme' => 'img/galons/caporal.png',
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
        
        // ÉLÈVES (1 image - aspirant utilisé pour élèves officiers)
        'eleve officier 1ere annee' => 'img/galons/aspirant.png',
        'eleve officier 2eme annee' => 'img/galons/aspirant.png',
        'eleve gendarme' => 'img/galons/aspirant.png',
    ];
    
    // Normalisation pour le nouveau système (grades sans abréviations)
    $grade_normalise = strtolower(trim($grade));
    $grade_normalise = str_replace('_', ' ', $grade_normalise); // Remplacer underscores par espaces
    
    // Retourner l'image correspondante ou vide si pas de galon
    return $grade_images[$grade_normalise] ?? '';
}

// Fonction pour formater le grade sans abréviations pour l'affichage
function formatGradeDisplay($grade) {
    $grade_normalise = strtoupper(trim($grade));
    
    // Mapping des grades avec abréviations vers grades sans abréviations
    $grade_mapping = [
        // ARMÉE DE TERRE
        'GENERAL_D_ARMEE (GA)' => 'GENERAL D ARMEE',
        'GENERAL_DE_CORPS_D_ARMEE (GCA)' => 'GENERAL DE CORPS D ARMEE',
        'GENERAL_DE_DIVISION (GD)' => 'GENERAL DE DIVISION',
        'GENERAL_DE_BRIGADE (GB)' => 'GENERAL DE BRIGADE',
        'COLONEL (COL)' => 'COLONEL',
        'LIEUTENANT_COLONEL (LCL)' => 'LIEUTENANT COLONEL',
        'CHEF_DE_BATAILLON (CBA)' => 'CHEF DE BATAILLON',
        'CAPITAINE (CNE)' => 'CAPITAINE',
        'LIEUTENANT (LT)' => 'LIEUTENANT',
        'SOUS_LIEUTENANT (SLT)' => 'SOUS LIEUTENANT',
        'ASPIRANT (ASP)' => 'ASPIRANT',
        'ADJUDANT_CHEF_MAJOR (ACM)' => 'ADJUDANT CHEF MAJOR',
        'ADJUDANT_CHEF (AC)' => 'ADJUDANT CHEF',
        'ADJUDANT (ADJT)' => 'ADJUDANT',
        'SERGENT_CHEF (SC)' => 'SERGENT CHEF',
        'SERGENT (SGT)' => 'SERGENT',
        'CAPORAL_CHEF (C/C)' => 'CAPORAL CHEF',
        'CAPORAL (CAL)' => 'CAPORAL',
        'SOLDAT_DE_1ERE_CLASSE (SDT1)' => 'SOLDAT DE 1ERE CLASSE',
        'SOLDAT_DE_2EME_CLASSE (SDT2)' => 'SOLDAT DE 2EME CLASSE',
        'ELEVE_OFFICIER_2EME_ANNEE (EOA)' => 'ELEVE OFFICIER 2EME ANNEE',
        
        // MARINE NATIONALE
        'AMIRAL (A)' => 'AMIRAL',
        'VICE_AMIRAL_D_ESCADRE (VAE)' => 'VICE AMIRAL D ESCADRE',
        'VICE_AMIRAL (VA)' => 'VICE AMIRAL',
        'CONTRE_AMIRAL (CA)' => 'CONTRE AMIRAL',
        'CAPITAINE_DE_VAISSEAU (CV)' => 'CAPITAINE DE VAISSEAU',
        'CAPITAINE_DE_FREGATE (CF)' => 'CAPITAINE DE FREGATE',
        'CAPITAINE_DE_CORVETTE (CC)' => 'CAPITAINE DE CORVETTE',
        'LIEUTENANT_DE_VAISSEAU (LV)' => 'LIEUTENANT DE VAISSEAU',
        'ENSEIGNE_DE_VAISSEAU_1ERE_CLASSE (EV1)' => 'ENSEIGNE DE VAISSEAU 1ERE CLASSE',
        'ENSEIGNE_DE_VAISSEAU_2EME_CLASSE (EV2)' => 'ENSEIGNE DE VAISSEAU 2EME CLASSE',
        'MAITRE_PRINCIPAL_MAJOR (MPM)' => 'MAITRE PRINCIPAL MAJOR',
        'MAITRE_PRINCIPAL (MP)' => 'MAITRE PRINCIPAL',
        'PREMIER_MAITRE (PM)' => 'PREMIER MAITRE',
        'MAITRE (MTRE)' => 'MAITRE',
        'SECOND_MAITRE (SM)' => 'SECOND MAITRE',
        'QUARTIER_MAITRE_DE_1ERE_CLASSE (QM1)' => 'QUARTIER MAITRE DE 1ERE CLASSE',
        'QUARTIER_MAITRE_DE_2EME_CLASSE (QM2)' => 'QUARTIER MAITRE DE 2EME CLASSE',
        'MATELOT_DE_1ERE_CLASSE (MLOT1)' => 'MATELOT DE 1ERE CLASSE',
        'MATELOT_DE_2EME_CLASSE (MLOT2)' => 'MATELOT DE 2EME CLASSE',
        
        // ARMÉE DE L'AIR
        'GENERAL_D_ARMEE_AERIENNE (GAA)' => 'GENERAL D ARMEE AERIENNE',
        'GENERAL_DE_CORPS_AERIEN (GCAA)' => 'GENERAL DE CORPS AERIEN',
        'GENERAL_DE_DIVISION_AERIENNE (GDA)' => 'GENERAL DE DIVISION AERIENNE',
        'GENERAL_DE_BRIGADE_AERIENNE (GBA)' => 'GENERAL DE BRIGADE AERIENNE',
        'COMMANDANT (CDT)' => 'COMMANDANT',
        'AVIATEUR_DE_1ERE_CLASSE' => 'AVIATEUR DE 1ERE CLASSE',
        'AVIATEUR_DE_2EME_CLASSE' => 'AVIATEUR DE 2EME CLASSE',
        'ELEVE_OFFICIER_1ERE_ANNEE (EOA)' => 'ELEVE OFFICIER 1ERE ANNEE',
        
        // GENDARMERIE
        'GENERAL_DE_GENDARMERIE' => 'GENERAL DE GENDARMERIE',
        'MARECHAL_DES_LOGIS_CHEF (MDLC)' => 'MARECHAL DES LOGIS CHEF',
        'MARECHAL_DES_LOGIS (MDL)' => 'MARECHAL DES LOGIS',
        'GENDARME_MAJOR (GM)' => 'GENDARME MAJOR',
        'GENDARME (GEND)' => 'GENDARME',
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
                    <img src="img/cimis.png" class="header-logo" alt="Logo Armée">
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
                    <img src="img/cameroun.png" class="hologram-image" alt="Hologramme Cameroun">
                </div>
                
                <!-- Conteneur principal pour les 4 blocs en ligne -->
                <div class="card-content-horizontal" style="margin-top: -15px;">
                    
                    <!-- 1er bloc - Photo (25%) -->
                    <div class="card-photo-block" style="margin-top: 18px;">
                        <div class="card-photo-container">
                            <?php 
                            // Gestion du chemin de la photo
                            if (!empty($candidat['photo'])) {
                                // Si le chemin est déjà complet, l'utiliser directement
                                if (file_exists($candidat['photo'])) {
                                    $photo_path = $candidat['photo'];
                                } 
                                // Si le chemin est relatif, le compléter
                                elseif (file_exists('img/candidats/' . basename($candidat['photo']))) {
                                    $photo_path = 'img/candidats/' . basename($candidat['photo']);
                                }
                                // Sinon, essayer avec le nom de fichier seul
                                else {
                                    $filename = basename($candidat['photo']);
                                    $photo_path = 'img/candidats/' . $filename;
                                }
                            } else {
                                $photo_path = '';
                            }
                            
                            if (file_exists($photo_path)) {
                                echo '<img src="' . htmlspecialchars($photo_path) . '" class="candidate-photo" alt="Photo du candidat">';
                            } else {
                                // Afficher un placeholder si la photo n'existe pas
                                echo '<div class="candidate-photo-placeholder" style="width: 100px; height: 130px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; border-radius: 5px; border: 2px solid #00ff00;">
                                            <i class="fa-solid fa-user"></i>
                                          </div>';
                            }
                        ?>
                        </div>
                    </div>
                    
                    <!-- 2ème bloc - Labels (25%) -->
                    <div class="card-labels-block<?php echo estOfficier($candidat['grade']) ? ' officier' : ''; ?>">
                        <div class="label-row">
                            <span class="label">Nom/Name</span>
                        </div>
                        <div class="label-row">
                            <span class="label">Prénom/First Name</span>
                        </div>
                        <div class="label-row">
                            <span class="label">Sexe/Sex</span>
                        </div>
                        <?php if ($unite !== 'CIVIL' && !estOfficier($candidat['grade'])): ?>
                        <div class="label-row">
                            <span class="label">Matricule/Service Number</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($unite !== 'CIVIL'): ?>
                        <div class="label-row">
                            <span class="label">Corps/Branch</span>
                        </div>
                        <div class="label-row">
                            <span class="label">Grade/Rank</span>
                        </div>
                        <?php else: ?>
                        <div class="label-row">
                            <span class="label">Fonction/Function</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 3ème bloc - Valeurs (40%) -->
                    <div class="card-values-block<?php echo estOfficier($candidat['grade']) ? ' officier' : ''; ?>" style="margin-left: 15px;">
                        <div class="value-row">
                            <span class="value"><?php echo htmlspecialchars($candidat['nom'] ?? ''); ?></span>
                        </div>
                        <div class="value-row">
                            <span class="value"><?php echo htmlspecialchars($candidat['prenom'] ?? ''); ?></span>
                        </div>
                        <div class="value-row">
                            <span class="value"><?php echo afficherSexe($candidat['sexe'] ?? ''); ?></span>
                        </div>
                        <?php if ($unite !== 'CIVIL' && !estOfficier($candidat['grade'])): ?>
                        <div class="value-row">
                            <span class="value"><?php echo htmlspecialchars($candidat['matricule_militaire'] ?? ''); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($unite !== 'CIVIL'): ?>
                        <div class="value-row">
                            <span class="value"><?php echo htmlspecialchars($candidat['unite'] ?? ''); ?></span>
                        </div>
                        <div class="value-row">
                            <span class="value"><?php echo htmlspecialchars(formatGradeDisplay($candidat['grade'] ?? '')); ?></span>
                        </div>
                        <?php else: ?>
                        <div class="value-row">
                            <span class="value"><?php echo htmlspecialchars(formatGradeDisplay($candidat['grade'] ?? '')); ?></span>
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
                            CIVIL IDENTIFICATION CARD        N°   <strong><?php echo htmlspecialchars($candidat['matricule'] ?? ''); ?></strong>
                        <?php else: ?>
                            MILITARY IDENTIFICATION CARD        N°   <strong><?php echo htmlspecialchars($candidat['matricule'] ?? ''); ?></strong>
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
                                <span class="verso-label">Valide depuis le/Valid since...</span>
                            </div>
                            <div class="verso-label-row">
                                <span class="verso-label">Numero CNI/National ID number</span>
                            </div>
                            <div class="verso-label-row">
                                <span class="verso-label">Taille (cm)/Height (cm)</span>
                            </div>
                            <div class="verso-label-row">
                                <span class="verso-label">Groupe sanguin/Blood group</span>
                            </div>
                        </div>
                        
                        <!-- 2ème bloc - Valeurs -->
                        <div class="verso-values-block">
                            <div class="verso-value-row">
                                <span class="verso-value"><?php echo !empty($candidat['date_enrolement']) ? date('d/m/Y', strtotime($candidat['date_enrolement'])) : 'N/A'; ?></span>
                            </div>
                            <div class="verso-value-row">
                                <span class="verso-value"><?php echo !empty($candidat['numero_cni']) ? htmlspecialchars($candidat['numero_cni']) : 'N/A'; ?></span>
                            </div>
                            <div class="verso-value-row">
                                <span class="verso-value"><?php echo !empty($candidat['taille']) ? htmlspecialchars($candidat['taille']) : 'N/A'; ?></span>
                            </div>
                            <div class="verso-value-row">
                                <span class="verso-value"><?php echo !empty($candidat['groupe_sanguin']) ? htmlspecialchars($candidat['groupe_sanguin']) : 'N/A'; ?></span>
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
                        
                        <div class="verso-signature" style="left: 55%; transform: translateX(-50%);">
                            <div class="signature-text signature-yellow" style="
                                font-family: 'Brush Script MT', cursive;
                                font-size: 12px;
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
                                margin-top: 8px;
                                line-height: 1.1;
                                font-size: 8px;
                                font-weight: bold;
                            "><?php 
                                if (estOfficier($candidat['grade'])) {
                                    echo htmlspecialchars(getSignature($candidat['grade']));
                                } else {
                                    echo 'Directeur des Resources Humaines';
                                }
                            ?></div>
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

    $fond_image = file_exists($config['fond']) ? $config['fond'] : 'img/default_fond.png';
    $logo_unit = !empty($config['logo']) && file_exists($config['logo']) ? $config['logo'] : '';

    ob_start(); ?>
    <div class="carte-militaire-container">
        <div class="cards-row">
            <?php echo renderRecto($candidat, $config, $unite, $fond_image, $logo_unit); ?>
            <?php echo renderVerso($candidat, $config, $unite, $fond_image, $logo_unit); ?>
        </div>
    </div>
    <script src="js/carte.js"></script>
    <?php return ob_get_clean();
}

// Fonction pour rendre une carte avec un fond uniforme (mode preview)
// Cette fonction ignore le fond défini dans config_unites et force un fond personnalisé
function renderCarteUniforme($candidat, $fond_uniforme) {
    $unite = $candidat['unite'] ?? 'ARMÉE DE TERRE';
    
    // Utiliser le fond uniforme fourni au lieu du fond de l'unité
    $fond_image = file_exists($fond_uniforme) ? $fond_uniforme : 'img/default_fond.png';
    
    // Récupérer la config pour le logo (on garde le logo de l'unité)
    $config_unites = include __DIR__ . '/config_unites.php';
    $config = $config_unites[$unite] ?? $config_unites['ARMÉE DE TERRE'];
    $logo_unit = !empty($config['logo']) && file_exists($config['logo']) ? $config['logo'] : '';

    ob_start(); ?>
    <div class="carte-militaire-container">
        <div class="cards-row">
            <?php echo renderRecto($candidat, $config, $unite, $fond_image, $logo_unit); ?>
            <?php echo renderVerso($candidat, $config, $unite, $fond_image, $logo_unit); ?>
        </div>
    </div>
    <script src="js/carte.js"></script>
    <?php return ob_get_clean();
}
?>
