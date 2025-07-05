<?php

namespace App\Controller;

use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur de la page d'accueil du site
 * 
 * Fonctionnalités principales :
 * - Affichage de la page d'accueil avec section héro
 * - Présentation des derniers produits ajoutés
 * - Gestion du compteur de panier pour l'affichage
 * - Interface moderne et responsive
 * 
 * Page accessible à tous (utilisateurs connectés et visiteurs)
 */
class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil du site Destocard
     * 
     * Cette méthode gère l'affichage de la page principale avec :
     * - Une section héro avec vidéo de fond
     * - Les 8 derniers produits ajoutés pour inciter à l'exploration
     * - Le compteur de panier récupéré depuis la session
     * 
     * @param ProductsRepository $productsRepository Pour récupérer les derniers produits
     * @param SessionInterface $session Pour récupérer les informations du panier
     * @return Response La page d'accueil rendue
     */
    #[Route('/', name: 'app_home')]
    public function index(ProductsRepository $productsRepository): Response
    {
        // Récupérer les 8 derniers produits ajoutés pour l'affichage d'accueil
        // Triés par date de création décroissante pour montrer les nouveautés
        $latestProducts = $productsRepository->findBy([], ['createdAt' => 'DESC'], 8);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'latestProducts' => $latestProducts,
        ]);
    }
}