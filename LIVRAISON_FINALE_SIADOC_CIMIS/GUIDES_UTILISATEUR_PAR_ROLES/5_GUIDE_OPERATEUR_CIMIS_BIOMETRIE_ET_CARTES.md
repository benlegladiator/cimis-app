# RÉPUBLIQUE DU CAMEROUN
## MINISTÈRE DE LA DÉFENSE — DIRECTION DES RESSOURCES HUMAINES
### CIMIS (Système Biométrique Militaire d'Identification & de Cartes)

---
# GUIDE UTILISATEUR OFFICIEL — OPÉRATEUR ENRÔLEMENT BIOMÉTRIQUE & IMPRESSION CARTES

## 1. Architecture d'Interopérabilité CIMIS-SIADOC
CIMIS est l'application partenaire spécialisée dans la capture biométrique et la confection des cartes militaires physiques.
- **Port d'écoute CIMIS** : `http://localhost/cimcim/public` (ou `http://192.168.20.14:8000`)
- **Protocole de liaison** : API REST sécurisée par clé `X-API-KEY: siadoc-2026-cimis-integration`.
- **Canal de synchronisation** : Notification instantanée via webhook vers SIADOC (`/api/cimis/webhook/statut-carte`).

## 2. Station d'Enrôlement Biométrique sur le Terrain
Pour chaque militaire ou recrue :
1. **Photographie Numérique Haute Définition** :
   - Fond uniforme clair réglementaire, tenue militaire de rigueur, regard face à l'objectif.
   - Recadrage automatique au ratio officiel de la Carte d'Identité Militaire.
2. **Capture des Empreintes Digitales (Protocole 4-4-2)** :
   - Capteur biométrique certifié (Morpho / SecuGen / Futronic).
   - Capture des 4 doigts main gauche, 4 doigts main droite, puis les 2 pouces simultanément.
   - Contrôle qualité automatique du score NFIQ (qualité minimale requise : 80 %).
3. **Émargement & Signature Électronique** :
   - Signature manuscrite sur tablette numérique Signotec (format vectoriel transparent sans bordure parasite).

## 3. Impression & Confection des Cartes d'Identité Militaires
1. **Lancement de l'impression** :
   - Accéder au module d'impression CIMIS ou depuis SIADOC > Biométrie CIMIS.
   - Sélectionner le militaire par son matricule.
   - Le système génère automatiquement le Recto/Verso avec micro-lignes de sécurité, cartouche bilingue, QR Code crypté et photo protégée.
2. **Suivi du statut de la carte** :
   - Statuts gérés : `EN ATTENTE` $ightarrow$ `EN COURS D'IMPRESSION` $ightarrow$ `IMPRIMÉE` $ightarrow$ `DÉLIVRÉE` $ightarrow$ `SUSPENDUE`.
