/**
 * ========================================
 * GESTION DU PANIER - DESTOCARD
 * ======================================== 
 * 
 * Fonctionnalités du panier d'achat :
 * - Ajout de produits au panier
 * - Suppression d'articles
 * - Mise à jour du compteur
 * - Gestion des notifications
 * 
 * Script chargé avec defer - le DOM est automatiquement prêt
 */

// Initialisation directe - defer garantit que le DOM est prêt
initCartFunctionality();

/**
 * Initialise les fonctionnalités du panier
 */
function initCartFunctionality() {
    // Boutons d'ajout au panier
    initAddToCartButtons();
    
    // Boutons de suppression du panier
    initRemoveFromCartButtons();
}

/**
 * Initialise les boutons d'ajout au panier
 */
function initAddToCartButtons() {
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    
    addToCartForms.forEach(form => {
        form.addEventListener('submit', handleAddToCart);
    });
}

/**
 * Gère l'ajout d'un produit au panier
 */
async function handleAddToCart(e) {
    e.preventDefault();
    
    const form = e.target;
    const button = form.querySelector('button[type="submit"]');
    const url = form.action;
    const formData = new FormData(form);
    
    // État de chargement du bouton avec classes CSS
    const originalText = button.textContent;
    button.disabled = true;
    button.classList.add('loading');
    button.textContent = 'Ajout...';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: new URLSearchParams(formData),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Retirer l'état de chargement
            button.classList.remove('loading');
            
            // Récupérer le nom du produit pour la notification
            const productName = getProductNameFromForm(form);
            
            // Afficher notification de succès
            if (window.showCartSuccess) {
                window.showCartSuccess(productName);
            }
            
            // Mettre à jour le compteur du panier
            updateCartCounter(data.cartCount);
            
            // Animation du bouton avec classe CSS
            button.classList.add('success');
            button.textContent = '✅ Ajouté !';
            
            setTimeout(() => {
                button.classList.remove('success');
                button.textContent = originalText;
                button.disabled = false;
            }, 2000);
            
        } else {
            // Retirer l'état de chargement
            button.classList.remove('loading');
            
            // Afficher notification d'erreur
            if (window.showError) {
                window.showError(data.error || 'Erreur lors de l\'ajout au panier');
            }
            
            // Restaurer le bouton
            button.textContent = originalText;
            button.disabled = false;
        }
        
    } catch (error) {
        console.error('Erreur lors de l\'ajout au panier:', error);
        
        // Retirer l'état de chargement
        button.classList.remove('loading');
        
        if (window.showError) {
            window.showError('Une erreur est survenue lors de l\'ajout au panier');
        }
        
        // Restaurer le bouton
        button.textContent = originalText;
        button.disabled = false;
    }
}

/**
 * Initialise les boutons de suppression du panier
 */
function initRemoveFromCartButtons() {
    const removeButtons = document.querySelectorAll('.cart-remove-btn');
    
    removeButtons.forEach(button => {
        button.addEventListener('click', handleRemoveFromCart);
    });
}

/**
 * Gère la suppression d'un produit du panier
 */
async function handleRemoveFromCart(e) {
    e.preventDefault();
    
    const button = e.target;
    const productId = button.dataset.productId;
    const csrfToken = button.dataset.csrfToken;
    const row = button.closest('tr');
    
    // Confirmation
    if (!confirm('Êtes-vous sûr de vouloir retirer cet article du panier ?')) {
        return;
    }
    
    try {
        const response = await fetch(`/cart/remove/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `_token=${encodeURIComponent(csrfToken)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Animation de suppression
            row.style.opacity = '0.5';
            row.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                row.remove();
                
                // Mettre à jour le compteur et le total
                updateCartCounter(data.cartCount);
                updateCartTotal(data.total);
                
                // Notification de succès
                if (window.showInfo) {
                    window.showInfo('Article retiré du panier');
                }
                
                // Vérifier si le panier est vide
                checkEmptyCart();
            }, 300);
            
        } else {
            if (window.showError) {
                window.showError('Erreur lors de la suppression');
            }
        }
        
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        if (window.showError) {
            window.showError('Une erreur est survenue');
        }
    }
}

/**
 * Met à jour le compteur du panier
 */
function updateCartCounter(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    
    cartCountElements.forEach(element => {
        element.textContent = count;
        element.style.display = count > 0 ? 'flex' : 'none';
        
        // Animation du compteur
        if (count > 0) {
            element.classList.add('cart-count-updated');
            setTimeout(() => {
                element.classList.remove('cart-count-updated');
            }, 600);
        }
    });
}

/**
 * Met à jour le total du panier
 */
function updateCartTotal(total) {
    const totalElements = document.querySelectorAll('#cart-total');
    
    totalElements.forEach(element => {
        element.textContent = `${total.toFixed(2).replace('.', ',')} €`;
    });
}

/**
 * Vérifie si le panier est vide et affiche un message
 */
function checkEmptyCart() {
    const cartTable = document.querySelector('.cart-table tbody');
    
    if (cartTable && cartTable.children.length === 0) {
        const container = document.querySelector('.cart-main-container');
        if (container) {
            container.innerHTML = `
                <h1 class="site-title">Votre panier</h1>
                <div class="text-center" style="padding: 4rem 0;">
                    <p>Votre panier est vide.</p>
                    <a href="/product" class="btn btn--primary">Continuer vos achats</a>
                </div>
            `;
        }
    }
}

/**
 * Récupère le nom du produit depuis le formulaire
 */
function getProductNameFromForm(form) {
    // Essayer de trouver le nom du produit dans la page
    const productCard = form.closest('.card, .product-card');
    if (productCard) {
        const titleElement = productCard.querySelector('.card__title a, h3, .product-title');
        if (titleElement) {
            return titleElement.textContent.trim();
        }
    }
    
    // Fallback pour la page de détail du produit
    const pageTitle = document.querySelector('.product-detail-title, h1');
    if (pageTitle) {
        return pageTitle.textContent.trim();
    }
    
    return 'Article';
}

// Exporter les fonctions pour utilisation globale
window.updateCartCounter = updateCartCounter;
window.updateCartTotal = updateCartTotal; 