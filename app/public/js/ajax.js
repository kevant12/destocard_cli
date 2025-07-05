/**
 * ========================================
 * GESTIONNAIRE AJAX GLOBAL - DESTOCARD
 * ========================================
 * 
 * Logique AJAX réutilisable pour tout le site :
 * - Gestion des favoris avec délégation d'événements
 * - Protection CSRF automatique
 * - Gestion des erreurs globales
 * 
 * Script chargé avec defer - le DOM est automatiquement prêt
 * (c) Kevin - projet_perso
 */

// Fichier central pour la logique AJAX globale du site
// (c) Kevin - projet_perso

/**
 * Gère l'ajout et la suppression d'un article des favoris de l'utilisateur.
 * La fonction utilise la délégation d'événements pour s'appliquer à tous
 * les boutons avec la classe '.btn-like', même ceux ajoutés dynamiquement.
 */
function addFavorite() {
    document.body.addEventListener('click', async (event) => {
        // Cible uniquement les clics sur ou dans un bouton de favori
        const likeButton = event.target.closest('.btn-like');
        if (!likeButton) {
            return;
        }

        event.preventDefault(); // Empêche le comportement par défaut

        const productId = likeButton.dataset.productId;
        const csrfToken = likeButton.dataset.csrfToken;
        const url = `/favorite/toggle/${productId}`;

        // Vérification de sécurité : le bouton doit avoir les informations nécessaires
        if (!productId || !csrfToken) {
            console.error('Bouton "like" mal configuré : attributs data-product-id ou data-csrf-token manquants.');
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ '_token': csrfToken })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Une erreur est survenue lors de la communication avec le serveur.');
            }

            if (data.success) {
                // Mettre à jour l'état visuel de tous les boutons correspondants sur la page
                document.querySelectorAll(`.btn-like[data-product-id="${productId}"]`).forEach(btn => {
                    btn.classList.toggle('active', data.isLiked);
                    btn.classList.toggle('is-liked', data.isLiked);
                });

                // Cas spécial pour la page des favoris : supprimer l'élément si on le retire
                const favoritePageContainer = document.querySelector('.favorites-container');
                if (favoritePageContainer && !data.isLiked) {
                    const card = likeButton.closest('.product-card');
                    if (card) {
                        card.remove();
                        // Si c'était le dernier favori, afficher un message
                        if (favoritePageContainer.querySelectorAll('.product-card').length === 0) {
                            favoritePageContainer.innerHTML = '<h1>Mes Favoris</h1><p>Vous n\'avez pas encore de favoris.</p>';
                        }
                    }
                }
            } else {
                alert(data.error || 'Une erreur inconnue est survenue.');
            }

        } catch (error) {
            console.error('Erreur AJAX:', error);
            alert('Une erreur de communication est survenue. Veuillez réessayer.');
        }
    });
}

// DÉSACTIVÉ : Conflit avec favorites.js 
// addFavorite(); 