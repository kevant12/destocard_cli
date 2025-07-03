/**
 * ========================================
 * GESTION DU CHECKOUT - DESTOCARD
 * ======================================== 
 */

// Variables globales
let initialTotal = 0;

/**
 * Initialisation de la page de checkout
 */
function initCheckoutPage() {
    const deliveryMethodRadios = document.querySelectorAll('input[name="checkout_form[deliveryMethod]"]');
    const shippingCostInput = document.getElementById('checkout_form_shippingCost');
    const totalElement = document.querySelector('#cart-total');
    
    // Récupérer le total initial depuis les données Twig ou l'élément DOM
    if (window.checkoutData && window.checkoutData.initialTotal) {
        initialTotal = parseFloat(window.checkoutData.initialTotal);
    } else if (totalElement) {
        const totalText = totalElement.textContent.replace(/[^\d,]/g, '').replace(',', '.');
        initialTotal = parseFloat(totalText) || 0;
    }

    // Gestion des méthodes de livraison
    deliveryMethodRadios.forEach(radio => {
        radio.addEventListener('change', updateShippingCost);
    });

    // Initialiser le coût de livraison
    updateShippingCost();

    // Bouton pour ajouter une adresse depuis le checkout
    const addAddressBtn = document.getElementById('add-address-checkout');
    if (addAddressBtn) {
        addAddressBtn.addEventListener('click', function() {
            openAddressModalForCheckout();
        });
    }

    /**
     * Met à jour le coût de livraison
     */
    function updateShippingCost() {
        let selectedMethod = null;
        deliveryMethodRadios.forEach(radio => {
            if (radio.checked) {
                selectedMethod = radio.value;
            }
        });

        let shippingCost = 0;
        if (selectedMethod === 'standard') {
            shippingCost = 5.00;
        } else if (selectedMethod === 'express') {
            shippingCost = 10.00;
        }

        if (shippingCostInput) {
            shippingCostInput.value = shippingCost;
        }
        updateTotalDisplay(shippingCost);
    }

    /**
     * Met à jour l'affichage du total
     */
    function updateTotalDisplay(shippingCost) {
        const newTotal = initialTotal + shippingCost;
        if (totalElement) {
            totalElement.textContent = newTotal.toLocaleString('fr-FR', {
                minimumFractionDigits: 2
            }) + ' €';
        }
    }
}

/**
 * Ouvre la modal d'ajout d'adresse depuis le checkout
 */
async function openAddressModalForCheckout() {
    try {
        const response = await fetch('/address/new-modal?type=shipping');
        const html = await response.text();
        
        // Créer la modal
        const modal = document.createElement('div');
        modal.className = 'address-modal';
        modal.innerHTML = `<div class="modal-content">${html}</div>`;
        
        // Ajouter au DOM
        document.body.appendChild(modal);
        
        // Initialiser les événements de la modal
        initCheckoutModalEvents(modal);
        
        // Le succès sera géré par handleCheckoutFormSubmit avec rechargement de page
        
    } catch (error) {
        console.error('Erreur lors du chargement de la modal:', error);
        showCheckoutNotification('error', 'Erreur lors du chargement du formulaire');
    }
}

/**
 * Initialise les événements de la modal dans le contexte checkout
 */
function initCheckoutModalEvents(modal) {
    const form = modal.querySelector('#address-form');
    const closeButton = modal.querySelector('.modal-close');
    const cancelButton = modal.querySelector('#cancel-address');
    
    // Soumission du formulaire
    if (form) {
        form.addEventListener('submit', handleCheckoutFormSubmit);
    }
    
    // Bouton fermer (X)
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            modal.remove();
        });
    }
    
    // Bouton annuler
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            modal.remove();
        });
    }
    
    // Fermer en cliquant sur l'overlay
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

/**
 * Gère la soumission du formulaire dans le contexte checkout
 */
async function handleCheckoutFormSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const modal = form.closest('.address-modal');
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    
    // État de chargement
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = '⏳ Enregistrement...';
    
    try {
        const response = await fetch(window.addressRoutes?.create || '/address/create', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Succès - afficher le message et recharger la page
            showCheckoutNotification('success', data.message);
            
            // Fermer la modal
            if (modal) {
                modal.remove();
            }
            
            // Recharger la page après un délai pour voir le message
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
        } else {
            // Erreur - afficher les erreurs
            if (data.errors && data.errors.length > 0) {
                showCheckoutNotification('error', data.errors.join('<br>'));
            }
            
            // Remplacer le formulaire avec les erreurs si fourni
            if (data.form_html) {
                const modalBody = modal.querySelector('.modal-body');
                modalBody.innerHTML = data.form_html;
                // Réinitialiser les événements pour le nouveau formulaire
                initCheckoutModalEvents(modal);
            }
        }
        
    } catch (error) {
        console.error('Erreur lors de l\'enregistrement:', error);
        showCheckoutNotification('error', 'Une erreur est survenue lors de l\'enregistrement.');
    } finally {
        // Restaurer l'état du bouton
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

/**
 * Affiche une notification dans le contexte checkout
 */
function showCheckoutNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
    notification.innerHTML = message;
    notification.style.marginBottom = '1rem';
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(notification, container.firstChild);
    }
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Fonctions globales pour compatibilité
window.openAddressModalForCheckout = openAddressModalForCheckout;
window.showCheckoutNotification = showCheckoutNotification;

// Auto-initialisation quand le script est chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCheckoutPage);
} else {
    initCheckoutPage();
} 