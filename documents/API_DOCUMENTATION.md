# API CIMIS - GESMIL2.0
## Documentation Technique pour l'Échange de Données

### 🔐 Authentification
Toutes les requêtes doivent inclure la clé API dans les headers :
```
Authorization: GESMIL2.0-CIMIS-2026-KEY
```

### 🌐 URL de Base
```
http://127.0.0.1/cim/api_gesmil.php
```

---

## 📋 Endpoints Disponibles

### 1. 📊 GET /candidats
Récupérer tous les candidats CIMIS

**Requête :**
```bash
GET http://127.0.0.1/cim/api_gesmil.php?endpoint=candidats
Headers: Authorization: GESMIL2.0-CIMIS-2026-KEY
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "matricule": "CIM-12345",
      "matricule_militaire": "T17/12345",
      "nom": "KAMDEM",
      "prenom": "JEAN PIERRE",
      "date_naissance": "1985-03-15",
      "sexe": "MASCULIN",
      "numero_cni": "123456789012345678",
      "taille": "175",
      "poids": "72",
      "groupe_sanguin": "O+",
      "type_personnel": "MILITAIRE",
      "unite": "ARMÉE DE TERRE",
      "grade": "CAPITAINE",
      "annee_dernier_galon": "2023",
      "date_enrolement": "2010-06-15",
      "photo_path": "http://127.0.0.1/cim/uploads/photos/candidat_12345.jpg",
      "code_qr": "http://127.0.0.1/cim/uploads/qrs/cim_12345.png",
      "statut": "ACTIVE",
      "date_creation": "2026-04-06 12:00:00"
    }
  ],
  "count": 1,
  "timestamp": "2026-04-06 12:00:00"
}
```

---

### 2. 👤 GET /candidat
Récupérer un candidat spécifique

**Requête :**
```bash
GET http://127.0.0.1/cim/api_gesmil.php?endpoint=candidat&matricule=CIM-12345
Headers: Authorization: GESMIL2.0-CIMIS-2026-KEY
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "matricule": "CIM-12345",
    "matricule_militaire": "T17/12345",
    "nom": "KAMDEM",
    "prenom": "JEAN PIERRE",
    "date_naissance": "1985-03-15",
    "sexe": "MASCULIN",
    "numero_cni": "123456789012345678",
    "taille": "175",
    "poids": "72",
    "groupe_sanguin": "O+",
    "type_personnel": "MILITAIRE",
    "unite": "ARMÉE DE TERRE",
    "grade": "CAPITAINE",
    "annee_dernier_galon": "2023",
    "date_enrolement": "2010-06-15",
    "photo_path": "http://127.0.0.1/cim/uploads/photos/candidat_12345.jpg",
    "code_qr": "http://127.0.0.1/cim/uploads/qrs/cim_12345.png",
    "statut": "ACTIVE",
    "date_creation": "2026-04-06 12:00:00"
  },
  "timestamp": "2026-04-06 12:00:00"
}
```

---

### 3. 🪖 GET /militaires
Récupérer tous les militaires (type_personnel = 'MILITAIRE')

**Requête :**
```bash
GET http://127.0.0.1/cim/api_gesmil.php?endpoint=militaires
Headers: Authorization: GESMIL2.0-CIMIS-2026-KEY
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "matricule": "CIM-12345",
      "matricule_militaire": "T17/12345",
      "nom": "KAMDEM",
      "prenom": "JEAN PIERRE",
      "date_naissance": "1985-03-15",
      "sexe": "MASCULIN",
      "type_personnel": "MILITAIRE",
      "unite": "ARMÉE DE TERRE",
      "grade": "CAPITAINE",
      "date_enrolement": "2010-06-15",
      "photo_path": "http://127.0.0.1/cim/uploads/photos/candidat_12345.jpg",
      "code_qr": "http://127.0.0.1/cim/uploads/qrs/cim_12345.png",
      "statut": "ACTIVE"
    }
  ],
  "count": 1,
  "timestamp": "2026-04-06 12:00:00"
}
```

---

### 4. 📅 GET /militaires-periode
Récupérer les militaires par période d'entrée en service

**Requête :**
```bash
GET http://127.0.0.1/cim/api_gesmil.php?endpoint=militaires-periode&annee_debut=2010&annee_fin=2015
Headers: Authorization: GESMIL2.0-CIMIS-2026-KEY
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "matricule": "CIM-12345",
      "matricule_militaire": "T17/12345",
      "nom": "KAMDEM",
      "prenom": "JEAN PIERRE",
      "date_naissance": "1985-03-15",
      "sexe": "MASCULIN",
      "type_personnel": "MILITAIRE",
      "unite": "ARMÉE DE TERRE",
      "grade": "CAPITAINE",
      "date_enrolement": "2010-06-15",
      "photo_path": "http://127.0.0.1/cim/uploads/photos/candidat_12345.jpg",
      "code_qr": "http://127.0.0.1/cim/uploads/qrs/cim_12345.png",
      "statut": "ACTIVE"
    }
  ],
  "count": 1,
  "periode": "2010-2015",
  "timestamp": "2026-04-06 12:00:00"
}
```

---

### 5. ➕ POST /candidat
Créer ou mettre à jour un candidat

**Requête :**
```bash
POST http://127.0.0.1/cim/api_gesmil.php?endpoint=candidat
Headers: 
  Authorization: GESMIL2.0-CIMIS-2026-KEY
  Content-Type: application/json

Body:
{
  "matricule_militaire": "T17/67890",
  "nom": "MBARGA",
  "prenom": "MARIE CLAIRE",
  "date_naissance": "1990-07-22",
  "sexe": "FEMININ",
  "numero_cni": "987654321098765432",
  "taille": "165",
  "poids": "58",
  "groupe_sanguin": "A+",
  "type_personnel": "MILITAIRE",
  "unite": "ARMÉE DE TERRE",
  "grade": "LIEUTENANT",
  "annee_dernier_galon": "2022",
  "date_enrolement": "2015-09-10",
  "date_dernier_grade": "2022-01-01"
}
```

**Réponse (Création) :**
```json
{
  "success": true,
  "data": {
    "action": "created",
    "matricule_cimis": "CIM-24680",
    "message": "Candidat créé avec succès"
  },
  "timestamp": "2026-04-06 12:00:00"
}
```

**Réponse (Mise à jour) :**
```json
{
  "success": true,
  "data": {
    "action": "updated",
    "matricule_cimis": "CIM-12345",
    "message": "Candidat mis à jour avec succès"
  },
  "timestamp": "2026-04-06 12:00:00"
}
```

---

### 6. 🗑️ DELETE /candidat
Supprimer un candidat

**Requête :**
```bash
DELETE http://127.0.0.1/cim/api_gesmil.php?endpoint=candidat&matricule=CIM-12345
Headers: Authorization: GESMIL2.0-CIMIS-2026-KEY
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "deleted": true,
    "message": "Candidat supprimé avec succès"
  },
  "timestamp": "2026-04-06 12:00:00"
}
```

---

### 7. ❤️ GET /health
Vérifier le statut de l'API

**Requête :**
```bash
GET http://127.0.0.1/cim/api_gesmil.php?endpoint=health
Headers: Authorization: GESMIL2.0-CIMIS-2026-KEY
```

**Réponse :**
```json
{
  "success": true,
  "status": "API CIMIS opérationnelle",
  "version": "1.0.0",
  "timestamp": "2026-04-06 12:00:00",
  "database": "Connectée"
}
```

---

## 📝 Champs Disponibles

| Champ | Type | Obligatoire | Description |
|--------|-------|--------------|-------------|
| matricule_militaire | String | ✅ | Matricule militaire GESMIL2.0 |
| nom | String | ✅ | Nom de famille (sera mis en majuscules) |
| prenom | String | ✅ | Prénom |
| date_naissance | Date | ✅ | Format YYYY-MM-DD |
| sexe | String | ✅ | "MASCULIN" ou "FEMININ" |
| numero_cni | String | ✅ | Numéro CNI (18 chiffres) |
| taille | String | ❌ | Taille en cm |
| poids | String | ❌ | Poids en kg |
| groupe_sanguin | String | ❌ | Groupe sanguin (O+, A+, B+, AB+, etc.) |
| type_personnel | String | ❌ | "MILITAIRE" ou "CIVIL" |
| unite | String | ❌ | Unité ou corps d'armée |
| grade | String | ❌ | Grade militaire |
| annee_dernier_galon | Integer | ❌ | Année du dernier galon |
| date_enrolement | Date | ❌ | Date d'enrôlement |
| date_dernier_grade | Date | ❌ | Date du dernier grade |

---

## ⚠️ Codes d'Erreur

| Code | Message | Description |
|------|----------|-------------|
| 200 | Success | Requête réussie |
| 400 | Bad Request | Requête invalide |
| 401 | Unauthorized | Clé API invalide |
| 404 | Not Found | Candidat/endpoint non trouvé |
| 405 | Method Not Allowed | Méthode HTTP non autorisée |
| 500 | Internal Server Error | Erreur serveur |

**Format d'erreur :**
```json
{
  "success": false,
  "error": "Message d'erreur détaillé",
  "code": "ERROR_CODE",
  "timestamp": "2026-04-06 12:00:00"
}
```

---

## 🔧 Exemples d'Utilisation

### Python
```python
import requests

headers = {
    'Authorization': 'GESMIL2.0-CIMIS-2026-KEY',
    'Content-Type': 'application/json'
}

# Récupérer tous les candidats
response = requests.get(
    'http://127.0.0.1/cim/api_gesmil.php?endpoint=candidats',
    headers=headers
)

# Créer un candidat
data = {
    "matricule_militaire": "T17/99999",
    "nom": "TEST",
    "prenom": "API",
    "date_naissance": "1990-01-01",
    "sexe": "MASCULIN",
    "numero_cni": "123456789012345678"
}

response = requests.post(
    'http://127.0.0.1/cim/api_gesmil.php?endpoint=candidat',
    headers=headers,
    json=data
)
```

### JavaScript
```javascript
const headers = {
    'Authorization': 'GESMIL2.0-CIMIS-2026-KEY',
    'Content-Type': 'application/json'
};

// Récupérer tous les candidats
fetch('http://127.0.0.1/cim/api_gesmil.php?endpoint=candidats', {
    headers: headers
})
.then(response => response.json())
.then(data => console.log(data));

// Créer un candidat
const data = {
    matricule_militaire: "T17/99999",
    nom: "TEST",
    prenom: "API",
    date_naissance: "1990-01-01",
    sexe: "MASCULIN",
    numero_cni: "123456789012345678"
};

fetch('http://127.0.0.1/cim/api_gesmil.php?endpoint=candidat', {
    method: 'POST',
    headers: headers,
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(data => console.log(data));
```

### curl
```bash
# Récupérer tous les candidats
curl -X GET \
  "http://127.0.0.1/cim/api_gesmil.php?endpoint=candidats" \
  -H "Authorization: GESMIL2.0-CIMIS-2026-KEY"

# Créer un candidat
curl -X POST \
  "http://127.0.0.1/cim/api_gesmil.php?endpoint=candidat" \
  -H "Authorization: GESMIL2.0-CIMIS-2026-KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "matricule_militaire": "T17/99999",
    "nom": "TEST",
    "prenom": "API",
    "date_naissance": "1990-01-01",
    "sexe": "MASCULIN",
    "numero_cni": "123456789012345678"
  }'
```

---

## 📞 Support

**Développeur CIMIS :** Contactez l'administrateur système
**Logs API :** Disponibles dans `logs/api_gesmil.log`
**Monitoring :** Tous les appels sont enregistrés avec timestamp et IP

---

## 🔒 Sécurité

- 🔐 **Clé API requise** pour toutes les requêtes
- 📊 **Logging complet** de toutes les transactions
- 🛡️ **Validation des données** en entrée/sortie
- 🚫 **Protection contre** les injections SQL
- 🌐 **CORS configuré** pour les échanges inter-domaines

---

*Dernière mise à jour : 06/04/2026*
*Version API : 1.0.0*
