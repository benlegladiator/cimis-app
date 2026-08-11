// JavaScript pour le mode Preview Uniforme
// Gestion du sélecteur de fonds

document.addEventListener('DOMContentLoaded', function() {
    // Récupérer toutes les options de fonds
    const fondOptions = document.querySelectorAll('.fond-option');
    
    // Ajouter l'événement de clic sur chaque option
    fondOptions.forEach(option => {
        option.addEventListener('click', function() {
            const fondFile = this.getAttribute('data-fond');
            if (fondFile) {
                changeFond(fondFile);
            }
        });
    });
});

/**
 * Change le fond uniforme pour toutes les cartes
 * @param {string} fondFile - Nom du fichier de fond (ex: 101.png)
 */
function changeFond(fondFile) {
    // Récupérer l'URL actuelle
    const currentUrl = new URL(window.location.href);
    
    // Mettre à jour le paramètre 'fond'
    currentUrl.searchParams.set('fond', fondFile);
    
    // Recharger la page avec le nouveau fond
    window.location.href = currentUrl.toString();
}

/**
 * Met à jour visuellement le sélecteur sans recharger (optionnel)
 * Pour une version future avec AJAX
 */
function updateFondPreview(fondFile) {
    // Retirer la classe active de toutes les options
    document.querySelectorAll('.fond-option').forEach(opt => {
        opt.classList.remove('active');
        opt.style.borderColor = 'transparent';
        
        const label = opt.querySelector('.fond-label');
        if (label) {
            label.style.color = '#fff';
            label.style.fontWeight = 'normal';
        }
    });
    
    // Ajouter la classe active à l'option sélectionnée
    const selectedOption = document.querySelector(`.fond-option[data-fond="${fondFile}"]`);
    if (selectedOption) {
        selectedOption.classList.add('active');
        selectedOption.style.borderColor = '#4ade80';
        
        const label = selectedOption.querySelector('.fond-label');
        if (label) {
            label.style.color = '#4ade80';
            label.style.fontWeight = 'bold';
        }
    }
}

/**
 * Affiche une notification à l'utilisateur
 * @param {string} message - Message à afficher
 * @param {string} type - Type de notification ('success', 'error', 'info')
 */
function showNotification(message, type = 'info') {
    // Vérifier si la fonction existe déjà (définie dans un autre script)
    if (typeof window.showNotification === 'function' && window.showNotification !== showNotification) {
        window.showNotification(message, type);
        return;
    }
    
    // Créer une notification simple
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        max-width: 400px;
        word-wrap: break-word;
        animation: slideIn 0.3s ease;
    `;
    
    // Couleur selon le type
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#4ade80';
            notification.style.color = '#000';
            break;
        case 'error':
            notification.style.backgroundColor = '#ef4444';
            break;
        case 'warning':
            notification.style.backgroundColor = '#f59e0b';
            notification.style.color = '#000';
            break;
        default:
            notification.style.backgroundColor = '#3b82f6';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Disparaître après 3 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Ajouter les animations CSS dynamiquement
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

console.log('Preview Uniforme JS chargé avec succès');
