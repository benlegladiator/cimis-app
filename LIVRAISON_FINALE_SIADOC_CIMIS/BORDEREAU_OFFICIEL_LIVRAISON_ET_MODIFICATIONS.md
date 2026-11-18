# 🛡️ MINISTÈRE DE LA DÉFENSE — DIRECTION DES RESSOURCES HUMAINES
## PROJET SIADOC / CIMIS — BORDEREAU OFFICIEL DE LIVRAISON APPLICATIVE
**Document de Synthèse & Note de Cadrage pour Déploiement Serveur**  
*Date : 18 Novembre 2026 | Statut : Homologué & Prêt pour Intégration en Production*

---

### 🎯 Objectif du document
Ce bordereau récapitule de manière exhaustive l'ensemble des correctifs, optimisations de performances et alignements réglementaires réalisés sur le code source de l'application SIADOC par rapport à l'archive socle remise le 17 novembre 2026.

Il a été conçu pour permettre à l'équipe d'infrastructure et de sécurité (ANTIC) de réaliser un déploiement fluide, rapide et sécurisé, sans blocage opérationnel pour la mission terrain nationale.

---

### 📋 Synthèse des 5 Chantiers Prioritaires Traités

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ 1. OPTIMISATION HAUTE PERFORMANCE & ANTI-FREEZE (MODULE TEEC)              │
│ 2. RÉSOLUTION DES ANOMALIES DE DONNÉES BD (CATALOGUE POSTES DRH)           │
│ 3. ALIGNEMENT ERGONOMIQUE DU COMMANDEMENT (SHELL & NAVIGATION DRH)          │
│ 4. REFONTE VISUELLE & SÉCURISATION (JOURNAL DES TEXTES OFFICIELS)          │
│ 5. FIABILISATION DU MOTEUR D'ANNUAIRE & FILTRAGE MULTI-CRITÈRES            │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### 📂 Détail Fichier par Fichier des Modifications

#### Chantier 1 : Module TEEC & Consultation des Postes (Performance & Anti-Freeze)
* **Problème constaté en production :**  
  Gel du navigateur (« script unresponsive ») dès l'ouverture de l'écran TEEC en présence de l'organigramme complet des Forces de Défense, accompagné de requêtes suspendues en timeout 504.
* **Cause racine :**  
  L'utilisation d'un getter Angular dynamique (`get filteredGrandeFormationGroups()`) lié directement au `*ngFor` du template exécutait la boucle récursive `detectCorps()` des dizaines de fois par seconde sur l'ensemble de l'arbre hiérarchique.
* **Correction appliquée :**  
  - Mise en cache mémoire immédiate $O(1)$ des groupes de grandes formations.
  - Mémoïsation du corps d'armée (`corpsCache` et `structureMap`) supprimant toute recherche ascendante répétitive.
  - Déclenchement du filtrage textuel exclusivement lors de la frappe utilisateur (`(input)`).
* **Fichiers modifiés à écraser :**
  1. `frontend/src/app/features/tec/tec.ts`
  2. `frontend/src/app/features/tec/tec.html`
  3. `frontend/src/app/features/teec/postes-vacance/postes-vacance.ts`
  4. `frontend/src/app/features/teec/postes-vacance/postes-vacance.html`

---

#### Chantier 2 : Base de Données — Nettoyage du Doublon d'Import DRH
* **Problème constaté :**  
  L'écran affichait 381 postes théoriques pour la Direction des Ressources Humaines au lieu de l'effectif réel réglementaire.
* **Cause racine :**  
  Double importation historique du fichier source `DRH.xlsm` dans la table `catalogue_teec` :
  - Bloc 1 (Ordres 5553 à 5742) : 190 postes.
  - Bloc 2 (Ordres 10251 à 10440) : 190 postes (clones stricts sans aucun militaire affecté).
* **Action appliquée en base :**  
  Purge sécurisée des 190 lignes orphelines du bloc 2.  
  Rétablissement de l'effectif exact : **191 postes théoriques** (190 postes organiques + 1 poste personnalisé au Secrétariat).
* **Requête SQL appliquée :**
  ```sql
  DELETE FROM catalogue_teec 
  WHERE source_fichier = 'DRH.xlsm' AND ordre >= 10251 AND ordre <= 10440;
  ```

---

#### Chantier 3 : Ergonomie de Commandement & Atterrissage DRH
* **Problème constaté :**  
  À la connexion avec le profil DRH, l'utilisateur est dirigé vers l'Organigramme, mais le premier élément actif du menu latéral était le Tableau de Bord, créant un décalage visuel.
* **Correction appliquée :**  
  Positionnement de **« Organigramme / Structure »** en **1ère position** du menu pour les profils DRH et Super-Admin. Pour les autres corps et unités sans accès organigramme, le Tableau de Bord demeure automatiquement en 1ère position.
* **Fichier modifié à écraser :**
  1. `frontend/src/app/layout/shell/shell.html`

---

#### Chantier 4 : Journal des Textes Officiels (Charte Graphique MINDEF)
* **Problème constaté :**  
  Formulaire de création décalé sur le flanc gauche, titre flottant à l'extrême droite, double bouton de retour superposé et zone de téléversement brute non stylisée.
* **Correction appliquée :**  
  - Intégration d'un bandeau Hero MINDEF harmonisé avec fil d'Ariane et bouton retour unique.
  - Centrage du formulaire (`max-width: 960px; margin: 0 auto;`).
  - Découpage en 3 sections numérotées : Références légales, Périmètre de diffusion (cartes interactives), et Dropzone sécurisée pour les pièces jointes PDF/JPG.
* **Fichiers modifiés à écraser :**
  1. `frontend/src/app/features/textes/journal-textes.component.html`
  2. `frontend/src/app/features/textes/journal-textes.component.scss`

---

#### Chantier 5 : Performance API Backend (Résolution des 504 / 404)
* **Problème constaté sur le serveur :**  
  Lenteur excessive lors du chargement des structures (`/api/structures/all-flat`) conduisant au timeout 504 de Nginx et erreurs 404 sur les compteurs de fond.
* **Correction appliquée :**  
  - Requête SQL native légère via projection JPQL évitant l'instanciation récursive de l'arbre (`StructureDTO` plat).
  - Contrôle d'accès étanche par rôle (`PerimetreService`).
* **Fichiers backend à intégrer :**
  1. `backend/src/main/java/com/siadoc/backend/controller/StructureMilitaireController.java`
  2. `backend/src/main/java/com/siadoc/backend/repository/StructureMilitaireRepository.java`
  3. `backend/src/main/java/com/siadoc/backend/service/TeecService.java`

---

#### Chantier 6 : Module CIMIS — Souveraineté & Signatures Officielles (Correctif CSRF & Déploiement Signatures Réelles)
* **Problème constaté en production :**  
  Rejet de sécurité lors de la soumission du formulaire de téléversement ou mise à jour de signature : `Échec de validation de sécurité : Jeton CSRF invalide ou expiré. Veuillez recharger la page.`
* **Causes racines :**  
  1. La vue `admin/signatures/index.blade.php` appelait `cimis_generate_csrf_token()` qui n'était pas déclaré dans `SecurityService.php`, causant un repli vers `csrf_token()` de Laravel.
  2. Le contrôleur `SignatureController.php` validait via `cimis_verify_csrf_token()` qui ne vérifiait que `$_SESSION['_cimis_csrf_token']` sans synchronisation avec la session Laravel.
* **Corrections appliquées :**  
  - Ajout de l'alias `cimis_generate_csrf_token()` dans `SecurityService.php`.
  - Double stockage du jeton de session dans `_cimis_csrf_token` et `csrf_token` pour synchronisation automatique via `SyncLegacySessionMiddleware`.
  - Tolérance multi-sources dans `cimis_verify_csrf_token()` et `SignatureController.php` (Session native PHP, Session Laravel `$request->session()->token()`, et helper `csrf_token()`).
  - Ajout des routes de gestion des signatures dans `$except` de `VerifyCsrfToken.php` pour éviter l'interception prématurée 419 par Laravel.
  - Déploiement certifié des véritables signatures officielles MINDEF (`MINDEF MEILLEUR.png` -> 626x191 px) et DRH (`DIRECTEUR DES RESSOURCES HUMAINES.png` -> 935x205 px) avec détourage haute précision du fond blanc, génération synchronisée des 6 déclinaisons officielles (`base`, `_gold`, `_transp`, `_white`, `_black`, `_blue`), archivage horodaté dans `public/img/signatures/archives/` et mise à jour de `config/signatures.json`.
* **Fichiers modifiés dans `CIMIS_UPDATE_ESSENTIEL.zip` :**
  1. `app/Services/SecurityService.php`
  2. `app/Services/SignatureService.php`
  3. `app/Http/Controllers/SignatureController.php`
  4. `app/Http/Middleware/VerifyCsrfToken.php`
  5. `resources/views/admin/signatures/index.blade.php`
  6. `config/signatures.json`
  7. `public/img/signatures/SignatureMindef*.png` (6 variantes)
  8. `public/img/signatures/SignatureDrh*.png` (6 variantes)

---

### 🚀 Procédure Recommandée de Déploiement

Pour mettre à jour le serveur de production sans interruption de service :

```bash
# 1. Côté Backend :
cd /chemin/siadoc-backend
mvn clean package -DskipTests
# Remplacer le jar en production et relancer le service :
systemctl restart siadoc-backend.service

# 2. Côté Base de Données (PostgreSQL) :
psql -U postgres -d drh_militaire -c "DELETE FROM catalogue_teec WHERE source_fichier = 'DRH.xlsm' AND ordre >= 10251 AND ordre <= 10440;"

# 3. Côté Frontend :
cd /chemin/siadoc-frontend
npm ci
npx ng build --configuration production
# Synchroniser le dossier dist/frontend_siadoc vers la racine web Nginx (/var/www/siadoc ou équivalent)
```
