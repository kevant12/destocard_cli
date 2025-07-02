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

class FavoriteController extends AbstractController
{
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

    #[Route('/favorite/toggle/{id}', name: 'app_favorite_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggle(Products $product, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        // Valider le jeton CSRF
        if (!$this->isCsrfTokenValid('toggle_favorite' . $product->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Jeton CSRF invalide.'], 403);
        }

        $user = $this->getUser();
        
        if ($user->getLikes()->contains($product)) {
            $user->removeLike($product);
            $isLiked = false;
        } else {
            $user->addLike($product);
            $isLiked = true;
        }
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'isLiked' => $isLiked,
            'likesCount' => $product->getLikes()->count()
        ]);
    }
} 