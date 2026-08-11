# ========================================
# INSTRUCTIONS POUR INSÉRER LES DONNÉES
# ========================================

## 📋 ÉTAT ACTUEL
✅ Backend démarré et fonctionnel
✅ Frontend démarré et fonctionnel  
✅ Connexion frontend-backend OK
❌ Base de données VIDE (retourne [])

## 🎯 ACTION REQUISE

Il faut insérer les données de test que j'ai créées :

### 1. Fichiers disponibles :
- `insertion_donnees_test.sql` - Script SQL complet
- `profils_et_roles.md` - Structure des rôles
- `militaires_cameroonais.md` - Liste des militaires
- `guide_donnees_test.md` - Guide d'utilisation

### 2. Données à insérer :
- 5 RMIA (Régions Militaires)
- 5 Brigades
- 5 Bataillons  
- 6 Compagnies
- 36 Militaires (noms camerounais)
- 4 Utilisateurs avec rôles

## 🚀 MÉTHODE D'INSERTION

### Option A - Via votre base de données
```bash
# Se connecter à PostgreSQL
psql -U votre_user -d siadoc_db

# Exécuter le script
\i insertion_donnees_test.sql
```

### Option B - Via Spring Boot
Le script SQL sera exécuté automatiquement au démarrage si vous ajoutez :

```java
// Dans votre application.properties ou application.yml
spring.sql.init.mode=always
spring.jpa.hibernate.ddl-auto=update
```

### Option C - Via interface d'administration
1. Connectez-vous avec `rmia1_admin` / `password123`
2. Allez dans "Administration"
3. Utilisez l'interface pour insérer les données

## 🔑 COMPTES DE TEST APRÈS INSERTION

| Rôle | Username | Mot de passe |
|-------|----------|-------------|
| RMIA Admin | `rmia1_admin` | `password123` |
| Chef Brigade | `bde1_chef` | `password123` |
| Chef Bataillon | `bta1_chef` | `password123` |
| Chef Compagnie | `cie1_chef` | `password123` |

## ✅ VALIDATION APRÈS INSERTION

Après insertion, testez :
1. `curl http://localhost:8080/api/militaires` → doit retourner 36 militaires
2. Recherche dans le frontend → doit trouver des militaires
3. Connexion avec les comptes de test

## 🎯 PROCHAINE ÉTAPE

**Voulez-vous que je vous aide à :**
1. **Exécuter le script SQL** directement ?
2. **Configurer Spring Boot** pour exécuter le script automatiquement ?
3. **Créer une interface d'administration** pour insérer les données ?

**Dites-moi quelle option vous préférez !**
