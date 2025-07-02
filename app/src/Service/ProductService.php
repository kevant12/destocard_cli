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
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaUploadService $mediaUploadService
    ) {}

    public function createProduct(Products $product, Users $user, array $imageFiles = []): void
    {
        $product->setUsers($user);

        foreach ($imageFiles as $imageFile) {
            if ($imageFile instanceof UploadedFile) {
                $media = $this->mediaUploadService->uploadImage($imageFile, 'products');
                $product->addMedium($media); // Assurez-vous que addMedium existe et fonctionne
            }
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function updateProduct(Products $product, array $imageFiles = []): void
    {
        foreach ($imageFiles as $imageFile) {
            if ($imageFile instanceof UploadedFile) {
                $media = $this->mediaUploadService->uploadImage($imageFile, 'products');
                $product->addMedium($media);
            }
        }
        
        // La logique pour supprimer des images existantes pourrait être ajoutée ici
        
        $this->entityManager->flush();
    }

    public function deleteProduct(Products $product): bool
    {
        if (!$product->getOrdersProducts()->isEmpty()) {
            return false;
        }

        // Supprimer les médias associés du système de fichiers
        foreach ($product->getMedia() as $media) {
            $this->mediaUploadService->removeImage($media, 'products');
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