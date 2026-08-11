# 📄 DOCUMENTATION TECHNIQUE API SIADOC-CIMIS
## Intégration des Données Administratives Militaires

---

## 📋 TABLE DES MATIÈRES

1. [Vue d'ensemble](#vue-densemble)
2. [Configuration technique](#configuration-technique)
3. [Authentification](#authentification)
4. [Endpoints disponibles (SIADOC → Partenaire)](#endpoints-disponibles)
5. [Modèle de données](#modèle-de-données)
6. [Exemples d'implémentation](#exemples-dimplémentation)
7. [Gestion des erreurs](#gestion-des-erreurs)

---

## 🎯 VUE D'ENSEMBLE

### Objectif
Permettre aux applications partenaires (CIMIS) d'accéder aux données administratives du personnel militaire stockées dans SIADOC et de synchroniser les données biométriques.

### Architecture
- **Système source** : SIADOC (Système Intégré d'Administration et d'Opérations Documentaires)
- **Système cible** : CIMIS (Cameroon Military Identification System)
- **Protocole** : REST API over HTTPS
- **Format** : JSON
- **Authentification** : Clé API (X-API-KEY)

---

## ⚙️ CONFIGURATION TECHNIQUE

### Environnement de Production
- **Base URL** : `https://siadoc.onrender.com`
- **Format** : application/json
- **Encodage** : UTF-8

---

## 🔐 AUTHENTIFICATION

### Clé API
Toutes les requêtes doivent inclure la clé API suivante dans le header de la requête.

```http
X-API-KEY: siadoc-2026-cimis-integration
```

---

## 🌐 ENDPOINTS DISPONIBLES

### 1. Consulter un militaire spécifique
Récupère les informations administratives essentielles d'un personnel.

```http
GET /api/export/militaire/info?matricule={matricule}
```

**Paramètres** :
- `matricule` (Obligatoire) : Matricule militaire ou de solde.

### 2. Lister tout le personnel actif
Récupère la liste complète des militaires enregistrés pour une synchronisation globale.

```http
GET /api/export/militaire/info/all
```

### 3. Envoyer des données biométriques vers SIADOC
Permet à CIMIS de pousser les informations de carte et de biométrie.

```http
POST /api/cimis/recevoir_carte
```

**Format du Body (JSON)** :
```json
{
  "data": {
    "matricule_militaire": "T14/6584",
    "matricule_cimis": "CIM-12345",
    "nom": "ESSOMBA",
    "prenom": "Jean",
    "grade": "Capitaine",
    "unite": "ARMÉE DE L'AIR",
    "photo_base64": "...",
    "qr_code": "...",
    "empreinte": "..."
  }
}
```

---

## 📊 MODÈLE DE DONNÉES (Réponse Info)

| Champ | Type | Description |
| :--- | :--- | :--- |
| `nom` | String | Nom patronymique |
| `prenom` | String | Prénoms |
| `matricule` | String | Matricule unique |
| `dateNaissance` | Date | Format YYYY-MM-DD |
| `corps` | String | Corps d'armée |
| `grade` | String | Grade actuel |
| `dateGrade` | Date | Date de dernière promotion |
| `sexe` | String | M / F |

---

## 💻 EXEMPLE D'IMPLÉMENTATION (PHP)

```php
<?php
$api_key = 'siadoc-2026-cimis-integration';
$url = 'https://siadoc.onrender.com/api/export/militaire/info?matricule=T14/6584';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-KEY: ' . $api_key,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $militaire = json_decode($response, true);
    echo "Nom : " . $militaire['nom'];
} else {
    echo "Erreur : " . $http_code;
}
?>
```

---

## ⚠️ GESTION DES ERREURS

- **401 Unauthorized** : Clé API manquante ou invalide.
- **404 Not Found** : Le matricule fourni n'existe pas dans la base SIADOC.
- **500 Internal Error** : Erreur serveur lors du traitement.

---
**Contact Technique** : Admin SIADOC
**Dernière mise à jour** : Mai 2026
