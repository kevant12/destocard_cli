<?php

namespace App\Service;

use App\Entity\Products;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service de gestion des produits
 * 
 * Centralise la logique métier complexe liée aux produits :
 * - Création et modification de produits avec gestion des médias
 * - Validation des droits d'accès et de modification
 * - Suppression sécurisée avec vérification des contraintes
 * - Intégration avec MediaUploadService pour les images
 * 
 * Sépare la logique métier des contrôleurs pour une meilleure maintenabilité
 */
class ProductService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaUploadService $mediaUploadService
    ) {}

    /**
     * Crée un nouveau produit avec ses images
     * 
     * Gère la création complète d'un produit :
     * - Association du produit à l'utilisateur vendeur
     * - Upload et association des images via MediaUploadService
     * - Persistance en base de données avec transaction
     * 
     * @param Products $product Le produit à créer
     * @param Users $user L'utilisateur vendeur
     * @param array $imageFiles Tableau des fichiers image uploadés
     */
    public function createProduct(Products $product, Users $user, array $imageFiles = []): void
    {
        // Associer le produit à l'utilisateur vendeur
        $product->setUsers($user);

        // Traiter chaque image uploadée
        foreach ($imageFiles as $imageFile) {
            if ($imageFile instanceof UploadedFile) {
                // Utiliser le service dédié pour l'upload sécurisé
                $media = $this->mediaUploadService->uploadImage($imageFile, 'products');
                $product->addMedium($media); // Assurez-vous que addMedium existe et fonctionne
            }
        }

        // Sauvegarder en base de données
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    /**
     * Met à jour un produit existant
     * 
     * Permet la mise à jour d'un produit avec ajout optionnel de nouvelles images.
     * Les images existantes sont conservées sauf suppression explicite.
     * 
     * @param Products $product Le produit à modifier
     * @param array $imageFiles Nouvelles images à ajouter (optionnel)
     */
    public function updateProduct(Products $product, array $imageFiles = []): void
    {
        // Ajouter de nouvelles images si fournies
        foreach ($imageFiles as $imageFile) {
            if ($imageFile instanceof UploadedFile) {
                $media = $this->mediaUploadService->uploadImage($imageFile, 'products');
                $product->addMedium($media);
            }
        }
        
        // La logique pour supprimer des images existantes pourrait être ajoutée ici
        // TODO: Implémenter la suppression sélective d'images si nécessaire
        
        // Sauvegarder les modifications
        $this->entityManager->flush();
    }

    /**
     * Supprime un produit de manière sécurisée
     * 
     * Vérifie les contraintes avant suppression :
     * - Le produit ne doit pas être lié à des commandes existantes
     * - Suppression des fichiers média associés du système de fichiers
     * - Suppression de l'entité en base de données
     * 
     * @param Products $product Le produit à supprimer
     * @return bool True si la suppression a réussi, False si impossible
     */
    public function deleteProduct(Products $product): bool
    {
        // Vérifier que le produit n'est pas lié à des commandes
        if (!$product->getOrdersProducts()->isEmpty()) {
            return false; // Impossible de supprimer un produit commandé
        }

        // Supprimer les médias associés du système de fichiers
        foreach ($product->getMedia() as $media) {
            $this->mediaUploadService->removeImage($media, 'products');
        }

        // Supprimer l'entité produit (cascade supprimera les médias en base)
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        return true;
    }

    /**
     * Vérifie si un utilisateur peut gérer un produit
     * 
     * Détermine les droits d'accès pour la modification/suppression :
     * - Le propriétaire du produit a tous les droits
     * - Les administrateurs peuvent gérer tous les produits
     * 
     * @param Products $product Le produit concerné
     * @param Users $user L'utilisateur demandant l'accès
     * @param bool $isAdmin True si l'utilisateur a le rôle admin
     * @return bool True si l'utilisateur peut gérer ce produit
     */
    public function canManageProduct(Products $product, Users $user, bool $isAdmin = false): bool
    {
        return $product->getUsers() === $user || $isAdmin;
    }
} 