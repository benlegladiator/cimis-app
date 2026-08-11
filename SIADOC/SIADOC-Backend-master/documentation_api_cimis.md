# 📄 Contrat d'Interface API (SIADOC ↔ CIMIS)

Ce document décrit les spécifications des d'échanges de données REST entre le système d'information SIADOC et l'application partenaire CIMIS.

## 🔐 1. Authentification globale
Toutes les requêtes adressées à l'API SIADOC doivent obligatoirement inclure un Header HTTP contenant la clé d'API (fournie par l'administrateur SIADOC).

**Header requis pour chaque requête :**
```http
X-API-KEY: [Votre-Clé-API-Fournie]
```

---

## ⬅️ 2. Récupérer les informations d'un Militaire (SIADOC vers CIMIS)
Cette route permet à CIMIS d'interroger SIADOC pour obtenir les informations administratives essentielles d'un personnel identifié par son matricule.

- **Méthode :** `GET`
- **URL :** `[HOST_SIADOC]/api/export/militaire/info`
- **Paramètres d'URL (Query) :**
  - `matricule` (String, Obligatoire) : Matricule militaire ou matricule de solde.

### Exemple de Requête
```http
GET /api/export/militaire/info?matricule=MAT-2023-12345 HTTP/1.1
X-API-KEY: a1b2c3d4-e5f6-7890
```

### Exemple de Réponse (Succès 200 OK)
```json
{
  "nom": "ESSOMBA",
  "prenom": "Jean-Pierre",
  "matricule": "MAT-2023-12345",
  "dateNaissance": "1985-03-15",
  "corps": "AA",
  "grade": "Sergent",
  "dateGrade": "2020-07-01",
  "sexe": "M",
  "numeroCNI": "203498993"
}
```
*Note : Le format des dates est en norme ISO-8601 (`YYYY-MM-DD`)..*

---

## ➡️ 3. Envoyer des données biométriques (CIMIS vers SIADOC)
Cette route permet à CIMIS de pousser les données biométriques et le QR Code vers la base de données SIADOC. Si des données existent déjà pour ce matricule, elles seront mises à jour (Upsert).

- **Méthode :** `POST`
- **URL :** `[HOST_SIADOC]/api/import/cimis/biometrie`
- **Content-Type :** `application/json`

### Remarques sur le Payload
- Toutes les images/données binaires doivent être encodées en format **Base64 Brut** (sans préfixe `data:image/png;base64,`).
- Les champs inutilisés peuvent être omis ou envoyés avec la valeur `null`.

### Exemple de Requête (Payload JSON)
```json
{
  "matricule": "MAT-2023-12345",
  
  "numeroCIM": "CIM-20230087",

  "photoVisage": "iVBORw0KGgoAAAANSUhEUgAA...",
  "photoVisageType": "image/jpeg",
  
  "empreinteDoigt1": "iVBORw0KGgoAAAANSUhEUgAA...",
  "empreinteDoigt1Type": "image/png",
  
  "empreinteDoigt2": null,
  "empreinteDoigt2Type": null,
  
  "qrCodeImage": "iVBORw0KGgoAAAANSUhEUgAA...",
  "qrCodeContenu": "https://cimis.cm/verify/MAT-2023-12345"
}
```

### Réponses possibles
- `200 OK` : Enregistrement/Mise à jour réussi. Le corps de la requête retournera un texte de confirmation (ex: *"Données biométriques mises à jour pour le militaire : MAT-2023-12345"*).
- `401 Unauthorized` : Clé API incorrecte ou manquante.
- `400 Bad Request` : Matricule manquant dans le JSON.
- `500 Internal Server Error` : Militaire non trouvé dans la base SIADOC pour le matricule fourni.

---
*Ce document est la source de vérité pour l'intégration logicielle. En cas d'erreur `500`, vérifier que le matricule cible a été créé au préalable dans SIADOC avant d'envoyer des données biométriques.*
