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
     * Affiche tous les produits disponibles
     */
    #[Route('/', name: 'app_product_index')]
    public function index(Request $request): Response
    {
        $query = $this->productsRepository->findAllAvailableProductsQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('product/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * Affiche les produits de l'utilisateur connecté
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

        $query = $this->productsRepository->findUserProductsQuery($user->getId());

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('product/user_products.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * Permet de créer une nouvelle carte et de la mettre en vente.
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
            $product->setQuantity(1); // Quantité par défaut

            // Utiliser le service pour créer le produit et uploader les images
            $imageFiles = $form->get('imageFiles')->getData();
            $this->productService->createProduct($product, $user, $imageFiles);

            $this->addFlash('success', 'Article mis en vente avec succès !');
            return $this->redirectToRoute('app_user_products');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form->createView(),
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
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour effectuer cette action.');
            return $this->redirectToRoute('app_login');
        }

        if (!$this->productService->canManageProduct($product, $user, $this->isGranted('ROLE_ADMIN'))) {
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
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    /**
     * Supprime un produit
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

        if (!$this->productService->canManageProduct($product, $user, $this->isGranted('ROLE_ADMIN'))) {
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
} 