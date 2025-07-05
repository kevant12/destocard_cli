/**
 * ========================================
 * GESTION DES FAVORIS - DESTOCARD
 * ======================================== 
 * 
 * Gestion de l'ajout/suppression des favoris avec requêtes AJAX
 * Script chargé avec defer - le DOM est automatiquement prêt
 */

// Initialisation directe - defer garantit que le DOM est prêt
initFavoriteButtons();

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
            
            // Mettre à jour l'état du bouton ET le compteur
            updateFavoriteButton(button, data.isLiked, data.likesCount);
            
            // 🚨 GESTION SPÉCIALE PAGE FAVORIS : Supprimer le produit si retiré des favoris
            if (!data.isLiked) {
                const favoritePageContainer = document.querySelector('.favorites-container');
                if (favoritePageContainer) {
                    // On est sur la page des favoris, supprimer la carte produit
                    const productCard = button.closest('.card, .product-card');
                    if (productCard) {
                        // Animation de sortie avant suppression
                        productCard.style.transition = 'all 0.3s ease-out';
                        productCard.style.transform = 'scale(0.8)';
                        productCard.style.opacity = '0';
                        
                        setTimeout(() => {
                            productCard.remove();
                            
                            // Vérifier s'il reste des favoris
                            const remainingCards = favoritePageContainer.querySelectorAll('.card, .product-card');
                            if (remainingCards.length === 0) {
                                // Plus de favoris, afficher le message vide
                                const gridContainer = favoritePageContainer.querySelector('.products-grid');
                                if (gridContainer) {
                                    gridContainer.innerHTML = '<p>Vous n\'avez plus de favoris.</p>';
                                }
                            }
                        }, 300);
                    }
                }
            }
            
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
            
            // Animation du bouton (seulement si pas sur page favoris ou si ajouté)
            if (data.isLiked || !document.querySelector('.favorites-container')) {
                button.classList.add('favorite-animation');
                setTimeout(() => {
                    button.classList.remove('favorite-animation');
                }, 600);
            }
            
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
 * Met à jour l'état visuel du bouton favori ET le compteur
 */
function updateFavoriteButton(button, isLiked, likesCount) {
    // 📊 MISE À JOUR DU COMPTEUR DE LIKES
    let countSpan = button.querySelector('.likes-count');
    if (countSpan) {
        // Le compteur existe déjà, on le met à jour
        countSpan.textContent = likesCount || 0;
    } else {
        // Le compteur n'existe pas, on le crée (cas d'urgence)
        countSpan = document.createElement('span');
        countSpan.className = 'likes-count';
        countSpan.textContent = likesCount || 0;
        button.appendChild(countSpan);
    }
    
    // 🎨 MISE À JOUR DE L'ÉTAT VISUEL DU BOUTON
    if (isLiked) {
        button.classList.add('active', 'is-liked');
        button.innerHTML = '❤️ <span class="likes-count">' + (likesCount || 0) + '</span>';
        button.title = 'Retirer des favoris';
        button.style.color = 'var(--error-color)';
    } else {
        button.classList.remove('active', 'is-liked');
        button.innerHTML = '🤍 <span class="likes-count">' + (likesCount || 0) + '</span>';
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

// Les styles CSS sont maintenant dans style.css pour une meilleure organisation

// Exporter les fonctions pour utilisation globale
window.initFavoriteButtons = initFavoriteButtons;
window.updateFavoriteButton = updateFavoriteButton; 