/**
 * product-form.js
 * Gère l'ajout de médias (upload et webcam) pour le formulaire de produit.
 * Conçu pour être utilisé avec l'attribut defer, sans dépendances.
 */

// On attend que le DOM soit prêt, mais sans utiliser DOMContentLoaded pour rester simple
const productForm = document.querySelector('form.product-add-form');

if (productForm) {
    // --- Éléments du DOM ---
    const mediaCollectionWrapper = productForm.querySelector('.media-collection-wrapper');
    const addFileBtn = productForm.querySelector('.add-media-button');
    const previewsContainer = productForm.querySelector('.media-previews');

    // Webcam
    const startWebcamBtn = productForm.querySelector('.start-webcam-btn');
    const capturePhotoBtn = productForm.querySelector('.capture-photo-btn');
    const retakePhotoBtn = productForm.querySelector('.retake-photo-btn');
    const addCapturedPhotoBtn = productForm.querySelector('.add-captured-photo-btn');
    const webcamVideo = productForm.querySelector('.webcam-video');
    const webcamCanvas = productForm.querySelector('.webcam-canvas');
    const webcamWrapper = productForm.querySelector('.webcam-capture-section');

    let mediaIndex = mediaCollectionWrapper?.querySelectorAll('.media-item').length || 0;
    let photoStream = null;

    // --- Fonctions Utilitaires ---

    /**
     * Affiche un aperçu d'un fichier image et retourne l'élément d'aperçu.
     * @param {File|string} source - Un objet File ou une Data URI.
     * @returns {HTMLElement|null} L'élément wrapper de l'aperçu.
     */
    const displayPreview = (source) => {
        if (!previewsContainer) return null;

        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'media-preview-item';

        const img = document.createElement('img');
        img.src = (typeof source === 'string') ? source : URL.createObjectURL(source);
        
        img.onload = () => {
            if (typeof source !== 'string') {
                URL.revokeObjectURL(img.src); // Libère la mémoire pour les objets File
            }
        };

        const removeButton = document.createElement('button');
        removeButton.className = 'remove-media-btn';
        removeButton.textContent = '×';
        removeButton.title = 'Supprimer ce média';
        removeButton.type = 'button';

        previewWrapper.appendChild(img);
        previewWrapper.appendChild(removeButton);
        previewsContainer.appendChild(previewWrapper);

        return previewWrapper;
    };

    /**
     * Ajoute un nouveau champ de formulaire MediaType au DOM.
     * @returns {HTMLElement|null} Le conteneur du nouveau champ de formulaire.
     */
    const addMediaFormPrototype = () => {
        const prototype = mediaCollectionWrapper?.dataset.prototype;
        if (!prototype) {
            console.error("Le prototype de collection de médias est introuvable.");
            return null;
        }

        const newFormHtml = prototype.replace(/__name__/g, mediaIndex);
        const newFormContainer = document.createElement('div');
        newFormContainer.className = 'media-item';
        newFormContainer.style.display = 'none'; // Le champ est caché, géré par l'aperçu
        newFormContainer.innerHTML = newFormHtml;

        mediaCollectionWrapper.appendChild(newFormContainer);
        mediaIndex++;

        return newFormContainer;
    };

    // --- Logique Principale ---

    // 1. Ajout via sélecteur de fichier
    addFileBtn?.addEventListener('click', () => {
        const formContainer = addMediaFormPrototype();
        if (!formContainer) return;

        const fileInput = formContainer.querySelector('input[type=file]');
        fileInput.click(); // Ouvre le dialogue de fichier

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const previewWrapper = displayPreview(file);
                if (previewWrapper) {
                    previewWrapper.querySelector('.remove-media-btn').addEventListener('click', () => {
                        formContainer.remove();
                        previewWrapper.remove();
                    });
                }
            } else {
                formContainer.remove(); // Si l'utilisateur annule la sélection
            }
        });
    });

    // 2. Ajout via Webcam
    startWebcamBtn?.addEventListener('click', async () => {
        try {
            photoStream = await navigator.mediaDevices.getUserMedia({ video: true });
            webcamVideo.srcObject = photoStream;
            webcamWrapper.classList.add('is-capturing');
            webcamWrapper.classList.remove('is-captured');
        } catch (err) {
            alert(`Erreur d'accès à la webcam: ${err.message}`);
        }
    });

    capturePhotoBtn?.addEventListener('click', () => {
        webcamCanvas.width = webcamVideo.videoWidth;
        webcamCanvas.height = webcamVideo.videoHeight;
        webcamCanvas.getContext('2d').drawImage(webcamVideo, 0, 0);

        if (photoStream) {
            photoStream.getTracks().forEach(track => track.stop());
            photoStream = null;
        }
        webcamWrapper.classList.remove('is-capturing');
        webcamWrapper.classList.add('is-captured');
    });

    addCapturedPhotoBtn?.addEventListener('click', () => {
        const dataURI = webcamCanvas.toDataURL('image/png');
        const formContainer = addMediaFormPrototype();
        if (!formContainer) return;

        const fileInput = formContainer.querySelector('input[type=file]');
        // C'est ici que la magie opère pour le transformer
        fileInput.type = 'hidden';
        fileInput.value = dataURI;

        const previewWrapper = displayPreview(dataURI);
        if (previewWrapper) {
            previewWrapper.querySelector('.remove-media-btn').addEventListener('click', () => {
                formContainer.remove();
                previewWrapper.remove();
            });
        }
        
        webcamWrapper.classList.remove('is-captured');
        alert('Photo ajoutée !');
    });

    retakePhotoBtn?.addEventListener('click', () => {
        webcamWrapper.classList.remove('is-captured');
        startWebcamBtn.click();
    });
}
