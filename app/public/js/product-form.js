/**
 * DESTOCARD - PRODUCT FORM MANAGEMENT
 * Gestion des formulaires de produits avec webcam et prévisualisation
 * 
 * Script chargé avec defer - fonctions disponibles pour utilisation manuelle
 */

/**
 * Initialise la prévisualisation des images
 */
function initImagePreview() {
    const fileInput = document.querySelector('input[type="file"][multiple]');
    const previewContainer = document.getElementById('image-preview-container');
    
    if (!fileInput || !previewContainer) return;
    
    fileInput.addEventListener('change', function() {
        previewContainer.innerHTML = '';
        
        Array.from(this.files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.className = 'img-preview';
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                previewContainer.appendChild(img);
            }
        });
    });
}

/**
 * Initialise la gestion de la webcam
 */
function initWebcamModal() {
    const webcamButton = document.getElementById('webcam-button');
    const modal = document.getElementById('webcam-modal');
    const video = document.getElementById('webcam-video');
    const canvas = document.getElementById('webcam-canvas');
    const captureButton = document.getElementById('capture-button');
    const closeButton = document.getElementById('close-webcam-button');
    const fileInput = document.querySelector('input[type="file"][multiple]');
    
    if (!webcamButton || !modal || !video || !canvas || !captureButton || !closeButton || !fileInput) {
        return;
    }
    
    let stream = null;

    // Ouvrir la webcam
    webcamButton.addEventListener('click', async function() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
        } catch (err) {
            console.error('Erreur webcam:', err);
            alert('Impossible d\'accéder à la webcam : ' + err.message);
        }
    });

    // Capturer une photo
    captureButton.addEventListener('click', function() {
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0);
        
        canvas.toBlob(function(blob) {
            const file = new File([blob], 'webcam_capture.png', { type: 'image/png' });
            
            // Créer un nouveau FileList avec le fichier capturé
            const dt = new DataTransfer();
            // Ajouter les fichiers existants
            Array.from(fileInput.files).forEach(f => dt.items.add(f));
            // Ajouter la nouvelle capture
            dt.items.add(file);
            fileInput.files = dt.files;
            
            // Déclencher l'événement change pour la prévisualisation
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Fermer la modal
            closeWebcam();
        }, 'image/png');
    });

    // Fermer la webcam
    closeButton.addEventListener('click', closeWebcam);

    // Fermer la modal en cliquant à l'extérieur
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeWebcam();
        }
    });

    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeWebcam();
        }
    });

    /**
     * Ferme la webcam et la modal
     */
    function closeWebcam() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        video.srcObject = null;
    }
}
