<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../backend/config.php';

// Récupérer les informations de l'utilisateur
$username = $_SESSION['username'] ?? 'Invité';
$role = $_SESSION['role'] ?? 'OFFICIER';
$matricule = $_SESSION['matricule'] ?? '';

// Fonction pour vérifier les permissions
function hasPermission($requiredRole, $userRole) {
    $roleHierarchy = [
        'SUPER_ADMIN' => 4,
        'ADMIN_IMPRESSION' => 3,
        'ADMIN_ENROLEMENT' => 2,
        'OFFICIER' => 1
    ];
    
    return ($roleHierarchy[$userRole] ?? 0) >= ($roleHierarchy[$requiredRole] ?? 0);
}

// Définir les permissions pour chaque module
$permissions = [
    'enrolement' => hasPermission('ADMIN_ENROLEMENT', $role),
    'impression' => hasPermission('ADMIN_IMPRESSION', $role),
    'verification' => hasPermission('OFFICIER', $role),
    'security' => hasPermission('SUPER_ADMIN', $role)
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CIMIS</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Canvas Background -->
    <canvas id="particles-canvas"></canvas>

    <div class="app-container">

        <!-- TOP STATUS BAR (100% width) -->
        <div class="top-status-bar">
            <div class="status-left">
                <span class="status-item warning-flash"><i class="fa-solid fa-triangle-exclamation"></i> SYSTÈME CLASSÉ SECRET DÉFENSE</span>
                <span class="status-item"><i class="fa-solid fa-globe"></i> RÉSEAU SÉCURISÉ</span>
                <span class="status-item"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($username); ?> / User</span>
                <?php if (!empty($matricule)): ?>
                <span class="status-item"><i class="fa-solid fa-id-badge"></i> <?php echo htmlspecialchars($matricule); ?> / ID</span>
                <?php endif; ?>
            </div>
            <div class="status-right">
                <span id="clock" class="text-mono">12:00:00</span>
                <a href="logout.php" class="btn-logout-styled">
                    <i class="fa-solid fa-power-off"></i> DÉCONNEXION / LOGOUT
                </a>
            </div>
        </div>

        <!-- HERO BANNER -->
        <div class="hero-section">
            <div class="hero-content">
                <img src="../img/cimis1.png" onerror="this.src='../img/insigne.PNG'" alt="CIMIS Logo" class="hero-logo">
                <div class="hero-text">
                    <h1 class="glitch" data-text="CIMIS">CIMIS</h1>
                    <h2>TABLEAU DE BORD - SYSTÈME INTELLIGENT / DASHBOARD - INTELLIGENT SYSTEM</h2>
                    <div class="hero-divider"></div>
                    <p>MINISTÈRE DE LA DÉFENSE • <?php echo date('d/m/Y H:i'); ?></p>
                </div>
            </div>
        </div>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="main-content">
            <div class="container">
                <div class="hero-grid">
                    <!-- Enrolement -->
                    <a href="enrolement.php" class="module-card <?php echo $permissions['enrolement'] ? '' : 'disabled-card'; ?>" <?php echo $permissions['enrolement'] ? '' : 'onclick="return false;"'; ?>>
                        <div class="card-body">
                            <div class="icon-box">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <h3 style="color: white;">AJOUTER UN PERSONNEL / ADD PERSONNEL</h3>
                            <p style="color: white;">Enregistrer un nouveau personnel dans le système / Register new personnel in the system</p>
                            <?php if (!$permissions['enrolement']): ?>
                            <div class="access-denied-overlay">
                                <i class="fa-solid fa-lock"></i>
                                <span>ACCÈS REFUSÉ / ACCESS DENIED</span>
                                <small>ADMIN_ENROLEMENT requis / ADMIN_ENROLEMENT required</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Impression -->
                    <a href="impression.php" class="module-card <?php echo $permissions['impression'] ? '' : 'disabled-card'; ?>" <?php echo $permissions['impression'] ? '' : 'onclick="return false;"'; ?>>
                        <div class="card-body">
                            <div class="icon-box">
                                <i class="fa-solid fa-id-card-clip"></i>
                            </div>
                            <h3 style="color: white;">GESTION CIM / CIM MANAGEMENT</h3>
                            <p style="color: white;">Visualisation et impression des cartes d'identités / View and print ID cards</p>
                            <?php if (!$permissions['impression']): ?>
                            <div class="access-denied-overlay">
                                <i class="fa-solid fa-lock"></i>
                                <span>ACCÈS REFUSÉ / ACCESS DENIED</span>
                                <small>ADMIN_IMPRESSION requis / ADMIN_IMPRESSION required</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Verification -->
                    <a href="verification.php" class="module-card <?php echo $permissions['verification'] ? '' : 'disabled-card'; ?>" <?php echo $permissions['verification'] ? '' : 'onclick="return false;"'; ?>>
                        <div class="card-body">
                            <div class="icon-box">
                                <i class="fa-solid fa-shield-cat"></i>
                            </div>
                            <h3 style="color: white;">CONTRÔLE D'IDENTITÉ / IDENTITY CONTROL</h3>
                            <p style="color: white;">Perspective d'enregistrement et contrôle d'identité / Registration perspective and identity control</p>
                            <?php if (!$permissions['verification']): ?>
                            <div class="access-denied-overlay">
                                <i class="fa-solid fa-lock"></i>
                                <span>ACCÈS REFUSÉ / ACCESS DENIED</span>
                                <small>OFFICIER requis / OFFICIER required</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>

                                    </div>
            </div>
        </main>

        <!-- SECURITY FOOTER -->
        <footer class="security-footer">
            <div class="footer-left">
                <div class="footer-warning">ACCÈS RESTREINT : TOUTE ACTIVITÉ EST SURVEILLÉE ET ENREGISTRÉE / RESTRICTED ACCESS: ALL ACTIVITY IS MONITORED AND RECORDED</div>
                <div class="footer-info">SYSTÈME PROTÉGÉ PAR CRYPTAGE MILITAIRE AES-256 / SYSTEM PROTECTED BY MILITARY AES-256 ENCRYPTION</div>
            </div>
            <div class="footer-center">
                <?php if ($_SESSION['role'] === 'SUPER_ADMIN'): ?>
                <a href="securite_admin.php" class="security-btn">
                    <i class="fa-solid fa-shield-halved"></i>
                    SECURITY / SÉCURITÉ
                </a>
                <?php endif; ?>
            </div>
            <div class="footer-right">
                <div class="footer-ministry">MINISTÈRE DE LA DÉFENSE - RÉPUBLIQUE DU CAMEROUN / MINISTRY OF DEFENSE - REPUBLIC OF CAMEROON</div>
            </div>
        </footer>

    </div>

    <!-- Error Display -->
    <?php if (isset($_GET['error'])): ?>
    <div class="error-toast" style="position: fixed; top: 20px; right: 20px; background: #ff3333; color: white; padding: 1rem; border-radius: 5px; z-index: 9999; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-exclamation-triangle"></i>
        <span><?php echo htmlspecialchars($_GET['error']); ?></span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer;">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Success Display -->
    <?php if (isset($_GET['success'])): ?>
    <div class="success-toast" style="position: fixed; top: 20px; right: 20px; background: var(--neon-green); color: black; padding: 1rem; border-radius: 5px; z-index: 9999; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-check-circle"></i>
        <span><?php echo htmlspecialchars($_GET['success']); ?></span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: black; cursor: pointer;">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <script>
        // --- CLOCK ---
        setInterval(() => {
            const now = new Date();
            const clockElement = document.getElementById('clock');
            if (clockElement) {
                clockElement.innerText = now.toLocaleTimeString('fr-FR');
            }
        }, 1000);

        // --- PARTICLE SYSTEM ---
        function initParticles() {
            const canvas = document.getElementById('particles-canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            const particles = [];
            
            class Particle {
                constructor() {
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                    this.size = Math.random() * 2;
                    this.speedX = (Math.random() - 0.5) * 0.5;
                    this.speedY = (Math.random() - 0.5) * 0.5;
                    this.opacity = Math.random() * 0.5;
                }
                update() {
                    this.x += this.speedX;
                    this.y += this.speedY;
                    if (this.opacity > 0) this.opacity -= 0.002;
                    if (this.opacity <= 0) {
                        this.x = Math.random() * canvas.width;
                        this.y = Math.random() * canvas.height;
                        this.opacity = Math.random() * 0.5;
                    }
                }
                draw() {
                    ctx.fillStyle = `rgba(10, 255, 186, ${this.opacity})`;
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
            
            for (let i = 0; i < 100; i++) particles.push(new Particle());
            
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles.forEach(p => {
                    p.update();
                    p.draw();
                });
                requestAnimationFrame(animate);
            }
            animate();
        }

        // Initialize particles
        initParticles();
        
        // Handle window resize
        window.addEventListener('resize', () => {
            const canvas = document.getElementById('particles-canvas');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });

        // Auto-hide notifications
        setTimeout(() => {
            document.querySelectorAll('.error-toast, .success-toast').forEach(toast => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
