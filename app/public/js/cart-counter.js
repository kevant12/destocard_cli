/**
 * ========================================
 * GESTIONNAIRE DU COMPTEUR DE PANIER
 * ======================================== 
 */

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    initCartCounter();
});

/**
 * Initialise le compteur de panier
 */
function initCartCounter() {
    const cartCount = document.querySelector('.cart-count');
    if (!cartCount) return;

    // Masquer si le compteur est à 0
    updateCartCountDisplay(cartCount);
    
    // Ajouter une classe pour les animations CSS
    cartCount.classList.add('cart-counter-loaded');
}

/**
 * Met à jour l'affichage du compteur
 */
function updateCartCountDisplay(cartCountElement) {
    const count = parseInt(cartCountElement.textContent) || 0;
    
    if (count === 0) {
        cartCountElement.style.display = 'none';
        cartCountElement.setAttribute('data-count', '0');
    } else {
        cartCountElement.style.display = 'flex';
        cartCountElement.setAttribute('data-count', count);
        
        // Animation de mise à jour
        cartCountElement.classList.add('cart-count-updated');
        setTimeout(() => {
            cartCountElement.classList.remove('cart-count-updated');
        }, 600);
    }
}

/**
 * Met à jour le compteur avec animation
 */
function updateCartCount(newCount) {
    const cartCountElement = document.querySelector('.cart-count');
    if (!cartCountElement) return;

    const oldCount = parseInt(cartCountElement.textContent) || 0;
    
    // Animation de transition
    if (newCount !== oldCount) {
        cartCountElement.style.transform = 'scale(1.3)';
        cartCountElement.style.background = 'var(--accent-color)';
        
        setTimeout(() => {
            cartCountElement.textContent = newCount;
            updateCartCountDisplay(cartCountElement);
            
            setTimeout(() => {
                cartCountElement.style.transform = '';
                cartCountElement.style.background = '';
            }, 200);
        }, 150);
    }
}

/**
 * Animation d'ajout au panier
 */
function animateAddToCart() {
    const cartCountElement = document.querySelector('.cart-count');
    if (!cartCountElement) return;

    // Animation spéciale pour l'ajout
    cartCountElement.classList.add('cart-add-animation');
    setTimeout(() => {
        cartCountElement.classList.remove('cart-add-animation');
    }, 800);
}

// Rendre les fonctions globales pour l'utilisation dans d'autres scripts
window.updateCartCount = updateCartCount;
window.animateAddToCart = animateAddToCart; 