const initWebcam = () => {
    const webcamButton = document.getElementById('webcam-button');
    const fileInput = document.querySelector('input[type="file"][id$="_imageFiles"]');
    const modal = document.getElementById('webcam-modal');
    const video = document.getElementById('webcam-video');
    const canvas = document.getElementById('webcam-canvas');
    const captureButton = document.getElementById('capture-button');
    const closeButton = document.getElementById('close-webcam-button');
    const previewContainer = document.getElementById('image-preview-container');

    if (!webcamButton || !fileInput || !modal || !video || !canvas || !captureButton || !closeButton || !previewContainer) {
        return;
    }

    let stream = null;

    const openWebcam = async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { width: 1280, height: 720 }, audio: false });
            video.srcObject = stream;
            modal.style.display = 'flex';
        } catch (err) {
            console.error("Erreur d'accès à la webcam : ", err);
            alert("Impossible d'accéder à la webcam. Veuillez vérifier les autorisations de votre navigateur.");
        }
    };

    const closeWebcam = () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        modal.style.display = 'none';
        video.srcObject = null;
    };

    const updatePreview = () => {
        previewContainer.innerHTML = ''; // Vider les anciennes prévisualisations
        const files = Array.from(fileInput.files);
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-preview');
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    };

    const captureImage = () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(blob => {
            const file = new File([blob], `webcam-${Date.now()}.png`, { type: 'image/png' });
            
            // Créer un DataTransfer pour gérer la liste de fichiers
            const dataTransfer = new DataTransfer();
            // Ajouter les fichiers déjà présents dans l'input
            Array.from(fileInput.files).forEach(f => dataTransfer.items.add(f));
            // Ajouter la nouvelle capture
            dataTransfer.items.add(file);

            fileInput.files = dataTransfer.files;
            
            // Mettre à jour la prévisualisation et fermer la modale
            updatePreview();
            closeWebcam();
        }, 'image/png');
    };

    webcamButton.addEventListener('click', openWebcam);
    closeButton.addEventListener('click', closeWebcam);
    captureButton.addEventListener('click', captureImage);
    // Mettre à jour la prévisualisation si l'utilisateur sélectionne des fichiers manuellement
    fileInput.addEventListener('change', updatePreview);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWebcam);
} else {
    initWebcam();
} 