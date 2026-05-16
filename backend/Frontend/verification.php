<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
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
            <a href="../logout.php" class="nav-item">
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
            <h2>Scanner Biométrique</h2>
            <div class="status-indicator">
                <i class="fa-solid fa-wifi"></i> CONNECTÉ
            </div>
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

