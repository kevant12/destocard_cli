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

#[Route('/product')]
class ProductController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(
        private ProductService $productService,
        private ProductsRepository $productsRepository,
        private PokemonCardRepository $pokemonCardRepository,
        private PaginatorInterface $paginator,
        EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    /**
     * Affiche tous les produits disponibles
     */
    #[Route('/', name: 'app_product_index')]
    public function index(Request $request): Response
    {
        $query = $this->productsRepository->findAllAvailableProductsQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // Nombre d'éléments par page
        );

        return $this->render('product/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * Affiche les produits de l'utilisateur connecté
     */
    #[Route('/mes-articles', name: 'app_user_products')]
    #[IsGranted('ROLE_USER')]
    public function userProducts(Request $request): Response
    {
        $query = $this->productsRepository->findUserProductsQuery($this->getUser()->getId());

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // Nombre d'éléments par page
        );

        return $this->render('product/user_products.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * Ajoute un nouveau produit
     */
    #[Route('/add/{pokemonCardId}', name: 'app_product_add', defaults: ['pokemonCardId' => null])]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request, ?int $pokemonCardId): Response
    {
        $product = new Products();
        $pokemonCard = null;

        if ($pokemonCardId) {
            $pokemonCard = $this->pokemonCardRepository->find($pokemonCardId);
            if ($pokemonCard) {
                $product->setPokemonCard($pokemonCard);
                $product->setTitle($pokemonCard->getName());
                $product->setDescription($pokemonCard->getDescription());
                $product->setCategory($pokemonCard->getCategory());
            }
        }
        
        // Récupérer toutes les séries, extensions et cartes pour les champs du formulaire
        $series = $this->em->getRepository(\App\Entity\Serie::class)->findAll();
        $extensions = $this->em->getRepository(\App\Entity\Extension::class)->findAll();
        $pokemonCards = $this->em->getRepository(\App\Entity\PokemonCard::class)->findAll();

        $form = $this->createForm(ProductFormType::class, $product, [
            'series' => $series,
            'extensions' => $extensions,
            'pokemonCards' => $pokemonCards,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Lier la bonne carte au produit
            $pokemonCard = $form->get('pokemonCard')->getData();
            $product->setPokemonCard($pokemonCard);

            // Pré-remplissage automatique si les champs sont vides
            if ($pokemonCard) {
                if (!$product->getTitle()) {
                    $product->setTitle($pokemonCard->getName());
                }
                if (method_exists($pokemonCard, 'getDescription') && !$product->getDescription()) {
                    $product->setDescription($pokemonCard->getDescription());
                }
                if (method_exists($pokemonCard, 'getCategory') && !$product->getCategory()) {
                    $product->setCategory($pokemonCard->getCategory());
                }
            }

            $this->productService->createProduct($product, $this->getUser());
            $this->addFlash('success', 'Article ajouté avec succès !');
            return $this->redirectToRoute('app_user_products');
        }

        return $this->render('product/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Products $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * Modifie un produit existant
     */
    #[Route('/{id}/edit', name: 'app_product_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Products $product): Response
    {
        if (!$this->productService->canManageProduct($product, $this->getUser(), $this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet article.');
        }

        $form = $this->createForm(ProductFormType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->productService->updateProduct($product);
            $this->addFlash('success', 'Article modifié avec succès !');
            return $this->redirectToRoute('app_user_products');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Supprime un produit
     */
    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Products $product): Response
    {
        if (!$this->productService->canManageProduct($product, $this->getUser(), $this->isGranted('ROLE_ADMIN'))) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet article.');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            if ($this->productService->deleteProduct($product)) {
                $this->addFlash('success', 'Article supprimé avec succès !');
            } else {
                $this->addFlash('error', 'Impossible de supprimer cet article car il est lié à une commande.');
            }
        }

        return $this->redirectToRoute('app_user_products');
    }

    #[Route('/api/pokemon-cards-by-extension/{extensionId}', name: 'api_pokemon_cards_by_extension', methods: ['GET'])]
    public function getPokemonCardsByExtension(int $extensionId): JsonResponse
    {
        $extension = $this->em->getRepository(\App\Entity\Extension::class)->find($extensionId);
        $cards = $this->pokemonCardRepository->findBy(['extension' => $extension]);

        $data = [];
        foreach ($cards as $card) {
            $data[] = [
                'id' => $card->getId(),
                'name' => $card->getName(),
                'imageUrl' => $card->getImage() ? $card->getImage()->getImageUrl() : null,
            ];
        }

        return $this->json($data, 200, [], ['groups' => 'product:read']);
    }

    #[Route('/api/pokemon-card/{id}', name: 'api_get_pokemon_card', methods: ['GET'])]
    public function getPokemonCard(PokemonCard $card): JsonResponse
    {
        return $this->json($card, 200, [], ['groups' => 'product:read']);
    }

    #[Route('/api/pokemon-card-details/{number}', name: 'api_pokemon_card_details', methods: ['GET'])]
    public function getPokemonCardDetails(string $number): JsonResponse
    {
        $card = $this->pokemonCardRepository->findOneByNumber($number);

        if (!$card) {
            return $this->json(['error' => 'Card not found'], 404);
        }

        return $this->json([
            'id' => $card->getId(),
            'name' => $card->getName(),
            'image' => $card->getImage(),
            'extensionId' => $card->getExtension() ? $card->getExtension()->getId() : null,
            'extensionName' => $card->getExtension() ? $card->getExtension()->getName() : null,
            'rarityText' => $card->getRarityText(),
            'raritySymbol' => $card->getRaritySymbol(),
            'category' => $card->getCategory(),
            'specialType' => $card->getSpecialType(),
            'subSerie' => $card->getSubSerie(),
            // ... autres champs utiles
        ], 200, [], ['groups' => 'product:read']);
    }

    /**
     * Affiche les résultats de la recherche de produits
     */
    #[Route('/search', name: 'app_product_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q');
        $category = $request->query->get('category');
        $rarity = $request->query->get('rarity');
        $sortBy = $request->query->get('sort_by');
        $sortOrder = $request->query->get('sort_order', 'asc');
        $page = $request->query->getInt('page', 1);
        $limit = 10; // Nombre d'éléments par page

        $productsQuery = $this->productsRepository->searchProductsQuery($query, $category, $rarity, $sortBy, $sortOrder);

        $pagination = $this->paginator->paginate(
            $productsQuery, // Requête Doctrine, pas les résultats
            $page, // Numéro de la page actuelle
            $limit // Nombre d'éléments par page
        );

        return $this->render('product/search_results.html.twig', [
            'pagination' => $pagination,
            'query' => $query,
            'selectedCategory' => $category,
            'selectedRarity' => $rarity,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    /**
     * Active/Désactive un produit
     */
    #[Route('/{id}/toggle', name: 'app_product_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggle(Request $request, Products $product): Response
    {
        if (!$this->productService->canManageProduct($product, $this->getUser(), false)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet article.');
        }

        $isVisible = $this->productService->toggleVisibility($product);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'isAvailable' => $isVisible
            ]);
        }

        return $this->redirectToRoute('app_user_products');
    }

    #[Route('/api/pokemon-card', name: 'api_pokemon_card_by_extension_and_number', methods: ['GET'])]
    public function getPokemonCardByExtensionAndNumber(Request $request): JsonResponse
    {
        $extensionId = $request->query->get('extensionId');
        $cardNumber = $request->query->get('cardNumber');
        if (!$extensionId || !$cardNumber) {
            return $this->json(['error' => 'Extension et numéro requis'], 400);
        }
        $extension = $this->em->getRepository(Extension::class)->find($extensionId);
        if (!$extension) {
            return $this->json(['error' => 'Extension inconnue'], 404);
        }
        $card = $this->pokemonCardRepository->findOneBy(['extension' => $extension, 'number' => $cardNumber]);
        if (!$card) {
            return $this->json(['error' => 'Carte non trouvée'], 404);
        }
        return $this->json($card, 200, [], ['groups' => 'product:read']);
    }
} 