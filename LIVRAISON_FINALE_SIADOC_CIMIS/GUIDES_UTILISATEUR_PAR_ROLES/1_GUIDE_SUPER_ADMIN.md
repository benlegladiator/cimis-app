# RÉPUBLIQUE DU CAMEROUN
## MINISTÈRE DE LA DÉFENSE — DIRECTION DES RESSOURCES HUMAINES
### SIADOC (Système Intégré d'Administration et de Documentation Militaire)

---
# GUIDE UTILISATEUR OFFICIEL — RÔLE : SUPER ADMINISTRATEUR (`SUPER_ADMIN`)

## 1. Présentation & Périmètre du Rôle
Le profil **SUPER_ADMIN** détient les prérogatives suprêmes sur l'ensemble de la plateforme SIADOC. Il est responsable de la gouvernance technique, de la sécurité des accès, de la gestion des identités, de l'intégrité de la base de données et de la supervision des connexions inter-systèmes (CIMIS, GESMIL).

## 2. Accès & Authentification Sécurisée
- **URL de connexion** : `https://siadoc.cm/login`
- **Politique de mot de passe** : 12 caractères minimum, majuscules, minuscules, chiffres et caractères spéciaux.
- **Protection Brute-Force** : Après 3 échecs consécutifs de mot de passe, le compte est temporairement consigné pendant 15 minutes.
- **Déverrouillage d'un compte utilisateur** :
  1. Accéder au menu **Administration > Gestion des Utilisateurs**.
  2. Filtrer par l'identifiant du compte bloqué.
  3. Cliquer sur l'action **« Déverrouiller le compte »** pour réinitialiser le compteur de tentatives et lever la restriction.

## 3. Gestion des Comptes & Attribution des Rôles
1. **Création d'un compte** : Renseigner impérativement :
   - *Identifiant unique (username)* : normé selon l'unité (ex: `cmd_ba101`, `drh_audit`, `sfnrrm_gestion`).
   - *Rôle attribué* : `SUPER_ADMIN`, `DRH`, `SFNRRM`, `ETAT_MAJOR`, `BRIGADE`, `BATAILLON`, `COMMANDANT_COMPAGNIE`, `COMMANDANT_CI`.
   - *Structure de rattachement* : Obligatoire pour cloisonner le périmètre d'action de l'officier.
2. **Création groupée / par lot** : Utiliser le bouton **« Création Groupée »** pour générer les accès d'une brigade ou d'un bataillon en une seule opération.

## 4. Supervision des 600 Kits USB de Déploiement
- **Menu** : **Supervision Kits USB** (`/kits-supervision`).
- **Suivi national** : Consultation en temps réel du taux de préparation, d'affectation et de remise effective des clés USB sur les 10 régions militaires.
- **Traçabilité** : Chaque clé remise est obligatoirement signée par l'officier récepteur avec enregistrement de l'horodatage et des coordonnées de décharge.

## 5. Maintenance & Sécurité de la Base de Données
- **Purge de maintenance** : Accessible discrètement via l'interface d'administration pour assainir les enregistrements orphelins de tests sans affecter les militaires réels.
- **Mouchard d'audit (Journal d'activité)** : Traçabilité absolue de chaque consultation de fiche, export d'extrant PDF, modification de solde ou création d'utilisateur avec l'adresse IP et l'identifiant de l'auteur.
