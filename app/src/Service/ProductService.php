<?php

namespace App\Service;

use App\Entity\Products;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileUploaderService $fileUploaderService
    ) {}

    public function createProduct(Products $product, Users $user): void
    {
        $product->setUsers($user);
        $product->setCreatedAt(new \DateTimeImmutable());
        
        $this->handleMediaUpload($product);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function updateProduct(Products $product): void
    {
        $this->handleMediaUpload($product);
        $this->entityManager->flush();
    }

    private function handleMediaUpload(Products $product): void
    {
        foreach ($product->getMedia() as $media) {
            $file = $media->getFile(); // Supposons que vous ayez une méthode getFile() dans votre entité Media
            if ($file instanceof UploadedFile) {
                $fileName = $this->fileUploaderService->upload($file);
                $media->setImageUrl($fileName);
                $media->setFile(null); // Supprime le fichier temporaire après l'upload
            }
        }
    }

    public function deleteProduct(Products $product): bool
    {
        if ($product->getOrdersProducts()) {
            return false;
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();
        return true;
    }

    public function toggleVisibility(Products $product): bool
    {
        $product->setQuantity($product->getQuantity() > 0 ? 0 : 1);
        $this->entityManager->flush();
        
        return $product->getQuantity() > 0;
    }

    public function canManageProduct(Products $product, Users $user, bool $isAdmin = false): bool
    {
        return $product->getUsers() === $user || $isAdmin;
    }
} 