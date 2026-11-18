<?php
/**
 * CIMIS - Console de Gestion & Souveraineté des Signatures Officielles
 * République du Cameroun - Ministère de la Défense
 */
if (!function_exists('cimis_csrf_token')) {
    $secServicePath = dirname(__DIR__, 3) . '/app/Services/SecurityService.php';
    if (file_exists($secServicePath)) {
        require_once $secServicePath;
    }
}
$csrfToken = function_exists('cimis_csrf_token') 
    ? cimis_csrf_token() 
    : (function_exists('csrf_token') ? csrf_token() : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIMIS | Souveraineté & Signatures Officielles MINDEF / DRH</title>
    <link rel="icon" type="image/png" href="/cimcim/public/img/cimis.png">
    
    <!-- Polices militaires et icônes -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --bg-dark: #020617;
            --card-bg: rgba(15, 23, 42, 0.85);
            --card-border: rgba(56, 189, 248, 0.25);
            --neon-green: #4ade80;
            --cyber-blue: #38bdf8;
            --gold: #d4af37;
            --gold-light: #fef08a;
            --gold-glow: rgba(212, 175, 55, 0.35);
            --danger-red: #ef4444;
            --danger-glow: rgba(239, 68, 68, 0.35);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(56, 189, 248, 0.08) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
                linear-gradient(rgba(2, 6, 23, 0.95), rgba(2, 6, 23, 0.95)),
                repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0, 0, 0, 0.2) 2px, rgba(0, 0, 0, 0.2) 4px);
            color: var(--text-main);
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            padding-bottom: 60px;
        }

        /* BARRE SUPÉRIEURE */
        .top-status-bar {
            background: rgba(2, 6, 23, 0.95);
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
            padding: 0.6rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.85rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .status-left, .status-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .badge-secret-defense {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--danger-red);
            color: #fca5a5;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 700;
            letter-spacing: 1px;
            animation: pulse-danger 2.5s infinite;
        }

        @keyframes pulse-danger {
            0%, 100% { box-shadow: 0 0 5px rgba(239, 68, 68, 0.3); }
            50% { box-shadow: 0 0 16px rgba(239, 68, 68, 0.6); }
        }

        .btn-logout-styled {
            color: #fca5a5;
            text-decoration: none;
            padding: 4px 12px;
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 4px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-logout-styled:hover {
            background: var(--danger-red);
            color: #fff;
        }

        /* EN-TÊTE PRINCIPAL */
        .main-header {
            max-width: 1400px;
            margin: 1.5rem auto 1rem auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .header-title-box {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .header-title-box img {
            height: 60px;
            filter: drop-shadow(0 0 10px rgba(212, 175, 55, 0.5));
        }

        .header-titles h1 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #ffffff;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-titles h1 span {
            color: var(--gold);
        }

        .header-titles p {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-family: 'Share Tech Mono', monospace;
        }

        /* NAVIGATION BAR */
        .nav-actions-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-nav-cimis {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            transition: all 0.2s ease;
            font-family: 'Rajdhani', sans-serif;
            letter-spacing: 0.5px;
        }

        .btn-nav-back {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        .btn-nav-back:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(-3px);
        }

        .btn-nav-security {
            background: rgba(74, 222, 128, 0.12);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
        }
        .btn-nav-security:hover {
            background: var(--neon-green);
            color: #020617;
            box-shadow: 0 0 15px rgba(74, 222, 128, 0.4);
        }

        .btn-nav-impression {
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid var(--cyber-blue);
            color: var(--cyber-blue);
        }
        .btn-nav-impression:hover {
            background: var(--cyber-blue);
            color: #020617;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.4);
        }

        /* ALERTES */
        .alert-box {
            max-width: 1400px;
            margin: 0 auto 1.5rem auto;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.92rem;
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.15);
            border: 1px solid var(--neon-green);
            color: #86efac;
            box-shadow: 0 0 20px rgba(74, 222, 128, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--danger-red);
            color: #fca5a5;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }

        /* BANNIÈRE D'AVERTISSEMENT LÉGAL */
        .legal-notice {
            max-width: 1400px;
            margin: 0 auto 2rem auto;
            padding: 1rem 1.5rem;
            background: linear-gradient(90deg, rgba(212, 175, 55, 0.12), rgba(15, 23, 42, 0.9));
            border-left: 4px solid var(--gold);
            border-radius: 0 8px 8px 0;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.85rem;
            color: var(--gold-light);
        }

        .legal-notice i {
            font-size: 1.6rem;
            color: var(--gold);
        }

        /* GRILLE DES DEUX SIGNATURES */
        .signatures-grid {
            max-width: 1400px;
            margin: 0 auto 2.5rem auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(620px, 1fr));
            gap: 2rem;
            padding: 0 1.5rem;
        }

        @media (max-width: 768px) {
            .signatures-grid {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }
        }

        .signature-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            position: relative;
            transition: border-color 0.3s;
        }

        .signature-card.card-mindef {
            border-color: rgba(212, 175, 55, 0.4);
        }
        .signature-card.card-mindef:hover {
            border-color: var(--gold);
            box-shadow: 0 10px 40px var(--gold-glow);
        }

        .signature-card.card-drh {
            border-color: rgba(56, 189, 248, 0.4);
        }
        .signature-card.card-drh:hover {
            border-color: var(--cyber-blue);
            box-shadow: 0 10px 40px rgba(56, 189, 248, 0.25);
        }

        .card-header-bar {
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.35);
        }

        .card-mindef .card-header-bar {
            background: linear-gradient(90deg, rgba(212, 175, 55, 0.15), rgba(0, 0, 0, 0.3));
        }

        .card-drh .card-header-bar {
            background: linear-gradient(90deg, rgba(56, 189, 248, 0.15), rgba(0, 0, 0, 0.3));
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-icon {
            font-size: 1.4rem;
        }
        .card-mindef .card-icon { color: var(--gold); }
        .card-drh .card-icon { color: var(--cyber-blue); }

        .card-header-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .card-badge-scope {
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.72rem;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid;
            font-weight: 700;
            text-transform: uppercase;
        }
        .card-mindef .card-badge-scope {
            background: rgba(212, 175, 55, 0.1);
            border-color: var(--gold);
            color: var(--gold-light);
        }
        .card-drh .card-badge-scope {
            background: rgba(56, 189, 248, 0.1);
            border-color: var(--cyber-blue);
            color: #bae6fd;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* ZONE D'APERÇU VISUEL OFFICIEL */
        .preview-stage {
            background: repeating-conic-gradient(#1e293b 0% 25%, #0f172a 0% 50%) 50% / 20px 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 1.2rem;
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.6);
        }

        .preview-tabs {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            gap: 6px;
            background: rgba(2, 6, 23, 0.85);
            padding: 3px 6px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .preview-tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.72rem;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 3px;
            transition: all 0.2s;
        }
        .preview-tab-btn.active {
            background: var(--cyber-blue);
            color: #020617;
            font-weight: 700;
        }
        .card-mindef .preview-tab-btn.active {
            background: var(--gold);
            color: #020617;
        }

        .signature-img-display {
            max-height: 90px;
            max-width: 90%;
            object-fit: contain;
            transition: transform 0.3s;
        }
        .card-mindef .signature-img-display {
            filter: drop-shadow(0 0 4px rgba(212, 175, 55, 0.6));
        }
        .card-drh .signature-img-display {
            filter: drop-shadow(0 0 4px rgba(56, 189, 248, 0.5));
        }

        .signatory-label-rendered {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            margin-top: 10px;
            text-transform: uppercase;
            color: #ffffff;
            background: rgba(0, 0, 0, 0.75);
            padding: 3px 12px;
            border-radius: 3px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* MÉTADONNÉES TECHNIQUES */
        .meta-specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 1.5rem;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.78rem;
        }

        .spec-item {
            background: rgba(0, 0, 0, 0.35);
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .spec-label {
            color: var(--text-muted);
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .spec-val {
            color: #ffffff;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* FORMULAIRES D'ACTION */
        .form-section-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cimis-form-group {
            margin-bottom: 1rem;
        }

        .cimis-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            font-family: 'Share Tech Mono', monospace;
        }

        .cimis-input {
            width: 100%;
            background: rgba(2, 6, 23, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            padding: 10px 14px;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .cimis-input:focus {
            outline: none;
            border-color: var(--cyber-blue);
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.3);
            background: #020617;
        }

        /* DRAG & DROP UPLOAD BOX */
        .dropzone-box {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 1.5rem 1rem;
            text-align: center;
            background: rgba(0, 0, 0, 0.25);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            margin-bottom: 1rem;
        }

        .dropzone-box:hover, .dropzone-box.dragover {
            border-color: var(--neon-green);
            background: rgba(74, 222, 128, 0.05);
        }

        .dropzone-icon {
            font-size: 2rem;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .dropzone-box:hover .dropzone-icon {
            color: var(--neon-green);
        }

        .dropzone-text {
            font-size: 0.85rem;
            color: var(--text-main);
            font-weight: 600;
        }

        .dropzone-sub {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-family: 'Share Tech Mono', monospace;
            margin-top: 4px;
        }

        .file-hidden-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .selected-file-name {
            display: none;
            margin-top: 8px;
            font-size: 0.8rem;
            color: var(--neon-green);
            font-weight: 700;
            font-family: 'Share Tech Mono', monospace;
        }

        /* ZONE CONFIRMATION ET MOT DE PASSE */
        .security-checkpoint {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 1.2rem;
            margin-top: 1.2rem;
        }

        .security-checkpoint-title {
            color: #fca5a5;
            font-size: 0.82rem;
            font-weight: 700;
            font-family: 'Share Tech Mono', monospace;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
        }

        .confirm-checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.78rem;
            color: #e2e8f0;
            cursor: pointer;
            margin-bottom: 1rem;
            user-select: none;
        }

        .confirm-checkbox-label input {
            margin-top: 2px;
            accent-color: var(--danger-red);
            width: 16px;
            height: 16px;
        }

        .btn-submit-signature {
            width: 100%;
            padding: 12px 18px;
            border-radius: 6px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            border: none;
        }

        .card-mindef .btn-submit-signature {
            background: linear-gradient(135deg, #d4af37, #996515);
            color: #020617;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }
        .card-mindef .btn-submit-signature:hover {
            background: linear-gradient(135deg, #fef08a, #d4af37);
            box-shadow: 0 6px 25px rgba(212, 175, 55, 0.6);
            transform: translateY(-2px);
        }

        .card-drh .btn-submit-signature {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }
        .card-drh .btn-submit-signature:hover {
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            box-shadow: 0 6px 25px rgba(56, 189, 248, 0.5);
            transform: translateY(-2px);
        }

        /* SECTION ARCHIVES */
        .archives-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .archives-box {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .archives-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-top: 1rem;
        }

        .archives-table th {
            text-align: left;
            padding: 10px 14px;
            background: rgba(0, 0, 0, 0.4);
            color: var(--text-muted);
            font-family: 'Share Tech Mono', monospace;
            text-transform: uppercase;
            font-size: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .archives-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }

        .archives-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .archive-thumb {
            height: 32px;
            max-width: 90px;
            object-fit: contain;
            background: rgba(0, 0, 0, 0.5);
            padding: 2px 4px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-restore {
            background: rgba(234, 179, 8, 0.15);
            border: 1px solid #eab308;
            color: #facc15;
            padding: 5px 12px;
            border-radius: 4px;
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-restore:hover {
            background: #eab308;
            color: #020617;
            box-shadow: 0 0 12px rgba(234, 179, 8, 0.5);
        }

        /* MODALE DE SÉCURITÉ POUR RESTAURATION */
        .cimis-modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .cimis-modal-box {
            background: #0f172a;
            border: 2px solid var(--gold);
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            padding: 1.8rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            position: relative;
        }

        .modal-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gold);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
        }
        .btn-modal-close:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- STATUS BAR -->
    <div class="top-status-bar">
        <div class="status-left">
            <span class="badge-secret-defense"><i class="fa-solid fa-shield-halved"></i> SECRET DÉFENSE // SOUVERAINETÉ RÉPUBLICAINE</span>
            <span style="color: var(--neon-green);"><i class="fa-solid fa-server"></i> CIMIS 2.0 SÉCURISÉ</span>
            <span style="color: var(--cyber-blue);"><i class="fa-solid fa-user-lock"></i> <?php echo htmlspecialchars($username); ?> (Super Admin)</span>
        </div>
        <div class="status-right">
            <a href="/cimcim/logout" class="btn-logout-styled">
                <i class="fa-solid fa-power-off"></i> DÉCONNEXION
            </a>
        </div>
    </div>

    <!-- MAIN HEADER -->
    <div class="main-header">
        <div class="header-title-box">
            <img src="/cimcim/public/img/cimis.png" alt="Armoiries MINDEF">
            <div class="header-titles">
                <h1>Souveraineté & <span>Signatures Officielles</span></h1>
                <p>GESTION ET TÉLÉVERSEMENT DES SCEAUX OFFICIELS DE L'ÉTAT DU CAMEROUN (MINDEF & DRH)</p>
            </div>
        </div>
        <div class="nav-actions-bar">
            <a href="/cimcim/securite" class="btn-nav-cimis btn-nav-security">
                <i class="fa-solid fa-shield-halved"></i> Console Sécurité & Audit
            </a>
            <a href="/cimcim/dashboard" class="btn-nav-cimis btn-nav-back">
                <i class="fa-solid fa-arrow-left"></i> Tableau de bord
            </a>
        </div>
    </div>

    <!-- ALERTES SUCCÈS OU ERREUR -->
    <?php if (!empty($success)): ?>
    <div class="alert-box alert-success">
        <i class="fa-solid fa-circle-check" style="font-size: 1.4rem;"></i>
        <div>
            <strong>ACTION VALIDÉE PAR L'AUTORITÉ MILITAIRE :</strong><br>
            <?php echo htmlspecialchars($success); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert-box alert-error">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.4rem;"></i>
        <div>
            <strong>ÉCHEC DE L'OPÉRATION / REJET DE SÉCURITÉ :</strong><br>
            <?php echo htmlspecialchars($error); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- NOTICE LÉGALE -->
    <div class="legal-notice">
        <i class="fa-solid fa-scale-balanced"></i>
        <div>
            <strong>AVERTISSEMENT SOLENNEL DU COMMANDEMENT MILITAIRE :</strong><br>
            Toute modification ou substitution d'une signature officielle engage la responsabilité pénale et disciplinaire de l'exécutant. Chaque action est immédiatement enregistrée dans le journal inaltérable avec empreinte SHA-256, horodatage certifié et adresse IP. Les anciennes versions sont automatiquement archivées de façon sécurisée.
        </div>
    </div>

    <!-- CONTENEUR DES DEUX SIGNATURES -->
    <div class="signatures-grid">

        <!-- ==================================================== -->
        <!-- 1. CARTE SIGNATURE MINDEF (MINISTRE DE LA DÉFENSE)   -->
        <!-- ==================================================== -->
        <div class="signature-card card-mindef">
            <div class="card-header-bar">
                <div class="card-header-left">
                    <i class="fa-solid fa-crown card-icon"></i>
                    <div>
                        <div class="card-header-title">Signature MINDEF</div>
                        <div style="font-size: 0.72rem; color: var(--gold-light);">Ministre Délégué à la Présidence chargé de la Défense</div>
                    </div>
                </div>
                <div class="card-badge-scope">
                    <i class="fa-solid fa-star"></i> Officiers
                </div>
            </div>

            <div class="card-body">
                <!-- APERÇU OFFICIEL -->
                <div class="preview-stage" id="preview_stage_mindef">
                    <div class="preview-tabs">
                        <button type="button" class="preview-tab-btn active" onclick="switchPreview('mindef', 'gold', this)">Or (Carte PVC)</button>
                        <button type="button" class="preview-tab-btn" onclick="switchPreview('mindef', 'transp', this)">Transparent</button>
                    </div>

                    <img src="<?php echo $signatures['mindef']['url_gold']; ?>" 
                         id="img_preview_mindef" 
                         alt="Signature MINDEF" 
                         class="signature-img-display"
                         onerror="this.src='/cimcim/public/img/signatures/SignatureMindef_gold.png';">

                    <div class="signatory-label-rendered" id="label_rendered_mindef">
                        <?php echo htmlspecialchars($signatures['mindef']['name']); ?>
                    </div>
                </div>

                <!-- MÉTADONNÉES TECHNIQUES -->
                <div class="meta-specs-grid">
                    <div class="spec-item">
                        <span class="spec-label">Signataire Actuel</span>
                        <span class="spec-val" style="color: var(--gold);"><?php echo htmlspecialchars($signatures['mindef']['name']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Dernier Déploiement</span>
                        <span class="spec-val"><?php echo htmlspecialchars($signatures['mindef']['updated_at']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Résolution & Poids</span>
                        <span class="spec-val"><?php echo htmlspecialchars($signatures['mindef']['dimensions'] . ' — ' . $signatures['mindef']['file_size_formatted']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Empreinte SHA-256</span>
                        <span class="spec-val" title="<?php echo htmlspecialchars($signatures['mindef']['sha256']); ?>">
                            <?php echo substr($signatures['mindef']['sha256'], 0, 14) . '...'; ?>
                        </span>
                    </div>
                </div>

                <!-- FORMULAIRE DE TÉLÉVERSEMENT NOUVELLE SIGNATURE -->
                <form action="/cimcim/admin/signatures" method="POST" enctype="multipart/form-data" id="form_upload_mindef">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="upload_signature">
                    <input type="hidden" name="signature_type" value="mindef">

                    <div class="form-section-title">
                        <i class="fa-solid fa-upload" style="color: var(--gold);"></i>
                        Remplacer la Signature MINDEF (Format PNG)
                    </div>

                    <!-- DROPZONE -->
                    <div class="dropzone-box" onclick="document.getElementById('file_input_mindef').click()">
                        <input type="file" 
                               name="signature_file" 
                               id="file_input_mindef" 
                               class="file-hidden-input" 
                               accept=".png,image/png" 
                               required
                               onchange="handleFileSelected(this, 'mindef')">
                        <i class="fa-solid fa-file-signature dropzone-icon"></i>
                        <div class="dropzone-text">Cliquez ou glissez-déposez le fichier PNG de la signature</div>
                        <div class="dropzone-sub">Format strict : .PNG transparent ou scanné (Max 3 Mo)</div>
                        <div class="selected-file-name" id="file_name_mindef"></div>
                    </div>

                    <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: #cbd5e1;">
                        <input type="checkbox" name="auto_remove_white" id="white_bg_mindef" value="1" checked style="accent-color: var(--gold);">
                        <label for="white_bg_mindef">Supprimer automatiquement le fond blanc (pour signature scannée sur papier)</label>
                    </div>

                    <div class="cimis-form-group">
                        <label class="cimis-label">Nom officiel du Signataire (Apparaît sous la signature sur la carte)</label>
                        <input type="text" 
                               name="signatory_name" 
                               class="cimis-input" 
                               value="<?php echo htmlspecialchars($signatures['mindef']['name']); ?>" 
                               required>
                    </div>

                    <div class="cimis-form-group">
                        <label class="cimis-label">Titre officiel de l'autorité</label>
                        <input type="text" 
                               name="signatory_title" 
                               class="cimis-input" 
                               value="<?php echo htmlspecialchars($signatures['mindef']['title']); ?>" 
                               required>
                    </div>

                    <!-- SAS DE SÉCURITÉ MOT DE PASSE -->
                    <div class="security-checkpoint">
                        <div class="security-checkpoint-title">
                            <i class="fa-solid fa-lock"></i> Validation de Sécurité Haute Autorité
                        </div>

                        <label class="confirm-checkbox-label">
                            <input type="checkbox" required>
                            <span>Je confirme solennellement que cette signature officielle est certifiée authentique et valide pour l'ensemble des cartes d'officiers des Forces Armées.</span>
                        </label>

                        <div class="cimis-form-group" style="margin-bottom: 0.8rem;">
                            <label class="cimis-label" style="color: #fca5a5;">Confirmer le mot de passe Super Administrateur</label>
                            <input type="password" 
                                   name="superadmin_password" 
                                   class="cimis-input" 
                                   placeholder="Mot de passe requis pour autoriser l'action" 
                                   required 
                                   autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn-submit-signature">
                            <i class="fa-solid fa-shield-halved"></i> Valider et Déployer la Signature MINDEF
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- ==================================================== -->
        <!-- 2. CARTE SIGNATURE DRH (DIRECTEUR RESSOURCES HUMAINES)-->
        <!-- ==================================================== -->
        <div class="signature-card card-drh">
            <div class="card-header-bar">
                <div class="card-header-left">
                    <i class="fa-solid fa-user-tie card-icon"></i>
                    <div>
                        <div class="card-header-title">Signature DRH / MINDEF</div>
                        <div style="font-size: 0.72rem; color: #bae6fd;">Directeur des Ressources Humaines du Ministère de la Défense</div>
                    </div>
                </div>
                <div class="card-badge-scope">
                    <i class="fa-solid fa-users"></i> Sous-officiers & Rang
                </div>
            </div>

            <div class="card-body">
                <!-- APERÇU OFFICIEL -->
                <div class="preview-stage" id="preview_stage_drh">
                    <div class="preview-tabs">
                        <button type="button" class="preview-tab-btn active" onclick="switchPreview('drh', 'gold', this)">Or (Carte PVC)</button>
                        <button type="button" class="preview-tab-btn" onclick="switchPreview('drh', 'transp', this)">Transparent</button>
                    </div>

                    <img src="<?php echo $signatures['drh']['url_gold']; ?>" 
                         id="img_preview_drh" 
                         alt="Signature DRH" 
                         class="signature-img-display"
                         onerror="this.src='/cimcim/public/img/signatures/SignatureDrh_gold.png';">

                    <div class="signatory-label-rendered" id="label_rendered_drh">
                        <?php echo htmlspecialchars($signatures['drh']['name']); ?>
                    </div>
                </div>

                <!-- MÉTADONNÉES TECHNIQUES -->
                <div class="meta-specs-grid">
                    <div class="spec-item">
                        <span class="spec-label">Signataire Actuel</span>
                        <span class="spec-val" style="color: var(--cyber-blue);"><?php echo htmlspecialchars($signatures['drh']['name']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Dernier Déploiement</span>
                        <span class="spec-val"><?php echo htmlspecialchars($signatures['drh']['updated_at']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Résolution & Poids</span>
                        <span class="spec-val"><?php echo htmlspecialchars($signatures['drh']['dimensions'] . ' — ' . $signatures['drh']['file_size_formatted']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Empreinte SHA-256</span>
                        <span class="spec-val" title="<?php echo htmlspecialchars($signatures['drh']['sha256']); ?>">
                            <?php echo substr($signatures['drh']['sha256'], 0, 14) . '...'; ?>
                        </span>
                    </div>
                </div>

                <!-- FORMULAIRE DE TÉLÉVERSEMENT NOUVELLE SIGNATURE -->
                <form action="/cimcim/admin/signatures" method="POST" enctype="multipart/form-data" id="form_upload_drh">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="upload_signature">
                    <input type="hidden" name="signature_type" value="drh">

                    <div class="form-section-title">
                        <i class="fa-solid fa-upload" style="color: var(--cyber-blue);"></i>
                        Remplacer la Signature DRH (Format PNG)
                    </div>

                    <!-- DROPZONE -->
                    <div class="dropzone-box" onclick="document.getElementById('file_input_drh').click()">
                        <input type="file" 
                               name="signature_file" 
                               id="file_input_drh" 
                               class="file-hidden-input" 
                               accept=".png,image/png" 
                               required
                               onchange="handleFileSelected(this, 'drh')">
                        <i class="fa-solid fa-file-signature dropzone-icon"></i>
                        <div class="dropzone-text">Cliquez ou glissez-déposez le fichier PNG de la signature</div>
                        <div class="dropzone-sub">Format strict : .PNG transparent ou scanné (Max 3 Mo)</div>
                        <div class="selected-file-name" id="file_name_drh"></div>
                    </div>

                    <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: #cbd5e1;">
                        <input type="checkbox" name="auto_remove_white" id="white_bg_drh" value="1" checked style="accent-color: var(--cyber-blue);">
                        <label for="white_bg_drh">Supprimer automatiquement le fond blanc (pour signature scannée sur papier)</label>
                    </div>

                    <div class="cimis-form-group">
                        <label class="cimis-label">Nom officiel du Signataire (Apparaît sous la signature sur la carte)</label>
                        <input type="text" 
                               name="signatory_name" 
                               class="cimis-input" 
                               value="<?php echo htmlspecialchars($signatures['drh']['name']); ?>" 
                               required>
                    </div>

                    <div class="cimis-form-group">
                        <label class="cimis-label">Titre officiel de l'autorité</label>
                        <input type="text" 
                               name="signatory_title" 
                               class="cimis-input" 
                               value="<?php echo htmlspecialchars($signatures['drh']['title']); ?>" 
                               required>
                    </div>

                    <!-- SAS DE SÉCURITÉ MOT DE PASSE -->
                    <div class="security-checkpoint">
                        <div class="security-checkpoint-title">
                            <i class="fa-solid fa-lock"></i> Validation de Sécurité Haute Autorité
                        </div>

                        <label class="confirm-checkbox-label">
                            <input type="checkbox" required>
                            <span>Je confirme solennellement que cette signature officielle est certifiée authentique pour l'ensemble des cartes des sous-officiers et militaires du rang.</span>
                        </label>

                        <div class="cimis-form-group" style="margin-bottom: 0.8rem;">
                            <label class="cimis-label" style="color: #fca5a5;">Confirmer le mot de passe Super Administrateur</label>
                            <input type="password" 
                                   name="superadmin_password" 
                                   class="cimis-input" 
                                   placeholder="Mot de passe requis pour autoriser l'action" 
                                   required 
                                   autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn-submit-signature">
                            <i class="fa-solid fa-shield-halved"></i> Valider et Déployer la Signature DRH
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- ==================================================== -->
    <!-- 3. HISTORIQUE & ARCHIVES DES SAUVEGARDES             -->
    <!-- ==================================================== -->
    <div class="archives-container">
        <div class="archives-box">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3 style="font-family: 'Rajdhani', sans-serif; font-size: 1.25rem; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: #eab308;"></i>
                    Historique des Remplacements & Coffre-fort des Archives
                </h3>
                <span style="font-family: 'Share Tech Mono', monospace; font-size: 0.8rem; color: var(--text-muted);">
                    Restauration d'urgence en 1 clic avec audit
                </span>
            </div>

            <?php if (empty($archives)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.85rem; font-family: 'Share Tech Mono', monospace;">
                    <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                    Aucune archive précédente enregistrée pour le moment. Une sauvegarde horodatée sera créée automatiquement à chaque remplacement.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="archives-table">
                        <thead>
                            <tr>
                                <th>Aperçu</th>
                                <th>Autorité</th>
                                <th>Date d'archivage</th>
                                <th>Fichier de sauvegarde</th>
                                <th>Taille</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archives as $arch): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $arch['url']; ?>" alt="Miniature" class="archive-thumb">
                                </td>
                                <td>
                                    <strong style="color: <?php echo ($arch['type'] === 'mindef') ? 'var(--gold)' : 'var(--cyber-blue)'; ?>;">
                                        <?php echo htmlspecialchars($arch['label_type']); ?>
                                    </strong>
                                </td>
                                <td style="font-family: 'Share Tech Mono', monospace;">
                                    <?php echo htmlspecialchars($arch['date']); ?>
                                </td>
                                <td style="font-family: 'Share Tech Mono', monospace; color: var(--text-muted); font-size: 0.78rem;">
                                    <?php echo htmlspecialchars($arch['filename']); ?>
                                </td>
                                <td style="font-family: 'Share Tech Mono', monospace;">
                                    <?php echo htmlspecialchars($arch['size_formatted']); ?>
                                </td>
                                <td style="text-align: right;">
                                    <button type="button" 
                                            class="btn-restore" 
                                            onclick="openRestoreModal('<?php echo htmlspecialchars($arch['filename'], ENT_QUOTES); ?>', '<?php echo $arch['type']; ?>', '<?php echo htmlspecialchars($arch['label_type'], ENT_QUOTES); ?>')">
                                        <i class="fa-solid fa-rotate-left"></i> Restaurer
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODALE DE CONFIRMATION DE RESTAURATION -->
    <div class="cimis-modal-backdrop" id="restoreModal">
        <div class="cimis-modal-box">
            <button type="button" class="btn-modal-close" onclick="closeRestoreModal()">&times;</button>
            <div class="modal-title">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Restauration Sécurisée de Signature
            </div>
            
            <p style="font-size: 0.85rem; color: #cbd5e1; margin-bottom: 1.2rem; line-height: 1.5;">
                Vous êtes sur le point de restaurer la signature archivée suivante pour l'autorité <strong id="modal_target_label" style="color: var(--gold);"></strong> :
                <br>
                <code id="modal_archive_name" style="color: var(--cyber-blue); font-family: 'Share Tech Mono', monospace; display: block; margin-top: 6px; padding: 4px 8px; background: rgba(0,0,0,0.5); border-radius: 4px;"></code>
            </p>

            <form action="/cimcim/admin/signatures" method="POST">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="restore_archive">
                <input type="hidden" name="signature_type" id="modal_sig_type" value="">
                <input type="hidden" name="archive_filename" id="modal_archive_filename" value="">

                <div class="cimis-form-group">
                    <label class="cimis-label" style="color: #fca5a5;">Saisir le mot de passe Super Administrateur</label>
                    <input type="password" 
                           name="superadmin_password" 
                           class="cimis-input" 
                           placeholder="Mot de passe requis" 
                           required 
                           autocomplete="current-password">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="closeRestoreModal()" class="btn-nav-cimis btn-nav-back" style="cursor: pointer; border: 1px solid rgba(255,255,255,0.2);">
                        Annuler
                    </button>
                    <button type="submit" class="btn-restore" style="padding: 8px 16px; font-size: 0.95rem;">
                        <i class="fa-solid fa-rotate-left"></i> Confirmer la Restauration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPTS CLIENTS -->
    <script>
        function switchPreview(type, mode, btn) {
            const container = document.getElementById('preview_stage_' + type);
            const buttons = container.querySelectorAll('.preview-tab-btn');
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const img = document.getElementById('img_preview_' + type);
            const prefix = (type === 'mindef') ? 'SignatureMindef' : 'SignatureDrh';
            const suffix = (mode === 'gold') ? '_gold.png' : '_transp.png';
            img.src = '/cimcim/public/img/signatures/' + prefix + suffix + '?t=' + Date.now();
        }

        function handleFileSelected(input, type) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileNameDiv = document.getElementById('file_name_' + type);
                
                // Vérification extension
                if (!file.name.toLowerCase().endsWith('.png')) {
                    alert('ERREUR DE SÉCURITÉ : Seuls les fichiers au format PNG authentique (.png) sont acceptés.');
                    input.value = '';
                    fileNameDiv.style.display = 'none';
                    return;
                }

                // Vérification taille (3 Mo)
                if (file.size > 3 * 1024 * 1024) {
                    alert('ERREUR : Le fichier sélectionné est trop volumineux (Max 3 Mo).');
                    input.value = '';
                    fileNameDiv.style.display = 'none';
                    return;
                }

                fileNameDiv.textContent = '✔ Fichier prêt : ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' Ko)';
                fileNameDiv.style.display = 'block';

                // Prévisualisation instantanée
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('img_preview_' + type);
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function openRestoreModal(filename, type, label) {
            document.getElementById('modal_archive_name').textContent = filename;
            document.getElementById('modal_archive_filename').value = filename;
            document.getElementById('modal_sig_type').value = type;
            document.getElementById('modal_target_label').textContent = label;
            document.getElementById('restoreModal').style.display = 'flex';
        }

        function closeRestoreModal() {
            document.getElementById('restoreModal').style.display = 'none';
        }

        // Fermer la modale si clic en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('restoreModal');
            if (event.target === modal) {
                closeRestoreModal();
            }
        };
    </script>
</body>
</html>
