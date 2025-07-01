<?php

namespace App\Service;

use App\Entity\Media;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MediaUploadService
{
    private string $basePath;
    private Filesystem $filesystem;

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        string $projectDir
    ) {
        $this->basePath = $projectDir . '/public/uploads';
        $this->filesystem = new Filesystem();
    }

    /**
     * Handles the upload of an image file, creates a Media entity, and saves it.
     *
     * @param UploadedFile $file The uploaded file object.
     * @param string $subDirectory The subdirectory within /public/uploads to store the file (e.g., 'pokemon_cards', 'products').
     * @return Media The persisted Media entity.
     * @throws FileException
     */
    public function uploadImage(UploadedFile $file, string $subDirectory): Media
    {
        $media = new Media();
        $media->setOriginalName($file->getClientOriginalName());
        $media->setMimeType($file->getMimeType());
        $media->setSize($file->getSize());
        $media->setFile($file); // Storing the file temporarily for potential further processing

        $fileName = $this->generateUniqueFileName($file);
        $media->setFileName($fileName);
        
        $targetDirectory = $this->getTargetDirectory($subDirectory);
        $media->setPath('/uploads/' . $subDirectory . '/' . $fileName);

        try {
            $file->move($targetDirectory, $fileName);
        } catch (FileException $e) {
            $this->logger->error('Failed to upload file: ' . $e->getMessage());
            throw new FileException('Une erreur est survenue lors du téléversement du fichier.');
        }

        // The Media entity is not persisted here, caller should persist it.
        return $media;
    }

    /**
     * Handles the upload of multiple images.
     *
     * @param UploadedFile[] $files
     * @param string $subDirectory
     * @return Media[]
     */
    public function uploadMultipleImages(array $files, string $subDirectory): array
    {
        $mediaEntities = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $mediaEntities[] = $this->uploadImage($file, $subDirectory);
            }
        }
        return $mediaEntities;
    }

    /**
     * Removes an image file and its associated Media entity.
     *
     * @param Media $media The Media entity to remove.
     * @return bool True on success, false on failure.
     */
    public function removeImage(Media $media): bool
    {
        $filePath = $this->basePath . str_replace('/uploads', '', $media->getPath());

        try {
            if ($this->filesystem->exists($filePath)) {
                $this->filesystem->remove($filePath);
            }
            
            // The Media entity should be removed from the database by the caller
            // $this->em->remove($media);
            // $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates a unique filename for the uploaded file.
     */
    private function generateUniqueFileName(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        return $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
    }

    /**
     * Returns the target directory path and ensures it exists.
     */
    private function getTargetDirectory(string $subDirectory = ''): string
    {
        $directory = rtrim($this->basePath . '/' . trim($subDirectory, '/'), '/');
        
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory);
        }

        return $directory;
    }

    /**
     * Get the full public URL for a media entity.
     *
     * @param Media|null $media
     * @return string|null
     */
    public function getUrl(?Media $media): ?string
    {
        if (!$media || !$media->getPath()) {
            return null;
        }
        // Assuming the app is hosted at the root of the domain.
        // Modify if your app is in a subdirectory.
        return $media->getPath();
    }

    /**
     * Uploads a file from a data URI.
     *
     * @param string $dataUri The data URI (e.g., from a webcam capture).
     * @param string $subDirectory The subdirectory for the upload.
     * @return Media The persisted Media entity.
     */
    public function uploadFromDataUri(string $dataUri, string $subDirectory): Media
    {
        // 1. Decode the data URI
        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri, $type)) {
            throw new \InvalidArgumentException('Invalid data URI format');
        }
        $imageData = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1));
        if ($imageData === false) {
            throw new \InvalidArgumentException('Base64 decode failed');
        }
        $imageType = strtolower($type[1]);
        if (!in_array($imageType, ['jpg', 'jpeg', 'gif', 'png'])) {
            throw new \InvalidArgumentException('Invalid image type');
        }

        // 2. Create a temporary file
        $tempFilePath = tempnam(sys_get_temp_dir(), 'data-uri-upload');
        file_put_contents($tempFilePath, $imageData);

        // 3. Create an UploadedFile instance
        $file = new UploadedFile(
            $tempFilePath,
            'capture.' . $imageType, // Original name
            'image/' . $imageType,  // Mime type
            null,
            true // Mark as test file to allow construction
        );
        
        // 4. Use the existing uploadImage method
        $media = $this->uploadImage($file, $subDirectory);

        // 5. The temporary file will be moved by uploadImage,
        // but if it fails, it's good practice to clean up.
        // The UploadedFile object should handle this when it's destructed.

        return $media;
    }
} 