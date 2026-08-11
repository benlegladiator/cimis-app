<?php
session_start();

// Vérifier si l'utilisateur est SUPER_ADMIN
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || $_SESSION['role'] !== 'SUPER_ADMIN') {
    header('Location: dashboard.php');
    exit;
}

require_once '../backend/config.php';

// Traitement de la restauration
if (isset($_POST['action']) && $_POST['action'] == 'restore') {
    try {
        $ids = $_POST['ids'] ?? [];
        if (!empty($ids) && is_array($ids)) {
            $placeholders = str_repeat('?,', count($ids));
            $placeholders = rtrim($placeholders, ',');
            
            // Restaurer les cartes
            $sql = "UPDATE candidat SET supprimer = 1, supprimer_par = NULL, date_suppression = NULL WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            
            $_SESSION['success'] = count($ids) . " carte(s) restaurée(s) avec succès / " . count($ids) . " card(s) restored successfully";
        } else {
            $_SESSION['error'] = "Aucune carte sélectionnée pour la restauration / No card selected for restoration";
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur lors de la restauration / Error during restoration: " . $e->getMessage();
    }
    
    header('Location: corbeille_admin.php');
    exit;
}

// Traitement du vidage complet de la corbeille
if (isset($_POST['action']) && $_POST['action'] == 'empty_trash') {
    try {
        // Suppression réelle de toutes les cartes dans la corbeille
        $stmt = $pdo->prepare("DELETE FROM candidat WHERE supprimer = 0");
        $count = $stmt->rowCount();
        
        $_SESSION['success'] = $count . " carte(s) supprimée(s) définitivement / " . $count . " card(s) permanently deleted";
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur lors du vidage de la corbeille / Error emptying trash: " . $e->getMessage();
    }
    
    header('Location: corbeille_admin.php');
    exit;
}

// Récupérer toutes les cartes supprimées
try {
    $sql = "SELECT id, matricule, nom, prenom, unite, grade, photo, numero_cni, date_dernier_grade, date_suppression, supprimer_par 
            FROM candidat 
            WHERE supprimer = 0 
            ORDER BY date_suppression DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
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
    <title>Corbeille Admin - CIMIS</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-trash-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .admin-trash-header {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }
        
        .admin-trash-header h1 {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: var(--neon-red);
            text-shadow: 0 0 25px rgba(220, 53, 69, 0.6);
        }
        
        .admin-trash-header .badge {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1.2rem;
            margin-left: 1rem;
        }
        
        .admin-trash-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .admin-stat-card {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            min-width: 250px;
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .admin-stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .admin-trash-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .admin-trash-item {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(220, 53, 69, 0.4);
            border-radius: 15px;
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .admin-trash-item:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(220, 53, 69, 0.7);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(220, 53, 69, 0.3);
        }
        
        .admin-trash-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .admin-trash-item-info {
            flex: 1;
        }
        
        .admin-trash-item-name {
            font-weight: bold;
            color: white;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        
        .admin-trash-item-details {
            font-size: 0.95rem;
            color: #ddd;
            line-height: 1.6;
        }
        
        .admin-trash-item-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .admin-trash-item-date {
            font-size: 0.85rem;
            color: #ff6b6b;
        }
        
        .admin-trash-item-user {
            font-size: 0.85rem;
            color: #ffd700;
            font-weight: 500;
        }
        
        .admin-trash-checkbox {
            margin-right: 1rem;
            transform: scale(1.3);
        }
        
        .admin-restore-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .admin-restore-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }
        
        .admin-batch-actions {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        
        .admin-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .admin-btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .admin-btn-primary:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .admin-btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .admin-btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
        }
        
        .admin-empty-trash {
            text-align: center;
            padding: 4rem;
            color: #ccc;
        }
        
        .admin-empty-trash i {
            font-size: 5rem;
            color: var(--neon-red);
            margin-bottom: 2rem;
        }
        
        .danger-zone {
            background: rgba(220, 53, 69, 0.1);
            border: 2px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            text-align: center;
        }
        
        .danger-zone h3 {
            color: #dc3545;
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
                <span class="status-item"><i class="fa-solid fa-trash-can"></i> CORBEILLE ADMIN / ADMIN TRASH</span>
                <span class="status-item"><i class="fa-solid fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="status-item badge">SUPER_ADMIN</span>
            </div>
            <div class="status-right">
                <span id="clock" class="text-mono">12:00:00</span>
                <a href="./securite_admin.php" class="btn-logout-styled">
                    <i class="fa-solid fa-arrow-left"></i> RETOUR / BACK
                </a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="admin-trash-container">
                <div class="admin-trash-header">
                    <h1><i class="fa-solid fa-trash-can"></i> CORBEILLE ADMIN / ADMIN TRASH</h1>
                    <p>Vue globale de toutes les cartes supprimées / Global view of all deleted cards</p>
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

                <div class="admin-trash-stats">
                    <div class="admin-stat-card">
                        <div class="admin-stat-number"><?php echo count($deletedCards); ?></div>
                        <div>Total des cartes dans la corbeille / Total cards in trash</div>
                    </div>
                </div>

                <?php if (!empty($deletedCards)): ?>
                    <form method="POST" id="restoreForm">
                        <input type="hidden" name="action" value="restore">
                        
                        <div class="admin-batch-actions">
                            <button type="button" class="admin-btn" onclick="selectAll()">
                                <i class="fa-solid fa-check-square"></i> SÉLECTIONNER TOUT / SELECT ALL
                            </button>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fa-solid fa-undo"></i> RESTAURER SÉLECTION / RESTORE SELECTION
                            </button>
                        </div>

                        <div class="admin-trash-grid">
                            <?php foreach ($deletedCards as $card): ?>
                                <div class="admin-trash-item">
                                    <div class="admin-trash-item-header">
                                        <input type="checkbox" name="ids[]" value="<?php echo $card['id']; ?>" class="admin-trash-checkbox">
                                        <div class="admin-trash-item-info">
                                            <div class="admin-trash-item-name">
                                                <?php echo htmlspecialchars($card['nom'] . ' ' . $card['prenom']); ?>
                                            </div>
                                            <div class="admin-trash-item-details">
                                                <i class="fa-solid fa-id-badge"></i> <?php echo htmlspecialchars($card['matricule']); ?>
                                                <br>
                                                <i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($card['grade']); ?>
                                                <br>
                                                <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($card['unite']); ?>
                                            </div>
                                            <div class="admin-trash-item-meta">
                                                <div class="admin-trash-item-date">
                                                    <i class="fa-solid fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($card['date_suppression'])); ?>
                                                </div>
                                                <div class="admin-trash-item-user">
                                                    <i class="fa-solid fa-user"></i> Par: <?php echo htmlspecialchars($card['supprimer_par']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="admin-restore-btn" onclick="restoreSingle(<?php echo $card['id']; ?>)">
                                            <i class="fa-solid fa-undo"></i> RESTAURER
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>

                    <div class="danger-zone">
                        <h3><i class="fa-solid fa-exclamation-triangle"></i> ZONE DE DANGER / DANGER ZONE</h3>
                        <p>Attention : Cette action supprimera définitivement toutes les cartes dans la corbeille.</p>
                        <p>Warning: This action will permanently delete all cards in the trash.</p>
                        <form method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="action" value="empty_trash">
                            <button type="submit" class="admin-btn admin-btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir vider la corbeille ? Cette action est irréversible !\n\nAre you sure you want to empty the trash? This action is irreversible!')">
                                <i class="fa-solid fa-trash"></i> VIDER LA CORBEILLE / EMPTY TRASH
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="admin-empty-trash">
                        <i class="fa-solid fa-trash-can"></i>
                        <h3>Corbeille vide / Empty trash</h3>
                        <p>Aucune carte supprimée dans le système / No deleted cards in the system</p>
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
            const checkboxes = document.querySelectorAll('.admin-trash-checkbox');
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
