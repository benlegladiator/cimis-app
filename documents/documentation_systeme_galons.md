# DOCUMENTATION SYSTÈME DE GALONS - CIMIS
===============================================

## 📋 FICHIERS CONCERNÉS

### 🗂️ Fichiers principaux qui gèrent les grades et galons :

1. **`modifier_candidat.php`** (lignes 598-753)
   - Contient le tableau `gradesParUnite` avec tous les grades par unité
   - Génère le JavaScript pour affichage dynamique des grades

2. **`js/enrolement.js`** (lignes 692-906)
   - Contient la fonction `updateGrades()` pour l'enrôlement
   - Gère l'affichage des grades selon l'unité sélectionnée

3. **`Carte/confection_carte.php`** (lignes 68-176)
   - Contient la fonction `getGradeImage($grade)` 
   - Gère la correspondance grade → image pour les cartes

4. **`css/styles_carte.css`** (lignes 1089-1115)
   - Contient le CSS pour l'affichage des images de grades sur les cartes

## 🎯 FONCTIONNEMENT TECHNIQUE

### Étape 1 : Définition des grades
```php
// Dans modifier_candidat.php et js/enrolement.js
const gradesParUnite = {
    'GENDARMERIE NATIONALE': [
        'Général d\'Armée (GA)',
        'Colonel (COL)',
        'Capitaine (Cne)',
        // ... tous les grades
    ]
    // ... autres unités
};
```

### Étape 2 : Génération JavaScript
```javascript
// Conversion du tableau PHP en JSON pour le JavaScript
const gradeData = <?php echo json_encode($gradesParUnite, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
```

### Étape 3 : Affichage dynamique
```javascript
function updateGrades() {
    const unite = document.getElementById('unite').value;
    const gradeSelect = document.getElementById('grade');
    
    // Vider et repeuplir les options selon l'unité
    gradeData[unite].forEach(function(grade) {
        const option = document.createElement('option');
        option.value = grade;
        option.textContent = grade;
        gradeSelect.appendChild(option);
    });
}
```

### Étape 4 : Correspondance image (côté serveur)
```php
// Dans Carte/confection_carte.php
function getGradeImage($grade) {
    $grade_images = [
        'general d armee' => 'img/galons/general_armé.png',
        'colonel' => 'img/galons/colonel.png',
        'capitaine' => 'img/galons/capitaine.png',
        // ... correspondance complète
    ];
    
    $grade_normalise = str_replace('_', ' ', strtolower($grade));
    return $grade_images[$grade_normalise] ?? '';
}
```

### Étape 5 : Affichage sur carte
```php
<!-- Dans le template de carte -->
<div class="grade-image-container">
    <img src="<?php echo getGradeImage($candidat['grade']); ?>" class="grade-image" alt="Grade">
</div>
```

## 📁 Structure des fichiers

### Dossier des images
```
c:\xampp\htdocs\cimcim\img\galons\
├── adjudant.png
├── adjudant_chef.png  
├── adjudant_chef_major.png
├── aspirant.png
├── capitaine.png
├── colonel.png
├── commandant.png
├── gendarme.png
├── gendarme_major.png
├── general_armé.png
├── generale_brigade.png
├── generale_corps.png
├── generale_division.png
├── lieutenant.png
├── lieutenant_colonel.png
├── sergent.png
├── sergent1.png
├── sergent_chef.png
├── soldat_1er_classe.png
└── sous_lieutenant.png
```

## 🔧 Maintenance et mises à jour

### Pour ajouter un nouveau grade :
1. Ajouter le grade dans `gradesParUnite` dans `modifier_candidat.php`
2. Ajouter le grade dans `gradesParUnite` dans `js/enrolement.js`
3. Ajouter l'image dans `getGradeImage()` dans `Carte/confection_carte.php`
4. Placer l'image dans `c:\xampp\htdocs\cimcim\img\galons\`

### Pour modifier une correspondance :
1. Modifier le nom du fichier dans `getGradeImage()`
2. Renommer ou remplacer l'image dans `img/galons/`
3. Tester l'affichage sur les pages d'enrôlement et de modification

## 📊 État actuel du système

- ✅ **25/25 grades** Gendarmerie avec images définies
- ✅ **Fonctionnalité complète** d'affichage dynamique
- ✅ **Correspondance automatique** grade → image
- ✅ **Support multi-unités** (Terre, Air, Marine, Gendarmerie, Civil)
- ⚠️ **2 grades** sans images : Maréchal des Logis-Chef, Maréchal des Logis

## 🎯 Recommandations

1. **Standardiser les noms** : Utiliser des underscores pour les grades avec espaces
2. **Optimiser les images** : Format PNG 200x200px avec fond transparent
3. **Documenter les changements** : Mettre à jour cette documentation à chaque modification
4. **Tests réguliers** : Vérifier l'affichage sur toutes les pages du système

---
*Dernière mise à jour : 27/04/2026*
*Système CIMIS NUMÉRISATION*
