<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HomeController extends AbstractController
{
    #[Route('/accueil', name: 'app_home')]
    public function index(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        $cartCount = array_sum(array_column($cart, 'quantity'));
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'cartCount' => $cartCount,
        ]);
    }
}