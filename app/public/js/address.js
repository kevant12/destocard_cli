/**
 * ========================================
 * GESTION DES ADRESSES - DESTOCARD
 * ======================================== 
 */

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    initAddressManagement();
});

/**
 * Initialise la gestion des adresses
 */
function initAddressManagement() {
    // Boutons d'ajout d'adresse
    initAddAddressButtons();
    
    // Boutons d'édition d'adresse
    initEditAddressButtons();
    
    // Boutons de suppression d'adresse
    initDeleteAddressButtons();
}

/**
 * Initialise les boutons d'ajout d'adresse
 */
function initAddAddressButtons() {
    // Bouton principal dans la page des adresses
    const addBtn = document.getElementById('add-address-btn');
    if (addBtn) {
        addBtn.addEventListener('click', () => openAddressModal());
    }
    
    // Bouton dans le checkout
    const checkoutBtn = document.getElementById('add-address-checkout');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => openAddressModal('shipping'));
    }
}

/**
 * Initialise les boutons d'édition d'adresse
 */
function initEditAddressButtons() {
    document.body.addEventListener('click', function(e) {
        if (e.target.matches('.btn-edit-address')) {
            const addressId = e.target.dataset.addressId;
            openEditAddressModal(addressId);
        }
    });
}

/**
 * Initialise les boutons de suppression d'adresse
 */
function initDeleteAddressButtons() {
    document.body.addEventListener('click', function(e) {
        if (e.target.matches('.btn-delete-address')) {
            const addressId = e.target.dataset.addressId;
            const csrfToken = e.target.dataset.csrfToken;
            confirmDeleteAddress(addressId, csrfToken);
        }
    });
}

/**
 * Ouvre la modal d'ajout d'adresse
 */
async function openAddressModal(type = null) {
    try {
        const url = type ? 
            `${window.addressRoutes.newModal}?type=${type}` : 
            window.addressRoutes.newModal;
            
        const response = await fetch(url);
        const html = await response.text();
        
        showModal(html);
        initModalForm();
        
    } catch (error) {
        console.error('Erreur lors du chargement de la modal:', error);
        if (window.showError) {
            window.showError('Impossible de charger le formulaire d\'adresse');
        }
    }
}

/**
 * Ouvre la modal d'édition d'adresse
 */
async function openEditAddressModal(addressId) {
    try {
        const url = window.addressRoutes.editModal.replace('{id}', addressId);
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error('Erreur lors du chargement');
        }
        
        const html = await response.text();
        showModal(html);
        initModalForm(true); // Mode édition
        
    } catch (error) {
        console.error('Erreur lors du chargement de la modal d\'édition:', error);
        if (window.showError) {
            window.showError('Impossible de charger le formulaire d\'édition');
        }
    }
}

/**
 * Affiche la modal avec le contenu HTML
 */
function showModal(html) {
    // Supprimer toute modal existante
    const existingModal = document.querySelector('.address-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Créer la nouvelle modal
    const modal = document.createElement('div');
    modal.className = 'address-modal';
    modal.innerHTML = `<div class="modal-content">${html}</div>`;
    
    document.body.appendChild(modal);
    
    // Focus sur le premier champ
    const firstInput = modal.querySelector('input:not([type="hidden"])');
    if (firstInput) {
        firstInput.focus();
    }
}

/**
 * Initialise le formulaire dans la modal
 */
function initModalForm(isEdit = false) {
    const modal = document.querySelector('.address-modal');
    if (!modal) return;
    
    const form = modal.querySelector('#address-form');
    const closeBtn = modal.querySelector('.modal-close, #cancel-address');
    
    // Gestion de la fermeture
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // Fermeture en cliquant en dehors
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Fermeture avec Échap
    document.addEventListener('keydown', handleEscapeKey);
    
    // Gestion du formulaire
    if (form) {
        form.addEventListener('submit', (e) => handleAddressSubmit(e, isEdit));
    }
}

/**
 * Gère la touche Échap
 */
function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
}

/**
 * Ferme la modal
 */
function closeModal() {
    const modal = document.querySelector('.address-modal');
    if (modal) {
        modal.remove();
    }
    document.removeEventListener('keydown', handleEscapeKey);
}

/**
 * Gère la soumission du formulaire d'adresse
 */
async function handleAddressSubmit(e, isEdit = false) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // État de chargement
    submitBtn.disabled = true;
    submitBtn.textContent = isEdit ? '⏳ Modification...' : '⏳ Enregistrement...';
    
    try {
        const formData = new FormData(form);
        const addressId = form.dataset.addressId;
        
        // Déterminer l'URL selon le mode
        const url = isEdit && addressId ? 
            window.addressRoutes.update.replace('{id}', addressId) : 
            window.addressRoutes.create;
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Notification de succès
            if (window.showAddressSuccess) {
                window.showAddressSuccess();
            }
            
            // Fermer la modal
            closeModal();
            
            // Recharger la page ou mettre à jour la liste
            if (window.location.pathname.includes('/address') || window.location.pathname.includes('/checkout')) {
                window.location.reload();
            }
            
        } else {
            // Afficher les erreurs
            if (data.errors && data.errors.length > 0) {
                if (window.showError) {
                    window.showError(data.errors.join(', '));
                }
            } else if (data.form_html) {
                // Remplacer le contenu de la modal avec les erreurs
                const modalContent = document.querySelector('.address-modal .modal-content');
                if (modalContent) {
                    modalContent.innerHTML = data.form_html;
                    initModalForm(isEdit);
                }
            }
        }
        
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error);
        if (window.showError) {
            window.showError('Une erreur est survenue lors de la sauvegarde');
        }
    } finally {
        // Restaurer le bouton
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

/**
 * Confirme et supprime une adresse
 */
async function confirmDeleteAddress(addressId, csrfToken) {
    // Confirmation utilisateur
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette adresse ?\nCette action est irréversible.')) {
        return;
    }
    
    try {
        const url = window.addressRoutes.delete.replace('{id}', addressId);
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `_token=${encodeURIComponent(csrfToken)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Notification de succès
            if (window.showInfo) {
                window.showInfo(data.message || 'Adresse supprimée avec succès');
            }
            
            // Supprimer visuellement la carte d'adresse en utilisant une classe CSS
            const addressCard = document.querySelector(`.address-card[data-address-id="${addressId}"]`);
            if (addressCard) {
                addressCard.classList.add('is-deleting');
                
                // Écouter la fin de la transition pour supprimer l'élément du DOM
                addressCard.addEventListener('transitionend', () => {
                    addressCard.remove();
                    
                    // Vérifier s'il reste des adresses
                    const remainingCards = document.querySelectorAll('.address-card');
                    if (remainingCards.length === 0) {
                        const grid = document.querySelector('.addresses-grid');
                        if(grid) {
                            grid.innerHTML = '<p class="empty-state">Vous n\'avez aucune adresse enregistrée.</p>';
                        }
                    }
                }, { once: true }); // Important: l'écouteur est retiré après exécution
            }
            
        } else {
            if (window.showError) {
                window.showError(data.error || 'Erreur lors de la suppression');
            }
        }
        
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        if (window.showError) {
            window.showError('Une erreur est survenue lors de la suppression');
        }
    }
}

/**
 * Met à jour le select d'adresses dans le checkout
 */
function updateAddressSelect(address) {
    const select = document.querySelector('select[name="checkout_form[deliveryAddress]"]');
    if (select) {
        const option = document.createElement('option');
        option.value = address.id;
        option.textContent = address.label;
        option.selected = true;
        select.appendChild(option);
    }
}

// Exporter les fonctions pour utilisation globale
window.openAddressModal = openAddressModal;
window.openEditAddressModal = openEditAddressModal;
window.confirmDeleteAddress = confirmDeleteAddress;
window.closeModal = closeModal; 