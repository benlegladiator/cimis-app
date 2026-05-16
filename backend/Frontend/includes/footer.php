<?php
// Footer pour le système CIMIS
// Configuration du footer pour toutes les pages du système
?>
<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4>Système CIMIS</h4>
            <p>Carte d'Identité Militaire Intégrée et Sécurisée</p>
            <p>Military Identity Card Integrated and Secure</p>
        </div>
        
        <div class="footer-section">
            <h4>Navigation / Navigation</h4>
            <ul>
                <li><a href="impression.php">Impression / Printing</a></li>
                <li><a href="corbeille.php">Corbeille / Trash</a></li>
                <li><a href="api_siadoc.php">Administration</a></li>
                <li><a href="logout.php">Déconnexion / Logout</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Informations / Information</h4>
            <p>Dernière mise à jour: <?php echo date('d/m/Y'); ?></p>
            <p>© 2026 - Système CIMIS</p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; 2026 CIMIS - Tous droits réservés / All rights reserved</p>
        <p>Système sécurisé de gestion des cartes d'identité militaires</p>
    </div>
</footer>

<style>
.main-footer {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    color: var(--text-muted);
    padding: 2rem 0 1rem;
    margin-top: auto;
    border-top: 2px solid var(--neon-green);
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    padding: 0 2rem;
}

.footer-section h4 {
    color: var(--neon-green);
    margin-bottom: 1rem;
    font-size: 1.1rem;
    font-weight: bold;
    text-transform: uppercase;
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: 0.5rem;
}

.footer-section ul li a {
    color: var(--text-muted);
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-section ul li a:hover {
    color: var(--neon-green);
}

.footer-bottom {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(74, 222, 128, 0.2);
    color: var(--text-muted);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .footer-section {
        margin-bottom: 1.5rem;
    }
}
</style>
