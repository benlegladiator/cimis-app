<?php
/**
 * SIADOC DIAGNOSTIC — Page de diagnostic et test de connexion CIMIS-SIADOC
 * Accès : Frontend/siadoc_diagnostic.php
 */
require_once '../backend/config.php';
requireLogin();

$siadoc_url = 'https://siadoc.onrender.com';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic SIADOC | CIMIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:     #080c14;
            --bg2:    #0d1320;
            --card:   rgba(255,255,255,.04);
            --border: rgba(255,255,255,.08);
            --prim:   #38bdf8;
            --acc:    #34d399;
            --warn:   #fbbf24;
            --err:    #f87171;
            --muted:  #64748b;
            --text:   #f1f5f9;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 0;
        }

        .header {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-left { display: flex; align-items: center; gap: 1rem; }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: var(--muted);
            text-decoration: none;
            font-size: .85rem;
            padding: .4rem .8rem;
            border-radius: 7px;
            border: 1px solid var(--border);
            transition: all .15s;
        }
        .back-btn:hover { border-color: var(--prim); color: var(--prim); }

        .page-title { font-size: 1.1rem; font-weight: 700; }

        .btn {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .6rem 1.2rem; border-radius: 8px; font-size: .85rem;
            font-weight: 600; border: none; cursor: pointer; transition: all .2s;
        }
        .btn-primary { background: linear-gradient(135deg, #0ea5e9, #38bdf8); color: #fff; }
        .btn-primary:hover { opacity: .9; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--muted); }
        .btn-outline:hover { border-color: var(--prim); color: var(--prim); }

        .content { max-width: 900px; margin: 0 auto; padding: 2rem; }

        .intro {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            font-size: .85rem;
            color: var(--muted);
        }
        .intro strong { color: var(--text); }

        /* ── TESTS ── */
        .tests-grid {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            margin-bottom: 1.5rem;
        }

        .test-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .test-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .9rem 1.25rem;
            cursor: pointer;
            user-select: none;
        }

        .test-header:hover { background: rgba(255,255,255,.02); }

        .test-left { display: flex; align-items: center; gap: .75rem; }

        .test-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem;
        }

        .test-name  { font-size: .87rem; font-weight: 600; }
        .test-desc  { font-size: .75rem; color: var(--muted); }

        .test-status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .25rem .75rem;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
        }

        .status-idle    { background: rgba(100,116,139,.15); color: var(--muted); }
        .status-running { background: rgba(56,189,248,.15); color: var(--prim); }
        .status-ok      { background: rgba(52,211,153,.15); color: var(--acc); }
        .status-warn    { background: rgba(251,191,36,.15); color: var(--warn); }
        .status-fail    { background: rgba(248,113,113,.15); color: var(--err); }

        .test-body {
            display: none;
            border-top: 1px solid var(--border);
            padding: 1rem 1.25rem;
            font-size: .8rem;
        }

        .test-body.open { display: block; }

        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .45rem 0;
            border-bottom: 1px solid rgba(255,255,255,.03);
        }
        .result-row:last-child { border-bottom: none; }

        .result-key { color: var(--muted); }
        .result-val { font-weight: 600; font-family: monospace; font-size: .78rem; }
        .val-ok   { color: var(--acc); }
        .val-warn { color: var(--warn); }
        .val-fail { color: var(--err); }

        pre.code-block {
            background: rgba(0,0,0,.4);
            border-radius: 8px;
            padding: .75rem;
            font-size: .72rem;
            overflow-x: auto;
            color: var(--acc);
            white-space: pre-wrap;
            word-break: break-all;
            margin-top: .5rem;
        }

        /* ── RAPPORT ── */
        .rapport-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .rapport-title {
            font-size: .9rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .rapport-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .rapport-item {
            background: rgba(255,255,255,.03);
            border-radius: 8px;
            padding: .75rem;
            text-align: center;
        }

        .rapport-num  { font-size: 1.5rem; font-weight: 800; }
        .rapport-lbl  { font-size: .7rem; color: var(--muted); margin-top: .2rem; }

        .spinner { display: inline-block; width: 12px; height: 12px; border: 2px solid rgba(255,255,255,.2); border-top-color: currentColor; border-radius: 50%; animation: spin .6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <a href="siadoc_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Tableau de bord</a>
        <div>
            <div class="page-title"><i class="fas fa-stethoscope" style="color:var(--prim);margin-right:.4rem"></i> Diagnostic SIADOC</div>
        </div>
    </div>
    <div style="display:flex;gap:.75rem">
        <button class="btn btn-outline" onclick="exportRapport()"><i class="fas fa-download"></i> Exporter rapport</button>
        <button class="btn btn-primary" id="btn-run-all" onclick="runAllTests()"><i class="fas fa-play"></i> Lancer tous les tests</button>
    </div>
</div>

<div class="content">

    <div class="intro">
        Cette page effectue des tests de diagnostic complets sur la connexion et l'interopérabilité entre
        <strong>CIMIS</strong> (<code><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/cimcim</code>)
        et <strong>SIADOC</strong> (<code><?= $siadoc_url ?></code>).
        Cliquez sur <strong>Lancer tous les tests</strong> pour un diagnostic complet.
    </div>

    <div class="tests-grid" id="tests-grid">

        <!-- TEST 1 : Ping SIADOC -->
        <div class="test-card" id="test-ping">
            <div class="test-header" onclick="toggleTest('ping-body')">
                <div class="test-left">
                    <div class="test-icon" style="background:rgba(56,189,248,.15);color:var(--prim)"><i class="fas fa-wifi"></i></div>
                    <div>
                        <div class="test-name">Connectivité SIADOC</div>
                        <div class="test-desc">Test de ping HTTP vers <?= $siadoc_url ?></div>
                    </div>
                </div>
                <span class="test-status status-idle" id="st-ping"><i class="fas fa-circle"></i> En attente</span>
            </div>
            <div class="test-body" id="ping-body">
                <div id="res-ping"><p style="color:var(--muted)">Cliquez sur "Lancer" pour effectuer ce test.</p></div>
            </div>
        </div>

        <!-- TEST 2 : Authentification API -->
        <div class="test-card" id="test-auth">
            <div class="test-header" onclick="toggleTest('auth-body')">
                <div class="test-left">
                    <div class="test-icon" style="background:rgba(52,211,153,.15);color:var(--acc)"><i class="fas fa-key"></i></div>
                    <div>
                        <div class="test-name">Authentification API CIMIS</div>
                        <div class="test-desc">Vérification de la clé API sur les endpoints exposés</div>
                    </div>
                </div>
                <span class="test-status status-idle" id="st-auth"><i class="fas fa-circle"></i> En attente</span>
            </div>
            <div class="test-body" id="auth-body">
                <div id="res-auth"><p style="color:var(--muted)">Test non lancé.</p></div>
            </div>
        </div>

        <!-- TEST 3 : Health Check API CIMIS -->
        <div class="test-card" id="test-health">
            <div class="test-header" onclick="toggleTest('health-body')">
                <div class="test-left">
                    <div class="test-icon" style="background:rgba(52,211,153,.15);color:var(--acc)"><i class="fas fa-heart-pulse"></i></div>
                    <div>
                        <div class="test-name">Health Check API CIMIS</div>
                        <div class="test-desc">Vérification que l'API CIMIS répond correctement</div>
                    </div>
                </div>
                <span class="test-status status-idle" id="st-health"><i class="fas fa-circle"></i> En attente</span>
            </div>
            <div class="test-body" id="health-body">
                <div id="res-health"><p style="color:var(--muted)">Test non lancé.</p></div>
            </div>
        </div>

        <!-- TEST 4 : Endpoint /cartes -->
        <div class="test-card" id="test-cartes">
            <div class="test-header" onclick="toggleTest('cartes-body')">
                <div class="test-left">
                    <div class="test-icon" style="background:rgba(251,191,36,.15);color:var(--warn)"><i class="fas fa-id-card"></i></div>
                    <div>
                        <div class="test-name">Endpoint /cartes (liste)</div>
                        <div class="test-desc">Test de récupération de la liste des cartes CIMIS</div>
                    </div>
                </div>
                <span class="test-status status-idle" id="st-cartes"><i class="fas fa-circle"></i> En attente</span>
            </div>
            <div class="test-body" id="cartes-body">
                <div id="res-cartes"><p style="color:var(--muted)">Test non lancé.</p></div>
            </div>
        </div>

        <!-- TEST 5 : Endpoint /statistiques -->
        <div class="test-card" id="test-stats">
            <div class="test-header" onclick="toggleTest('stats-body')">
                <div class="test-left">
                    <div class="test-icon" style="background:rgba(56,189,248,.15);color:var(--prim)"><i class="fas fa-chart-pie"></i></div>
                    <div>
                        <div class="test-name">Endpoint /statistiques</div>
                        <div class="test-desc">Test des statistiques globales de la base CIMIS</div>
                    </div>
                </div>
                <span class="test-status status-idle" id="st-stats"><i class="fas fa-circle"></i> En attente</span>
            </div>
            <div class="test-body" id="stats-body">
                <div id="res-stats"><p style="color:var(--muted)">Test non lancé.</p></div>
            </div>
        </div>

        <!-- TEST 6 : Endpoint /synchronisation -->
        <div class="test-card" id="test-sync">
            <div class="test-header" onclick="toggleTest('sync-body')">
                <div class="test-left">
                    <div class="test-icon" style="background:rgba(52,211,153,.15);color:var(--acc)"><i class="fas fa-sync-alt"></i></div>
                    <div>
                        <div class="test-name">Endpoint /synchronisation</div>
                        <div class="test-desc">Test de la synchronisation incrémentielle CIMIS → SIADOC</div>
                    </div>
                </div>
                <span class="test-status status-idle" id="st-sync"><i class="fas fa-circle"></i> En attente</span>
            </div>
            <div class="test-body" id="sync-body">
                <div id="res-sync"><p style="color:var(--muted)">Test non lancé.</p></div>
            </div>
        </div>

        <!-- TEST 7 : Import SIADOC -->
        <div class="test-card" id="test-import">
            <div class="test-header" onclick="toggleTest('import-body')">
                <div class="test-left">
                    <div class="test-icon" style="background:rgba(248,113,113,.15);color:var(--err)"><i class="fas fa-file-import"></i></div>
                    <div>
                        <div class="test-name">Connexion import SIADOC</div>
                        <div class="test-desc">Test du service d'import : connexion à <?= $siadoc_url ?></div>
                    </div>
                </div>
                <span class="test-status status-idle" id="st-import"><i class="fas fa-circle"></i> En attente</span>
            </div>
            <div class="test-body" id="import-body">
                <div id="res-import"><p style="color:var(--muted)">Test non lancé.</p></div>
            </div>
        </div>

        <!-- TEST 8 : BDD tables SIADOC -->
        <div class="test-card" id="test-bdd">
            <div class="test-header" onclick="toggleTest('bdd-body')">
                <div class="test-left">
                    <div class="test-icon" style="background:rgba(56,189,248,.15);color:var(--prim)"><i class="fas fa-database"></i></div>
                    <div>
                        <div class="test-name">Tables BDD SIADOC</div>
                        <div class="test-desc">Vérification de l'existence des tables api_sync_log, siadoc_sync_details</div>
                    </div>
                </div>
                <span class="test-status status-idle" id="st-bdd"><i class="fas fa-circle"></i> En attente</span>
            </div>
            <div class="test-body" id="bdd-body">
                <div id="res-bdd"><p style="color:var(--muted)">Test non lancé.</p></div>
            </div>
        </div>

    </div><!-- /tests-grid -->

    <!-- RAPPORT RÉSUMÉ -->
    <div class="rapport-card" id="rapport-summary" style="display:none">
        <div class="rapport-title"><i class="fas fa-clipboard-list" style="color:var(--prim)"></i> Rapport de diagnostic</div>
        <div class="rapport-summary">
            <div class="rapport-item">
                <div class="rapport-num" id="rp-total" style="color:var(--prim)">0</div>
                <div class="rapport-lbl">Tests effectués</div>
            </div>
            <div class="rapport-item">
                <div class="rapport-num" id="rp-ok" style="color:var(--acc)">0</div>
                <div class="rapport-lbl">Réussis</div>
            </div>
            <div class="rapport-item">
                <div class="rapport-num" id="rp-warn" style="color:var(--warn)">0</div>
                <div class="rapport-lbl">Avertissements</div>
            </div>
            <div class="rapport-item">
                <div class="rapport-num" id="rp-fail" style="color:var(--err)">0</div>
                <div class="rapport-lbl">Échecs</div>
            </div>
        </div>
        <div id="rapport-conclusion" style="font-size:.85rem;padding:.75rem;border-radius:8px;margin-top:.5rem"></div>
        <div id="rapport-details" style="margin-top:1rem;font-size:.8rem;color:var(--muted)"></div>
    </div>

</div><!-- /content -->

<script>
const API_KEY  = '<?= htmlspecialchars(SIADOC_API_KEY) ?>';
const SIADOC   = '<?= $siadoc_url ?>';

const testResults = {};

async function runAllTests() {
    const btn = document.getElementById('btn-run-all');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Tests en cours...';

    // Ouvrir tous les panels
    document.querySelectorAll('.test-body').forEach(b => b.classList.add('open'));

    await runTest_Ping();
    await runTest_Auth();
    await runTest_Health();
    await runTest_Cartes();
    await runTest_Stats();
    await runTest_Sync();
    await runTest_Import();
    await runTest_BDD();

    showRapport();

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-redo"></i> Relancer les tests';
}

function setStatus(id, type, label) {
    const icons = { idle: 'fa-circle', running: 'fa-spinner fa-spin', ok: 'fa-check-circle', warn: 'fa-exclamation-triangle', fail: 'fa-times-circle' };
    const el = document.getElementById('st-' + id);
    if (!el) return;
    el.className = `test-status status-${type}`;
    el.innerHTML = `<i class="fas ${icons[type]}"></i> ${label}`;
}

function renderRows(rows, containerId) {
    let html = '';
    rows.forEach(r => {
        html += `<div class="result-row">
            <span class="result-key">${r.key}</span>
            <span class="result-val ${r.cls || ''}">${r.val}</span>
        </div>`;
    });
    if (r => r.raw) {
        // handled below
    }
    document.getElementById(containerId).innerHTML = html;
}

function renderResult(containerId, rows, raw = null) {
    let html = rows.map(r => `
        <div class="result-row">
            <span class="result-key">${r.key}</span>
            <span class="result-val ${r.cls || ''}">${r.val}</span>
        </div>`).join('');
    if (raw) {
        html += `<pre class="code-block">${escapeHtml(JSON.stringify(raw, null, 2)).substring(0, 500)}${JSON.stringify(raw, null, 2).length > 500 ? '\n...(tronqué)' : ''}</pre>`;
    }
    document.getElementById(containerId).innerHTML = html;
}

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

async function timed(fn) {
    const t0 = performance.now();
    const result = await fn();
    return { ...result, ms: Math.round(performance.now() - t0) };
}

// ── TESTS ─────────────────────────────────────────────────────────────────────

async function runTest_Ping() {
    setStatus('ping', 'running', 'Test...');
    try {
        const t0 = performance.now();
        const res = await fetch('../backend/siadoc_import.php?action=test_connexion', {
            headers: { 'X-API-KEY': API_KEY }
        });
        const ms = Math.round(performance.now() - t0);
        const data = await res.json();

        const ok = data.success === true || data.http_code === 200;
        setStatus('ping', ok ? 'ok' : (data.http_code ? 'warn' : 'fail'), ok ? `OK — ${data.duree_ms || ms} ms` : 'Échec');
        testResults.ping = ok ? 'ok' : 'fail';

        renderResult('res-ping', [
            { key: 'URL SIADOC',   val: SIADOC, cls: 'val-ok' },
            { key: 'Code HTTP',    val: data.http_code || '?', cls: data.http_code === 200 ? 'val-ok' : 'val-fail' },
            { key: 'Temps réponse', val: (data.duree_ms || ms) + ' ms', cls: (data.duree_ms || ms) < 1000 ? 'val-ok' : 'val-warn' },
            { key: 'Tentatives',   val: data.attempts || 1 },
            { key: 'Erreur',       val: data.error || 'Aucune', cls: data.error ? 'val-fail' : 'val-ok' },
        ], data);
    } catch(e) {
        setStatus('ping', 'fail', 'Erreur');
        testResults.ping = 'fail';
        document.getElementById('res-ping').innerHTML = `<div style="color:var(--err)">${e.message}</div>`;
    }
}

async function runTest_Auth() {
    setStatus('auth', 'running', 'Test...');

    // Test 1 : Sans clé API → doit retourner 401
    let noAuthOk = false;
    try {
        const r1 = await fetch('../backend/api_siadoc_envoie.php?action=sante');
        noAuthOk = r1.status === 401;
    } catch(e) { }

    // Test 2 : Avec mauvaise clé → doit retourner 401
    let badAuthOk = false;
    try {
        const r2 = await fetch('../backend/api_siadoc_envoie.php?action=sante', { headers: { 'X-API-KEY': 'MAUVAISE_CLE' }});
        badAuthOk = r2.status === 401;
    } catch(e) { }

    // Test 3 : Avec bonne clé → doit retourner 200
    let goodAuthOk = false;
    try {
        const r3 = await fetch('../backend/api_siadoc_envoie.php?action=sante', { headers: { 'X-API-KEY': API_KEY }});
        goodAuthOk = r3.status === 200;
    } catch(e) { }

    const allOk = noAuthOk && badAuthOk && goodAuthOk;
    setStatus('auth', allOk ? 'ok' : (goodAuthOk ? 'warn' : 'fail'), allOk ? 'Authentification OK' : 'Problème détecté');
    testResults.auth = allOk ? 'ok' : (goodAuthOk ? 'warn' : 'fail');

    renderResult('res-auth', [
        { key: 'Sans clé → 401',       val: noAuthOk  ? '✓ Correct' : '✗ Incorrect (doit bloquer)', cls: noAuthOk  ? 'val-ok' : 'val-fail' },
        { key: 'Mauvaise clé → 401',   val: badAuthOk ? '✓ Correct' : '✗ Incorrect (doit bloquer)', cls: badAuthOk ? 'val-ok' : 'val-fail' },
        { key: 'Bonne clé → 200',      val: goodAuthOk? '✓ Correct' : '✗ API inaccessible', cls: goodAuthOk? 'val-ok' : 'val-fail' },
        { key: 'Clé API configurée',   val: API_KEY ? '✓ Oui' : '✗ Non', cls: API_KEY ? 'val-ok' : 'val-fail' },
    ]);
}

async function runTest_Health() {
    setStatus('health', 'running', 'Test...');
    try {
        const t0 = performance.now();
        const res = await fetch('../backend/api_siadoc_envoie.php?action=sante', { headers: { 'X-API-KEY': API_KEY }});
        const ms  = Math.round(performance.now() - t0);
        const data = await res.json();

        const ok = res.status === 200 && data.success;
        setStatus('health', ok ? 'ok' : 'fail', ok ? 'OK' : 'Échec');
        testResults.health = ok ? 'ok' : 'fail';

        renderResult('res-health', [
            { key: 'Code HTTP',   val: res.status, cls: res.status === 200 ? 'val-ok' : 'val-fail' },
            { key: 'Statut',      val: data.data?.status || '?', cls: 'val-ok' },
            { key: 'Base de données', val: data.data?.database || '?', cls: 'val-ok' },
            { key: 'Total actifs', val: data.data?.total_actifs ?? '?', cls: 'val-ok' },
            { key: 'Temps réponse', val: ms + ' ms', cls: ms < 500 ? 'val-ok' : 'val-warn' },
        ], data);
    } catch(e) {
        setStatus('health', 'fail', 'Erreur');
        testResults.health = 'fail';
        document.getElementById('res-health').innerHTML = `<div style="color:var(--err)">${e.message}</div>`;
    }
}

async function runTest_Cartes() {
    setStatus('cartes', 'running', 'Test...');
    try {
        const t0 = performance.now();
        const res = await fetch('../backend/api_siadoc_envoie.php?action=cartes&limit=5', { headers: { 'X-API-KEY': API_KEY }});
        const ms  = Math.round(performance.now() - t0);
        const data = await res.json();

        const ok = res.status === 200 && data.success;
        const count = data.data?.pagination?.total ?? 0;
        setStatus('cartes', ok ? 'ok' : 'fail', ok ? `${count} cartes` : 'Échec');
        testResults.cartes = ok ? 'ok' : 'fail';

        renderResult('res-cartes', [
            { key: 'Code HTTP',       val: res.status, cls: res.status === 200 ? 'val-ok' : 'val-fail' },
            { key: 'Total cartes actives', val: count, cls: count > 0 ? 'val-ok' : 'val-warn' },
            { key: 'Page actuelle',   val: data.data?.pagination?.page ?? '?' },
            { key: 'Temps réponse',   val: ms + ' ms', cls: ms < 1000 ? 'val-ok' : 'val-warn' },
            { key: 'Pagination',      val: data.data?.pagination ? '✓ Présente' : '✗ Absente', cls: data.data?.pagination ? 'val-ok' : 'val-warn' },
        ]);
    } catch(e) {
        setStatus('cartes', 'fail', 'Erreur');
        testResults.cartes = 'fail';
        document.getElementById('res-cartes').innerHTML = `<div style="color:var(--err)">${e.message}</div>`;
    }
}

async function runTest_Stats() {
    setStatus('stats', 'running', 'Test...');
    try {
        const t0 = performance.now();
        const res = await fetch('../backend/api_siadoc_envoie.php?action=statistiques', { headers: { 'X-API-KEY': API_KEY }});
        const ms  = Math.round(performance.now() - t0);
        const data = await res.json();

        const ok = res.status === 200 && data.success;
        const g = data.data?.generales || {};
        setStatus('stats', ok ? 'ok' : 'fail', ok ? 'OK' : 'Échec');
        testResults.stats = ok ? 'ok' : 'fail';

        renderResult('res-stats', [
            { key: 'Code HTTP',        val: res.status, cls: res.status === 200 ? 'val-ok' : 'val-fail' },
            { key: 'Total actifs',     val: g.total_actifs ?? '?' },
            { key: 'Vus de SIADOC',    val: g.venus_de_siadoc ?? '?' },
            { key: 'Vus de CIMIS',     val: g.venus_de_cimis  ?? '?' },
            { key: 'Suspendus',        val: g.suspendus ?? '?', cls: parseInt(g.suspendus) > 0 ? 'val-warn' : 'val-ok' },
            { key: 'Avec QR code',     val: g.avec_qr_code ?? '?' },
            { key: 'Avec biométrie',   val: g.avec_biometrie ?? '?' },
            { key: 'Temps réponse',    val: ms + ' ms' },
        ]);
    } catch(e) {
        setStatus('stats', 'fail', 'Erreur');
        testResults.stats = 'fail';
        document.getElementById('res-stats').innerHTML = `<div style="color:var(--err)">${e.message}</div>`;
    }
}

async function runTest_Sync() {
    setStatus('sync', 'running', 'Test...');
    try {
        const t0 = performance.now();
        const res = await fetch('../backend/api_siadoc_envoie.php?action=synchronisation', { headers: { 'X-API-KEY': API_KEY }});
        const ms  = Math.round(performance.now() - t0);
        const data = await res.json();

        const ok = res.status === 200 && data.success;
        const count = data.data?.total_modifiees ?? 0;
        setStatus('sync', ok ? 'ok' : 'fail', ok ? `${count} modifiés` : 'Échec');
        testResults.sync = ok ? 'ok' : 'fail';

        renderResult('res-sync', [
            { key: 'Code HTTP',            val: res.status, cls: res.status === 200 ? 'val-ok' : 'val-fail' },
            { key: 'Enregistrements modif.', val: count, cls: 'val-ok' },
            { key: 'Dernière sync',        val: data.data?.derniere_sync || 'Jamais' },
            { key: 'Date sync actuelle',   val: data.data?.date_sync_actuelle ? new Date(data.data.date_sync_actuelle).toLocaleString('fr-FR') : '?' },
            { key: 'Temps réponse',        val: ms + ' ms', cls: ms < 2000 ? 'val-ok' : 'val-warn' },
        ]);
    } catch(e) {
        setStatus('sync', 'fail', 'Erreur');
        testResults.sync = 'fail';
        document.getElementById('res-sync').innerHTML = `<div style="color:var(--err)">${e.message}</div>`;
    }
}

async function runTest_Import() {
    setStatus('import', 'running', 'Test...');
    try {
        const t0 = performance.now();
        const res = await fetch('../backend/siadoc_import.php?action=test_connexion', { headers: { 'X-API-KEY': API_KEY }});
        const ms  = Math.round(performance.now() - t0);
        const data = await res.json();

        const ok = data.success === true;
        const latency = data.duree_ms ?? ms;
        setStatus('import', ok ? 'ok' : 'warn', ok ? `SIADOC ${latency} ms` : 'SIADOC hors ligne');
        testResults.import = ok ? 'ok' : 'warn';

        renderResult('res-import', [
            { key: 'URL SIADOC',         val: SIADOC },
            { key: 'SIADOC accessible',  val: ok ? '✓ Oui' : '✗ Non ou timeout', cls: ok ? 'val-ok' : 'val-warn' },
            { key: 'Code HTTP SIADOC',   val: data.http_code ?? '?', cls: data.http_code === 200 ? 'val-ok' : 'val-warn' },
            { key: 'Temps réponse',      val: latency + ' ms', cls: latency < 2000 ? 'val-ok' : 'val-warn' },
            { key: 'Tentatives',         val: data.attempts ?? 1 },
            { key: 'Erreur',             val: data.error || 'Aucune', cls: data.error ? 'val-warn' : 'val-ok' },
        ], data.siadoc_data);
    } catch(e) {
        setStatus('import', 'fail', 'Erreur');
        testResults.import = 'fail';
        document.getElementById('res-import').innerHTML = `<div style="color:var(--err)">${e.message}</div>`;
    }
}

async function runTest_BDD() {
    setStatus('bdd', 'running', 'Test...');
    try {
        const res = await fetch('../backend/siadoc_import.php?action=statistiques', { headers: { 'X-API-KEY': API_KEY }});
        const data = await res.json();

        const tables = ['api_sync_log présente', 'siadoc_sync_details présente'];
        const ok = res.status === 200 && data.success;
        setStatus('bdd', ok ? 'ok' : 'warn', ok ? 'Tables OK' : 'Avertissement');
        testResults.bdd = ok ? 'ok' : 'warn';

        const g = data.generales || {};
        renderResult('res-bdd', [
            { key: 'api_sync_log',          val: ok ? '✓ Accessible' : '? Non testée', cls: ok ? 'val-ok' : 'val-warn' },
            { key: 'siadoc_sync_details',   val: ok ? '✓ Accessible' : '? Non testée', cls: ok ? 'val-ok' : 'val-warn' },
            { key: 'Total imports SIADOC',  val: g.venus_de_siadoc ?? '?' },
            { key: 'Records synchronisés',  val: g.synchronises ?? '?' },
            { key: 'Dernière sync',         val: g.derniere_sync || 'Jamais' },
        ]);
    } catch(e) {
        setStatus('bdd', 'fail', 'Erreur');
        testResults.bdd = 'fail';
        document.getElementById('res-bdd').innerHTML = `<div style="color:var(--err)">${e.message}</div>`;
    }
}

// ── RAPPORT ───────────────────────────────────────────────────────────────────

function showRapport() {
    const vals = Object.values(testResults);
    const ok   = vals.filter(v => v === 'ok').length;
    const warn = vals.filter(v => v === 'warn').length;
    const fail = vals.filter(v => v === 'fail').length;
    const total = vals.length;

    document.getElementById('rp-total').textContent = total;
    document.getElementById('rp-ok').textContent    = ok;
    document.getElementById('rp-warn').textContent  = warn;
    document.getElementById('rp-fail').textContent  = fail;

    const conc = document.getElementById('rapport-conclusion');
    if (fail === 0 && warn === 0) {
        conc.style.cssText = 'background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.25);color:var(--acc)';
        conc.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Système opérationnel</strong> — Tous les tests ont réussi. L\'interopérabilité CIMIS-SIADOC est correctement configurée.';
    } else if (fail === 0) {
        conc.style.cssText = 'background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.25);color:var(--warn)';
        conc.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <strong>Avertissements</strong> — ${warn} test(s) avec avertissement. L\'interopérabilité fonctionne mais nécessite attention.`;
    } else {
        conc.style.cssText = 'background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:var(--err)';
        conc.innerHTML = `<i class="fas fa-times-circle"></i> <strong>${fail} test(s) échoué(s)</strong> — Des problèmes ont été détectés. Vérifiez la configuration SIADOC.`;
    }

    document.getElementById('rapport-details').innerHTML =
        `Rapport généré le ${new Date().toLocaleString('fr-FR')} | CIMIS v2.0 | SIADOC: ${SIADOC}`;

    document.getElementById('rapport-summary').style.display = 'block';
    document.getElementById('rapport-summary').scrollIntoView({ behavior: 'smooth' });
}

function toggleTest(id) {
    document.getElementById(id).classList.toggle('open');
}

function exportRapport() {
    const vals = Object.values(testResults);
    const ok   = vals.filter(v => v === 'ok').length;
    const warn = vals.filter(v => v === 'warn').length;
    const fail = vals.filter(v => v === 'fail').length;

    const rapport = {
        date: new Date().toISOString(),
        systeme: 'CIMIS',
        siadoc_url: SIADOC,
        resume: { total: vals.length, ok, warn, fail },
        tests: testResults
    };

    const blob = new Blob([JSON.stringify(rapport, null, 2)], { type: 'application/json' });
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = `diagnostic_siadoc_${new Date().toISOString().slice(0,10)}.json`;
    a.click();
}
</script>
</body>
</html>
