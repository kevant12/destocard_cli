<?php

namespace App\Controller;

use App\Entity\Products;
use App\Entity\PokemonCard;
use App\Form\Product\ProductFormType;
use App\Repository\ProductsRepository;
use App\Repository\PokemonCardRepository;
use App\Service\ProductService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Extension;
use App\Entity\Serie;

/**
 * Contrôleur de gestion des produits
 * 
 * Fonctionnalités principales :
 * - CRUD complet pour les produits (Create, Read, Update, Delete)
 * - Affichage public des produits avec pagination
 * - Gestion des articles de l'utilisateur connecté
 * - Upload et gestion des images via MediaUploadService
 * - Contrôles d'accès et validation des droits
 * 
 * Intègre ProductService pour la logique métier complexe
 */
#[Route('/product')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductsRepository $productsRepository,
        private readonly PaginatorInterface $paginator,
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Affiche tous les produits disponibles (page publique)
     * 
     * Page de catalogue principal avec pagination pour les performances.
     * Affiche uniquement les produits avec une quantité > 0 (disponibles).
     * 
     * @param Request $request Pour la gestion de la pagination
     * @return Response La page de catalogue avec les produits paginés
     */
    #[Route('/', name: 'app_product_index')]
    public function index(Request $request): Response
    {
        // Récupérer la requête de base pour les produits disponibles uniquement
        $query = $this->productsRepository->findAllAvailableProductsQuery();

        // Paginer les résultats pour améliorer les performances et l'UX
        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Page courante (défaut: 1)
            12 // Nombre de produits par page
        );

        return $this->render('product/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * Affiche les produits de l'utilisateur connecté
     * 
     * Interface de gestion personnelle permettant à l'utilisateur de :
     * - Voir tous ses articles en vente
     * - Accéder aux actions de modification/suppression
     * - Suivre l'état de ses ventes
     * 
     * @param Request $request Pour la pagination
     * @return Response Page de gestion des articles utilisateur
     */
    #[Route('/my-articles', name: 'app_user_products')]
    #[IsGranted('ROLE_USER')]
    public function userProducts(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos articles.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer uniquement les produits de l'utilisateur connecté
        $query = $this->productsRepository->findUserProductsQuery($user->getId());

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // Moins d'éléments par page pour la gestion personnelle
        );

        return $this->render('product/user_products.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * Formulaire de création d'un nouveau produit
     * 
     * Permet aux utilisateurs connectés de mettre en vente leurs articles.
     * Gère l'upload d'images multiples et la validation complète des données.
     * 
     * @param Request $request Pour le traitement du formulaire
     * @return Response Le formulaire de création ou redirection après succès
     */
    #[Route('/new', name: 'app_product_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $product = new Products();
        $form = $this->createForm(ProductFormType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour vendre un article.');
                return $this->redirectToRoute('app_login');
            }
            
            $product = $form->getData();
            $product->setQuantity(1); // Quantité par défaut pour un article unique

            // Utiliser le service pour créer le produit et gérer l'upload des images
            $imageFiles = $form->get('imageFiles')->getData();
            $this->productService->createProduct($product, $user, $imageFiles);

            $this->addFlash('success', 'Article mis en vente avec succès !');
            return $this->redirectToRoute('app_user_products');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * 🔍 PAGE DE RECHERCHE - SUPER SIMPLE !
     * =====================================
     * 
     * Cette méthode fait comme un moteur de recherche :
     * 1. On récupère ce que l'utilisateur a tapé (le mot "Pikachu" par exemple)
     * 2. On demande à notre "livre magique" (le repository) de trouver les cartes
     * 3. On affiche les résultats sur une jolie page
     * 
     * C'est comme chercher dans un dictionnaire, mais pour les cartes !
     * 
     * ⚠️ IMPORTANT : Cette route DOIT être AVANT /{id} sinon Symfony confond "search" avec un ID !
     * 
     * @param Request $request Pour récupérer ce que l'utilisateur a tapé
     * @return Response La page avec tous les résultats trouvés
     */
    #[Route('/search', name: 'app_product_search', methods: ['GET'])]
    public function search(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $request->query->get('q', '');
        $category = $request->query->get('category');
        $rarity = $request->query->get('rarity');
        $seller = $request->query->get('seller');
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'desc');

        $queryBuilder = $this->productsRepository->searchProductsQuery($query, $category, $rarity, $seller, $sortBy, $sortOrder);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12
        );

        $sellers = $this->productsRepository->findAllSellers();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'content' => $this->renderView('product/_search_results_content.html.twig', [
                    'pagination' => $pagination,
                    'query' => $query,
                ])
            ]);
        }

        return $this->render('product/search_results.html.twig', [
            'query' => $query,
            'pagination' => $pagination,
            'selectedCategory' => $category,
            'selectedRarity' => $rarity,
            'selectedSeller' => $seller,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'sellers' => $sellers,
        ]);
    }

    /**
     * Affiche le détail d'un produit (page publique)
     * 
     * Page de présentation complète d'un produit avec :
     * - Toutes les informations détaillées
     * - Images et description
     * - Actions possibles (favoris, panier)
     * - Informations sur le vendeur
     * 
     * ⚠️ IMPORTANT : Cette route /{id} DOIT être APRÈS /search sinon elle capture tout !
     * 
     * @param Products $product Le produit à afficher (injection automatique via l'ID)
     * @return Response La page de détail du produit
     */
    #[Route('/{id}', name: 'app_product_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Products $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * Formulaire de modification d'un produit existant
     * 
     * Permet au propriétaire (ou admin) de modifier son article.
     * Vérifie les droits d'accès avant d'autoriser la modification.
     * 
     * @param Request $request Pour le traitement du formulaire
     * @param Products $product Le produit à modifier
     * @return Response Le formulaire de modification ou redirection
     */
    #[Route('/{id}/edit', name: 'app_product_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Products $product): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier que l'utilisateur a le droit de modifier ce produit
        if (!$this->productService->canManageProduct($product, $user, $this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet article.');
        }

        $form = $this->createForm(ProductFormType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Utiliser le service pour les mises à jour complexes
            $this->productService->updateProduct($product);
            $this->addFlash('success', 'Article modifié avec succès !');
            return $this->redirectToRoute('app_user_products');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    /**
     * Suppression d'un produit (action sécurisée)
     * 
     * Supprime un produit après vérification des droits et contraintes.
     * Vérifie qu'il n'est pas lié à des commandes avant suppression.
     * 
     * @param Request $request Pour le token CSRF
     * @param Products $product Le produit à supprimer
     * @return Response Redirection vers la liste des produits utilisateur
     */
    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Products $product): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier les droits de suppression
        if (!$this->productService->canManageProduct($product, $user, $this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet article.');
        }

        // Valider le token CSRF pour éviter les suppressions malveillantes
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            // Utiliser le service qui gère les contraintes de suppression
            if ($this->productService->deleteProduct($product)) {
                $this->addFlash('success', 'Article supprimé avec succès !');
            } else {
                $this->addFlash('error', 'Impossible de supprimer cet article car il est lié à une commande.');
            }
        }

        return $this->redirectToRoute('app_user_products');
    }

} 