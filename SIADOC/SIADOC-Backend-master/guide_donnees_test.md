# ========================================
# GUIDE D'UTILISATION DES DONNÉES DE TEST
# ========================================

## 📋 RÉSUMÉ DES DONNÉES CRÉÉES

### Structure Hiérarchique Complète
- ✅ **5 RMIA** (Régions Militaires)
- ✅ **5 Brigades** (sous les RMIA)
- ✅ **5 Bataillons** (sous les Brigades)
- ✅ **6 Compagnies** (sous les Bataillons)
- ✅ **36 Militaires** (6 par compagnie)

### Utilisateurs/Rôles Créés
- ✅ **1 RMIA Admin** (rmia1_admin)
- ✅ **1 Chef de Brigade** (bde1_chef)
- ✅ **1 Chef de Bataillon** (bta1_chef)
- ✅ **1 Chef de Compagnie** (cie1_chef)

## 🔑 IDENTIFIANTS DE CONNEXION

### Mot de passe universel: `password123`

| Rôle | Username | Email | Permissions |
|-------|----------|--------|-------------|
| RMIA Admin | rmia1_admin | rmia1.admin@siadoc.cm | Accès total à la région |
| Chef Brigade | bde1_chef | bde1.chef@siadoc.cm | Gestion de sa brigade |
| Chef Bataillon | bta1_chef | bta1.chef@siadoc.cm | Gestion de son bataillon |
| Chef Compagnie | cie1_chef | cie1.chef@siadoc.cm | Gestion de sa compagnie |

## 👥 MILITAIRES PAR COMPAGNIE

### Compagnie de Commandement (CIE-001)
1. **Colonel Etoundi Essomba** - MAT-2024-0001
2. **Capitaine Mbarga Ousmanou** - MAT-2024-0002
3. **Adjudant Tchatchoua Pierre** - MAT-2024-0003
4. **Caporal Biya Thomas** - MAT-2024-0004
5. **Soldat Fouda Mathieu** - MAT-2024-0005
6. **Soldat Moukouri Jean** - MAT-2024-0006

### 1ère Compagnie d'Infanterie (CIE-002)
1. **Capitaine Ngo Nlend** - MAT-2024-0007
2. **Lieutenant Talla André** - MAT-2024-0008
3. **Adjudant Mballa Roger** - MAT-2024-0009
4. **Caporal Etoa Jean-Claude** - MAT-2024-0010
5. **Soldat Mbappe Samuel** - MAT-2024-0011
6. **Soldat Abega Paul** - MAT-2024-0012

### 2ème Compagnie d'Infanterie (CIE-003)
1. **Capitaine Kamga Yves** - MAT-2024-0013
2. **Lieutenant Fouda Joseph** - MAT-2024-0014
3. **Adjudant Tchameni Martin** - MAT-2024-0015
4. **Caporal Ze Emile** - MAT-2024-0016
5. **Soldat Mba Michel** - MAT-2024-0017
6. **Soldat Nkodo Pierre** - MAT-2024-0018

### Compagnie de Support (CIE-SUP-001)
1. **Capitaine Olinga Henri** - MAT-2024-0019
2. **Lieutenant Ngando Paul** - MAT-2024-0020
3. **Adjudant Mounkala Jean** - MAT-2024-0021
4. **Caporal Etoundi Jacques** - MAT-2024-0022
5. **Soldat Fotsing Laurent** - MAT-2024-0023
6. **Soldat Mbemba Armand** - MAT-2024-0024

### Compagnie du Génie (CIE-GEN-001)
1. **Capitaine Mvondo Arsène** - MAT-2024-0025
2. **Lieutenant Tchuisse Blaise** - MAT-2024-0026
3. **Adjudant Nkoulou Thomas** - MAT-2024-0027
4. **Caporal Mballa Etienne** - MAT-2024-0028
5. **Soldat Fotsing Marcel** - MAT-2024-0029
6. **Soldat Ngando René** - MAT-2024-0030

### Compagnie de Transmission (CIE-TRANS-001)
1. **Capitaine Nlend Joseph** - MAT-2024-0031
2. **Lieutenant Fouda Emile** - MAT-2024-0032
3. **Adjudant Talla Jean-Pierre** - MAT-2024-0033
4. **Caporal Ousmanou Moussa** - MAT-2024-0034
5. **Soldat Etoundi François** - MAT-2024-0035
6. **Soldat Mballa Antoine** - MAT-2024-0036

## 🗂️ FICHIERS CRÉÉS

1. **profils_et_roles.md** - Définition complète des profils et rôles
2. **militaires_cameroonais.md** - Liste détaillée des militaires avec noms camerounais
3. **insertion_donnees_test.sql** - Script SQL complet pour l'insertion en base

## 🚀 ÉTAPES SUIVANTES

### 1. Validation des fichiers
✅ Vérifier que tous les noms sont camerounais
✅ Vérifier que la hiérarchie est cohérente
✅ Vérifier que les matricules sont uniques

### 2. Insertion en base de données
```bash
# Se connecter à la base de données
psql -U username -d siadoc_db

# Exécuter le script
\i insertion_donnees_test.sql
```

### 3. Test de connexion
- Se connecter avec `rmia1_admin` / `password123`
- Vérifier l'accès aux différentes fonctionnalités
- Tester la navigation hiérarchique

## 📊 STATISTIQUES

- **5 Régions Militaires** couvertes
- **5 Brigades** opérationnelles
- **5 Bataillons** déployés
- **6 Compagnies** actives
- **36 Militaires** en service
- **4 Utilisateurs** avec différents niveaux de permission

## 🎯 CAS D'USAGE TEST

### Test RMIA
1. Se connecter comme `rmia1_admin`
2. Voir toutes les brigades de sa région
3. Voir les statistiques globales
4. Valider des dossiers

### Test Brigade
1. Se connecter comme `bde1_chef`
2. Voir les bataillons de sa brigade
3. Gérer les compagnies
4. Approuver les mutations

### Test Bataillon
1. Se connecter comme `bta1_chef`
2. Voir les compagnies de son bataillon
3. Gérer les militaires
4. Valider les notations

### Test Compagnie
1. Se connecter comme `cie1_chef`
2. Voir ses 6 militaires
3. Gérer les dossiers administratifs
4. Créer des rapports

## ✅ VALIDATION FINALE

Avant d'exécuter le script, vérifier:
- [ ] La structure des tables correspond à votre schéma
- [ ] Les mots de passe sont correctement hashés
- [ ] Les UUID sont générés correctement
- [ ] Les relations étrangères sont cohérentes

Le script est prêt pour l'insertion ! 🎉
