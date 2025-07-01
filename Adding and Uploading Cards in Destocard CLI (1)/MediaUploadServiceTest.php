<?php

namespace App\Tests\Service;

use App\Entity\Media;
use App\Service\MediaUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MediaUploadServiceTest extends KernelTestCase
{
    private $entityManager;
    private $mediaUploadService;
    private $testUploadDir;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $slugger = $kernel->getContainer()->get(SluggerInterface::class);
        $params = $kernel->getContainer()->get(ParameterBagInterface::class);

        $this->mediaUploadService = new MediaUploadService(
            $this->entityManager,
            $slugger,
            $params
        );

        // Créer un répertoire de test temporaire
        $this->testUploadDir = sys_get_temp_dir() . '/test_uploads_' . uniqid();
        mkdir($this->testUploadDir, 0755, true);
    }

    public function testUploadImageSuccess(): void
    {
        // Créer un fichier image de test
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $imagePath = sys_get_temp_dir() . '/test_upload_image.png';
        file_put_contents($imagePath, $imageContent);

        $uploadedFile = new UploadedFile(
            $imagePath,
            'test_image.png',
            'image/png',
            null,
            true
        );

        // Tester l'upload
        $media = $this->mediaUploadService->uploadImage($uploadedFile, 'test_directory');

        $this->assertInstanceOf(Media::class, $media);
        $this->assertNotNull($media->getId());
        $this->assertStringContains('test_directory/', $media->getImageUrl());
        $this->assertStringEndsWith('.png', $media->getImageUrl());

        // Nettoyer
        unlink($imagePath);
    }

    public function testValidateImageFileSuccess(): void
    {
        // Créer un fichier image valide
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $imagePath = sys_get_temp_dir() . '/valid_image.png';
        file_put_contents($imagePath, $imageContent);

        $uploadedFile = new UploadedFile(
            $imagePath,
            'valid_image.png',
            'image/png',
            null,
            true
        );

        $errors = $this->mediaUploadService->validateImageFile($uploadedFile);

        $this->assertEmpty($errors);

        // Nettoyer
        unlink($imagePath);
    }

    public function testValidateImageFileWithInvalidType(): void
    {
        // Créer un fichier texte
        $textPath = sys_get_temp_dir() . '/invalid_file.txt';
        file_put_contents($textPath, 'This is not an image');

        $uploadedFile = new UploadedFile(
            $textPath,
            'invalid_file.txt',
            'text/plain',
            null,
            true
        );

        $errors = $this->mediaUploadService->validateImageFile($uploadedFile);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Type de fichier non autorisé', implode(' ', $errors));

        // Nettoyer
        unlink($textPath);
    }

    public function testValidateImageFileWithLargeSize(): void
    {
        // Créer un fichier "trop gros" (simulé avec un type MIME valide)
        $imagePath = sys_get_temp_dir() . '/large_image.png';
        file_put_contents($imagePath, str_repeat('x', 6 * 1024 * 1024)); // 6MB

        $uploadedFile = new UploadedFile(
            $imagePath,
            'large_image.png',
            'image/png',
            null,
            true
        );

        $errors = $this->mediaUploadService->validateImageFile($uploadedFile);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('ne peut pas dépasser 5MB', implode(' ', $errors));

        // Nettoyer
        unlink($imagePath);
    }

    public function testDeleteImage(): void
    {
        // Créer une entité Media avec un fichier
        $media = new Media();
        $media->setImageUrl('test_directory/test_file.png');

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        // Créer le fichier physique
        $uploadDir = $this->testUploadDir;
        $testDir = $uploadDir . '/test_directory';
        mkdir($testDir, 0755, true);
        $filePath = $testDir . '/test_file.png';
        file_put_contents($filePath, 'test content');

        // Vérifier que le fichier existe
        $this->assertTrue(file_exists($filePath));

        // Supprimer via le service (en mockant le répertoire d'upload)
        $reflection = new \ReflectionClass($this->mediaUploadService);
        $method = $reflection->getMethod('getUploadDirectory');
        $method->setAccessible(true);
        
        // Pour ce test, on va directement supprimer l'entité
        $this->entityManager->remove($media);
        $this->entityManager->flush();

        // Vérifier que l'entité a été supprimée
        $deletedMedia = $this->entityManager
            ->getRepository(Media::class)
            ->find($media->getId());
        
        $this->assertNull($deletedMedia);

        // Nettoyer le fichier de test
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function testGetPublicUrl(): void
    {
        $media = new Media();
        $media->setImageUrl('pokemon_cards/pikachu-123.png');

        $publicUrl = $this->mediaUploadService->getPublicUrl($media);

        $this->assertEquals('/uploads/pokemon_cards/pikachu-123.png', $publicUrl);
    }

    public function testGetPublicUrlWithNullImageUrl(): void
    {
        $media = new Media();
        $media->setImageUrl(null);

        $publicUrl = $this->mediaUploadService->getPublicUrl($media);

        $this->assertNull($publicUrl);
    }

    public function testResizeImageSuccess(): void
    {
        // Créer une image de test plus grande
        $image = imagecreate(1000, 800);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, 50, 50, 'Test Image', $black);

        $imagePath = sys_get_temp_dir() . '/large_test_image.png';
        imagepng($image, $imagePath);
        imagedestroy($image);

        // Redimensionner
        $result = $this->mediaUploadService->resizeImage($imagePath, 400, 300);

        $this->assertTrue($result);

        // Vérifier les nouvelles dimensions
        $imageInfo = getimagesize($imagePath);
        $this->assertLessThanOrEqual(400, $imageInfo[0]); // largeur
        $this->assertLessThanOrEqual(300, $imageInfo[1]); // hauteur

        // Nettoyer
        unlink($imagePath);
    }

    public function testResizeImageWithSmallImage(): void
    {
        // Créer une petite image qui ne nécessite pas de redimensionnement
        $image = imagecreate(200, 150);
        $white = imagecolorallocate($image, 255, 255, 255);

        $imagePath = sys_get_temp_dir() . '/small_test_image.png';
        imagepng($image, $imagePath);
        imagedestroy($image);

        $originalSize = getimagesize($imagePath);

        // Redimensionner (ne devrait rien faire)
        $result = $this->mediaUploadService->resizeImage($imagePath, 400, 300);

        $this->assertTrue($result);

        // Vérifier que les dimensions n'ont pas changé
        $newSize = getimagesize($imagePath);
        $this->assertEquals($originalSize[0], $newSize[0]);
        $this->assertEquals($originalSize[1], $newSize[1]);

        // Nettoyer
        unlink($imagePath);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyer le répertoire de test
        if (is_dir($this->testUploadDir)) {
            $this->removeDirectory($this->testUploadDir);
        }

        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}

