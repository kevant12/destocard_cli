// Fichier central pour la logique AJAX globale du site
// (c) Kevin - projet_perso

// --- AJOUT AU PANIER ---
// (plus besoin de DOMContentLoaded car le script est chargé avec defer)
// Délégation sur tous les boutons "ajouter" (panier)
document.body.addEventListener('click', function(e) {
    // Ancienne logique pour les boutons spécifiques (si encore utilisés)
    if (e.target.classList.contains('home-latest-add-btn') || e.target.classList.contains('home-popular-btn')) {
        e.preventDefault();
        const article = e.target.closest('article, .home-popular-card');
        const productId = article?.dataset?.id || article?.getAttribute('data-id') || 1; // à adapter avec l'id réel
        // Simule la soumission du formulaire pour réutiliser la logique ci-dessous
        const tempForm = document.createElement('form');
        tempForm.classList.add('add-to-cart-form');
        tempForm.action = `/add-to-cart/${productId}`;
        document.body.appendChild(tempForm);
        const submitEvent = new Event('submit', { cancelable: true });
        tempForm.dispatchEvent(submitEvent);
        tempForm.remove();
    }
});

// --- Gestionnaire générique pour les soumissions de formulaire AJAX ---
document.body.addEventListener('submit', async function(e) {
    if (e.target.classList.contains('add-to-cart-form') || e.target.id === 'cart-buy-form') {
        e.preventDefault(); // Empêche la soumission normale du formulaire

        const form = e.target;
        const url = form.action;
        const method = form.method;
        const formData = new FormData(form);

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    // 'Content-Type': 'application/json', // FormData gère son propre Content-Type
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.message) {
                showFlash(data.message, data.success ? 'success' : 'error');
            }

            if (data.redirect) {
                window.location.href = data.redirect;
            }

            if (data.success) {
                // Logique spécifique pour le panier si l'opération réussit
                if (form.classList.contains('add-to-cart-form')) {
                    updateCartBadge(data.cartCount);
                } else if (form.id === 'cart-buy-form') {
                    // Le contrôleur gère déjà la redirection en cas de succès
                }
            }
            else {
                // Logique spécifique pour le panier si l'opération échoue
                if (form.classList.contains('add-to-cart-form')) {
                    // Rien de plus à faire, le message flash est géré
                }
            }

        } catch (error) {
            console.error('Erreur réseau ou de traitement AJAX:', error);
            showFlash('Erreur réseau ou de traitement.', 'error');
        }
    }

    // --- Suppression panier (via bouton) ---
    if (e.target.classList.contains('cart-remove-btn')) {
        e.preventDefault();
        const btn = e.target;
        const productId = btn.dataset.productId;
        const csrfToken = btn.dataset.csrfToken; // Récupérer le jeton CSRF

        const formData = new FormData();
        formData.append('_token', csrfToken);

        fetch(`/cart/remove/${productId}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Supprime la ligne du tableau
                const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                if (row) row.remove();
                // Met à jour le total du panier
                const total = document.querySelector('#cart-total');
                if (total) total.textContent = data.total.toLocaleString('fr-FR', {minimumFractionDigits: 2});
                // Met à jour le badge du panier
                updateCartBadge(data.cartCount);
                // Si le panier est vide, affiche le message
                if (data.cartCount === 0) {
                    const container = document.querySelector('.cart-main-container');
                    if (container) container.innerHTML = '<h1 class="cart-title">Votre panier</h1><p class="cart-empty">Votre panier est vide.</p>';
                }
                showFlash('Article retiré du panier.', 'success');
            } else {
                showFlash(data.error || 'Erreur lors de la suppression', 'error');
            }
        })
        .catch(() => showFlash('Erreur réseau lors de la suppression', 'error'));
    }

    // --- FAVORIS (like/unlike) ---
    if (e.target.classList.contains('like-button')) {
        e.preventDefault();
        const btn = e.target;
        const productId = btn.dataset.productId || btn.getAttribute('data-product-id');
        const csrfToken = btn.dataset.csrfToken; // Récupérer le jeton CSRF

        const formData = new FormData();
        formData.append('_token', csrfToken);

        fetch(`/favorite/toggle/${productId}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Toggle l'état du bouton
                btn.classList.toggle('active', data.isLiked);
                // Met à jour le compteur de likes si présent
                const countSpan = btn.querySelector('.likes-count');
                if (countSpan) countSpan.textContent = data.likesCount;
                showFlash(data.isLiked ? 'Ajouté aux favoris.' : 'Retiré des favoris.', 'success');
            } else {
                showFlash(data.error || 'Erreur favoris', 'error');
            }
        })
        .catch(() => showFlash('Erreur réseau favoris', 'error'));
    }
});

// --- Changement d'affichage de la grille Derniers ajouts ---
document.querySelectorAll('.home-latest-display-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        // Retire la classe active des autres boutons
        document.querySelectorAll('.home-latest-display-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // Change la classe de la grille
        const cols = this.dataset.cols;
        const grid = document.querySelector('.home-latest-grid');
        if (grid) {
            grid.classList.remove('home-latest-grid-2', 'home-latest-grid-3', 'home-latest-grid-4');
            grid.classList.add('home-latest-grid-' + cols);
        }
    });
});

// Fonction utilitaire pour afficher un message flash
function showFlash(message, type = 'success') {
    let flash = document.createElement('div');
    flash.className = 'alert alert-' + type;
    flash.textContent = message;
    flash.setAttribute('role', 'alert');
    document.body.appendChild(flash);
    setTimeout(() => flash.remove(), 2500);
}

// --- Utilitaires réutilisables ---
function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) badge.textContent = count;
}

// --- Filtrage dynamique des cartes Pokémon par extension ---
const extensionSelect = document.querySelector('[data-extension-target="extensionSelect"]');
const pokemonCardSelect = document.querySelector('[data-pokemon-card-target="pokemonCardSelect"]');
const mediaPreviewBlock = document.querySelector('.media-preview-block');

if (extensionSelect && pokemonCardSelect) {
    // Fonction pour mettre à jour les options de pokemonCardSelect
    const updatePokemonCards = async (extensionName) => {
        pokemonCardSelect.innerHTML = '<option value="">Chargement...</option>';
        pokemonCardSelect.disabled = true;
        mediaPreviewBlock.innerHTML = '<span class="media-preview-text">Aperçu image/vidéo<br><small>(Chargement...)</small></span>';

        if (!extensionName) {
            pokemonCardSelect.innerHTML = '<option value="">Sélectionnez une extension d\'abord</option>';
            pokemonCardSelect.disabled = false;
            mediaPreviewBlock.innerHTML = '<span class="media-preview-text">Aperçu image/vidéo<br><small>(à venir)</small></span>';
            return;
        }

        try {
            const response = await fetch(`/product/api/pokemon-cards-by-extension/${extensionName}`);
            const cards = await response.json();

            pokemonCardSelect.innerHTML = '<option value="">Sélectionnez une carte</option>';
            cards.forEach(card => {
                const option = document.createElement('option');
                option.value = card.id;
                option.textContent = card.name;
                option.dataset.imageUrl = card.imageUrl; // Stocker l'URL de l'image
                pokemonCardSelect.appendChild(option);
            });
            pokemonCardSelect.disabled = false;
        } catch (error) {
            console.error('Erreur lors du chargement des cartes Pokémon:', error);
            pokemonCardSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            pokemonCardSelect.disabled = false;
            mediaPreviewBlock.innerHTML = '<span class="media-preview-text">Aperçu image/vidéo<br><small>(Erreur de chargement)</small></span>';
        }
    };

    // Écouter les changements sur le sélecteur d'extension
    extensionSelect.addEventListener('change', (event) => {
        updatePokemonCards(event.target.value);
    });

    // Écouter les changements sur le sélecteur de carte Pokémon pour afficher l'image
    pokemonCardSelect.addEventListener('change', (event) => {
        const selectedOption = pokemonCardSelect.options[pokemonCardSelect.selectedIndex];
        const imageUrl = selectedOption.dataset.imageUrl;

        if (imageUrl) {
            mediaPreviewBlock.innerHTML = `<img src="/media/images/${imageUrl}" alt="Aperçu de la carte" style="max-width: 100%; height: auto;">`;
        } else {
            mediaPreviewBlock.innerHTML = '<span class="media-preview-text">Aperçu image/vidéo<br><small>(Image non disponible)</small></span>';
        }
    });

    // Initialiser les cartes si une extension est déjà sélectionnée (par exemple, après une erreur de validation)
    if (extensionSelect.value) {
        updatePokemonCards(extensionSelect.value);
    }
}

// --- Remplissage automatique des champs de produit par numéro de carte ---
document.addEventListener('DOMContentLoaded', function() {
    // On cible le formulaire d'ajout de produit spécifiquement
    const addProductForm = document.querySelector('.product-add-form');
    if (!addProductForm) {
        return; // Ne rien faire si on n'est pas sur la bonne page
    }

    // Cible le nouveau champ de saisie du numéro de carte
    const numberInput = addProductForm.querySelector('#product_form_pokemon_card_number');
    const titleInput = addProductForm.querySelector('input[id^="product_form_title"]');
    const pokemonCardSelect = addProductForm.querySelector('[data-pokemon-card-target="pokemonCardSelect"]');
    const imagePreview = addProductForm.querySelector('[data-pokemon-card-image-target="imagePreview"]');

    if (numberInput && titleInput && pokemonCardSelect && imagePreview) {
        numberInput.addEventListener('blur', function() {
            const number = this.value.trim();
            if (number) {
                fetch(`/product/api/pokemon-card-details/${number}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Carte non trouvée');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Remplir le titre du produit avec le nom de la carte
                        if (data.name) titleInput.value = data.name;

                        // Sélectionner la carte Pokémon dans le champ EntityType
                        if (data.id) {
                            // Assurez-vous que l'option existe avant de la sélectionner
                            // La logique de `updatePokemonCards` doit avoir déjà peuplé le select
                            // Si l'extension n'est pas déjà sélectionnée, cela peut poser problème
                            // Il faudrait peut-être déclencher la sélection de l'extension d'abord
                            // ou s'assurer que toutes les cartes sont chargées au départ (moins performant)
                            setSelectValue(pokemonCardSelect, data.id, true); // true pour comparer par value (ID)
                        }

                        // Afficher l'image
                        if (data.image && data.image.imageUrl) {
                            imagePreview.src = `/media/images/${data.image.imageUrl}`;
                            imagePreview.style.display = 'block';
                        } else {
                            imagePreview.style.display = 'none';
                        }

                    })
                    .catch(error => {
                        console.error('Erreur lors de la récupération des détails de la carte:', error);
                        // Optionnel: réinitialiser les champs si la carte n'est pas trouvée
                        titleInput.value = '';
                        setSelectValue(pokemonCardSelect, '');
                        imagePreview.style.display = 'none';
                    });
            }
        });
    }

    // Fonction utilitaire pour définir la valeur d'un <select>
    function setSelectValue(selectElement, value, byValue = false) {
        let option;
        if (byValue) {
            option = Array.from(selectElement.options).find(opt => opt.value === String(value));
        } else {
            option = Array.from(selectElement.options).find(opt => opt.text.toLowerCase() === value.toLowerCase() || opt.value.toLowerCase() === value.toLowerCase());
        }
        
        if (option) {
            option.selected = true;
            // Déclencher un événement change pour s'assurer que les écouteurs associés réagissent
            selectElement.dispatchEvent(new Event('change'));
        }
    }
});

// --- Gestion de l'aperçu dynamique des médias ---
document.querySelectorAll('.media-collection-wrapper').forEach(wrapper => {
    const addButton = wrapper.querySelector('.add-media-button');
    const previewsContainer = wrapper.querySelector('.media-previews');
    const form = wrapper.closest('form');

    let index = wrapper.querySelectorAll('.media-item').length; // Compte les éléments existants

    const addMediaForm = (file = null, imageUrl = null) => {
        const prototype = wrapper.dataset.prototype;
        const newFormHtml = prototype.replace(/__name__/g, index);
        const newFormElement = document.createElement('div');
        newFormElement.innerHTML = newFormHtml;
        newFormElement.classList.add('media-item');
        wrapper.insertBefore(newFormElement, addButton); // Insère avant le bouton

        const fileInput = newFormElement.querySelector('input[type="file"]');
        const webcamImageInput = newFormElement.querySelector('.webcam-image-input'); // Assuming this class exists in the prototype

        if (file) {
            // If a File object is provided (from file input or webcam)
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            
            // Display preview for the added file
            displayMediaPreview(file, previewsContainer);
        } else if (imageUrl) {
            // If an imageUrl is provided (for existing media)
            displayMediaPreview(imageUrl, previewsContainer, true);
        }

        // Add event listener for file input change
        if (fileInput) {
            fileInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    displayMediaPreview(file, previewsContainer);
                }
            });
        }

        index++;
    };

    if (addButton) {
        addButton.addEventListener('click', () => addMediaForm());
    }

    // Function to display media preview
    const displayMediaPreview = (source, container, isUrl = false) => {
        let previewElement;
        let previewWrapper = document.createElement('div');
        previewWrapper.classList.add('media-preview-item');

        if (isUrl) {
            previewElement = document.createElement('img');
            previewElement.src = source.startsWith('http') ? source : `/upload/products/${source}`;
            previewElement.style.maxWidth = '100px';
            previewElement.style.maxHeight = '100px';
        } else {
            const file = source;
            if (file.type.startsWith('image/')) {
                previewElement = document.createElement('img');
                const reader = new FileReader();
                reader.onload = (e) => { previewElement.src = e.target.result; };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                previewElement = document.createElement('video');
                previewElement.controls = true;
                const reader = new FileReader();
                reader.onload = (e) => { previewElement.src = e.target.result; };
                reader.readAsDataURL(file);
            } else {
                previewElement = document.createElement('span');
                previewElement.textContent = `Fichier: ${file.name}`;
            }
            previewElement.style.maxWidth = '100px';
            previewElement.style.maxHeight = '100px';
        }
        
        previewWrapper.appendChild(previewElement);
        container.appendChild(previewWrapper);
    };

    // Gérer les médias existants lors du chargement de la page (pour la page d'édition)
    wrapper.querySelectorAll('input[type="file"]').forEach(fileInput => {
        const mediaId = fileInput.id.match(/_(\d+)_file$/);
        if (mediaId && fileInput.dataset.imageUrl) {
            const imageUrl = fileInput.dataset.imageUrl;
            displayMediaPreview(imageUrl, previewsContainer, true);
        }
    });
});

// --- Logique de paiement Stripe ---
document.addEventListener('DOMContentLoaded', function() {
    const paymentPage = document.querySelector('.container.mt-5'); // Cibler un élément unique à la page de paiement
    if (!paymentPage || typeof Stripe === 'undefined' || !clientSecret || !stripePublicKey || !confirmOrderUrl) {
        return; // Ne rien faire si on n'est pas sur la page de paiement ou si les variables ne sont pas définies
    }

    const stripe = Stripe(stripePublicKey);
    const elements = stripe.elements({ clientSecret });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    const submitButton = document.getElementById('submit');
    const paymentMessage = document.getElementById('payment-message');

    submitButton.addEventListener('click', async (e) => {
        e.preventDefault();
        submitButton.disabled = true;
        paymentMessage.textContent = '';

        const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: window.location.origin + confirmOrderUrl,
            },
            redirect: 'if_required',
        });

        if (error) {
            if (error.type === "card_error" || error.type === "validation_error") {
                paymentMessage.textContent = error.message;
            } else {
                paymentMessage.textContent = "Une erreur inattendue est survenue.";
            }
            submitButton.disabled = false;
        } else {
            // Le paiement a été confirmé côté client par Stripe.
            // Maintenant, finaliser la commande côté serveur.
            fetch(confirmOrderUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ /* Pas besoin d'envoyer de données ici, tout est en session */ })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFlash(data.message, 'success');
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    showFlash(data.message, 'error');
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                }
            })
            .catch(error => {
                console.error('Erreur lors de la finalisation de la commande:', error);
                showFlash('Erreur réseau lors de la finalisation de la commande.', 'error');
            });
        }
    });
});