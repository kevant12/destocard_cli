<?php

namespace App\Controller;

use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductsRepository $productsRepository, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));

        // Récupérer les 8 derniers produits ajoutés
        $latestProducts = $productsRepository->findBy([], ['createdAt' => 'DESC'], 8);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'cartCount' => $cartCount,
            'latestProducts' => $latestProducts,
        ]);
    }
}