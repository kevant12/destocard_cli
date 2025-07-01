// Ce script utilise la délégation d'événements et ne s'exécute que si le conteneur du panier est trouvé.
// Il est destiné à être chargé avec 'defer', rendant DOMContentLoaded inutile.

const cartContainer = document.querySelector('.cart-main-container');

if (cartContainer) {
    let debounceTimeout;

    const sendRequest = async (url, token, body = {}) => {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({ ...body, '_token': token })
            });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Réponse invalide du serveur.' }));
                throw new Error(errorData.error || `Erreur serveur: ${response.statusText}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Erreur de communication:', error);
            alert(`Une erreur est survenue: ${error.message}`);
            return null;
        }
    };

    const updateRow = (productId, itemTotal) => {
        const row = cartContainer.querySelector(`tr[data-product-id="${productId}"]`);
        if (row) {
            const totalCell = row.querySelector('.cart-item-total');
            if (totalCell) totalCell.textContent = `${itemTotal.toLocaleString('fr-FR', { minimumFractionDigits: 2 })} €`;
        }
    };

    const removeRow = (productId) => {
        cartContainer.querySelector(`tr[data-product-id="${productId}"]`)?.remove();
    };

    const updateCartSummary = (total, count) => {
        const totalEl = document.getElementById('cart-total');
        if (totalEl) totalEl.textContent = total.toLocaleString('fr-FR', { minimumFractionDigits: 2 });

        const cartCountEl = document.querySelector('.cart-count');
        if (cartCountEl) cartCountEl.textContent = count > 0 ? count : '';
        
        if (count === 0 && cartContainer.querySelector('.cart-table')) {
             cartContainer.innerHTML = `<h1 class="site-title" style="margin-bottom: 2rem;">Votre panier</h1><p class="text-center">Votre panier est vide.</p>`;
        }
    };

    cartContainer.addEventListener('click', async (event) => {
        const removeButton = event.target.closest('.cart-remove-btn');
        if (removeButton && confirm('Êtes-vous sûr de vouloir retirer cet article ?')) {
            event.preventDefault();
            const productId = removeButton.dataset.productId;
            const token = removeButton.dataset.csrfToken;
            const data = await sendRequest(`/cart/remove/${productId}`, token);
            if (data?.success) {
                removeRow(productId);
                updateCartSummary(data.total, data.cartCount);
            }
        }
    });

    cartContainer.addEventListener('input', (event) => {
        const quantityInput = event.target.closest('.cart-quantity-input');
        if (quantityInput) {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(async () => {
                let quantity = parseInt(quantityInput.value, 10);
                const productId = quantityInput.dataset.productId;
                
                if (isNaN(quantity)) {
                     quantityInput.value = 1; // réinitialise si la valeur n'est pas un nombre
                     return;
                }

                if (quantity > 0) {
                    const token = quantityInput.dataset.csrfToken;
                    const data = await sendRequest(`/cart/update/${productId}`, token, { quantity });
                    if (data?.success) {
                        updateRow(productId, data.itemTotal);
                        updateCartSummary(data.total, data.cartCount);
                    }
                } else {
                    const removeButton = quantityInput.closest('tr')?.querySelector('.cart-remove-btn');
                    if (removeButton && confirm('La quantité à zéro retire l\'article. Continuer ?')) {
                        const token = removeButton.dataset.csrfToken;
                        const data = await sendRequest(`/cart/remove/${productId}`, token);
                        if (data?.success) {
                            removeRow(productId);
                            updateCartSummary(data.total, data.cartCount);
                        }
                    } else {
                        quantityInput.value = 1;
                    }
                }
            }, 500);
        }
    });
} 