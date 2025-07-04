/**
 * ========================================
 * NAVIGATION ET PANIER - DESTOCARD
 * ========================================
 * 
 * Fonctionnalités de la barre de navigation :
 * - Gestion du badge du panier
 * - Ajout de produits via AJAX
 * - Synchronisation avec localStorage
 * - Mise à jour en temps réel
 * 
 * Script chargé avec defer - le DOM est automatiquement prêt
 */

/**
 * Met à jour le badge du panier dans la navigation
 */
function updateCartBadge() {
    const cartItems = JSON.parse(localStorage.getItem('cart') || '[]');
    const totalItems = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    const badge = document.querySelector('.cart-badge');
    if (badge) {
        badge.textContent = totalItems.toString();
        badge.style.display = totalItems > 0 ? 'inline' : 'none';
    }
}

// Mettre à jour le badge au chargement de la page
updateCartBadge();

// Écouter les changements dans le localStorage (synchronisation entre onglets)
window.addEventListener('storage', (e) => {
    if (e.key === 'cart') {
        updateCartBadge();
    }
});

/**
 * Gestion de l'ajout au panier via AJAX
 * Utilise la délégation d'événements pour capturer les soumissions de formulaires
 */
document.addEventListener('click', function(e) {
    const button = e.target.closest('button[type="submit"]');
    if (button) {
        const form = button.closest('form');
        if (form && form.action.includes('add-to-cart')) {
            e.preventDefault();
            
            // Requête AJAX pour ajouter au panier
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartBadge();
                }
            })
            .catch(error => console.error('Erreur:', error));
        }
    }
});

// Initialiser le badge au chargement
const badge = document.querySelector('.cart-badge');
if (badge) {
    const count = parseInt(badge.textContent) || 0;
    badge.style.display = count > 0 ? 'block' : 'none';
} 