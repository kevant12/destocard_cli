/**
 * ========================================
 * SYSTÈME DE NOTIFICATIONS - DESTOCARD
 * ======================================== 
 * 
 * Système de notifications toast modernes :
 * - Affichage de messages (succès, erreur, warning, info)
 * - Auto-fermeture configurable
 * - Animations d'entrée/sortie
 * - Support du stacking multiple
 * 
 * Script chargé avec defer - le DOM est automatiquement prêt
 */

// Conteneur des notifications
let notificationContainer = null;

// Initialisation directe - defer garantit que le DOM est prêt
initNotificationSystem();

/**
 * Initialise le système de notifications
 */
function initNotificationSystem() {
    // Créer le conteneur de notifications s'il n'existe pas
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
}

/**
 * Affiche une notification
 * @param {string} type - Type de notification (success, error, warning, info)
 * @param {string} message - Message à afficher
 * @param {number} duration - Durée d'affichage en ms (défaut: 4000)
 * @param {boolean} autoDismiss - Auto-fermeture (défaut: true)
 */
function showNotification(type = 'info', message = '', duration = 4000, autoDismiss = true) {
    if (!notificationContainer) {
        initNotificationSystem();
    }

    // Créer l'élément notification
    const notification = document.createElement('div');
    notification.className = `notification notification--${type}`;
    
    // Icônes pour chaque type
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };

    // Contenu de la notification
    notification.innerHTML = `
        <div class="notification__icon">${icons[type] || icons.info}</div>
        <div class="notification__content">
            <div class="notification__message">${message}</div>
        </div>
        <button class="notification__close" aria-label="Fermer la notification">×</button>
    `;

    // Ajouter les événements
    const closeBtn = notification.querySelector('.notification__close');
    closeBtn.addEventListener('click', () => {
        dismissNotification(notification);
    });

    // Ajouter au conteneur
    notificationContainer.appendChild(notification);

    // Animation d'entrée
    setTimeout(() => {
        notification.classList.add('notification--show');
    }, 10);

    // Auto-fermeture
    if (autoDismiss && duration > 0) {
        setTimeout(() => {
            dismissNotification(notification);
        }, duration);
    }

    return notification;
}

/**
 * Ferme une notification
 */
function dismissNotification(notification) {
    if (!notification || !notification.parentNode) return;

    notification.classList.add('notification--hide');
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

/**
 * Notifications spécialisées
 */
function showSuccess(message, duration = 4000) {
    return showNotification('success', message, duration);
}

function showError(message, duration = 6000) {
    return showNotification('error', message, duration);
}

function showWarning(message, duration = 5000) {
    return showNotification('warning', message, duration);
}

function showInfo(message, duration = 4000) {
    return showNotification('info', message, duration);
}

/**
 * Notification pour l'ajout au panier
 */
function showCartSuccess(productName) {
    return showSuccess(`🛒 "${productName}" ajouté au panier avec succès !`);
}

/**
 * Notification pour les favoris
 */
function showFavoriteAdded(productName) {
    return showSuccess(`❤️ "${productName}" ajouté aux favoris !`);
}

function showFavoriteRemoved(productName) {
    return showInfo(`💔 "${productName}" retiré des favoris`);
}

/**
 * Notification pour les adresses
 */
function showAddressSuccess(message = 'Adresse sauvegardée avec succès !') {
    return showSuccess(`📍 ${message}`);
}

/**
 * Ferme toutes les notifications
 */
function dismissAllNotifications() {
    if (!notificationContainer) return;
    
    const notifications = notificationContainer.querySelectorAll('.notification');
    notifications.forEach(notification => {
        dismissNotification(notification);
    });
}

// Exporter les fonctions pour utilisation globale
window.showNotification = showNotification;
window.showSuccess = showSuccess;
window.showError = showError;
window.showWarning = showWarning;
window.showInfo = showInfo;
window.showCartSuccess = showCartSuccess;
window.showFavoriteAdded = showFavoriteAdded;
window.showFavoriteRemoved = showFavoriteRemoved;
window.showAddressSuccess = showAddressSuccess;
window.dismissNotification = dismissNotification;
window.dismissAllNotifications = dismissAllNotifications; 