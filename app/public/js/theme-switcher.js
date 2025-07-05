/**
 * ========================================
 * GESTIONNAIRE DE THÈME - DESTOCARD
 * ========================================
 * 
 * Système de basculement entre thème clair et sombre :
 * - Détection des préférences système
 * - Mémorisation du choix utilisateur
 * - Bouton de basculement dynamique
 * - Transitions fluides
 * 
 * Script chargé avec defer - le DOM est automatiquement prêt
 */

class ThemeSwitcher {
    constructor() {
        this.themes = {
            dark: 'dark-theme',
            light: 'light-theme'
        };
        
        this.currentTheme = this.getStoredTheme() || this.getSystemPreference();
        this.init();
    }

    /**
     * Initialise le gestionnaire de thème
     */
    init() {
        // Applique le thème au chargement
        this.applyTheme(this.currentTheme);
        
        // Crée le bouton de basculement
        this.createThemeToggle();
        
        // Écoute les changements de préférence système
        this.watchSystemPreference();
    }

    /**
     * Récupère le thème stocké dans localStorage
     */
    getStoredTheme() {
        return localStorage.getItem('destocard-theme');
    }

    /**
     * Récupère la préférence système de l'utilisateur
     */
    getSystemPreference() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    /**
     * Applique le thème sélectionné
     */
    applyTheme(theme) {
        const body = document.body;
        
        // Supprime les classes de thème existantes
        Object.values(this.themes).forEach(themeClass => {
            body.classList.remove(themeClass);
        });
        
        // Ajoute la nouvelle classe de thème
        body.classList.add(this.themes[theme]);
        
        // Met à jour l'attribut data-theme pour le CSS
        body.setAttribute('data-theme', theme);
        
        // Sauvegarde dans localStorage
        localStorage.setItem('destocard-theme', theme);
        
        this.currentTheme = theme;
        this.updateToggleButton();
    }

    /**
     * Bascule entre les thèmes
     */
    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        
        // Animation du bouton
        const button = document.getElementById('theme-toggle');
        if (button) {
            button.classList.add('rotating');
            setTimeout(() => {
                button.classList.remove('rotating');
            }, 300);
        }
        
        this.applyTheme(newTheme);
        
        // Animation de transition douce pour le body
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    /**
     * Crée le bouton de basculement de thème
     */
    createThemeToggle() {
        // Vérifie si le bouton existe déjà
        if (document.getElementById('theme-toggle')) return;

        const toggleButton = document.createElement('button');
        toggleButton.id = 'theme-toggle';
        toggleButton.className = 'theme-toggle-btn';
        toggleButton.setAttribute('aria-label', 'Basculer le thème');
        toggleButton.setAttribute('title', 'Changer le thème');
        
        // Ajoute le bouton au header
        const header = document.querySelector('.site-header__nav-user');
        if (header) {
            header.insertBefore(toggleButton, header.firstChild);
        }
        
        // Événement de clic
        toggleButton.addEventListener('click', () => this.toggleTheme());
        
        // Marquer comme chargé après l'animation d'entrée
        setTimeout(() => {
            toggleButton.classList.add('loaded');
        }, 700);
        
        this.updateToggleButton();
    }

    /**
     * Met à jour l'apparence du bouton de basculement
     */
    updateToggleButton() {
        const button = document.getElementById('theme-toggle');
        if (!button) return;
        
        if (this.currentTheme === 'dark') {
            button.innerHTML = '☀️';
            button.setAttribute('title', 'Passer au thème clair');
        } else {
            button.innerHTML = '🌙';
            button.setAttribute('title', 'Passer au thème sombre');
        }
    }

    /**
     * Écoute les changements de préférence système
     */
    watchSystemPreference() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        mediaQuery.addEventListener('change', (e) => {
            // Ne change que si aucun thème n'est explicitement stocké
            if (!this.getStoredTheme()) {
                const systemTheme = e.matches ? 'dark' : 'light';
                this.applyTheme(systemTheme);
            }
        });
    }

    /**
     * Méthode publique pour changer le thème programmatiquement
     */
    setTheme(theme) {
        if (theme === 'dark' || theme === 'light') {
            this.applyTheme(theme);
        }
    }

    /**
     * Méthode publique pour obtenir le thème actuel
     */
    getCurrentTheme() {
        return this.currentTheme;
    }
}

// Initialisation automatique directe - defer garantit que le DOM est prêt
// Crée une instance globale
window.themeSwitcher = new ThemeSwitcher();

// Export pour utilisation en module (optionnel)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeSwitcher;
} 