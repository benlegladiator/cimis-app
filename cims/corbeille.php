<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'backend/config.php';

// Récupérer les informations de l'utilisateur
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Traitement de la restauration
if (isset($_POST['action']) && $_POST['action'] == 'restore') {
    try {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $placeholders = str_repeat('?,', count($ids));
            $placeholders = rtrim($placeholders, ',');
            
            // Restaurer les cartes
            $sql = "UPDATE candidat SET supprimer = 1, supprimer_par = NULL, date_suppression = NULL WHERE id IN ($placeholders) AND supprimer_par = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($ids, ['username' => $username]));
            
            $_SESSION['success'] = count($ids) . " carte(s) restaurée(s) avec succès / " . count($ids) . " card(s) restored successfully";
        } else {
            $_SESSION['error'] = "Aucune carte sélectionnée pour la restauration / No card selected for restoration";
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur lors de la restauration / Error during restoration: " . $e->getMessage();
    }
    
    header('Location: corbeille.php');
    exit;
}

// Récupérer les cartes supprimées par l'utilisateur
try {
    $sql = "SELECT id, matricule, nom, prenom, unite, grade, photo, numero_cni, date_dernier_grade, date_suppression 
            FROM candidat 
            WHERE supprimer = 0 AND supprimer_par = :username 
            ORDER BY date_suppression DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $deletedCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $deletedCards = [];
    $_SESSION['error'] = "Erreur lors du chargement de la corbeille / Error loading trash: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbeille - CIMIS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .trash-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .trash-header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
            position: relative;
        }
        
        .trash-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--neon-red);
            text-shadow: 0 0 30px rgba(220, 53, 69, 0.6);
            animation: titleGlow 2s ease-in-out infinite alternate;
        }
        
        .trash-header h1 i {
            margin-right: 1rem;
            animation: iconPulse 2s ease-in-out infinite;
        }
        
        @keyframes titleGlow {
            0% { text-shadow: 0 0 30px rgba(220, 53, 69, 0.6); }
            100% { text-shadow: 0 0 40px rgba(220, 53, 69, 0.8); }
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .trash-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            min-width: 250px;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 35px rgba(220, 53, 69, 0.5);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .trash-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .trash-item {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(220, 53, 69, 0.05));
            border: 2px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
            padding: 2rem;
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .trash-item:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(220, 53, 69, 0.08));
            border-color: rgba(220, 53, 69, 0.7);
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 30px rgba(220, 53, 69, 0.2);
        }
        
        .trash-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(220, 53, 69, 0.1), transparent);
            border-radius: 15px;
            opacity: 0;
            transition: opacity 0.4s ease;
            pointer-events: none;
        }
        
        .trash-item:hover::before {
            opacity: 1;
        }
        
        .trash-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .trash-item-info {
            flex: 1;
        }
        
        .trash-item-name {
            font-weight: bold;
            color: white;
            margin-bottom: 0.25rem;
        }
        
        .trash-item-details {
            font-size: 0.9rem;
            color: #ccc;
        }
        
        .trash-item-date {
            font-size: 0.8rem;
            color: var(--neon-red);
            margin-top: 0.5rem;
        }
        
        .trash-checkbox {
            margin-right: 1rem;
            transform: scale(1.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .trash-checkbox:hover {
            transform: scale(1.4);
        }
        
        .trash-checkbox:checked {
            accent-color: #28a745;
        }
        
        .restore-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .restore-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .restore-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .restore-btn:hover::before {
            left: 100%;
        }
        
        .restore-btn:active {
            transform: translateY(-1px) scale(0.98);
        }
        
        .batch-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:active {
            transform: translateY(-1px) scale(0.98);
        }
        
        .empty-trash {
            text-align: center;
            padding: 3rem;
            color: #ccc;
        }
        
        .empty-trash i {
            font-size: 4rem;
            color: var(--neon-red);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Canvas Background -->
    <canvas id="particles-canvas"></canvas>

    <div class="app-container">
        <!-- TOP STATUS BAR -->
        <div class="top-status-bar">
            <div class="status-left">
                <span class="status-item"><i class="fa-solid fa-trash-can"></i> CORBEILLE / TRASH</span>
                <span class="status-item"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($username); ?></span>
            </div>
            <div class="status-right">
                <span id="clock" class="text-mono">12:00:00</span>
                <a href="dashboard.php" class="btn-logout-styled">
                    <i class="fa-solid fa-arrow-left"></i> RETOUR / BACK
                </a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="trash-container">
                <div class="trash-header">
                    <h1><i class="fa-solid fa-trash-can"></i> CORBEILLE / TRASH</h1>
                    <p>Cartes supprimées par l'utilisateur / Deleted cards by user</p>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-toast" style="position: fixed; top: 20px; right: 20px; background: var(--neon-green); color: black; padding: 1rem; border-radius: 5px; z-index: 9999;">
                        <i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-toast" style="position: fixed; top: 20px; right: 20px; background: var(--neon-red); color: white; padding: 1rem; border-radius: 5px; z-index: 9999;">
                        <i class="fa-solid fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="trash-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($deletedCards); ?></div>
                        <div>Cartes dans la corbeille / Cards in trash</div>
                    </div>
                </div>

                <?php if (!empty($deletedCards)): ?>
                    <form method="POST" id="restoreForm">
                        <input type="hidden" name="action" value="restore">
                        
                        <div class="batch-actions">
                            <button type="button" class="btn" onclick="selectAll()">
                                <i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-undo"></i> RESTAURER SÉLECTION / RESTORE SELECTION
                            </button>
                        </div>

                        <div class="trash-grid">
                            <?php foreach ($deletedCards as $card): ?>
                                <div class="trash-item">
                                    <div class="trash-item-header">
                                        <input type="checkbox" name="ids[]" value="<?php echo $card['id']; ?>" class="trash-checkbox">
                                        <div class="trash-item-info">
                                            <div class="trash-item-name">
                                                <?php echo htmlspecialchars($card['nom'] . ' ' . $card['prenom']); ?>
                                            </div>
                                            <div class="trash-item-details">
                                                <i class="fa-solid fa-id-badge"></i> <?php echo htmlspecialchars($card['matricule']); ?>
                                                <br>
                                                <i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($card['grade']); ?>
                                                <br>
                                                <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($card['unite']); ?>
                                            </div>
                                            <div class="trash-item-date">
                                                <i class="fa-solid fa-clock"></i> Supprimé le / Deleted on: <?php echo date('d/m/Y H:i', strtotime($card['date_suppression'])); ?>
                                            </div>
                                        </div>
                                        <button type="button" class="restore-btn" onclick="restoreSingle(<?php echo $card['id']; ?>)">
                                            <i class="fa-solid fa-undo"></i> RESTAURER
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-trash">
                        <i class="fa-solid fa-trash-can"></i>
                        <h3>Corbeille vide / Empty trash</h3>
                        <p>Aucune carte supprimée / No deleted cards</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script>
        // Clock
        setInterval(() => {
            const now = new Date();
            const clockElement = document.getElementById('clock');
            if (clockElement) {
                clockElement.innerText = now.toLocaleTimeString('fr-FR');
            }
        }, 1000);

        // Particle system
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

        initParticles();

        // Select all checkboxes
        function selectAll() {
            const checkboxes = document.querySelectorAll('.trash-checkbox');
            const selectAllBtn = event.target;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !checkbox.checked;
            });
            
            selectAllBtn.innerHTML = checkboxes[0].checked ? 
                '<i class="fa-solid fa-square"></i> DÉSÉLECTIONNER TOUT / DESELECT ALL' : 
                '<i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL';
        }

        // Restore single card
        function restoreSingle(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="ids[]" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Auto-hide notifications
        setTimeout(() => {
            document.querySelectorAll('.success-toast, .error-toast').forEach(toast => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
