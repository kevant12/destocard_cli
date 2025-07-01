<?php

namespace App\Service;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MediaUploadService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
        private ParameterBagInterface $params
    ) {
    }

    /**
     * Upload une image et retourne l'entité Media correspondante
     */
    public function uploadImage(UploadedFile $file, string $directory = 'uploads'): Media
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Créer le répertoire de destination s'il n'existe pas
        $uploadDirectory = $this->getUploadDirectory() . '/' . $directory;
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        try {
            $file->move($uploadDirectory, $fileName);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload du fichier : ' . $e->getMessage());
        }

        // Créer l'entité Media
        $media = new Media();
        $media->setImageUrl($directory . '/' . $fileName);

        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    /**
     * Supprime une image et son entité Media
     */
    public function deleteImage(Media $media): bool
    {
        try {
            // Supprimer le fichier physique
            if ($media->getImageUrl()) {
                $filePath = $this->getUploadDirectory() . '/' . $media->getImageUrl();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Supprimer l'entité
            $this->em->remove($media);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la suppression du fichier : ' . $e->getMessage());
        }
    }

    /**
     * Retourne le répertoire d'upload configuré
     */
    private function getUploadDirectory(): string
    {
        return $this->params->get('kernel.project_dir') . '/public/uploads';
    }

    /**
     * Retourne l'URL publique d'un média
     */
    public function getPublicUrl(Media $media): ?string
    {
        if (!$media->getImageUrl()) {
            return null;
        }

        return '/uploads/' . $media->getImageUrl();
    }

    /**
     * Valide un fichier image
     */
    public function validateImageFile(UploadedFile $file): array
    {
        $errors = [];

        // Vérifier la taille (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $errors[] = 'Le fichier ne peut pas dépasser 5MB';
        }

        // Vérifier le type MIME
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $errors[] = 'Type de fichier non autorisé. Utilisez JPEG, PNG, WebP ou GIF';
        }

        // Vérifier l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Extension de fichier non autorisée';
        }

        return $errors;
    }

    /**
     * Redimensionne une image si nécessaire
     */
    public function resizeImage(string $filePath, int $maxWidth = 800, int $maxHeight = 600): bool
    {
        try {
            $imageInfo = getimagesize($filePath);
            if (!$imageInfo) {
                return false;
            }

            [$width, $height, $type] = $imageInfo;

            // Si l'image est déjà plus petite, ne pas la redimensionner
            if ($width <= $maxWidth && $height <= $maxHeight) {
                return true;
            }

            // Calculer les nouvelles dimensions en conservant le ratio
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            // Créer l'image source
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($filePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($filePath);
                    break;
                case IMAGETYPE_WEBP:
                    $source = imagecreatefromwebp($filePath);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($filePath);
                    break;
                default:
                    return false;
            }

            if (!$source) {
                return false;
            }

            // Créer l'image de destination
            $destination = imagecreatetruecolor($newWidth, $newHeight);

            // Préserver la transparence pour PNG et GIF
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
                $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
                imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Redimensionner
            imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Sauvegarder
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($destination, $filePath, 85);
                    break;
                case IMAGETYPE_PNG:
                    $result = imagepng($destination, $filePath, 6);
                    break;
                case IMAGETYPE_WEBP:
                    $result = imagewebp($destination, $filePath, 85);
                    break;
                case IMAGETYPE_GIF:
                    $result = imagegif($destination, $filePath);
                    break;
                default:
                    $result = false;
            }

            // Nettoyer la mémoire
            imagedestroy($source);
            imagedestroy($destination);

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }
}

