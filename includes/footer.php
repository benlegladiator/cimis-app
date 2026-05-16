<?php
// Footer global CIMIS avec bouton corbeille
session_start();
require_once 'backend/config.php';

// Fonction pour compter les cartes dans la corbeille de l'utilisateur
function getTrashCount($username) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidat WHERE supprimer = 0 AND supprimer_par = :username");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

$trashCount = getTrashCount($_SESSION['username'] ?? '');
?>
        <!-- FOOTER GLOBAL -->
        <footer class="security-footer">
            <div class="footer-left">
                <span><i class="fa-solid fa-shield-alt"></i> SYSTÈME CIMIS NUMÉRISATION / CIMIS DIGITIZATION SYSTEM</span>
                <span><i class="fa-solid fa-lock"></i> Connexion sécurisée / Secure connection</span>
            </div>
            
            <div class="footer-center">
                <!-- Bouton Corbeille -->
                <a href="corbeille.php" class="trash-btn" title="Corbeille / Trash">
                    <i class="fa-solid fa-trash-can"></i>
                    <?php if ($trashCount > 0): ?>
                        <span class="trash-count"><?php echo $trashCount; ?></span>
                    <?php endif; ?>
                </a>
                
                <?php if ($_SESSION['role'] === 'SUPER_ADMIN'): ?>
                <a href="securite_admin.php" class="security-btn">
                    <i class="fa-solid fa-shield-halved"></i>
                    SECURITY / SÉCURITÉ
                </a>
                <?php endif; ?>
            </div>
            
            <div class="footer-right">
                <div class="footer-version">SYSTÈME SÉCURISÉ / SECURED SYSTEM</div>
                <div class="footer-ministry">MINISTÈRE DE LA DÉFENSE - RÉPUBLIQUE DU CAMEROUN / MINISTRY OF DEFENSE - REPUBLIC OF CAMEROON</div>
            </div>
        </footer>

        <style>
        .trash-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: 2px solid #dc3545;
            border-radius: 50%;
            text-decoration: none;
            font-size: 18px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            margin: 0 10px;
        }

        .trash-btn:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
        }

        .trash-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff6b6b;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            border: 2px solid white;
            min-width: 18px;
            text-align: center;
            line-height: 1;
        }

        .security-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .security-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
            text-decoration: none;
        }

        .footer-center {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        </style>
