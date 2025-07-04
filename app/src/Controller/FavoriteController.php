<?php

namespace App\Controller;

use App\Entity\Products;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des favoris utilisateur
 * 
 * Fonctionnalités principales :
 * - Affichage de la liste des produits favoris de l'utilisateur
 * - Toggle des favoris (ajout/suppression) via AJAX
 * - Protection CSRF pour toutes les actions sensibles
 * - Interface responsive avec JavaScript pour l'interactivité
 * 
 * Toutes les routes nécessitent une authentification (ROLE_USER)
 */
class FavoriteController extends AbstractController
{
    /**
     * Affiche la page des favoris de l'utilisateur connecté
     * 
     * Récupère tous les produits que l'utilisateur a ajoutés à ses favoris
     * et les affiche dans une grille utilisant le template partiel des cartes produits.
     */
    #[Route('/favorites', name: 'app_favorites')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        $favorites = $user->getLikes();

        return $this->render('favorite/index.html.twig', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * Toggle d'un produit dans les favoris (AJAX)
     * 
     * Ajoute le produit aux favoris s'il n'y est pas, le retire s'il y est déjà.
     * Utilisé par le JavaScript (favorites.js) pour une expérience utilisateur fluide.
     * 
     * Sécurité :
     * - Protection CSRF obligatoire
     * - Vérification de l'authentification utilisateur
     * - Validation de l'existence du produit
     * 
     * @param Products $product Le produit à ajouter/retirer des favoris
     * @param EntityManagerInterface $entityManager Pour persister les changements
     * @param Request $request Pour récupérer le token CSRF
     * @return JsonResponse Réponse JSON avec le statut et les nouvelles données
     */
    #[Route('/favorite/toggle/{id}', name: 'app_favorite_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggle(Products $product, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        // Valider le jeton CSRF pour éviter les attaques Cross-Site Request Forgery
        if (!$this->isCsrfTokenValid('toggle_favorite' . $product->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Jeton CSRF invalide.'], 403);
        }

        $user = $this->getUser();
        
        // Vérifier si le produit est déjà dans les favoris pour déterminer l'action
        if ($user->getLikes()->contains($product)) {
            // Le produit est déjà aimé, on le retire des favoris
            $user->removeLike($product);
            $isLiked = false;
        } else {
            // Le produit n'est pas aimé, on l'ajoute aux favoris
            $user->addLike($product);
            $isLiked = true;
        }
        
        // Sauvegarder les changements en base de données
        $entityManager->flush();
        
        // Retourner les informations mises à jour pour le frontend
        return $this->json([
            'success' => true,
            'isLiked' => $isLiked,
            'likesCount' => $product->getLikes()->count()
        ]);
    }
} 