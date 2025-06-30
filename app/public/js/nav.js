// Fonction pour mettre à jour le badge du panier
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

// Écouter les changements dans le localStorage
window.addEventListener('storage', (e) => {
    if (e.key === 'cart') {
        updateCartBadge();
    }
});

// Gestion de l'ajout au panier
document.addEventListener('click', function(e) {
    const button = e.target.closest('button[type="submit"]');
    if (button) {
        const form = button.closest('form');
        if (form && form.action.includes('add-to-cart')) {
            e.preventDefault();
            
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