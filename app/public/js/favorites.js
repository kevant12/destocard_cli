/**
 * ========================================
 * GESTION DES FAVORIS - DESTOCARD
 * ======================================== 
 */

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    initFavoriteButtons();
});

/**
 * Initialise les boutons de favoris
 */
function initFavoriteButtons() {
    const favoriteButtons = document.querySelectorAll('.btn-like');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', handleFavoriteToggle);
    });
}

/**
 * Gère le toggle des favoris
 */
async function handleFavoriteToggle(e) {
    e.preventDefault();
    
    const button = e.target.closest('.btn-like');
    const productId = button.dataset.productId;
    const csrfToken = button.dataset.csrfToken;
    
    if (!productId || !csrfToken) {
        console.error('Données manquantes pour le toggle des favoris');
        return;
    }
    
    // État de chargement
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '⏳';
    
    try {
        const response = await fetch(`/favorite/toggle/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `_token=${encodeURIComponent(csrfToken)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Récupérer le nom du produit
            const productName = getProductNameFromButton(button);
            
            // Mettre à jour l'état du bouton
            updateFavoriteButton(button, data.isLiked);
            
            // Afficher notification appropriée
            if (data.isLiked) {
                if (window.showFavoriteAdded) {
                    window.showFavoriteAdded(productName);
                }
            } else {
                if (window.showFavoriteRemoved) {
                    window.showFavoriteRemoved(productName);
                }
            }
            
            // Animation du bouton
            button.classList.add('favorite-animation');
            setTimeout(() => {
                button.classList.remove('favorite-animation');
            }, 600);
            
        } else {
            // Erreur
            if (window.showError) {
                window.showError('Erreur lors de la mise à jour des favoris');
            }
            button.innerHTML = originalContent;
        }
        
    } catch (error) {
        console.error('Erreur lors du toggle des favoris:', error);
        
        if (window.showError) {
            window.showError('Une erreur est survenue');
        }
        
        button.innerHTML = originalContent;
    } finally {
        button.disabled = false;
    }
}

/**
 * Met à jour l'état visuel du bouton favori
 */
function updateFavoriteButton(button, isLiked) {
    if (isLiked) {
        button.classList.add('active', 'is-liked');
        button.innerHTML = '❤️';
        button.title = 'Retirer des favoris';
        button.style.color = 'var(--error-color)';
    } else {
        button.classList.remove('active', 'is-liked');
        button.innerHTML = '🤍';
        button.title = 'Ajouter aux favoris';
        button.style.color = '';
    }
}

/**
 * Récupère le nom du produit depuis le bouton
 */
function getProductNameFromButton(button) {
    // Essayer de trouver le nom du produit dans la carte parente
    const productCard = button.closest('.card, .product-card, .product-detail-container');
    
    if (productCard) {
        // Chercher le titre dans différents sélecteurs possibles
        const selectors = [
            '.card__title a',
            '.product-detail-title',
            'h1',
            'h2',
            'h3',
            '.product-title'
        ];
        
        for (const selector of selectors) {
            const titleElement = productCard.querySelector(selector);
            if (titleElement) {
                return titleElement.textContent.trim();
            }
        }
    }
    
    // Fallback
    return 'Article';
}

// CSS pour l'animation des favoris
const favoriteStyles = `
    .favorite-animation {
        animation: favoriteHeartBeat 0.6s ease-out;
    }
    
    @keyframes favoriteHeartBeat {
        0% { transform: scale(1); }
        25% { transform: scale(1.3) rotate(-5deg); }
        50% { transform: scale(1.1) rotate(5deg); }
        75% { transform: scale(1.2) rotate(-2deg); }
        100% { transform: scale(1); }
    }
    
    .btn-like {
        transition: all 0.3s ease;
    }
    
    .btn-like:hover {
        transform: scale(1.1);
    }
    
    .btn-like.active {
        animation: favoriteGlow 2s infinite;
    }
    
    @keyframes favoriteGlow {
        0%, 100% { filter: drop-shadow(0 0 5px rgba(220, 53, 69, 0.5)); }
        50% { filter: drop-shadow(0 0 10px rgba(220, 53, 69, 0.8)); }
    }
`;

// Ajouter les styles à la page
const styleSheet = document.createElement('style');
styleSheet.textContent = favoriteStyles;
document.head.appendChild(styleSheet);

// Exporter les fonctions pour utilisation globale
window.initFavoriteButtons = initFavoriteButtons;
window.updateFavoriteButton = updateFavoriteButton; 