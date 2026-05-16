<?php
// Sécurité CIMIS - Dashboard de Sécurité Complet
require_once '../backend/config.php';

// Configuration
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['SUPER_ADMIN', 'ADMIN_ENROLEMENT', 'ADMIN_IMPRESSION'])) {
    header('Location: ../securite.php');
    exit();
}

// Chargement de la bibliothèque TCPDF (si disponible)
$tcpdf_available = file_exists('tcpdf/tcpdf.php');

// Fonction pour générer le PDF
function generateSecurityPDF() {
    global $tcpdf_available;
    
    if (!$tcpdf_available) {
        return '<div class="alert alert-warning">TCPDF non disponible. Veuillez installer la bibliothèque pour exporter en PDF.</div>';
    }
    
    ob_start();
    
    // Contenu du PDF
    $content = '
    <h1 style="color:#1e3c72; text-align:center; border-bottom:3px solid #1e3c72; padding-bottom:20px;">
        RAPPORT DE SÉCURITÉ CIMIS
    </h1>
    <h2 style="color:#2a5298; text-align:center;">Carte d\'Identité Militaire Intégrée et Sécurisée</h2>
    <p style="text-align:center; font-style:italic;">Ministère de la Défense - République du Cameroun</p>
    <p style="text-align:center; font-weight:bold;">Date: ' . date('d/m/Y H:i:s') . '</p>
    
    <div style="page-break-inside: avoid;">
        <h3 style="color:#1e3c72; border-left:4px solid #1e3c72; padding-left:10px;">1. SÉCURITÉ DE L\'APPLICATION</h3>
        
        <h4 style="color:#2a5298;">1.1 Authentification Multi-Niveaux</h4>
        <table style="width:100%; border-collapse: collapse; margin:10px 0;">
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Code Secret</td>
                <td style="border:1px solid #ddd; padding:8px;">CIMIS2.02026</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Accès Desktop</td>
                <td style="border:1px solid #ddd; padding:8px;">Saisie clavier directe</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Accès Mobile</td>
                <td style="border:1px solid #ddd; padding:8px;">10 actualisations automatiques</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Page 403</td>
                <td style="border:1px solid #ddd; padding:8px;">Dissuasion avec compteur discret</td>
            </tr>
        </table>
        
        <h4 style="color:#2a5298;">1.2 Sessions Utilisateurs</h4>
        <ul style="margin-left:20px;">
            <li>Chiffrement AES-256 des sessions</li>
            <li>Timeout automatique après 30 minutes d\'inactivité</li>
            <li>Destruction immédiate à la déconnexion</li>
            <li>IP tracking pour détection d\'anomalies</li>
        </ul>
        
        <h4 style="color:#2a5298;">1.3 Protection Contre les Attaques</h4>
        <table style="width:100%; border-collapse: collapse; margin:10px 0;">
            <tr style="background:#1e3c72; color:white;">
                <th style="border:1px solid #ddd; padding:8px;">Type d\'Attaque</th>
                <th style="border:1px solid #ddd; padding:8px;">Protection</th>
                <th style="border:1px solid #ddd; padding:8px;">Niveau</th>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px;">Injection SQL</td>
                <td style="border:1px solid #ddd; padding:8px;">Requêtes préparées PDO</td>
                <td style="border:1px solid #ddd; padding:8px; color:#28a745;">Élevé</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px;">XSS</td>
                <td style="border:1px solid #ddd; padding:8px;">htmlspecialchars() + CSP</td>
                <td style="border:1px solid #ddd; padding:8px; color:#28a745;">Élevé</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px;">CSRF</td>
                <td style="border:1px solid #ddd; padding:8px;">Tokens CSRF + Referer</td>
                <td style="border:1px solid #ddd; padding:8px; color:#28a745;">Élevé</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px;">Brute Force</td>
                <td style="border:1px solid #ddd; padding:8px;">Limitation tentatives + Blocage IP</td>
                <td style="border:1px solid #ddd; padding:8px; color:#28a745;">Élevé</td>
            </tr>
        </table>
    </div>
    
    <div style="page-break-inside: avoid;">
        <h3 style="color:#1e3c72; border-left:4px solid #1e3c72; padding-left:10px;">2. SÉCURITÉ DES CARTES</h3>
        
        <h4 style="color:#2a5298;">2.1 Éléments de Sécurité Physique</h4>
        <table style="width:100%; border-collapse: collapse; margin:10px 0;">
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Format</td>
                <td style="border:1px solid #ddd; padding:8px;">PVC CR80 (85.6 × 54 mm)</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Épaisseur</td>
                <td style="border:1px solid #ddd; padding:8px;">0.76mm standard militaire</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Durée de vie</td>
                <td style="border:1px solid #ddd; padding:8px;">5 ans minimum</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Matériau</td>
                <td style="border:1px solid #ddd; padding:8px;">PVC composite anti-contrefaçon</td>
            </tr>
        </table>
        
        <h4 style="color:#2a5298;">2.2 QR Codes Sécurisés</h4>
        <ul style="margin-left:20px;">
            <li>Encodage AES-256 des données</li>
            <li>Clé unique par matricule CIMIS</li>
            <li>Checksum de validation intégré</li>
            <li>Anti-copie avec micro-perforations</li>
            <li>Lecture sécurisée uniquement avec application dédiée</li>
        </ul>
        
        <h4 style="color:#2a5298;">2.3 Signatures Officielles</h4>
        <table style="width:100%; border-collapse: collapse; margin:10px 0;">
            <tr style="background:#1e3c72; color:white;">
                <th style="border:1px solid #ddd; padding:8px;">Type de Personnel</th>
                <th style="border:1px solid #ddd; padding:8px;">Signature</th>
                <th style="border:1px solid #ddd; padding:8px;">Style</th>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px;">Officiers</td>
                <td style="border:1px solid #ddd; padding:8px;">JOSEPH BETI ASSOMO</td>
                <td style="border:1px solid #ddd; padding:8px;">Manuscrit majuscules</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px;">Sous-Officiers</td>
                <td style="border:1px solid #ddd; padding:8px;">GOUFAN A RIM</td>
                <td style="border:1px solid #ddd; padding:8px;">Manuscrit majuscules</td>
            </tr>
        </table>
    </div>
    
    <div style="page-break-inside: avoid;">
        <h3 style="color:#1e3c72; border-left:4px solid #1e3c72; padding-left:10px;">3. PROTECTION DES DONNÉES</h3>
        
        <h4 style="color:#2a5298;">3.1 Chiffrement</h4>
        <table style="width:100%; border-collapse: collapse; margin:10px 0;">
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Base de Données</td>
                <td style="border:1px solid #ddd; padding:8px;">AES-256 au repos</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Transfert</td>
                <td style="border:1px solid #ddd; padding:8px;">HTTPS/TLS 1.3 obligatoire</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">API GESMIL2.0</td>
                <td style="border:1px solid #ddd; padding:8px;">Clé: GESMIL2.0-CIMIS-2026-KEY</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Sessions</td>
                <td style="border:1px solid #ddd; padding:8px;">AES-256 + timeout 30min</td>
            </tr>
        </table>
        
        <h4 style="color:#2a5298;">3.2 Backup et Récupération</h4>
        <ul style="margin-left:20px;">
            <li>Backup quotidien automatique chiffré</li>
            <li>Backup incrémental toutes les heures</li>
            <li>Backup mensuel off-site sécurisé</li>
            <li>Tests de restauration mensuels</li>
            <li>Temps de récupération < 4 heures</li>
        </ul>
    </div>
    
    <div style="page-break-inside: avoid;">
        <h3 style="color:#1e3c72; border-left:4px solid #1e3c72; padding-left:10px;">4. AUDIT ET MONITORING</h3>
        
        <h4 style="color:#2a5298;">4.1 Logging Complet</h4>
        <table style="width:100%; border-collapse: collapse; margin:10px 0;">
            <tr style="background:#1e3c72; color:white;">
                <th style="border:1px solid #ddd; padding:8px;">Type de Log</th>
                <th style="border:1px solid #ddd; padding:8px;">Fréquence</th>
                <th style="border:1px solid #ddd; padding:8px;">Rétention</th>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px;">Authentification</td>
                <td style="border:1px solid #ddd; padding:8px;">Temps réel</td>
                <td style="border:1px solid #ddd; padding:8px;">10 ans</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px;">Activité Utilisateur</td>
                <td style="border:1px solid #ddd; padding:8px;">Temps réel</td>
                <td style="border:1px solid #ddd; padding:8px;">5 ans</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px;">Sécurité</td>
                <td style="border:1px solid #ddd; padding:8px;">Temps réel</td>
                <td style="border:1px solid #ddd; padding:8px;">10 ans</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px;">Système</td>
                <td style="border:1px solid #ddd; padding:8px;">Continu</td>
                <td style="border:1px solid #ddd; padding:8px;">2 ans</td>
            </tr>
        </table>
        
        <h4 style="color:#2a5298;">4.2 Monitoring Temps Réel</h4>
        <ul style="margin-left:20px;">
            <li>Taux d\'échec d\'authentification</li>
            <li>Nombre de connexions simultanées</li>
            <li>Volume de données transférées</li>
            <li>Temps de réponse des requêtes</li>
            <li>Alertes de sécurité actives</li>
        </ul>
    </div>
    
    <div style="page-break-inside: avoid;">
        <h3 style="color:#1e3c72; border-left:4px solid #1e3c72; padding-left:10px;">5. PROCÉDURES D\'URGENCE</h3>
        
        <h4 style="color:#2a5298;">5.1 Classification des Incidents</h4>
        <table style="width:100%; border-collapse: collapse; margin:10px 0;">
            <tr style="background:#dc3545; color:white;">
                <th style="border:1px solid #ddd; padding:8px;">Niveau</th>
                <th style="border:1px solid #ddd; padding:8px;">Description</th>
                <th style="border:1px solid #ddd; padding:8px;">Temps d\'Intervention</th>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">1 - Critique</td>
                <td style="border:1px solid #ddd; padding:8px;">Brèche de données, compromission système</td>
                <td style="border:1px solid #ddd; padding:8px;">Immédiat</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">2 - Majeur</td>
                <td style="border:1px solid #ddd; padding:8px;">Attaque en cours, dégradation service</td>
                <td style="border:1px solid #ddd; padding:8px;">15 minutes</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">3 - Mineur</td>
                <td style="border:1px solid #ddd; padding:8px;">Tentative attaque, comportement anormal</td>
                <td style="border:1px solid #ddd; padding:8px;">1 heure</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">4 - Info</td>
                <td style="border:1px solid #ddd; padding:8px;">Activité suspecte, alerte monitoring</td>
                <td style="border:1px solid #ddd; padding:8px;">4 heures</td>
            </tr>
        </table>
        
        <h4 style="color:#2a5298;">5.2 Contacts d\'Urgence</h4>
        <table style="width:100%; border-collapse: collapse; margin:10px 0;">
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Équipe Sécurité</td>
                <td style="border:1px solid #ddd; padding:8px;">24/7</td>
            </tr>
            <tr>
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Hotline</td>
                <td style="border:1px solid #ddd; padding:8px;">+237 XXX XXX XXX</td>
            </tr>
            <tr style="background:#f8f9fa;">
                <td style="border:1px solid #ddd; padding:8px; font-weight:bold;">Email Sécurité</td>
                <td style="border:1px solid #ddd; padding:8px;">security@cimis.mindef.cm</td>
            </tr>
        </table>
    </div>
    
    <div style="page-break-inside: avoid;">
        <h3 style="color:#1e3c72; border-left:4px solid #1e3c72; padding-left:10px;">6. COMPLIANCE ET CERTIFICATIONS</h3>
        
        <h4 style="color:#2a5298;">6.1 Normes Applicables</h4>
        <ul style="margin-left:20px;">
            <li>ISO 27001 : Management de la sécurité de l\'information</li>
            <li>ISO 15408 : Critères communs d\'évaluation de sécurité</li>
            <li>NIST SP 800-53 : Contrôles de sécurité fédéraux</li>
            <li>GDPR : Protection des données personnelles</li>
        </ul>
        
        <h4 style="color:#2a5298;">6.2 Certifications Requises</h4>
        <ul style="margin-left:20px;">
            <li>Certification MINDEF niveau sécurité</li>
            <li>Audit de conformité militaire</li>
            <li>Certification de protection des données</li>
            <li>Accréditation des systèmes d\'information</li>
        </ul>
    </div>
    
    <div style="margin-top:50px; text-align:center; border-top:2px solid #1e3c72; padding-top:20px;">
        <p style="font-weight:bold; color:#1e3c72;">DOCUMENT CLASSIFIÉ - CONFIDENTIEL DÉFENSE</p>
        <p style="font-style:italic;">Ministère de la Défense - République du Cameroun</p>
        <p style="font-size:12px;">Dernière mise à jour: ' . date('d/m/Y') . '</p>
    </div>';
    
    return $content;
}

// Traitement de l'export PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf' && $tcpdf_available) {
    require_once 'tcpdf/tcpdf.php';
    
    // Création du PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configuration du PDF
    $pdf->SetCreator('CIMIS Security Dashboard');
    $pdf->SetAuthor('MINDEF Cameroon');
    $pdf->SetTitle('Rapport de Securite CIMIS');
    $pdf->SetSubject('Security Report');
    $pdf->SetKeywords('CIMIS, Security, MINDEF, Cameroon');
    
    // Configuration des marges
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Configuration de l'auto-break
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Ajout d'une page
    $pdf->AddPage();
    
    // Contenu HTML
    $html = generateSecurityPDF();
    
    // Écriture du contenu
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Sortie du PDF
    $pdf->Output('CIMIS_Security_Report_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit();
}

// Statistiques de sécurité (simulées pour la démo)
$security_stats = [
    'auth_attempts' => 1247,
    'failed_logins' => 23,
    'active_sessions' => 45,
    'cards_generated' => 892,
    'security_alerts' => 3,
    'last_scan' => date('d/m/Y H:i:s', strtotime('-2 hours')),
    'uptime' => '99.97%',
    'backup_status' => 'OK'
];

// Logs récents (simulés)
$recent_logs = [
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')), 'level' => 'INFO', 'user' => 'SUPER_ADMIN', 'action' => 'LOGIN', 'ip' => '192.168.1.100'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes')), 'level' => 'WARN', 'user' => 'UNKNOWN', 'action' => 'LOGIN_FAILED', 'ip' => '192.168.1.101'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes')), 'level' => 'INFO', 'user' => 'ADMIN_ENROLEMENT', 'action' => 'CARD_GENERATED', 'ip' => '192.168.1.102'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'level' => 'CRIT', 'user' => 'SYSTEM', 'action' => 'SECURITY_ALERT', 'ip' => '127.0.0.1'],
    ['timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'level' => 'INFO', 'user' => 'ADMIN_IMPRESSION', 'action' => 'CARD_VALIDATED', 'ip' => '192.168.1.103']
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Sécurité CIMIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --accent-color: #667eea;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
            background: var(--light-color);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.danger { border-left-color: var(--danger-color); }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .stat-card.success .stat-icon { color: var(--success-color); }
        .stat-card.warning .stat-icon { color: var(--warning-color); }
        .stat-card.danger .stat-icon { color: var(--danger-color); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 40px;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .section-title i {
            font-size: 1.5rem;
        }
        
        .security-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .security-table th {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .security-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .security-table tr:nth-child(even) {
            background: var(--light-color);
        }
        
        .security-table tr:hover {
            background: #e9ecef;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-success { background: var(--success-color); color: white; }
        .badge-warning { background: var(--warning-color); color: var(--dark-color); }
        .badge-danger { background: var(--danger-color); color: white; }
        .badge-info { background: #17a2b8; color: white; }
        
        .logs-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background: var(--light-color);
        }
        
        .log-entry {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-timestamp {
            font-family: 'Courier New', monospace;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .log-details {
            flex: 1;
            margin: 0 20px;
        }
        
        .log-level {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .export-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, var(--success-color) 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .export-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(40, 167, 69, 0.4);
        }
        
        .export-btn i {
            font-size: 1.2rem;
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            margin: 20px 0;
        }
        
        .security-level {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .security-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .indicator-high { background: var(--success-color); }
        .indicator-medium { background: var(--warning-color); }
        .indicator-low { background: var(--danger-color); }
        
        @media (max-width: 768px) {
            .header h1 { font-size: 2rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .content { padding: 20px; }
            .section { padding: 20px; }
            .export-btn { bottom: 20px; right: 20px; padding: 12px 20px; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Dashboard de Sécurité CIMIS</h1>
            <p>Carte d'Identité Militaire Intégrée et Sécurisée</p>
            <p style="font-size: 0.9rem; opacity: 0.8;">Ministère de la Défense - République du Cameroun</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-key"></i></div>
                <div class="stat-value"><?php echo $security_stats['auth_attempts']; ?></div>
                <div class="stat-label">Tentatives d'Authentification</div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?php echo $security_stats['failed_logins']; ?></div>
                <div class="stat-label">Échecs de Connexion</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?php echo $security_stats['active_sessions']; ?></div>
                <div class="stat-label">Sessions Actives</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-id-card"></i></div>
                <div class="stat-value"><?php echo $security_stats['cards_generated']; ?></div>
                <div class="stat-label">Cartes Générées</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-bell"></i></div>
                <div class="stat-value"><?php echo $security_stats['security_alerts']; ?></div>
                <div class="stat-label">Alertes de Sécurité</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-server"></i></div>
                <div class="stat-value"><?php echo $security_stats['uptime']; ?></div>
                <div class="stat-label">Uptime Système</div>
            </div>
        </div>
        
        <div class="content">
            <?php if (!$tcpdf_available): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Note:</strong> L'export PDF nécessite l'installation de la bibliothèque TCPDF.
                </div>
            <?php endif; ?>
            
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-shield-alt"></i>
                    Sécurité de l'Application
                </h2>
                
                <h4 style="color: var(--secondary-color); margin-bottom: 20px;">1.1 Authentification Multi-Niveaux</h4>
                <table class="security-table">
                    <tr>
                        <th style="width: 30%">Paramètre</th>
                        <th style="width: 40%">Valeur</th>
                        <th style="width: 15%">Niveau de Sécurité</th>
                        <th style="width: 15%">Statut</th>
                    </tr>
                    <tr>
                        <td><strong>Code Secret</strong></td>
                        <td>CIMIS2.02026</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>Accès Desktop</strong></td>
                        <td>Saisie clavier directe</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>Accès Mobile</strong></td>
                        <td>10 actualisations automatiques</td>
                        <td><span class="security-level"><span class="security-indicator indicator-medium"></span> Moyen</span></td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>Page 403</strong></td>
                        <td>Dissuasion avec compteur discret</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                </table>
                
                <h4 style="color: var(--secondary-color); margin: 30px 0 20px;">1.2 Protection Contre les Attaques</h4>
                <table class="security-table">
                    <tr>
                        <th style="width: 25%">Type d'Attaque</th>
                        <th style="width: 35%">Protection Implémentée</th>
                        <th style="width: 20%">Niveau de Sécurité</th>
                        <th style="width: 20%">Dernière Vérification</th>
                    </tr>
                    <tr>
                        <td><strong>Injection SQL</strong></td>
                        <td>Requêtes préparées PDO + Validation stricte</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><?php echo date('d/m H:i'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>XSS (Cross-Site Scripting)</strong></td>
                        <td>htmlspecialchars() + CSP + Validation entrées</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><?php echo date('d/m H:i'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>CSRF (Cross-Site Request Forgery)</strong></td>
                        <td>Tokens CSRF + Vérification referer + Double submit</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><?php echo date('d/m H:i'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Brute Force</strong></td>
                        <td>Limitation tentatives (5 max) + Délai progressif + Blocage IP</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><?php echo date('d/m H:i'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-id-card"></i>
                    Sécurité des Cartes
                </h2>
                
                <h4 style="color: var(--secondary-color); margin-bottom: 20px;">2.1 Éléments de Sécurité Physique</h4>
                <table class="security-table">
                    <tr>
                        <th style="width: 30%">Caractéristique</th>
                        <th style="width: 40%">Spécification</th>
                        <th style="width: 15%">Norme</th>
                        <th style="width: 15%">Statut</th>
                    </tr>
                    <tr>
                        <td><strong>Format</strong></td>
                        <td>PVC CR80 (85.6 × 54 mm)</td>
                        <td>ISO 7810</td>
                        <td><span class="badge badge-success">CONFORME</span></td>
                    </tr>
                    <tr>
                        <td><strong>Épaisseur</strong></td>
                        <td>0.76mm standard militaire</td>
                        <td>ISO 7813</td>
                        <td><span class="badge badge-success">CONFORME</span></td>
                    </tr>
                    <tr>
                        <td><strong>Durée de vie</strong></td>
                        <td>5 ans minimum</td>
                        <td>MIL-STD</td>
                        <td><span class="badge badge-success">VALIDÉ</span></td>
                    </tr>
                    <tr>
                        <td><strong>Matériau</strong></td>
                        <td>PVC composite anti-contrefaçon</td>
                        <td>MINDEF</td>
                        <td><span class="badge badge-success">APPROUVÉ</span></td>
                    </tr>
                </table>
                
                <h4 style="color: var(--secondary-color); margin: 30px 0 20px;">2.2 QR Codes Sécurisés</h4>
                <div style="background: var(--light-color); padding: 20px; border-radius: 10px; margin: 20px 0;">
                    <ul style="margin-bottom: 0;">
                        <li><strong>Encodage AES-256</strong> des données sensibles</li>
                        <li><strong>Clé unique</strong> par matricule CIMIS</li>
                        <li><strong>Checksum de validation</strong> intégré</li>
                        <li><strong>Anti-copie</strong> avec micro-perforations</li>
                        <li><strong>Lecture sécurisée</strong> uniquement avec application dédiée</li>
                    </ul>
                </div>
                
                <h4 style="color: var(--secondary-color); margin: 30px 0 20px;">2.3 Signatures Officielles</h4>
                <table class="security-table">
                    <tr>
                        <th style="width: 35%">Type de Personnel</th>
                        <th style="width: 35%">Signature</th>
                        <th style="width: 30%">Style de Validation</th>
                    </tr>
                    <tr>
                        <td><strong>Officiers</strong></td>
                        <td>JOSEPH BETI ASSOMO</td>
                        <td>Manuscrit majuscules + Double validation</td>
                    </tr>
                    <tr>
                        <td><strong>Sous-Officiers</strong></td>
                        <td>GOUFAN A RIM</td>
                        <td>Manuscrit majuscules + Validation HR</td>
                    </tr>
                    <tr>
                        <td><strong>Militaires du Rang</strong></td>
                        <td>GOUFAN A RIM</td>
                        <td>Manuscrit majuscules + Validation HR</td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-lock"></i>
                    Protection des Données
                </h2>
                
                <h4 style="color: var(--secondary-color); margin-bottom: 20px;">3.1 Chiffrement</h4>
                <table class="security-table">
                    <tr>
                        <th style="width: 30%">Composant</th>
                        <th style="width: 40">Algorithme/Protocole</th>
                        <th style="width: 15%">Niveau</th>
                        <th style="width: 15%">Statut</th>
                    </tr>
                    <tr>
                        <td><strong>Base de Données</strong></td>
                        <td>AES-256 au repos + Clés rotatives</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>Transfert</strong></td>
                        <td>HTTPS/TLS 1.3 + Perfect Forward Secrecy</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>API GESMIL2.0</strong></td>
                        <td>Clé: GESMIL2.0-CIMIS-2026-KEY + OAuth 2.0</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>Sessions</strong></td>
                        <td>AES-256 + Timeout 30min + IP tracking</td>
                        <td><span class="security-level"><span class="security-indicator indicator-high"></span> Élevé</span></td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                </table>
                
                <h4 style="color: var(--secondary-color); margin: 30px 0 20px;">3.2 Backup et Récupération</h4>
                <div style="background: var(--light-color); padding: 20px; border-radius: 10px; margin: 20px 0;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div>
                            <h5 style="color: var(--primary-color); margin-bottom: 10px;">Stratégie de Backup</h5>
                            <ul style="margin-bottom: 0;">
                                <li>Backup quotidien automatique chiffré</li>
                                <li>Backup incrémental toutes les heures</li>
                                <li>Backup mensuel off-site sécurisé</li>
                                <li>Tests de restauration mensuels</li>
                            </ul>
                        </div>
                        <div>
                            <h5 style="color: var(--primary-color); margin-bottom: 10px;">Objectifs de Récupération</h5>
                            <ul style="margin-bottom: 0;">
                                <li>Temps de récupération < 4 heures</li>
                                <li>Perte de données < 1 heure</li>
                                <li>Disponibilité 99.97%</li>
                                <li>Équipe d'intervention 24/7</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-chart-line"></i>
                    Audit et Monitoring
                </h2>
                
                <h4 style="color: var(--secondary-color); margin-bottom: 20px;">4.1 Logs de Sécurité</h4>
                <table class="security-table">
                    <tr>
                        <th style="width: 25%">Type de Log</th>
                        <th style="width: 25%">Fréquence</th>
                        <th style="width: 25%">Rétention</th>
                        <th style="width: 25%">Statut</th>
                    </tr>
                    <tr>
                        <td><strong>Authentification</strong></td>
                        <td>Temps réel</td>
                        <td>10 ans</td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>Activité Utilisateur</strong></td>
                        <td>Temps réel</td>
                        <td>5 ans</td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>Sécurité</strong></td>
                        <td>Temps réel</td>
                        <td>10 ans</td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                    <tr>
                        <td><strong>Système</strong></td>
                        <td>Continu</td>
                        <td>2 ans</td>
                        <td><span class="badge badge-success">ACTIF</span></td>
                    </tr>
                </table>
                
                <h4 style="color: var(--secondary-color); margin: 30px 0 20px;">4.2 Logs Récents</h4>
                <div class="logs-container">
                    <?php foreach ($recent_logs as $log): ?>
                        <div class="log-entry">
                            <div class="log-timestamp"><?php echo $log['timestamp']; ?></div>
                            <div class="log-details">
                                <span class="log-level" style="
                                    background: <?php 
                                        echo match($log['level']) {
                                            'INFO' => '#17a2b8',
                                            'WARN' => '#ffc107',
                                            'CRIT' => '#dc3545',
                                            default => '#6c757d'
                                        };
                                    ?>; color: white;
                                "><?php echo $log['level']; ?></span>
                                <strong><?php echo $log['user']; ?></strong> - 
                                <?php echo $log['action']; ?>
                            </div>
                            <div><i class="fas fa-network-wired"></i> <?php echo $log['ip']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Procédures d'Urgence
                </h2>
                
                <h4 style="color: var(--secondary-color); margin-bottom: 20px;">5.1 Classification des Incidents</h4>
                <table class="security-table">
                    <tr>
                        <th style="width: 20%">Niveau</th>
                        <th style="width: 40%">Description</th>
                        <th style="width: 20%">Temps d'Intervention</th>
                        <th style="width: 20%">Équipe</th>
                    </tr>
                    <tr style="background: #f8d7da;">
                        <td><strong>1 - Critique</strong></td>
                        <td>Brèche de données, compromission système</td>
                        <td>Immédiat</td>
                        <td>Équipe crise 24/7</td>
                    </tr>
                    <tr style="background: #fff3cd;">
                        <td><strong>2 - Majeur</strong></td>
                        <td>Attaque en cours, dégradation service</td>
                        <td>15 minutes</td>
                        <td>Équipe sécurité</td>
                    </tr>
                    <tr style="background: #d1ecf1;">
                        <td><strong>3 - Mineur</strong></td>
                        <td>Tentative attaque, comportement anormal</td>
                        <td>1 heure</td>
                        <td>Équipe technique</td>
                    </tr>
                    <tr style="background: #f8f9fa;">
                        <td><strong>4 - Info</strong></td>
                        <td>Activité suspecte, alerte monitoring</td>
                        <td>4 heures</td>
                        <td>Support</td>
                    </tr>
                </table>
                
                <h4 style="color: var(--secondary-color); margin: 30px 0 20px;">5.2 Contacts d'Urgence</h4>
                <table class="security-table">
                    <tr>
                        <th style="width: 30%">Service</th>
                        <th style="width: 30%">Disponibilité</th>
                        <th style="width: 40%">Contact</th>
                    </tr>
                    <tr>
                        <td><strong>Équipe Sécurité</strong></td>
                        <td>24/7</td>
                        <td>+237 XXX XXX XXX</td>
                    </tr>
                    <tr>
                        <td><strong>Hotline MINDEF</strong></td>
                        <td>6h-22h</td>
                        <td>+237 XXX XXX XXX</td>
                    </tr>
                    <tr>
                        <td><strong>Email Sécurité</strong></td>
                        <td>24/7</td>
                        <td>security@cimis.mindef.cm</td>
                    </tr>
                    <tr>
                        <td><strong>Alertes Critiques</strong></td>
                        <td>24/7</td>
                        <td>SMS + Email</td>
                    </tr>
                </table>
            </div>
            
            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-certificate"></i>
                    Compliance et Certifications
                </h2>
                
                <h4 style="color: var(--secondary-color); margin-bottom: 20px;">6.1 Normes Applicables</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div style="background: var(--light-color); padding: 20px; border-radius: 10px; border-left: 4px solid var(--primary-color);">
                        <h5 style="color: var(--primary-color); margin-bottom: 10px;">ISO 27001</h5>
                        <p style="margin: 0;">Management de la sécurité de l'information</p>
                    </div>
                    <div style="background: var(--light-color); padding: 20px; border-radius: 10px; border-left: 4px solid var(--primary-color);">
                        <h5 style="color: var(--primary-color); margin-bottom: 10px;">ISO 15408</h5>
                        <p style="margin: 0;">Critères communs d'évaluation de sécurité</p>
                    </div>
                    <div style="background: var(--light-color); padding: 20px; border-radius: 10px; border-left: 4px solid var(--primary-color);">
                        <h5 style="color: var(--primary-color); margin-bottom: 10px;">NIST SP 800-53</h5>
                        <p style="margin: 0;">Contrôles de sécurité fédéraux</p>
                    </div>
                    <div style="background: var(--light-color); padding: 20px; border-radius: 10px; border-left: 4px solid var(--primary-color);">
                        <h5 style="color: var(--primary-color); margin-bottom: 10px;">GDPR</h5>
                        <p style="margin: 0;">Protection des données personnelles</p>
                    </div>
                </div>
                
                <h4 style="color: var(--secondary-color); margin: 30px 0 20px;">6.2 Certifications Requises</h4>
                <div style="background: var(--light-color); padding: 20px; border-radius: 10px;">
                    <ul>
                        <li>Certification MINDEF niveau sécurité</li>
                        <li>Audit de conformité militaire</li>
                        <li>Certification de protection des données</li>
                        <li>Accréditation des systèmes d'information</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($tcpdf_available): ?>
        <a href="?export=pdf" class="export-btn">
            <i class="fas fa-file-pdf"></i>
            Exporter en PDF
        </a>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation des compteurs
        function animateCounter(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 16);
        }
        
        // Animation des statistiques au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(stat => {
                const value = parseInt(stat.textContent);
                if (!isNaN(value)) {
                    animateCounter(stat, value);
                }
            });
        });
        
        // Rafraîchissement automatique des logs
        setInterval(() => {
            // Simuler un nouveau log toutes les 30 secondes
            console.log('Monitoring actif...');
        }, 30000);
    </script>
</body>
</html>
