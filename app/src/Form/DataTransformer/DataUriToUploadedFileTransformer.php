<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DataUriToUploadedFileTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): mixed
    {
        // Transforme un UploadedFile en Data URI (non utilisé pour ce cas)
        return $value;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        // Vérifie si la valeur est une Data URI
        if (str_starts_with($value, 'data:')) {
            // Extrait le type MIME et les données base64
            list($type, $data) = explode(';base64,', $value);
            list(, $mime) = explode(':', $type);

            // Décode les données base64
            $decodedData = base64_decode($data);

            // Crée un fichier temporaire
            $tmpFile = tempnam(sys_get_temp_dir(), 'webcam_upload');
            file_put_contents($tmpFile, $decodedData);

            // Crée un objet UploadedFile
            // Le nom de fichier et le type MIME sont déduits ou génériques
            return new UploadedFile(
                $tmpFile,
                'webcam_image.png', // Nom de fichier générique
                $mime,
                null, // Taille du fichier (sera calculée automatiquement)
                true // Indique que le fichier est un fichier de test (temporaire)
            );
        }

        return $value;
    }
}
