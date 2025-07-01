import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour gérer le formulaire d'ajout/édition de produit.
 * Gère l'ajout de médias via upload et webcam.
 */
export default class extends Controller {
    static targets = [
        'mediaCollection', // Le conteneur pour les formulaires de médias
        'previews',        // Le conteneur pour les aperçus
        'webcamVideo',     // L'élément <video> de la webcam
        'webcamCanvas',    // L'élément <canvas> pour la capture
        'webcamWrapper',   // Le conteneur global de la webcam
        'startWebcamBtn',  // Bouton pour démarrer la webcam
        'capturePhotoBtn', // Bouton pour prendre la photo
        'retakePhotoBtn',  // Bouton pour reprendre la photo
    ];

    static values = {
        prototype: String, // Le prototype du formulaire de collection
    };

    connect() {
        this.mediaIndex = this.mediaCollectionTarget.children.length;
        this.photoStream = null;
    }

    disconnect() {
        this.stopWebcam();
    }

    /**
     * Ajoute un nouveau champ de formulaire pour un fichier et déclenche le clic.
     */
    addFile(event) {
        event.preventDefault();
        const formContainer = this.addMediaFormPrototype();
        if (!formContainer) return;

        const fileInput = formContainer.querySelector('input[type=file]');
        fileInput.click();

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const previewWrapper = this.displayPreview(file);
                if (previewWrapper) {
                    previewWrapper.querySelector('[data-action="product-form#removeMedia"]').addEventListener('click', () => {
                        formContainer.remove();
                        previewWrapper.remove();
                    });
                }
            } else {
                formContainer.remove(); // L'utilisateur a annulé
            }
        });
    }

    /**
     * Démarre la webcam.
     */
    async startWebcam() {
        try {
            this.photoStream = await navigator.mediaDevices.getUserMedia({ video: true });
            this.webcamVideoTarget.srcObject = this.photoStream;
            this.webcamWrapperTarget.classList.add('is-capturing');
        } catch (err) {
            alert(`Erreur d'accès à la webcam: ${err.message}`);
        }
    }

    /**
     * Arrête la webcam.
     */
    stopWebcam() {
        if (this.photoStream) {
            this.photoStream.getTracks().forEach(track => track.stop());
            this.photoStream = null;
        }
    }

    /**
     * Capture une photo depuis la webcam.
     */
    capturePhoto() {
        const canvas = this.webcamCanvasTarget;
        const video = this.webcamVideoTarget;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        const dataURI = canvas.toDataURL('image/png');
        this.stopWebcam();
        this.webcamWrapperTarget.classList.remove('is-capturing');
        this.webcamWrapperTarget.classList.add('is-captured');

        // Stocke la data URI temporairement
        this.webcamCanvasTarget.dataset.capturedData = dataURI;
    }
    
    /**
     * Ajoute la photo capturée au formulaire.
     */
    addCapturedPhoto() {
        const dataURI = this.webcamCanvasTarget.dataset.capturedData;
        if (!dataURI) return;

        const formContainer = this.addMediaFormPrototype();
        if (!formContainer) return;

        const fileInput = formContainer.querySelector('input[type=file]');
        fileInput.type = 'hidden';
        fileInput.value = dataURI;

        const previewWrapper = this.displayPreview(dataURI);
        if (previewWrapper) {
            previewWrapper.querySelector('[data-action="product-form#removeMedia"]').addEventListener('click', () => {
                formContainer.remove();
                previewWrapper.remove();
            });
        }

        this.resetWebcamCapture();
        alert('Photo ajoutée !');
    }

    /**
     * Réinitialise l'interface de la webcam pour une nouvelle capture.
     */
    resetWebcamCapture() {
        this.webcamWrapperTarget.classList.remove('is-captured');
        this.webcamCanvasTarget.dataset.capturedData = '';
        this.startWebcam(); // On peut relancer directement la webcam
    }

    /**
     * Crée et ajoute un nouveau champ de formulaire MediaType.
     * @returns {HTMLElement|null}
     */
    addMediaFormPrototype() {
        const prototype = this.prototypeValue;
        if (!prototype) return null;

        const newFormHtml = prototype.replace(/__name__/g, this.mediaIndex);
        const newFormContainer = document.createElement('div');
        newFormContainer.className = 'media-item';
        newFormContainer.style.display = 'none';
        newFormContainer.innerHTML = newFormHtml;

        this.mediaCollectionTarget.appendChild(newFormContainer);
        this.mediaIndex++;

        return newFormContainer;
    }

    /**
     * Affiche un aperçu d'une image.
     * @param {File|string} source
     * @returns {HTMLElement|null}
     */
    displayPreview(source) {
        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'media-preview-item';

        const img = document.createElement('img');
        img.src = (typeof source === 'string') ? source : URL.createObjectURL(source);
        img.style.maxWidth = '100px';
        img.style.maxHeight = '100px';
        img.onload = () => {
            if (typeof source !== 'string') URL.revokeObjectURL(img.src);
        };

        const removeButton = document.createElement('button');
        removeButton.className = 'btn btn-sm btn-danger';
        removeButton.textContent = '×';
        removeButton.type = 'button';
        removeButton.dataset.action = 'product-form#removeMedia';

        previewWrapper.appendChild(img);
        previewWrapper.appendChild(removeButton);
        this.previewsTarget.appendChild(previewWrapper);

        return previewWrapper;
    }
}
