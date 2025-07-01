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
        const numberInput = document.querySelector('#product_form_pokemon_card_number');

        if (selectedOption.value) {
            // Récupérer les détails de la carte sélectionnée
            fetch(`/product/api/pokemon-card/${selectedOption.value}`)
                .then(response => response.json())
                .then(data => {
                    if (data.number && numberInput) {
                        numberInput.value = data.number;
                    }
                    if (imageUrl) {
                        mediaPreviewBlock.innerHTML = `<img src="/media/images/${imageUrl}" alt="Aperçu de la carte" style="max-width: 100%; height: auto;">`;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération du numéro de carte:', error);
                    if (imageUrl) {
                        mediaPreviewBlock.innerHTML = `<img src="/media/images/${imageUrl}" alt="Aperçu de la carte" style="max-width: 100%; height: auto;">`;
                    } else {
                        mediaPreviewBlock.innerHTML = '<span class="media-preview-text">Aperçu image/vidéo<br><small>(Image non disponible)</small></span>';
                    }
                });
        } else {
            if (numberInput) {
                numberInput.value = '';
            }
            mediaPreviewBlock.innerHTML = '<span class="media-preview-text">Aperçu image/vidéo<br><small>(Image non disponible)</small></span>';
        }
    });

    // Initialiser les cartes si une extension est déjà sélectionnée (par exemple, après une erreur de validation)
    if (extensionSelect.value) {
        updatePokemonCards(extensionSelect.value);
    }
}

// --- Remplissage automatique des champs de produit par numéro de carte ---
const addProductForm = document.querySelector('.product-add-form');
if (addProductForm) {
    // Cible le nouveau champ de saisie du numéro de carte
    const numberInput = addProductForm.querySelector('#product_form_pokemon_card_number');
    const titleInput = addProductForm.querySelector('input[id^="product_form_title"]');
    const pokemonCardSelect = addProductForm.querySelector('[data-pokemon-card-target="pokemonCardSelect"]');
    const imagePreview = addProductForm.querySelector('[data-pokemon-card-image-target="imagePreview"]');
    const extensionSelect = addProductForm.querySelector('[data-extension-target="extensionSelect"]');

    if (numberInput && titleInput && pokemonCardSelect && imagePreview && extensionSelect) {
        // Créer ou cibler la div d'erreur sous le champ numéro
        let errorDiv = document.getElementById('card-number-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'card-number-error';
            errorDiv.style.color = '#ff9800';
            errorDiv.style.fontWeight = 'bold';
            errorDiv.style.marginTop = '0.3rem';
            numberInput.parentNode.appendChild(errorDiv);
        }

        function showCardNumberError(msg) {
            errorDiv.textContent = msg;
            errorDiv.style.display = 'block';
            setTimeout(() => { errorDiv.style.display = 'none'; }, 4000);
        }

        numberInput.addEventListener('blur', function() {
            const number = this.value.trim();
            const extensionId = extensionSelect.value;
            if (!number) {
                showCardNumberError('Veuillez saisir un numéro de carte.');
                return;
            }
            if (!extensionId) {
                showCardNumberError('Veuillez d\'abord sélectionner une extension.');
                return;
            }
            fetch(`/api/pokemon-card?extensionId=${extensionId}&cardNumber=${encodeURIComponent(number)}`)
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 404) {
                            showCardNumberError('Aucune carte trouvée avec ce numéro dans cette extension.');
                        } else {
                            showCardNumberError('Erreur lors de la requête API (code ' + response.status + ').');
                        }
                        throw new Error('Carte non trouvée ou erreur API');
                    }
                    return response.json();
                })
                .then(data => {
                    let missingField = null;
                    if (!data.name) missingField = 'nom';
                    else if (!data.extension || !data.extension.id) missingField = 'extension';
                    else if (!data.rarityText) missingField = 'rareté';
                    if (missingField) {
                        showCardNumberError('Champ manquant dans la base : ' + missingField);
                        return;
                    }
                    // Remplir le titre du produit avec le nom de la carte
                    if (data.name) titleInput.value = data.name;
                    // Sélectionner l'extension
                    if (data.extension && data.extension.id) {
                        setSelectValue(extensionSelect, data.extension.id, true);
                    }
                    // Sélectionner la carte (après que le select des cartes soit mis à jour)
                    if (data.id) {
                        setTimeout(() => setSelectValue(pokemonCardSelect, data.id, true), 300);
                    }
                    // Remplir la rareté si un champ existe
                    const rarityInput = document.querySelector('#product_form_rarity');
                    if (rarityInput && data.rarityText) {
                        rarityInput.value = data.rarityText;
                    }
                    // Remplir d'autres champs si besoin (catégorie, sous-série, etc.)
                    const categoryInput = document.querySelector('#product_form_category');
                    if (categoryInput && data.category) {
                        categoryInput.value = data.category;
                    }
                    const subSerieInput = document.querySelector('#product_form_subSerie');
                    if (subSerieInput && data.subSerie) {
                        subSerieInput.value = data.subSerie;
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
                    // Optionnel: réinitialiser les champs si la carte n'est pas trouvée
                    titleInput.value = '';
                    setSelectValue(pokemonCardSelect, '');
                    imagePreview.style.display = 'none';
                });
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
}

// --- Gestion de l'ajout de produit et des médias (avec defer) ---
const productForm = document.querySelector('form.product-add-form');

if (productForm) {
    // --- Gestionnaire de la collection de médias ---
    const mediaCollectionWrapper = document.querySelector('.media-collection-wrapper');
    const addMediaButton = mediaCollectionWrapper?.querySelector('.add-media-button');
    const previewsContainer = document.getElementById('media-previews-add');
    let mediaIndex = mediaCollectionWrapper?.querySelectorAll('.media-item').length || 0;

    // Fonction pour convertir une data URI en objet File
    const dataURItoFile = (dataURI, filename) => {
        const byteString = atob(dataURI.split(',')[1]);
        const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
        const ab = new ArrayBuffer(byteString.length);
        const ia = new Uint8Array(ab);
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        return new File([ab], filename, { type: mimeString });
    };

    // Fonction pour afficher un aperçu et retourner le conteneur de l'aperçu
    const displayPreview = (file) => {
        if (!previewsContainer) return;

        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'media-preview-item';

        let previewElement;
        if (file.type.startsWith('image/')) {
            previewElement = document.createElement('img');
            previewElement.src = URL.createObjectURL(file);
        } else {
            return null; // Ne gère que les images pour l'instant
        }
        previewElement.style.maxWidth = '100px';
        previewElement.style.maxHeight = '100px';

        const removeButton = document.createElement('button');
        removeButton.className = 'btn btn-sm btn-danger remove-media-btn';
        removeButton.textContent = 'X';
        removeButton.type = 'button';

        previewWrapper.appendChild(previewElement);
        previewWrapper.appendChild(removeButton);
        previewsContainer.appendChild(previewWrapper);

        return previewWrapper;
    };

    // Fonction pour ajouter un nouveau formulaire de média
    const addMediaForm = (file = null) => {
        const prototype = mediaCollectionWrapper.dataset.prototype;
        if (!prototype) return;

        const newFormHtml = prototype.replace(/__name__/g, mediaIndex);
        const newFormContainer = document.createElement('div');
        newFormContainer.className = 'media-item d-none'; // Caché par défaut
        newFormContainer.innerHTML = newFormHtml;

        const fileInput = newFormContainer.querySelector('input[type=file]');
        mediaCollectionWrapper.insertBefore(newFormContainer, addMediaButton);
        mediaIndex++;

        if (file) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;

            const previewWrapper = displayPreview(file);
            if (previewWrapper) {
                previewWrapper.querySelector('.remove-media-btn').addEventListener('click', () => {
                    newFormContainer.remove();
                    previewWrapper.remove();
                });
            }
        }

        return fileInput;
    };

    // Clic sur "Ajouter une photo"
    addMediaButton?.addEventListener('click', () => {
        const fileInput = addMediaForm();
        if (fileInput) {
            fileInput.click(); // Ouvre le sélecteur de fichier

            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    const previewWrapper = displayPreview(e.target.files[0]);
                     if (previewWrapper) {
                        previewWrapper.querySelector('.remove-media-btn').addEventListener('click', () => {
                            fileInput.closest('.media-item').remove();
                            previewWrapper.remove();
                        });
                    }
                }
            });
        }
    });

    // --- Gestion de la webcam (photo) ---
    const startWebcamBtn = document.getElementById('start-webcam');
    const capturePhotoBtn = document.getElementById('capture-photo');
    const retakePhotoBtn = document.getElementById('retake-photo');
    const addCapturedPhotoBtn = document.getElementById('add-captured-photo');
    const webcamVideo = document.getElementById('webcam-video');
    const webcamCanvas = document.getElementById('webcam-canvas');
    let photoStream = null;

    startWebcamBtn?.addEventListener('click', async () => {
        try {
            photoStream = await navigator.mediaDevices.getUserMedia({ video: true });
            webcamVideo.srcObject = photoStream;
            webcamVideo.style.display = 'block';
            startWebcamBtn.style.display = 'none';
            capturePhotoBtn.style.display = 'inline-block';
        } catch (err) { alert(`Erreur webcam: ${err.message}`); }
    });

    capturePhotoBtn?.addEventListener('click', () => {
        webcamCanvas.width = webcamVideo.videoWidth;
        webcamCanvas.height = webcamVideo.videoHeight;
        webcamCanvas.getContext('2d').drawImage(webcamVideo, 0, 0);
        webcamVideo.style.display = 'none';
        webcamCanvas.style.display = 'block';
        capturePhotoBtn.style.display = 'none';
        retakePhotoBtn.style.display = 'inline-block';
        addCapturedPhotoBtn.style.display = 'inline-block';
        if (photoStream) {
            photoStream.getTracks().forEach(track => track.stop());
            photoStream = null;
        }
    });

    retakePhotoBtn?.addEventListener('click', () => {
        webcamCanvas.style.display = 'none';
        startWebcamBtn.style.display = 'inline-block';
        retakePhotoBtn.style.display = 'none';
        addCapturedPhotoBtn.style.display = 'none';
        startWebcamBtn.click();
    });

    addCapturedPhotoBtn?.addEventListener('click', () => {
        const dataURI = webcamCanvas.toDataURL('image/png');
        const file = dataURItoFile(dataURI, `capture-${Date.now()}.png`);
        addMediaForm(file);

        // Réinitialiser l'interface de capture
        webcamCanvas.style.display = 'none';
        retakePhotoBtn.style.display = 'none';
        addCapturedPhotoBtn.style.display = 'none';
        startWebcamBtn.style.display = 'inline-block';
        alert('Photo ajoutée !');
    });
}
