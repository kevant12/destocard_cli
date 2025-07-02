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
            .then(async (response) => {
                const data = await response.json();
                if (!response.ok) {
                    // S'il y a une erreur (ex: 400), on la lance avec le message du serveur
                    throw new Error(data.error || 'Une erreur est survenue.');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    // Met à jour uniquement le badge si la requête a réussi
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cartCount;
                        cartCountElement.style.display = data.cartCount > 0 ? 'inline-block' : 'none';
                    }
                    alert('Produit ajouté au panier !');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'ajout au panier:', error);
                alert(error.message); // Affiche le message d'erreur spécifique (ex: "Déjà dans le panier")
            });
        }
    }
});

// Initialiser le badge au chargement
// ... existing code ...
