// public/js/ajax-filters.js

document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.querySelector('#search-filter-form');
    
    if (filterForm) {
        filterForm.addEventListener('submit', function(event) {
            // 1. Empêcher le rechargement de la page
            event.preventDefault();

            // 2. Récupérer les données du formulaire et l'URL
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            const url = this.getAttribute('action') + '?' + params.toString();

            // 3. Afficher un indicateur de chargement (optionnel mais bon pour l'UX)
            const resultsContainer = document.querySelector('#search-results-container');
            resultsContainer.classList.add('loading');
            
            // 4. Envoyer la requête AJAX
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // 5. Mettre à jour le contenu de la page
                resultsContainer.innerHTML = data.content;

                // 6. Mettre à jour l'URL dans la barre d'adresse
                window.history.pushState({}, '', url);
            })
            .catch(error => {
                console.error('Erreur lors du filtrage AJAX:', error);
                // En cas d'erreur, on peut recharger la page normalement
                window.location.href = url;
            })
            .finally(() => {
                // 7. Retirer l'indicateur de chargement
                resultsContainer.classList.remove('loading');
            });
        });
    }
}); 