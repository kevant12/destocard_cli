/**
 * ========================================
 * GESTION DES ADRESSES - DESTOCARD
 * ======================================== 
 */

// Variables globales
let addressRoutes = window.addressRoutes || {};

/**
 * Initialisation de la page des adresses
 */
function initAddressPage() {
    // Bouton d'ajout d'adresse
    const addAddressBtn = document.getElementById('add-address-btn');
    if (addAddressBtn) {
        addAddressBtn.addEventListener('click', function() {
            openAddressModal();
        });
    }

    // Boutons de suppression d'adresse
    const deleteButtons = document.querySelectorAll('.btn-delete-address');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const addressId = this.dataset.addressId;
            confirmDeleteAddress(addressId);
        });
    });
}

/**
 * Ouvre la modal d'ajout d'adresse
 */
async function openAddressModal(type = 'shipping') {
    try {
        const url = `${addressRoutes.newModal}?type=${type}`;
        const response = await fetch(url);
        const html = await response.text();
        
        // Créer la modal
        const modal = document.createElement('div');
        modal.className = 'address-modal';
        modal.innerHTML = `<div class="modal-content">${html}</div>`;
        
        // Ajouter au DOM
        const modalContainer = document.getElementById('modal-container') || document.body;
        modalContainer.appendChild(modal);
        
        // Initialiser les événements de la modal
        initModalEvents(modal);
        
        // Callback pour gérer le succès
        window.addressModalCallback = function(address) {
            // Recharger la page pour afficher la nouvelle adresse
            window.location.reload();
        };
        
    } catch (error) {
        console.error('Erreur lors du chargement de la modal:', error);
        showNotification('error', 'Erreur lors du chargement du formulaire');
    }
}

/**
 * Initialise les événements de la modal
 */
function initModalEvents(modal) {
    const form = modal.querySelector('#address-form');
    const closeButton = modal.querySelector('.modal-close');
    const cancelButton = modal.querySelector('#cancel-address');
    
    // Soumission du formulaire
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
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
 * Gère la soumission du formulaire d'adresse
 */
async function handleFormSubmit(e) {
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
        const response = await fetch(addressRoutes.create || '/address/create', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Afficher notification de succès avec le nouveau système
            if (window.showAddressSuccess) {
                window.showAddressSuccess(data.message);
            } else {
                showNotification('success', data.message);
            }
            
            // Succès - fermer la modal et mettre à jour
            if (window.addressModalCallback) {
                window.addressModalCallback(data.address);
            }
            
            // Fermer la modal
            if (modal) {
                modal.remove();
            }
            
        } else {
            // Erreur - afficher les erreurs
            if (data.errors && data.errors.length > 0) {
                showNotification('error', data.errors.join('<br>'));
            }
            
            // Remplacer le formulaire avec les erreurs si fourni
            if (data.form_html) {
                const modalBody = modal.querySelector('.modal-body');
                modalBody.innerHTML = data.form_html;
                // Réinitialiser les événements pour le nouveau formulaire
                initModalEvents(modal);
            }
        }
        
    } catch (error) {
        console.error('Erreur lors de l\'enregistrement:', error);
        showNotification('error', 'Une erreur est survenue lors de l\'enregistrement.');
    } finally {
        // Restaurer l'état du bouton
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

/**
 * Confirme la suppression d'une adresse
 */
function confirmDeleteAddress(addressId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette adresse ?')) {
        deleteAddress(addressId);
    }
}

/**
 * Supprime une adresse
 */
async function deleteAddress(addressId) {
    try {
        const url = addressRoutes.delete.replace('{id}', addressId);
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            // Supprimer l'élément du DOM
            const card = document.querySelector(`[data-address-id="${addressId}"]`);
            if (card) {
                card.remove();
            }
            
            // Afficher un message
            showNotification('success', 'Adresse supprimée avec succès');
        } else {
            throw new Error('Erreur lors de la suppression');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('error', 'Erreur lors de la suppression de l\'adresse');
    }
}

/**
 * Affiche une notification temporaire
 */
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `flash-message flash-message--${type}`;
    notification.innerHTML = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '400px';
    
    document.body.appendChild(notification);
    
    // Supprimer après 5 secondes
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Fonctions globales pour compatibilité
window.openAddressModal = openAddressModal;
window.confirmDeleteAddress = confirmDeleteAddress;
window.showNotification = showNotification;

// Auto-initialisation quand le script est chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAddressPage);
} else {
    initAddressPage();
} 