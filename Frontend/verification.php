<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/qrcode_generator.php';

$matricule_search = trim($_GET['matricule_input'] ?? $_POST['matricule_input'] ?? '');
$generated_qr_rel = '';
$candidat_found   = null;
$qr_payload_text  = '';

if (!empty($matricule_search)) {
    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM candidat WHERE (matricule_militaire = :m OR matricule = :m) AND supprimer = 1 LIMIT 1");
            $stmt->execute(['m' => $matricule_search]);
            $candidat_found = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }

    $generated_qr_rel = generateQRCodeForMatricule($matricule_search, $candidat_found);

    $nom      = $candidat_found['nom'] ?? 'CANDIDAT';
    $prenom   = $candidat_found['prenom'] ?? '';
    $grade    = $candidat_found['grade'] ?? 'MILITAIRE';
    $unite    = $candidat_found['unite'] ?? 'MINDEF';
    $cni      = $candidat_found['numero_cni'] ?? 'N/A';
    $hash_key = strtoupper(substr(hash('sha256', $matricule_search . $nom . 'MINDEF_CIMIS_2026'), 0, 10));

    $qr_payload_text  = "[MINISTÈRE DE LA DÉFENSE - CAMEROUN]\n";
    $qr_payload_text .= "CARTE D'IDENTITÉ MILITAIRE (CIMIS)\n";
    $qr_payload_text .= "MATRICULE : " . $matricule_search . "\n";
    $qr_payload_text .= "NOM & PRÉNOM : " . trim($nom . ' ' . $prenom) . "\n";
    $qr_payload_text .= "GRADE : " . $grade . "\n";
    $qr_payload_text .= "CORPS : " . $unite . "\n";
    $qr_payload_text .= "CNI : " . $cni . "\n";
    $qr_payload_text .= "STATUT : CERTIFIÉ CONFORME\n";
    $qr_payload_text .= "SIG-HASH : MINDEF-CIM-" . $hash_key;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Biométrique - CIMIS 2.0</title>
    <link rel="stylesheet" href="../css/verification.css">
    <link rel="stylesheet" href="../css/bouton-retour.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle shifted" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../img/cimis.png" alt="CIMIS Logo" class="sidebar-logo">
            <h3>CIMIS 2.0</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-item">
                <i class="fa-solid fa-home"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="../enrolement.php" class="nav-item">
                <i class="fa-solid fa-user-plus"></i>
                <span>Enrôlement</span>
            </a>
            <a href="../verification.php" class="nav-item active">
                <i class="fa-solid fa-qrcode"></i>
                <span>Vérification</span>
            </a>
            <a href="../visualiser_carte.php" class="nav-item">
                <i class="fa-solid fa-id-card"></i>
                <span>Cartes</span>
            </a>
            <a href="logout.php" class="nav-item">
                <i class="fa-solid fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </nav>
    </div>

    <nav class="navbar shifted">
        <div class="nav-brand">
            <img src="../img/cimis1.png" alt="Logo">
            <span>CIMIS <small>// POINT DE CONTRÔLE</small></span>
        </div>
        <!-- BOUTON RETOUR VERS DASHBOARD -->
        <div class="back-button-container">
            <a href="../dashboard.php" class="btn-back btn-back-dashboard">
                <i class="fa-solid fa-arrow-left"></i>
                <span>RETOUR AU DASHBOARD</span>
            </a>
        </div>
    </nav>

    <div class="container shifted">
        <div class="page-header">
            <h2>Générateur & Vérificateur Biométrique</h2>
            <div class="status-indicator">
                <i class="fa-solid fa-wifi"></i> CONNECTÉ
            </div>
        </div>

        <!-- BLOC GÉNÉRATION INTERACTIVE CODE QR PAR MATRICULE -->
        <div style="background: rgba(15, 23, 42, 0.85); border: 2px solid rgba(13, 148, 136, 0.4); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <h3 style="color: #2dd4bf; margin-top: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                <i class="fa-solid fa-qrcode" style="color: #38bdf8;"></i> GÉNÉRATEUR & CONTRÔLEUR DE CODE QR MILITAIRE
            </h3>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 1.25rem;">Saisissez le matricule officiel du militaire pour générer et prévisualiser son Code QR autonome scannable.</p>

            <form method="GET" action="verification.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 250px;">
                    <input type="text" name="matricule_input" placeholder="Entrez le matricule (ex: T14/5748, T22/1529, CIM-96354)..." value="<?php echo htmlspecialchars($matricule_search); ?>" required style="width: 100%; padding: 0.85rem 1rem; border-radius: 10px; background: #0f172a; border: 1.5px solid #334155; color: #fff; font-size: 1rem; font-family: monospace;">
                </div>
                <button type="submit" style="padding: 0.85rem 1.5rem; border-radius: 10px; background: linear-gradient(135deg, #0d9488, #0f766e); color: white; border: none; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                    <i class="fa-solid fa-bolt"></i> GÉNÉRER LE CODE QR
                </button>
            </form>

            <?php if (!empty($matricule_search)): ?>
                <div style="margin-top: 1.75rem; background: #0f172a; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; align-items: center;">
                    <!-- COLONNE CODE QR -->
                    <div style="text-align: center;">
                        <div style="background: white; padding: 12px; border-radius: 12px; display: inline-block; box-shadow: 0 8px 25px rgba(0,0,0,0.5);">
                            <img src="../<?php echo ltrim($generated_qr_rel, '/'); ?>" alt="Code QR Généré" style="width: 200px; height: 200px; object-fit: contain; display: block;" onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=2&data=<?php echo urlencode($qr_payload_text); ?>';">
                        </div>
                        <div style="margin-top: 0.75rem;">
                            <a href="../<?php echo ltrim($generated_qr_rel, '/'); ?>" download="<?php echo htmlspecialchars($matricule_search); ?>_qr.png" target="_blank" style="color: #38bdf8; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;">
                                <i class="fa-solid fa-download"></i> Télécharger le QR Code PNG
                            </a>
                        </div>
                    </div>

                    <!-- COLONNE DONNÉES DU MILITAIRE -->
                    <div>
                        <?php if ($candidat_found): ?>
                            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; display: inline-block; margin-bottom: 0.75rem;">
                                <i class="fa-solid fa-check-circle"></i> MILITAIRE ENREGISTRÉ DANS CIMIS
                            </div>
                            <h3 style="color: #f8fafc; margin: 0 0 0.25rem 0;"><?php echo htmlspecialchars(($candidat_found['nom'] ?? '') . ' ' . ($candidat_found['prenom'] ?? '')); ?></h3>
                            <div style="color: #a78bfa; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars(($candidat_found['grade'] ?? '') . ' • ' . ($candidat_found['unite'] ?? '')); ?></div>
                            <div style="font-size: 0.85rem; color: #cbd5e1;">
                                <p style="margin: 3px 0;"><strong>Matricule Officiel:</strong> <code style="background: #1e293b; padding: 2px 6px; border-radius: 4px; color: #38bdf8;"><?php echo htmlspecialchars($candidat_found['matricule_militaire'] ?? $candidat_found['matricule']); ?></code></p>
                                <p style="margin: 3px 0;"><strong>CNI:</strong> <?php echo htmlspecialchars($candidat_found['numero_cni'] ?? 'Non renseigné'); ?></p>
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; color: #fde047; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; display: inline-block; margin-bottom: 0.75rem;">
                                <i class="fa-solid fa-triangle-exclamation"></i> MATRICULE NON ENREGISTRÉ EN BDD (GÉNÉRATION AUTONOME)
                            </div>
                            <h3 style="color: #f8fafc; margin: 0 0 0.25rem 0;">Matricule: <?php echo htmlspecialchars($matricule_search); ?></h3>
                            <p style="color: #94a3b8; font-size: 0.85rem;">Ce QR Code a été généré avec les paramètres de sécurité par défaut MINDEF.</p>
                        <?php endif; ?>

                        <!-- TEXTE DU PAYLOAD BRUT -->
                        <div style="margin-top: 1rem;">
                            <label style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; display: block; margin-bottom: 0.3rem;">CONTENU TEXTE BRUT DU CODE QR :</label>
                            <pre style="background: #020617; border: 1px solid #1e293b; color: #4ade80; padding: 0.6rem; border-radius: 6px; font-size: 0.75rem; margin: 0; font-family: monospace; white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($qr_payload_text); ?></pre>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="scanner-zone" id="scanner">
            <div class="laser"></div>
            <i class="fa-solid fa-fingerprint"></i>
            <div class="scan-status">EN ATTENTE DE CARTE...</div>
            <p>Placez le QR Code ou la puce RFID sur le lecteur</p>

            <button class="btn" onclick="simulateScan()">
                <i class="fa-solid fa-play"></i> SIMULER UN SCAN
            </button>
        </div>

        <!-- Résultat Simulation -->
        <div class="result-panel" id="result">
            <div>
                <img src="https://ui-avatars.com/api/?name=John+Doe&background=0D8ABC&color=fff&size=128">
                <div>
                    <h3>ACCÈS AUTORISÉ</h3>
                    <p><strong>Identité:</strong> SERGENT DOE JOHN</p>
                    <p><strong>Matricule:</strong> ML-8842-XJ</p>
                    <p><strong>Statut:</strong> <span style="background: green; color: white; padding: 2px 5px;">EN SERVICE</span></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const navbar = document.querySelector('.navbar');
            const container = document.querySelector('.container');
            const toggle = document.querySelector('.sidebar-toggle');
            
            sidebar.classList.toggle('active');
            navbar.classList.toggle('shifted');
            container.classList.toggle('shifted');
            toggle.classList.toggle('shifted');
        }

        function simulateScan() {
            const scanner = document.getElementById('scanner');
            const result = document.getElementById('result');
            const status = document.querySelector('.scan-status');

            status.innerText = "ANALYSE EN COURS...";
            status.style.color = "var(--neon-blue)";

            setTimeout(() => {
                status.innerText = "SCAN TERMINÉ";
                status.style.color = "var(--neon-green)";
                result.style.display = "block";
                result.classList.add('fadeInDown');
            }, 1500);
        }

        // --- PARTICLE SYSTEM ---
        const canvas = document.createElement('canvas');
        canvas.id = 'particles-canvas';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        const particles = [];

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 0.5;
                this.speedX = (Math.random() - 0.5) * 0.8;
                this.speedY = (Math.random() - 0.5) * 0.8;
                this.opacity = Math.random() * 0.6 + 0.2;
                this.color = Math.random() > 0.5 ? '10, 255, 186' : '0, 212, 255';
            }
            
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                
                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
                
                if (this.opacity > 0.1) this.opacity -= 0.002;
                if (this.opacity <= 0.1) {
                    this.opacity = Math.random() * 0.6 + 0.2;
                }
            }
            
            draw() {
                ctx.fillStyle = `rgba(${this.color}, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function initParticles() {
            particles.length = 0;
            for (let i = 0; i < 80; i++) {
                particles.push(new Particle());
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });
            
            requestAnimationFrame(animate);
        }

        // Initialisation
        resizeCanvas();
        initParticles();
        animate();

        window.addEventListener('resize', () => {
            resizeCanvas();
            initParticles();
        });

        // Auto-ouvrir le sidebar au démarrage
        setTimeout(() => {
            toggleSidebar();
        }, 500);
    </script>
</body>
</html>

