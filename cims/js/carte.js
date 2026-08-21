// JavaScript pour les effets de sécurité des cartes

document.addEventListener('DOMContentLoaded', function() {
    
    // Effet hologramme 3D interactif
    function initHologramEffect() {
        const cards = document.querySelectorAll('.id-card');
        
        cards.forEach(card => {
            const hologram = card.querySelector('.card-hologram');
            if (!hologram) return;
            
            // Effet de suivi de souris pour l'hologramme
            card.addEventListener('mousemove', function(e) {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = ((y - centerY) / centerY) * 15;
                const rotateY = ((x - centerX) / centerX) * 15;
                
                hologram.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
            
            // Reset au départ de la souris
            card.addEventListener('mouseleave', function() {
                hologram.style.transform = 'rotateX(0deg) rotateY(0deg)';
            });
        });
    }
    
    // Initialiser les effets
    initHologramEffect();
    
    // Fonction pour tester la sécurité (utilitaire pour securite.php)
    window.testSecurity = function(securityType) {
        console.log('Test de sécurité:', securityType);
        
        switch(securityType) {
            case 'hologram':
                alert('Test Hologramme 3D: Inclinez la carte pour voir l\'effet 3D animé');
                break;
            case 'watermark':
                alert('Test Watermark: Inclinez la carte sous lumière bleue/violette');
                break;
            case 'microtext':
                alert('Test Micro-texte: Utilisez une loupe pour voir la signature cachée');
                break;
            case 'qrcode':
                alert('Test QR Code: Scannez le code pour vérifier l\'authenticité');
                break;
        }
    };
    
});
