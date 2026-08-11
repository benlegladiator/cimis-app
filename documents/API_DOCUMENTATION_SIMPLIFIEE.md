# API CIMIS - GESMIL2.0
## Documentation Technique

### 🔐 Authentification
```
Authorization: GESMIL2.0-CIMIS-2026-KEY
```

### 🌐 URL de Base
```
http://127.0.0.1/cim/api_gesmil.php
```

---

## 📋 Endpoints

### 1. GET /candidats
Tous les candidats CIMIS

```bash
GET ?endpoint=candidats
```

### 2. GET /candidat
Un candidat spécifique

```bash
GET ?endpoint=candidat&matricule=CIM-12345
```

### 3. GET /militaires
Tous les militaires uniquement

```bash
GET ?endpoint=militaires
```

### 4. GET /militaires-periode
Militaires par période d'entrée en service

```bash
GET ?endpoint=militaires-periode&annee_debut=2010&annee_fin=2015
```

### 5. POST /candidat
Créer ou mettre à jour un candidat

```bash
POST ?endpoint=candidat
Content-Type: application/json

{
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
  "date_enrolement": "2010-06-15"
}
```

### 6. DELETE /candidat
Supprimer un candidat

```bash
DELETE ?endpoint=candidat&matricule=CIM-12345
```

### 7. GET /health
Vérifier le statut de l'API

```bash
GET ?endpoint=health
```

---

## 📝 Champs Disponibles

| Champ | Type | Obligatoire | Description |
|--------|-------|--------------|-------------|
| matricule_militaire | String | ✅ | Matricule militaire GESMIL2.0 |
| nom | String | ✅ | Nom de famille |
| prenom | String | ✅ | Prénom |
| date_naissance | Date | ✅ | YYYY-MM-DD |
| sexe | String | ✅ | MASCULIN/FEMININ |
| numero_cni | String | ✅ | Numéro CNI (18 chiffres) |
| taille | String | ❌ | Taille en cm |
| poids | String | ❌ | Poids en kg |
| groupe_sanguin | String | ❌ | Groupe sanguin |
| type_personnel | String | ❌ | MILITAIRE/CIVIL |
| unite | String | ❌ | Unité ou corps d'armée |
| grade | String | ❌ | Grade militaire |
| annee_dernier_galon | Integer | ❌ | Année du dernier galon |
| date_enrolement | Date | ❌ | Date d'enrôlement |
| date_dernier_grade | Date | ❌ | Date du dernier grade |

---

## ⚠️ Codes d'Erreur

| Code | Message |
|------|----------|
| 200 | Success |
| 400 | Bad Request |
| 401 | Unauthorized |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 500 | Internal Server Error |

---

## 🔧 Exemples

### Python
```python
import requests

headers = {
    'Authorization': 'GESMIL2.0-CIMIS-2026-KEY',
    'Content-Type': 'application/json'
}

# Récupérer les militaires 2010-2015
response = requests.get(
    'http://127.0.0.1/cim/api_gesmil.php?endpoint=militaires-periode&annee_debut=2010&annee_fin=2015',
    headers=headers
)
```

### JavaScript
```javascript
const headers = {
    'Authorization': 'GESMIL2.0-CIMIS-2026-KEY',
    'Content-Type': 'application/json'
};

// Récupérer tous les militaires
fetch('http://127.0.0.1/cim/api_gesmil.php?endpoint=militaires', {
    headers: headers
})
.then(response => response.json())
.then(data => console.log(data));
```

### curl
```bash
# Récupérer un militaire par matricule
curl -X GET \
  "http://127.0.0.1/cim/api_gesmil.php?endpoint=candidat&matricule=T17/12345" \
  -H "Authorization: GESMIL2.0-CIMIS-2026-KEY"
```

---

## 📞 Support

**API Key:** GESMIL2.0-CIMIS-2026-KEY  
**Logs:** logs/api_gesmil.log  
**Version:** 1.0.0
