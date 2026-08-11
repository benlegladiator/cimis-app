// Script de correction du problème d'affichage du sexe sur les cartes CIMIS
// À exécuter directement dans la console du navigateur (F12 → Console)

console.log('🔧 DÉMARRAGE - Correction du problème d\'affichage du sexe sur les cartes CIMIS');

// ÉTAPE 1: Mettre à jour le fichier confection_carte.php
async function updateConfectionCarte() {
    console.log('📝 ÉTAPE 1: Mise à jour du fichier confection_carte.php');
    
    try {
        // Récupérer le contenu actuel du fichier
        const response = await fetch('Carte/confection_carte.php');
        let content = await response.text();
        
        // Ajouter la fonction afficherSexe() si elle n'existe pas
        if (!content.includes('function afficherSexe')) {
            console.log('✅ Ajout de la fonction afficherSexe()');
            
            const fonctionSexe = `// Fonction pour afficher le sexe correctement
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

`;
            
            // Insérer la fonction après le premier <?php
            content = content.replace('<?php', '<?php\n' + fonctionSexe);
        }
        
        // Remplacer l'affichage du sexe pour utiliser la nouvelle fonction
        const oldDisplay = '<?php echo htmlspecialchars($candidat[\'sexe\'] ?? \'\'); ?>';
        const newDisplay = '<?php echo afficherSexe($candidat[\'sexe\'] ?? \'\'); ?>';
        
        if (content.includes(oldDisplay)) {
            console.log('✅ Remplacement de l\'affichage du sexe');
            content = content.replace(oldDisplay, newDisplay);
        }
        
        // Mettre à jour le CSS pour éviter la troncature
        if (!content.includes('white-space: nowrap')) {
            console.log('✅ Ajout des styles CSS anti-troncature');
            
            const cssUpdate = `.value {
    white-space: nowrap; /* Empêche le retour à la ligne */
    overflow: visible; /* Assure que le texte n'est pas coupé */
    text-overflow: clip; /* Coupe proprement si nécessaire */
    min-width: 0; /* Permet au flex de réduire si nécessaire */
}`;
            
            // Remplacer la classe .value existante
            const oldCSS = '.value {';
            const newCSS = cssUpdate;
            
            if (content.includes(oldCSS)) {
                // Trouver la fin du bloc .value
                const startIndex = content.indexOf(oldCSS);
                const endIndex = content.indexOf('}', startIndex) + 1;
                
                content = content.substring(0, startIndex) + newCSS + content.substring(endIndex);
            }
        }
        
        console.log('✅ Fichier confection_carte.php mis à jour avec succès');
        console.log('📄 Contenu mis à jour:');
        console.log('- Fonction afficherSexe() ajoutée');
        console.log('- Affichage du sexe corrigé');
        console.log('- Styles CSS anti-troncature ajoutés');
        
        return content;
        
    } catch (error) {
        console.error('❌ Erreur lors de la mise à jour:', error);
        return null;
    }
}

// ÉTAPE 2: Tester l'enregistrement d'un candidat
async function testCandidateEnrollment() {
    console.log('\n🧪 ÉTAPE 2: Test d\'enregistrement d\'un candidat');
    
    const testData = {
        nom: 'TEST' + Date.now(),
        prenom: 'CORRECTION',
        sexe: 'MASCULIN',
        date_naissance: '1990-01-01',
        numero_cni: 'TEST123456789012345',
        taille: '175',
        poids: '70',
        groupe_sanguin: 'O+',
        type_personnel: 'MILITAIRE',
        unite: 'ARMÉE DE TERRE',
        grade: 'CAPITAINE',
        matricule_militaire: 'T17/99999',
        annee_dernier_galon: '2023'
    };
    
    try {
        console.log('📤 Envoi des données de test:', testData);
        
        const formData = new FormData();
        Object.keys(testData).forEach(key => {
            formData.append(key, testData[key]);
        });
        
        // Simuler l'upload d'une photo
        const photoBlob = new Blob(['TEST PHOTO'], { type: 'image/png' });
        formData.append('photo', photoBlob, 'test.png');
        
        const response = await fetch('backend/enrolement_traitement.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Succès! Candidat enregistré:', result);
            console.log('🆔 Matricule généré:', result.matricule);
            console.log('📱 Code QR généré:', result.code_qr);
            
            // ÉTAPE 3: Tester la génération de la carte
            setTimeout(() => testCardGeneration(result.matricule), 2000);
            
        } else {
            console.error('❌ Erreur lors de l\'enregistrement:', result);
        }
        
    } catch (error) {
        console.error('❌ Erreur lors du test:', error);
    }
}

// ÉTAPE 3: Tester la génération de la carte
async function testCardGeneration(matricule) {
    console.log('\n🎴 ÉTAPE 3: Test de génération de la carte pour le matricule:', matricule);
    
    try {
        const response = await fetch(`visualiser_carte.php?matricule=${matricule}`);
        const html = await response.text();
        
        if (html.includes('afficherSexe')) {
            console.log('✅ Succès! La fonction afficherSexe() est utilisée dans la carte');
        } else {
            console.log('⚠️ Attention: La fonction afficherSexe() n\'est pas trouvée dans la carte');
        }
        
        if (html.includes('MASCULIN')) {
            console.log('✅ Succès! Le sexe "MASCULIN" s\'affiche correctement');
        } else if (html.includes('M</span>')) {
            console.log('❌ Problème! Le sexe s\'affiche toujours comme "M"');
        } else {
            console.log('ℹ️ Le sexe n\'est pas trouvé dans la carte générée');
        }
        
        console.log('🎯 Test de génération de carte terminé');
        
    } catch (error) {
        console.error('❌ Erreur lors du test de génération:', error);
    }
}

// Fonction principale d'exécution
async function runFixAndTest() {
    console.log('🚀 DÉMARRAGE DU SCRIPT DE CORRECTION ET TEST\n');
    
    // Mettre à jour le fichier
    const updatedContent = await updateConfectionCarte();
    
    if (updatedContent) {
        console.log('\n⚠️ Le fichier a été mis à jour en mémoire.');
        console.log('💡 Pour appliquer les changements définitivement:');
        console.log('1. Copiez le contenu affiché ci-dessous');
        console.log('2. Remplacez le contenu du fichier Carte/confection_carte.php');
        console.log('3. Rechargez la page et testez l\'enregistrement\n');
        
        console.log('📄 CONTENU MIS À JOUR:');
        console.log('='.repeat(80));
        console.log(updatedContent);
        console.log('='.repeat(80));
    }
    
    // Tester l'enregistrement
    await testCandidateEnrollment();
}

// Instructions pour l'utilisateur
console.log('📋 INSTRUCTIONS:');
console.log('1. Exécutez cette commande dans la console: runFixAndTest()');
console.log('2. Attendez la fin des tests');
console.log('3. Suivez les instructions pour appliquer les changements');
console.log('4. Testez manuellement l\'enregistrement d\'un candidat\n');

console.log('🎯 POUR DÉMARRER LE TEST, EXÉCUTEZ:');
console.log('runFixAndTest()');

// Exporter les fonctions pour utilisation manuelle
window.fixSexeDisplay = {
    updateFile: updateConfectionCarte,
    testEnrollment: testCandidateEnrollment,
    testCard: testCardGeneration,
    runAll: runFixAndTest
};

console.log('\n✅ Fonctions disponibles:');
console.log('- fixSexeDisplay.updateFile() : Mettre à jour le fichier');
console.log('- fixSexeDisplay.testEnrollment() : Tester l\'enregistrement');
console.log('- fixSexeDisplay.testCard(matricule) : Tester la génération de carte');
console.log('- fixSexeDisplay.runAll() : Exécuter tout le processus');
