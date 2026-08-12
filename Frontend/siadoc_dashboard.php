<?php
/**
 * SIADOC DASHBOARD - Tableau de bord de monitoring de l'interopérabilité CIMIS-SIADOC
 * Accès : Frontend/siadoc_dashboard.php
 */
require_once '../backend/config.php';
requireLogin();

// Récupérer les statistiques de la BDD pour la vue initiale
$stats = [];
try {
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_actifs,
            COUNT(CASE WHEN source_system = 'SIADOC' THEN 1 END) as venus_de_siadoc,
            COUNT(CASE WHEN source_system = 'CIMIS'  THEN 1 END) as venus_de_cimis,
            COUNT(CASE WHEN suspendus = 1 THEN 1 END)            as suspendus,
            COUNT(CASE WHEN code_qr IS NOT NULL THEN 1 END)      as avec_qr
        FROM candidat WHERE supprimer = 1
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// Dernières opérations de sync
$last_ops = [];
try {
    $stmt = $pdo->query("
        SELECT system, action, status, details, last_sync
        FROM api_sync_log ORDER BY last_sync DESC LIMIT 20
    ");
    $last_ops = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// Activité des 7 derniers jours
$activity_chart = [];
try {
    $stmt = $pdo->query("
        SELECT DATE(last_sync) as jour, COUNT(*) as operations, 
               COUNT(CASE WHEN status='SUCCESS' THEN 1 END) as succes,
               COUNT(CASE WHEN status='ERROR' THEN 1 END) as erreurs
        FROM api_sync_log
        WHERE last_sync >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(last_sync)
        ORDER BY jour ASC
    ");
    $activity_chart = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

$derniere_sync = $last_ops[0]['last_sync'] ?? null;
$taux_succes = 0;
if (!empty($last_ops)) {
    $succes_count = count(array_filter($last_ops, fn($o) => $o['status'] === 'SUCCESS'));
    $taux_succes  = round($succes_count / count($last_ops) * 100);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIADOC — Tableau de Bord Interopérabilité | CIMIS</title>
    <meta name="description" content="Monitoring et gestion de l'interopérabilité CIMIS-SIADOC. Synchronisation, import de données militaires, diagnostic.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:          #080c14;
            --bg2:         #0d1320;
            --bg3:         #111827;
            --card:        rgba(255,255,255,0.04);
            --border:      rgba(255,255,255,0.08);
            --border-glow: rgba(56,189,248,0.3);
            --primary:     #38bdf8;
            --primary-dk:  #0ea5e9;
            --accent:      #34d399;
            --accent-dk:   #059669;
            --warning:     #fbbf24;
            --danger:      #f87171;
            --text:        #f1f5f9;
            --muted:       #64748b;
            --sidebar-w:   260px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: grid;
            grid-template-columns: var(--sidebar-w) 1fr;
            grid-template-rows: 1fr;
        }

        /* â”€â”€ SIDEBAR â”€â”€ */
        .sidebar {
            background: var(--bg2);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 1.5rem 1rem;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1rem;
            margin-bottom: 1.5rem;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff;
            flex-shrink: 0;
        }

        .brand-text { line-height: 1.2; }
        .brand-title { font-size: .95rem; font-weight: 700; color: var(--text); }
        .brand-sub   { font-size: .7rem;  color: var(--muted); }

        .nav-section {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .12em;
            color: var(--muted);
            text-transform: uppercase;
            padding: 1rem 1rem .4rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .65rem 1rem;
            border-radius: 8px;
            color: var(--muted);
            text-decoration: none;
            font-size: .85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            margin-bottom: 2px;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(56,189,248,.1);
            color: var(--primary);
        }

        .nav-item i { width: 18px; text-align: center; font-size: .9rem; }

        .connection-badge {
            margin-top: auto;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
        }

        .conn-status {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .8rem;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-green  { background: var(--accent); box-shadow: 0 0 6px var(--accent); animation: pulse 2s infinite; }
        .dot-red    { background: var(--danger);  box-shadow: 0 0 6px var(--danger); }
        .dot-yellow { background: var(--warning); box-shadow: 0 0 6px var(--warning); }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

        /* â”€â”€ MAIN â”€â”€ */
        .main {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow: hidden;
        }

        .topbar {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .topbar-left { display: flex; align-items: center; gap: .75rem; }

        .page-title {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .page-badge {
            background: rgba(56,189,248,.15);
            color: var(--primary);
            font-size: .7rem;
            font-weight: 600;
            padding: .25rem .6rem;
            border-radius: 20px;
            border: 1px solid rgba(56,189,248,.3);
        }

        .topbar-actions { display: flex; gap: .75rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem 1.1rem;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-dk), var(--primary));
            color: #fff;
        }

        .btn-primary:hover { opacity: .9; transform: translateY(-1px); }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
        }

        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        .btn-success {
            background: linear-gradient(135deg, var(--accent-dk), var(--accent));
            color: #fff;
        }

        .btn-success:hover { opacity: .9; transform: translateY(-1px); }

        .btn-danger {
            background: rgba(248,113,113,.15);
            border: 1px solid rgba(248,113,113,.3);
            color: var(--danger);
        }

        .btn-sm { padding: .4rem .8rem; font-size: .78rem; }

        .content { flex: 1; padding: 1.75rem; overflow-y: auto; }

        /* â”€â”€ MÉTRIQUES â”€â”€ */
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .metric-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            transition: border-color .2s;
        }

        .metric-card:hover { border-color: var(--border-glow); }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--metric-color, var(--primary));
        }

        .metric-card.green   { --metric-color: var(--accent); }
        .metric-card.blue    { --metric-color: var(--primary); }
        .metric-card.yellow  { --metric-color: var(--warning); }
        .metric-card.red     { --metric-color: var(--danger); }

        .metric-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            margin-bottom: .75rem;
            background: var(--metric-bg, rgba(56,189,248,.12));
            color: var(--metric-color, var(--primary));
        }

        .metric-card.green  .metric-icon { --metric-bg: rgba(52,211,153,.12); }
        .metric-card.yellow .metric-icon { --metric-bg: rgba(251,191,36,.12);  }
        .metric-card.red    .metric-icon { --metric-bg: rgba(248,113,113,.12); }

        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: .25rem;
        }

        .metric-label { font-size: .78rem; color: var(--muted); }
        .metric-delta {
            font-size: .72rem;
            margin-top: .4rem;
            color: var(--muted);
        }

        /* â”€â”€ GRILLE 2 COL â”€â”€ */
        .grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }

        @media (max-width: 1100px) { .grid-2 { grid-template-columns: 1fr; } }

        /* â”€â”€ CARDS â”€â”€ */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            font-size: .88rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .card-title i { color: var(--primary); }

        .card-body { padding: 1.25rem; }

        /* â”€â”€ GRAPHIQUE ACTIVITÉ â”€â”€ */
        .chart-container {
            height: 200px;
            display: flex;
            align-items: flex-end;
            gap: 6px;
            padding: 0 .5rem .5rem;
        }

        .chart-bar-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            height: 100%;
            justify-content: flex-end;
        }

        .chart-bar {
            width: 100%;
            border-radius: 4px 4px 0 0;
            min-height: 2px;
            transition: opacity .2s;
            position: relative;
        }

        .chart-bar:hover { opacity: .8; }

        .chart-bar.success { background: var(--accent); }
        .chart-bar.error   { background: var(--danger); }

        .chart-label {
            font-size: .6rem;
            color: var(--muted);
            margin-top: 4px;
            text-align: center;
        }

        /* â”€â”€ JOURNAL â”€â”€ */
        .log-table { width: 100%; border-collapse: collapse; font-size: .8rem; }

        .log-table th {
            text-align: left;
            padding: .6rem 1rem;
            color: var(--muted);
            font-weight: 600;
            font-size: .7rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
        }

        .log-table td {
            padding: .65rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.03);
            vertical-align: middle;
        }

        .log-table tr:hover td { background: rgba(255,255,255,.02); }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .2rem .6rem;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 600;
        }

        .pill-success { background: rgba(52,211,153,.15); color: var(--accent); }
        .pill-error   { background: rgba(248,113,113,.15); color: var(--danger); }
        .pill-partial { background: rgba(251,191,36,.15);  color: var(--warning); }
        .pill-pending { background: rgba(148,163,184,.15); color: var(--muted); }

        /* â”€â”€ PANEL IMPORT â”€â”€ */
        .import-panel { display: flex; flex-direction: column; gap: 1rem; }

        .import-method {
            background: rgba(255,255,255,.03);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all .2s;
        }

        .import-method:hover { border-color: var(--primary); background: rgba(56,189,248,.05); }
        .import-method.active { border-color: var(--primary); background: rgba(56,189,248,.08); }

        .method-title {
            font-size: .85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .4rem;
        }

        .method-desc { font-size: .75rem; color: var(--muted); }

        .import-form { display: none; margin-top: .75rem; }
        .import-form.show { display: block; }

        .form-group { margin-bottom: .75rem; }
        .form-label { font-size: .75rem; font-weight: 600; color: var(--muted); display: block; margin-bottom: .35rem; }

        .form-input, .form-textarea {
            width: 100%;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .55rem .85rem;
            color: var(--text);
            font-size: .82rem;
            font-family: 'Inter', sans-serif;
            transition: border-color .15s;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-textarea { resize: vertical; min-height: 80px; }

        /* â”€â”€ RÉSULTAT D'OPÉRATION â”€â”€ */
        #operation-result {
            display: none;
            margin-top: 1rem;
            border-radius: 10px;
            padding: 1rem;
            font-size: .82rem;
        }

        #operation-result.show { display: block; }
        #operation-result.success { background: rgba(52,211,153,.1); border: 1px solid rgba(52,211,153,.25); }
        #operation-result.error   { background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.25); }

        .result-title { font-weight: 700; margin-bottom: .5rem; }
        .result-grid  { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-top: .5rem; }

        .result-item { text-align: center; }
        .result-num  { font-size: 1.4rem; font-weight: 800; }
        .result-lbl  { font-size: .7rem; color: var(--muted); }

        /* â”€â”€ TOAST â”€â”€ */
        #toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: .5rem;
            z-index: 9999;
        }

        .toast {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
            border-radius: 10px;
            padding: .9rem 1.2rem;
            font-size: .82rem;
            max-width: 320px;
            animation: slideIn .3s ease;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .toast.success { border-left-color: var(--accent); }
        .toast.error   { border-left-color: var(--danger); }
        .toast.warning { border-left-color: var(--warning); }

        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        /* â”€â”€ SPINNER â”€â”€ */
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .loading-overlay.show { display: flex; }

        .loading-box {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 2rem 3rem;
            text-align: center;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(56,189,248,.2);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin .8s linear infinite;
            margin: 0 auto 1rem;
        }

        .loading-text { color: var(--muted); font-size: .85rem; }

        /* â”€â”€ STATUT CONNEXION â”€â”€ */
        .siadoc-url { font-size: .7rem; color: var(--muted); margin-top: .35rem; word-break: break-all; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-exchange-alt"></i></div>
        <div class="brand-text">
            <div class="brand-title">CIMIS × SIADOC</div>
            <div class="brand-sub">Interopérabilité v2.0</div>
        </div>
    </div>

    <span class="nav-section">Tableau de bord</span>
    <a class="nav-item active" onclick="showSection('dashboard')">
        <i class="fas fa-chart-line"></i> Vue d'ensemble
    </a>

    <span class="nav-section">Import SIADOC → CIMIS</span>
    <a class="nav-item" onclick="showSection('import')">
        <i class="fas fa-file-import"></i> Importer des données
    </a>
    <a class="nav-item" onclick="showSection('sync')">
        <i class="fas fa-sync-alt"></i> Synchronisation auto
    </a>

    <span class="nav-section">Contrôle</span>
    <a class="nav-item" onclick="showSection('journal')">
        <i class="fas fa-list-alt"></i> Journal des échanges
    </a>
    <a class="nav-item" href="siadoc_diagnostic.php">
        <i class="fas fa-stethoscope"></i> Diagnostic
    </a>

    <span class="nav-section">Liens</span>
    <a class="nav-item" href="dashboard.php">
        <i class="fas fa-home"></i> Dashboard CIMIS
    </a>
    <a class="nav-item" href="enrolement.php">
        <i class="fas fa-user-plus"></i> Enrôlement
    </a>

    <div class="connection-badge">
        <div class="conn-status">
            <div class="dot dot-yellow" id="conn-dot"></div>
            <span id="conn-label">Vérification...</span>
        </div>
        <div class="siadoc-url" id="siadoc-url-display">siadoc.onrender.com</div>
        <button class="btn btn-outline btn-sm" style="margin-top:.75rem;width:100%" onclick="testConnexion()">
            <i class="fas fa-plug"></i> Tester
        </button>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <div class="page-title">Interopérabilité SIADOC</div>
            <span class="page-badge">Monitoring en direct</span>
        </div>
        <div class="topbar-actions">
            <button class="btn btn-outline" onclick="refreshDashboard()">
                <i class="fas fa-redo"></i> Actualiser
            </button>
            <button class="btn btn-success" onclick="syncIncremental()">
                <i class="fas fa-sync-alt"></i> Sync incrémentielle
            </button>
            <button class="btn btn-primary" onclick="showSection('import')">
                <i class="fas fa-file-import"></i> Importer
            </button>
        </div>
    </div>

    <div class="content">

        <!-- â”€â”€ SECTION DASHBOARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <div id="section-dashboard">

            <!-- MÉTRIQUES -->
            <div class="metrics">
                <div class="metric-card blue">
                    <div class="metric-icon"><i class="fas fa-id-card"></i></div>
                    <div class="metric-value" id="m-total"><?= number_format((int)($stats['total_actifs']??0)) ?></div>
                    <div class="metric-label">Cartes actives CIMIS</div>
                    <div class="metric-delta"><i class="fas fa-circle-info"></i> supprimer = 1</div>
                </div>
                <div class="metric-card green">
                    <div class="metric-icon"><i class="fas fa-download"></i></div>
                    <div class="metric-value" id="m-siadoc"><?= number_format((int)($stats['venus_de_siadoc']??0)) ?></div>
                    <div class="metric-label">Importés depuis SIADOC</div>
                    <div class="metric-delta"><i class="fas fa-arrow-trend-up"></i> source_system = SIADOC</div>
                </div>
                <div class="metric-card blue">
                    <div class="metric-icon"><i class="fas fa-qrcode"></i></div>
                    <div class="metric-value" id="m-qr"><?= number_format((int)($stats['avec_qr']??0)) ?></div>
                    <div class="metric-label">Avec code QR</div>
                    <div class="metric-delta">Prêts pour impression</div>
                </div>
                <div class="metric-card yellow">
                    <div class="metric-icon"><i class="fas fa-ban"></i></div>
                    <div class="metric-value" id="m-suspendus"><?= number_format((int)($stats['suspendus']??0)) ?></div>
                    <div class="metric-label">Suspendus</div>
                    <div class="metric-delta">Carte non visible</div>
                </div>
                <div class="metric-card green">
                    <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="metric-value"><?= $taux_succes ?>%</div>
                    <div class="metric-label">Taux de succès sync</div>
                    <div class="metric-delta">Sur les 20 dernières ops.</div>
                </div>
            </div>

            <!-- GRAPHIQUE + SYNC STATUS -->
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-chart-bar"></i> Activité des 7 derniers jours</div>
                        <span style="font-size:.72rem;color:var(--muted)">
                            <span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:var(--accent);margin-right:4px"></span>Succès
                            <span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:var(--danger);margin-left:8px;margin-right:4px"></span>Erreurs
                        </span>
                    </div>
                    <div class="card-body" style="padding-bottom:.5rem">
                        <?php
                        $max_ops = 1;
                        foreach ($activity_chart as $day) {
                            $max_ops = max($max_ops, (int)$day['operations']);
                        }
                        ?>
                        <div class="chart-container" id="activity-chart">
                        <?php
                        // Générer les 7 derniers jours
                        for ($i = 6; $i >= 0; $i--) {
                            $date = date('Y-m-d', strtotime("-$i days"));
                            $label = date('d/m', strtotime("-$i days"));
                            $day_data = null;
                            foreach ($activity_chart as $d) {
                                if ($d['jour'] === $date) { $day_data = $d; break; }
                            }
                            $ops    = $day_data ? (int)$day_data['operations'] : 0;
                            $suc    = $day_data ? (int)$day_data['succes']     : 0;
                            $err    = $day_data ? (int)$day_data['erreurs']    : 0;
                            $h_suc  = $max_ops > 0 ? round($suc / $max_ops * 150) : 0;
                            $h_err  = $max_ops > 0 ? round($err / $max_ops * 150) : 0;
                            echo "
                            <div class='chart-bar-group' title='$label : $suc succès, $err erreurs'>
                                <div class='chart-bar error'   style='height:{$h_err}px'></div>
                                <div class='chart-bar success' style='height:{$h_suc}px'></div>
                                <div class='chart-label'>$label</div>
                            </div>";
                        }
                        ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-link"></i> Statut SIADOC</div>
                    </div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:.75rem">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border)">
                            <div style="font-size:.8rem;color:var(--muted)">Connexion API</div>
                            <div class="status-pill" id="siadoc-conn-pill" style="background:rgba(251,191,36,.15);color:var(--warning)">
                                <div class="dot dot-yellow"></div>
                                <span>En attente</span>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border)">
                            <div style="font-size:.8rem;color:var(--muted)">Temps de réponse</div>
                            <div style="font-size:.85rem;font-weight:700" id="siadoc-latency">—</div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border)">
                            <div style="font-size:.8rem;color:var(--muted)">Dernière sync</div>
                            <div style="font-size:.8rem" id="last-sync-time">
                                <?= $derniere_sync ? date('d/m/Y H:i', strtotime($derniere_sync)) : 'Jamais' ?>
                            </div>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border)">
                            <div style="font-size:.8rem;color:var(--muted)">URL SIADOC</div>
                            <div style="font-size:.72rem;color:var(--primary)">siadoc.onrender.com</div>
                        </div>
                        <button class="btn btn-primary" style="width:100%" onclick="testConnexion()" id="btn-test-conn">
                            <i class="fas fa-plug"></i> Tester la connexion
                        </button>
                    </div>
                </div>
            </div>

            <!-- JOURNAL RÉCENT -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-history"></i> Journal récent des échanges</div>
                    <button class="btn btn-outline btn-sm" onclick="showSection('journal')">Voir tout</button>
                </div>
                <div style="overflow-x:auto">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Système</th>
                                <th>Action</th>
                                <th>Statut</th>
                                <th>Détails</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="log-tbody">
                        <?php foreach (array_slice($last_ops, 0, 10) as $op):
                            $pill = match($op['status']) {
                                'SUCCESS'  => 'pill-success',
                                'ERROR'    => 'pill-error',
                                'PARTIAL'  => 'pill-partial',
                                default    => 'pill-pending'
                            };
                            $icon = match($op['status']) {
                                'SUCCESS' => 'fa-check',
                                'ERROR'   => 'fa-times',
                                'PARTIAL' => 'fa-exclamation',
                                default   => 'fa-clock'
                            };
                            $details = '';
                            if ($op['details']) {
                                $d = json_decode($op['details'], true);
                                if ($d) {
                                    $parts = [];
                                    if (isset($d['total'])) $parts[] = $d['total'] . ' enr.';
                                    if (isset($d['succes'])) $parts[] = $d['succes'] . ' ok';
                                    if (isset($d['erreurs'])) $parts[] = $d['erreurs'] . ' err.';
                                    $details = implode(' · ', $parts);
                                } else {
                                    $details = substr($op['details'], 0, 40);
                                }
                            }
                        ?>
                            <tr>
                                <td><span style="font-size:.75rem;font-weight:600"><?= htmlspecialchars($op['system']) ?></span></td>
                                <td style="color:var(--muted);font-size:.78rem"><?= htmlspecialchars($op['action']) ?></td>
                                <td>
                                    <span class="status-pill <?= $pill ?>">
                                        <i class="fas <?= $icon ?>" style="font-size:.6rem"></i>
                                        <?= $op['status'] ?>
                                    </span>
                                </td>
                                <td style="color:var(--muted);font-size:.75rem"><?= htmlspecialchars($details) ?></td>
                                <td style="font-size:.75rem;color:var(--muted)"><?= date('d/m H:i', strtotime($op['last_sync'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($last_ops)): ?>
                    <div style="text-align:center;padding:2rem;color:var(--muted);font-size:.85rem">
                        <i class="fas fa-inbox" style="font-size:2rem;margin-bottom:.75rem;display:block;opacity:.3"></i>
                        Aucune opération enregistrée
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- â”€â”€ SECTION IMPORT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <div id="section-import" style="display:none">
            <div style="margin-bottom:1.25rem">
                <h2 style="font-size:1rem;font-weight:700;margin-bottom:.25rem">Import SIADOC → CIMIS</h2>
                <p style="font-size:.8rem;color:var(--muted)">
                    Récupérez les données depuis SIADOC, vérifiez-les et importez-les dans CIMIS.
                    Un matricule CIMIS et un QR code sont générés automatiquement.
                </p>
            </div>

            <div class="grid-2">
                <div class="import-panel">

                    <!-- Méthode 1 : Par matricule -->
                    <div class="import-method" onclick="toggleMethod('method-single')">
                        <div class="method-title">
                            <i class="fas fa-user" style="color:var(--primary)"></i>
                            Par matricule militaire
                        </div>
                        <div class="method-desc">Importez un militaire en entrant son matricule SIADOC (ex: T14/6584)</div>
                        <div class="import-form" id="method-single">
                            <div class="form-group">
                                <label class="form-label">Matricule SIADOC</label>
                                <input type="text" id="inp-matricule-single" class="form-input" placeholder="ex: T14/6584" autocomplete="off">
                            </div>
                            <button class="btn btn-primary" onclick="importSingle(event)">
                                <i class="fas fa-file-import"></i> Importer
                            </button>
                        </div>
                    </div>

                    <!-- Méthode 2 : Plusieurs matricules -->
                    <div class="import-method" onclick="toggleMethod('method-multiple')">
                        <div class="method-title">
                            <i class="fas fa-users" style="color:var(--accent)"></i>
                            Plusieurs matricules
                        </div>
                        <div class="method-desc">Importez plusieurs militaires en entrant leurs matricules (un par ligne)</div>
                        <div class="import-form" id="method-multiple">
                            <div class="form-group">
                                <label class="form-label">Matricules (un par ligne)</label>
                                <textarea id="inp-matricules-multi" class="form-textarea" placeholder="T14/6584&#10;M15/4578&#10;A16/7845"></textarea>
                            </div>
                            <button class="btn btn-success" onclick="importMultiple(event)">
                                <i class="fas fa-file-import"></i> Importer tout
                            </button>
                        </div>
                    </div>

                    <!-- Méthode 3 : Par période -->
                    <div class="import-method" onclick="toggleMethod('method-periode')">
                        <div class="method-title">
                            <i class="fas fa-calendar" style="color:var(--warning)"></i>
                            Par période d'enrôlement
                        </div>
                        <div class="method-desc">Importez tous les militaires enrôlés entre deux dates</div>
                        <div class="import-form" id="method-periode">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                                <div class="form-group">
                                    <label class="form-label">Date début</label>
                                    <input type="date" id="inp-date-debut" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date fin</label>
                                    <input type="date" id="inp-date-fin" class="form-input">
                                </div>
                            </div>
                            <button class="btn btn-outline" onclick="importPeriode(event)">
                                <i class="fas fa-file-import"></i> Importer la période
                            </button>
                        </div>
                    </div>

                    <!-- Méthode 4 : JSON direct -->
                    <div class="import-method" onclick="toggleMethod('method-json')">
                        <div class="method-title">
                            <i class="fas fa-code" style="color:var(--danger)"></i>
                            Données JSON directes
                        </div>
                        <div class="method-desc">Collez directement les données JSON reçues de SIADOC</div>
                        <div class="import-form" id="method-json">
                            <div class="form-group">
                                <label class="form-label">JSON des données militaires</label>
                                <textarea id="inp-json-data" class="form-textarea" style="min-height:120px;font-family:monospace;font-size:.75rem" placeholder='{"matricule":"T14/6584","nom":"DUPONT","prenom":"Jean",...}'></textarea>
                            </div>
                            <button class="btn btn-danger" onclick="importJSON(event)">
                                <i class="fas fa-file-import"></i> Importer JSON
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Résultat -->
                <div>
                    <div class="card" style="position:sticky;top:1rem">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-clipboard-check"></i> Résultat</div>
                        </div>
                        <div class="card-body">
                            <div id="operation-result">
                                <div class="result-title" id="result-title">—</div>
                                <div id="result-body"></div>
                            </div>
                            <div id="result-placeholder" style="text-align:center;padding:2rem;color:var(--muted);font-size:.82rem">
                                <i class="fas fa-arrow-left" style="font-size:1.5rem;margin-bottom:.5rem;display:block;opacity:.3"></i>
                                Sélectionnez une méthode d'import et cliquez sur le bouton.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- â”€â”€ SECTION JOURNAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <div id="section-journal" style="display:none">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-history"></i> Journal complet des échanges</div>
                    <button class="btn btn-outline btn-sm" onclick="loadJournal()">
                        <i class="fas fa-redo"></i> Actualiser
                    </button>
                </div>
                <div style="overflow-x:auto">
                    <table class="log-table" id="journal-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Système</th>
                                <th>Action</th>
                                <th>Statut</th>
                                <th>Détails</th>
                                <th>Date/Heure</th>
                            </tr>
                        </thead>
                        <tbody id="journal-tbody">
                            <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">Chargement...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- â”€â”€ SECTION SYNC â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
        <div id="section-sync" style="display:none">
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-sync-alt"></i> Synchronisation incrémentielle</div>
                    </div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
                        <p style="font-size:.82rem;color:var(--muted)">
                            La synchronisation incrémentielle récupère uniquement les enregistrements modifiés depuis la dernière synchronisation.
                            Cela évite de re-traiter toute la base de données Ã  chaque fois.
                        </p>
                        <div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:1rem">
                            <div style="font-size:.75rem;color:var(--muted);margin-bottom:.35rem">Dernière synchronisation</div>
                            <div style="font-size:1rem;font-weight:700" id="sync-last-date">
                                <?= $derniere_sync ? date('d/m/Y Ã  H:i:s', strtotime($derniere_sync)) : 'Jamais effectuée' ?>
                            </div>
                        </div>
                        <button class="btn btn-success" onclick="syncIncremental()" id="btn-sync-incr">
                            <i class="fas fa-sync-alt"></i> Lancer la synchronisation
                        </button>
                        <button class="btn btn-danger" onclick="syncTous()" style="width:100%">
                            <i class="fas fa-database"></i> Import complet (tous)
                        </button>
                        <div id="sync-result" style="display:none"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-clock"></i> Script CRON automatique</div>
                    </div>
                    <div class="card-body">
                        <p style="font-size:.8rem;color:var(--muted);margin-bottom:1rem">
                            Pour automatiser la synchronisation, ajoutez cette ligne Ã  votre crontab :
                        </p>
                        <div style="background:rgba(0,0,0,.4);border-radius:8px;padding:.75rem;font-family:monospace;font-size:.72rem;color:var(--accent);word-break:break-all">
                            */30 * * * * php <?= realpath(dirname(__DIR__)) ?>/scripts/siadoc_sync_cron.php >> <?= realpath(dirname(__DIR__)) ?>/logs/siadoc_sync.log 2>&1
                        </div>
                        <p style="font-size:.75rem;color:var(--muted);margin-top:.75rem">
                            <i class="fas fa-info-circle"></i> Ce script se lance toutes les 30 minutes.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /content -->
</main>

<!-- LOADING OVERLAY -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-box">
        <div class="loading-spinner"></div>
        <div class="loading-text" id="loading-text">Traitement en cours...</div>
    </div>
</div>

<!-- TOASTS -->
<div id="toast-container"></div>

<script>
const API_KEY   = '<?= htmlspecialchars(SIADOC_API_KEY) ?>';
const BASE_PATH = '../backend/';

// â”€â”€ NAVIGATION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function showSection(name) {
    document.querySelectorAll('[id^="section-"]').forEach(el => el.style.display = 'none');
    document.getElementById('section-' + name).style.display = 'block';
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));

    if (name === 'journal') loadJournal();
}

// â”€â”€ TEST CONNEXION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function testConnexion() {
    const btn = document.getElementById('btn-test-conn');
    const dot = document.getElementById('conn-dot');
    const label = document.getElementById('conn-label');
    const pill = document.getElementById('siadoc-conn-pill');
    const latency = document.getElementById('siadoc-latency');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Test...';
    dot.className = 'dot dot-yellow';
    label.textContent = 'Test en cours...';

    try {
        const t0 = performance.now();
        const res = await apiFetch('siadoc_import.php?action=test_connexion', {method: 'GET'});
        const ms = Math.round(performance.now() - t0);

        if (res.success) {
            dot.className = 'dot dot-green';
            label.textContent = 'Connecté';
            pill.innerHTML = '<div class="dot dot-green"></div><span>Connecté</span>';
            pill.style.cssText = 'background:rgba(52,211,153,.15);color:var(--accent)';
            latency.textContent = res.duree_ms + ' ms';
            latency.style.color = res.duree_ms < 500 ? 'var(--accent)' : 'var(--warning)';
            showToast('Connexion SIADOC OK — ' + res.duree_ms + ' ms', 'success');
        } else {
            throw new Error('HTTP ' + res.http_code);
        }
    } catch (e) {
        dot.className = 'dot dot-red';
        label.textContent = 'Hors ligne';
        pill.innerHTML = '<div class="dot dot-red"></div><span>Hors ligne</span>';
        pill.style.cssText = 'background:rgba(248,113,113,.15);color:var(--danger)';
        latency.textContent = '—';
        showToast('Connexion SIADOC échouée: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Tester la connexion';
    }
}

// â”€â”€ TOGGLES MÉTHODES IMPORT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function toggleMethod(id) {
    const form = document.getElementById(id);
    form.classList.toggle('show');
    form.closest('.import-method').classList.toggle('active');
    event.stopPropagation();
}

// â”€â”€ IMPORT UNIQUE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function importSingle(e) {
    e.stopPropagation();
    const mat = document.getElementById('inp-matricule-single').value.trim();
    if (!mat) { showToast('Entrez un matricule', 'error'); return; }

    showLoading('Récupération depuis SIADOC...');
    try {
        const res = await apiFetch('siadoc_import.php?action=importer', {
            method: 'POST',
            body: JSON.stringify({ matricule: mat })
        });
        showImportResult(res, [res]);
    } catch(e) {
        showToast('Erreur: ' + e.message, 'error');
    } finally { hideLoading(); }
}

// â”€â”€ IMPORT MULTIPLE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function importMultiple(e) {
    e.stopPropagation();
    const raw = document.getElementById('inp-matricules-multi').value.trim();
    if (!raw) { showToast('Entrez au moins un matricule', 'error'); return; }

    const matricules = raw.split('\n').map(m => m.trim()).filter(m => m.length > 0);
    if (matricules.length === 0) { showToast('Aucun matricule valide', 'error'); return; }

    showLoading(`Import de ${matricules.length} militaires...`);
    try {
        const res = await apiFetch('siadoc_import.php?action=importer_multiple', {
            method: 'POST',
            body: JSON.stringify({ matricules })
        });
        showImportResult(res, res.resultats || []);
    } catch(e) {
        showToast('Erreur: ' + e.message, 'error');
    } finally { hideLoading(); }
}

// â”€â”€ IMPORT PÉRIODE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function importPeriode(e) {
    e.stopPropagation();
    const d1 = document.getElementById('inp-date-debut').value;
    const d2 = document.getElementById('inp-date-fin').value;
    if (!d1 || !d2) { showToast('Sélectionnez les deux dates', 'error'); return; }
    if (d1 > d2) { showToast('La date de début doit être avant la date de fin', 'error'); return; }

    showLoading('Import de la période...');
    try {
        const res = await apiFetch('siadoc_import.php?action=importer_periode', {
            method: 'POST',
            body: JSON.stringify({ date_debut: d1, date_fin: d2 })
        });
        showImportResult(res, res.resultats || []);
    } catch(e) {
        showToast('Erreur: ' + e.message, 'error');
    } finally { hideLoading(); }
}

// â”€â”€ IMPORT JSON â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function importJSON(e) {
    e.stopPropagation();
    const raw = document.getElementById('inp-json-data').value.trim();
    if (!raw) { showToast('Collez des données JSON', 'error'); return; }

    let parsed;
    try { parsed = JSON.parse(raw); }
    catch(err) { showToast('JSON invalide: ' + err.message, 'error'); return; }

    // Accepter objet unique ou tableau
    const data = Array.isArray(parsed) ? parsed : [parsed];
    showLoading(`Import de ${data.length} enregistrement(s)...`);

    try {
        const res = await apiFetch('siadoc_import.php?action=importer_multiple', {
            method: 'POST',
            body: JSON.stringify({ militaires: data })
        });
        showImportResult(res, res.resultats || []);
    } catch(e) {
        showToast('Erreur: ' + e.message, 'error');
    } finally { hideLoading(); }
}

// â”€â”€ SYNCHRONISATION â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function syncIncremental() {
    showLoading('Synchronisation incrémentielle...');
    try {
        const res = await apiFetch('api_siadoc_envoie.php?action=synchronisation', { method: 'GET' });
        const count = res.data?.total_modifiees ?? 0;
        showToast(`Sync terminée — ${count} enregistrement(s) mis Ã  jour`, 'success');
        document.getElementById('sync-last-date').textContent = 'À l\'instant';
        document.getElementById('last-sync-time').textContent = 'À l\'instant';

        const r = document.getElementById('sync-result');
        r.style.display = 'block';
        r.innerHTML = `<div style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.25);border-radius:8px;padding:.75rem;font-size:.82rem;margin-top:.75rem">
            <strong style="color:var(--accent)">Synchronisation réussie</strong><br>
            ${count} enregistrement(s) traité(s).
        </div>`;
    } catch(e) {
        showToast('Erreur sync: ' + e.message, 'error');
    } finally { hideLoading(); }
}

async function syncTous() {
    if (!confirm('âš ï¸ Import complet depuis SIADOC.\nCela peut prendre du temps selon le volume de données.\n\nContinuer ?')) return;

    showLoading('Import complet en cours...');
    try {
        const res = await apiFetch('siadoc_import.php?action=importer_tous', { method: 'POST', body: '{}' });
        showToast(`Import terminé — ${res.succes || 0} créés, ${res.mises_a_jour || 0} mis Ã  jour, ${res.erreurs || 0} erreurs`, res.erreurs > 0 ? 'warning' : 'success');
    } catch(e) {
        showToast('Erreur: ' + e.message, 'error');
    } finally { hideLoading(); }
}

// â”€â”€ JOURNAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function loadJournal() {
    const tbody = document.getElementById('journal-tbody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)"><span class="spinner"></span> Chargement...</td></tr>';

    try {
        const res = await fetch('../backend/api_siadoc_envoie.php?action=statistiques', {
            headers: { 'X-API-KEY': API_KEY }
        });
        // Fallback: recharger la page pour le journal
    } catch(e) { }

    // Recharger via AJAX la page pour les logs
    try {
        const r = await fetch(window.location.href + '?json_logs=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        // Le journal est généré PHP-side, on recharge la page simplement
    } catch(e) { }

    location.reload();
}

// â”€â”€ AFFICHAGE RÉSULTAT IMPORT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function showImportResult(res, resultats) {
    const el       = document.getElementById('operation-result');
    const title    = document.getElementById('result-title');
    const body     = document.getElementById('result-body');
    const ph       = document.getElementById('result-placeholder');

    ph.style.display = 'none';
    el.classList.add('show');

    const succes     = res.succes || (res.success && res.action === 'CREATION' ? 1 : 0);
    const majs       = res.mises_a_jour || (res.success && res.action === 'MISE_A_JOUR' ? 1 : 0);
    const erreurs    = res.erreurs || (!res.success ? 1 : 0);
    const isSuccess  = res.success || succes > 0;

    el.className = 'show ' + (isSuccess ? 'success' : 'error');

    title.innerHTML = isSuccess
        ? `<i class="fas fa-check-circle" style="color:var(--accent)"></i> Import réussi`
        : `<i class="fas fa-times-circle" style="color:var(--danger)"></i> ${res.message || 'Erreur'}`;

    let detailsHTML = '';

    if (res.matricule_cimis) {
        detailsHTML += `<div style="margin-top:.75rem;padding:.75rem;background:rgba(0,0,0,.3);border-radius:8px;font-size:.8rem">
            <div><strong>Matricule CIMIS :</strong> <span style="color:var(--primary)">${res.matricule_cimis}</span></div>
            <div><strong>Matricule militaire :</strong> ${res.matricule_militaire || '—'}</div>
            ${res.qr_code ? `<div><strong>QR Code :</strong> <span style="color:var(--accent)">Généré âœ“</span></div>` : ''}
        </div>`;
    } else if (resultats && resultats.length > 0) {
        detailsHTML += `<div class="result-grid" style="margin-top:.75rem">
            <div class="result-item"><div class="result-num" style="color:var(--accent)">${succes}</div><div class="result-lbl">Créés</div></div>
            <div class="result-item"><div class="result-num" style="color:var(--primary)">${majs}</div><div class="result-lbl">Mis Ã  jour</div></div>
            <div class="result-item"><div class="result-num" style="color:var(--danger)">${erreurs}</div><div class="result-lbl">Erreurs</div></div>
        </div>`;

        // Lister les erreurs
        const errors = resultats.filter(r => !r.success);
        if (errors.length > 0) {
            detailsHTML += `<div style="margin-top:.75rem;font-size:.75rem">`;
            errors.slice(0, 5).forEach(e => {
                detailsHTML += `<div style="color:var(--danger);margin-bottom:.25rem">âš  ${e.matricule || '?'}: ${e.message || 'Erreur'}</div>`;
            });
            if (errors.length > 5) detailsHTML += `<div style="color:var(--muted)">... et ${errors.length - 5} autre(s) erreur(s)</div>`;
            detailsHTML += '</div>';
        }
    }

    body.innerHTML = detailsHTML;

    if (isSuccess) showToast(`Import terminé — ${succes} créé(s), ${majs} mis Ã  jour, ${erreurs} erreur(s)`, erreurs > 0 ? 'warning' : 'success');
}

// â”€â”€ UTILITAIRES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function apiFetch(path, options = {}) {
    const opts = {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'X-API-KEY': API_KEY,
            ...(options.headers || {})
        }
    };
    const res = await fetch(BASE_PATH + path, opts);
    if (!res.ok && res.status !== 200) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error || err.message || 'HTTP ' + res.status);
    }
    return res.json();
}

function showLoading(text = 'Traitement...') {
    document.getElementById('loading-text').textContent = text;
    document.getElementById('loading-overlay').classList.add('show');
}

function hideLoading() {
    document.getElementById('loading-overlay').classList.remove('show');
}

function showToast(msg, type = 'info') {
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    const colors = { success: 'var(--accent)', error: 'var(--danger)', warning: 'var(--warning)', info: 'var(--primary)' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info}" style="color:${colors[type]};font-size:1.1rem;flex-shrink:0"></i>${msg}`;
    document.getElementById('toast-container').appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

function refreshDashboard() { location.reload(); }

// â”€â”€ INIT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

window.addEventListener('DOMContentLoaded', () => {
    // Tester la connexion SIADOC au chargement
    setTimeout(testConnexion, 800);
});
</script>
</body>
</html>

