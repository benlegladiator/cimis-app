<?php
// Générateur de Texte de Sécurité CIMIS
require_once 'backend/config.php';

// Configuration
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: securite.php');
    exit();
}

// Fonction pour générer le texte de sécurité complet
function generateSecurityText() {
    $content = "# SÉCURITÉ CIMIS\n";
    $content .= "## Carte d'Identité Militaire Intégrée et Sécurisée\n\n";
    $content .= "Ministère de la Défense - République du Cameroun\n";
    $content .= "Date de génération : " . date('d/m/Y H:i:s') . "\n\n";
    $content .= "Classification : CONFIDENTIEL DÉFENSE\n\n";
    $content .= "---\n\n";
    
    // Section 1: Sécurité de l'Application
    $content .= "## 1. SÉCURITÉ DE L'APPLICATION\n\n";
    
    $content .= "### 1.1 Authentification Multi-Niveaux\n\n";
    $content .= "**Code Secret :** CIMIS2.02026\n";
    $content .= "**Accès Desktop :** Saisie clavier directe\n";
    $content .= "**Accès Mobile :** 10 actualisations automatiques\n";
    $content .= "**Page 403 :** Dissuasion avec compteur discret\n\n";
    
    $content .= "**Sessions Utilisateurs :**\n";
    $content .= "- Chiffrement AES-256 des sessions\n";
    $content .= "- Timeout automatique après 30 minutes d'inactivité\n";
    $content .= "- Destruction immédiate à la déconnexion\n";
    $content .= "- IP tracking pour détection d'anomalies\n\n";
    
    $content .= "### 1.2 Rôles et Permissions\n\n";
    $content .= "**SUPER_ADMIN :**\n";
    $content .= "- Accès complet à toutes les fonctionnalités\n";
    $content .= "- Gestion des utilisateurs et permissions\n";
    $content .= "- Accès aux logs et monitoring\n";
    $content .= "- Configuration système\n\n";
    
    $content .= "**ADMIN_ENROLEMENT :**\n";
    $content .= "- Création et modification des candidats\n";
    $content .= "- Upload photos et documents\n";
    $content .= "- Génération QR codes\n";
    $content .= "- Export des données\n\n";
    
    $content .= "**ADMIN_IMPRESSION :**\n";
    $content .= "- Validation des impressions de cartes\n";
    $content .= "- Gestion des files d'attente\n";
    $content .= "- Contrôle qualité des cartes\n";
    $content .= "- Statistiques de production\n\n";
    
    $content .= "### 1.3 Protection Contre les Attaques\n\n";
    $content .= "**Injection SQL :**\n";
    $content .= "- Requêtes préparées avec PDO\n";
    $content .= "- Validation stricte des paramètres\n";
    $content .= "- Escaping automatique des entrées\n";
    $content .= "- Logging des tentatives d'injection\n\n";
    
    $content .= "**XSS (Cross-Site Scripting) :**\n";
    $content .= "- htmlspecialchars() sur toutes les sorties\n";
    $content .= "- Content Security Policy configurée\n";
    $content .= "- Validation des entrées utilisateur\n";
    $content .= "- Sanitisation des fichiers uploadés\n\n";
    
    $content .= "**CSRF (Cross-Site Request Forgery) :**\n";
    $content .= "- Tokens CSRF sur les formulaires sensibles\n";
    $content .= "- Vérification du referer\n";
    $content .= "- Double submit pattern\n";
    $content .= "- Expiration des tokens\n\n";
    
    $content .= "**Brute Force :**\n";
    $content .= "- Limitation des tentatives : 5 essais maximum\n";
    $content .= "- Délai progressif entre tentatives\n";
    $content .= "- Blocage temporaire de l'IP\n";
    $content .= "- Alertes administrateur en cas d'attaque\n\n";
    
    // Section 2: Sécurité des Cartes
    $content .= "## 2. SÉCURITÉ DES CARTES\n\n";
    
    $content .= "### 2.1 Éléments de Sécurité Physique\n\n";
    $content .= "**Format PVC :**\n";
    $content .= "- Standard militaire CR80 (85.6 × 54 mm)\n";
    $content .= "- Épaisseur : 0.76mm\n";
    $content .= "- Matériau : PVC composite anti-contrefaçon\n";
    $content .= "- Durée de vie : 5 ans minimum\n\n";
    
    $content .= "**QR Codes Sécurisés :**\n";
    $content .= "- Encodage AES-256 des données\n";
    $content .= "- Clé unique par matricule CIMIS\n";
    $content .= "- Checksum de validation\n";
    $content .= "- Anti-copie avec micro-perforations\n";
    $content .= "- Lecture sécurisée uniquement avec application dédiée\n\n";
    
    $content .= "**Photos Biométriques :**\n";
    $content .= "- Résolution minimum : 300 DPI\n";
    $content .= "- Format standard : JPEG 2000\n";
    $content .= "- Watermark invisible avec matricule\n";
    $content .= "- Validation faciale optionnelle\n";
    $content .= "- Archivage sécurisé 10 ans\n\n";
    
    $content .= "### 2.2 Signatures et Validation\n\n";
    $content .= "**Signatures Officielles :**\n";
    $content .= "- JOSEPH BETI ASSOMO (officiers)\n";
    $content .= "- GOUFAN A RIM (sous-officiers)\n";
    $content .= "- Style manuscrit sécurisé\n";
    $content .= "- Double validation (jaune + blanc)\n";
    $content .= "- Timestamp dynamique\n\n";
    
    $content .= "**Éléments de Validation :**\n";
    $content .= "- Hologramme MINDEF personnalisé\n";
    $content .= "- Micro-texte visible uniquement au loupe\n";
    $content .= "- UV invisible sous lumière noire\n";
    $content .= "- Guillochage complexe en arrière-plan\n";
    $content .= "- Numéro de série unique\n\n";
    
    $content .= "### 2.3 Protection Contre la Contrefaçon\n\n";
    $content .= "**Technologies Intégrées :**\n";
    $content .= "- RFID/NFC optionnel pour vérification\n";
    $content .= "- Champ magnétique sécurisé\n";
    $content .= "- Laser gravé des informations critiques\n";
    $content .= "- Encres spéciales (UV, thermochromiques)\n";
    $content .= "- Multi-couche de protection\n\n";
    
    $content .= "**Contrôles Qualité :**\n";
    $content .= "- Validation automatique à l'impression\n";
    $content .= "- Contrôle visuel 100%\n";
    $content .= "- Scannage QR code systématique\n";
    $content .= "- Test UV sur échantillons\n";
    $content .= "- Archivage des modèles de référence\n\n";
    
    // Section 3: Protection des Données
    $content .= "## 3. PROTECTION DES DONNÉES\n\n";
    
    $content .= "### 3.1 Chiffrement\n\n";
    $content .= "**Base de Données :**\n";
    $content .= "- Chiffrement au repos avec AES-256\n";
    $content .= "- Clés de chiffrement rotatives\n";
    $content .= "- Backup chiffrés automatiquement\n";
    $content .= "- Accès sécurisé par certificats\n\n";
    
    $content .= "**Transfert de Données :**\n";
    $content .= "- HTTPS/TLS 1.3 obligatoire\n";
    $content .= "- Chiffrement bout-en-bout\n";
    $content .= "- Validation des certificats\n";
    $content .= "- Perfect Forward Secrecy\n\n";
    
    $content .= "**API GESMIL2.0 :**\n";
    $content .= "- Clé API : GESMIL2.0-CIMIS-2026-KEY\n";
    $content .= "- OAuth 2.0 pour l'authentification\n";
    $content .= "- Rate limiting par IP\n";
    $content .= "- Logging complet des échanges\n\n";
    
    $content .= "### 3.2 Gestion des Accès\n\n";
    $content .= "**Contrôle d'Accès :**\n";
    $content .= "- Principe du moindre privilège\n";
    $content .= "- Ségrégation des duties\n";
    $content .= "- Validation continue des permissions\n";
    $content .= "- Audit trails complets\n\n";
    
    $content .= "**Monitoring :**\n";
    $content .= "- Logs en temps réel de toutes les activités\n";
    $content .= "- Alertes automatiques sur comportements anormaux\n";
    $content .= "- Dashboard de sécurité pour administrateurs\n";
    $content .= "- Rapports journaliers d'activité\n\n";
    
    $content .= "### 3.3 Backup et Récupération\n\n";
    $content .= "**Stratégie de Backup :**\n";
    $content .= "- Backup quotidien automatique\n";
    $content .= "- Backup incrémental toutes les heures\n";
    $content .= "- Backup mensuel off-site\n";
    $content .= "- Tests de restauration mensuels\n\n";
    
    $content .= "**Récupération d'Urgence :**\n";
    $content .= "- Plan de continuité documenté\n";
    $content .= "- Temps de récupération < 4 heures\n";
    $content .= "- Perte de données < 1 heure\n";
    $content .= "- Équipe d'intervention 24/7\n\n";
    
    // Section 4: Audit et Monitoring
    $content .= "## 4. AUDIT ET MONITORING\n\n";
    
    $content .= "### 4.1 Logging Complet\n\n";
    $content .= "**Types de Logs :**\n";
    $content .= "- Logs d'authentification (succès/échec)\n";
    $content .= "- Logs d'activité utilisateur\n";
    $content .= "- Logs système et erreurs\n";
    $content .= "- Logs de sécurité et alertes\n";
    $content .= "- Logs d'audit des modifications\n\n";
    
    $content .= "**Format des Logs :**\n";
    $content .= "```\n";
    $content .= "[TIMESTAMP] [LEVEL] [IP] [USER] [ACTION] [DETAILS]\n";
    $content .= "[2026-04-07 09:57:00] [INFO] [192.168.1.100] [SUPER_ADMIN] [LOGIN] [Success]\n";
    $content .= "[2026-04-07 09:57:15] [WARN] [192.168.1.101] [UNKNOWN] [LOGIN] [Failed attempt]\n";
    $content .= "[2026-04-07 09:57:30] [CRIT] [192.168.1.102] [ADMIN] [DATA_EXPORT] [Unauthorized access]\n";
    $content .= "```\n\n";
    
    $content .= "### 4.2 Monitoring en Temps Réel\n\n";
    $content .= "**Indicateurs Clés :**\n";
    $content .= "- Taux d'échec d'authentification\n";
    $content .= "- Nombre de connexions simultanées\n";
    $content .= "- Volume de données transférées\n";
    $content .= "- Temps de réponse des requêtes\n";
    $content .= "- Alertes de sécurité actives\n\n";
    
    $content .= "**Tableaux de Bord :**\n";
    $content .= "- Dashboard sécurité pour SUPER_ADMIN\n";
    $content .= "- Alertes en temps réel par email/SMS\n";
    $content .= "- Graphiques d'activité par utilisateur\n";
    $content .= "- Cartographie des accès géographique\n\n";
    
    $content .= "### 4.3 Audits de Sécurité\n\n";
    $content .= "**Audits Automatisés :**\n";
    $content .= "- Scan de vulnérabilités hebdomadaire\n";
    $content .= "- Test de pénétration mensuel\n";
    $content .= "- Audit des permissions trimestriel\n";
    $content .= "- Revues de code régulières\n\n";
    
    $content .= "**Audits Manuels :**\n";
    $content .= "- Audit interne annuel\n";
    $content .= "- Audit externe semestriel\n";
    $content .= "- Audit de conformité MINDEF\n";
    $content .= "- Audit de certification sécurité\n\n";
    
    // Section 5: Procédures d'Urgence
    $content .= "## 5. PROCÉDURES D'URGENCE\n\n";
    
    $content .= "### 5.1 Incidents de Sécurité\n\n";
    $content .= "**Classification des Incidents :**\n";
    $content .= "- **Niveau 1 (Critique)** : Brèche de données, compromission système\n";
    $content .= "- **Niveau 2 (Majeur)** : Attaque en cours, dégradation de service\n";
    $content .= "- **Niveau 3 (Mineur)** : Tentative d'attaque, comportement anormal\n";
    $content .= "- **Niveau 4 (Info)** : Activité suspecte, alerte monitoring\n\n";
    
    $content .= "**Plan d'Intervention :**\n";
    $content .= "1. Détection immédiate (automatique)\n";
    $content .= "2. Évaluation de l'impact (5 minutes)\n";
    $content .= "3. Containment (15 minutes)\n";
    $content .= "4. Éradication (1 heure)\n";
    $content .= "5. Récupération (4 heures)\n";
    $content .= "6. Post-mortem (24 heures)\n\n";
    
    $content .= "### 5.2 Compromission de Cartes\n\n";
    $content .= "**Procédure en Cas de Vol/Perte :**\n";
    $content .= "1. Signalement immédiat au système\n";
    $content .= "2. Invalidations automatique du QR code\n";
    $content .= "3. Blocage de l'accès aux systèmes\n";
    $content .= "4. Émission d'une nouvelle carte (24h)\n";
    $content .= "5. Mise à jour des bases de données\n\n";
    
    $content .= "**Liste Noire :**\n";
    $content .= "- Centralisation des cartes invalidées\n";
    $content .= "- Synchronisation temps réel avec tous les systèmes\n";
    $content .= "- Archivage des incidents (10 ans)\n";
    $content .= "- Analyse de tendances des fraudes\n\n";
    
    $content .= "### 5.3 Continuité de Service\n\n";
    $content .= "**Plan de Continuité :**\n";
    $content .= "- Site de secours géographiquement distant\n";
    $content .= "- Réplication synchrone des données critiques\n";
    $content .= "- Basculement automatique en cas de défaillance\n";
    $content .= "- Tests mensuels de basculement\n\n";
    
    $content .= "**Communication de Crise :**\n";
    $content .= "- Protocole d'alerte hiérarchique\n";
    $content .= "- Communication unifiée aux utilisateurs\n";
    $content .= "- Mise à jour régulière du statut\n";
    $content .= "- Post-crise : leçons apprises\n\n";
    
    // Section 6: Compliance et Certifications
    $content .= "## 6. COMPLIANCE ET CERTIFICATIONS\n\n";
    
    $content .= "### 6.1 Normes Applicables\n\n";
    $content .= "- **ISO 27001** : Management de la sécurité de l'information\n";
    $content .= "- **ISO 15408** : Critères communs d'évaluation de sécurité\n";
    $content .= "- **NIST SP 800-53** : Contrôles de sécurité fédéraux\n";
    $content .= "- **GDPR** : Protection des données personnelles\n\n";
    
    $content .= "### 6.2 Certifications Requises\n\n";
    $content .= "- Certification MINDEF niveau sécurité\n";
    $content .= "- Audit de conformité militaire\n";
    $content .= "- Certification de protection des données\n";
    $content .= "- Accréditation des systèmes d'information\n\n";
    
    $content .= "### 6.3 Documentation\n\n";
    $content .= "- Politique de sécurité complète\n";
    $content .= "- Procédures opérationnelles détaillées\n";
    $content .= "- Manuels d'utilisation sécurisés\n";
    $content .= "- Guides de bonnes pratiques\n\n";
    
    // Section 7: Contacts d'Urgence
    $content .= "## 7. CONTACTS D'URGENCE\n\n";
    
    $content .= "### Équipe de Sécurité\n";
    $content .= "- **Administrateur Principal** : 24/7\n";
    $content .= "- **Équipe Technique** : 6h-22h\n";
    $content .= "- **Support MINDEF** : 8h-18h\n";
    $content .= "- **Alertes Critiques** : SMS + Email\n\n";
    
    $content .= "### Coordonnées\n";
    $content .= "- **Email Sécurité** : security@cimis.mindef.cm\n";
    $content .= "- **Hotline** : +237 XXX XXX XXX\n";
    $content .= "- **Urgence** : +237 XXX XXX XXX\n";
    $content .= "- **Reporting** : security@cimis.mindef.cm\n\n";
    
    // Footer
    $content .= "---\n\n";
    $content .= "*Document de Sécurité CIMIS - Version 1.0*\n";
    $content .= "*Dernière mise à jour : " . date('d/m/Y') . "*\n";
    $content .= "*Classification : CONFIDENTIEL DÉFENSE*\n";
    
    return $content;
}

// Traitement des actions
$action = $_GET['action'] ?? 'view';

switch ($action) {
    case 'generate':
        $content = generateSecurityText();
        
        // Headers pour le téléchargement
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="SECURITE_CIMIS_' . date('Y-m-d_H-i-s') . '.txt"');
        
        echo $content;
        break;
        
    case 'view':
    default:
        // Affichage simple du contenu
        $content = generateSecurityText();
        
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur de Sécurité CIMIS</title>
    <style>
        body {
            font-family: "Courier New", monospace;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1e3c72;
        }
        .content {
            white-space: pre-wrap;
            font-family: "Courier New", monospace;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #fafafa;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            max-height: 600px;
            overflow-y: auto;
        }
        .actions {
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            background: #1e3c72;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #2a5298;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Générateur de Sécurité CIMIS</h1>
            <p>Carte d\'Identité Militaire Intégrée et Sécurisée</p>
        </div>
        
        <div class="content">' . htmlspecialchars($content) . '</div>
        
        <div class="actions">
            <a href="?action=generate" class="btn">Télécharger le fichier texte</a>
            <a href="securite_cimis.php" class="btn">Retour au dashboard</a>
        </div>
    </div>
</body>
</html>';
        break;
}
?>
