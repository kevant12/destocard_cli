<?php

namespace App\Controller;

use App\Entity\PokemonCard;
use App\Form\PokemonCard\PokemonCardFormType;
use App\Repository\PokemonCardRepository;
use App\Service\MediaUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/pokemon-card')]
class PokemonCardController extends AbstractController
{
    public function __construct(
        private readonly PokemonCardRepository $pokemonCardRepository,
        private readonly MediaUploadService $mediaUploadService,
        private readonly EntityManagerInterface $em,
        private readonly PaginatorInterface $paginator
    ) {}

    #[Route('/', name: 'app_pokemon_card_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $this->pokemonCardRepository->createQueryBuilder('pc')
            ->leftJoin('pc.extension', 'e')
            ->addSelect('e')
            ->orderBy('e.name', 'ASC')
            ->addOrderBy('pc.number', 'ASC');

        $pagination = $this->paginator->paginate(
            $query->getQuery(),
            $request->query->getInt('page', 1),
            24
        );

        return $this->render('pokemon_card/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_pokemon_card_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $pokemonCard = new PokemonCard();
        $form = $this->createForm(PokemonCardFormType::class, $pokemonCard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $media = $this->mediaUploadService->uploadImage($imageFile, 'pokemon_cards');
                $pokemonCard->setImage($media);
            }

            $this->em->persist($pokemonCard);
            $this->em->flush();

            $this->addFlash('success', 'La carte Pokémon a été créée avec succès.');
            return $this->redirectToRoute('app_pokemon_card_index');
        }

        return $this->render('pokemon_card/new.html.twig', [
            'pokemon_card' => $pokemonCard,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_pokemon_card_show', methods: ['GET'])]
    public function show(PokemonCard $pokemonCard): Response
    {
        // Récupérer les produits (annonces) associés à cette carte
        $products = $this->em->getRepository(\App\Entity\Products::class)->findBy([
            'pokemonCard' => $pokemonCard,
            // 'isAvailable' => true // Optionnel: ne montrer que les annonces actives
        ]);
        
        return $this->render('pokemon_card/show.html.twig', [
            'pokemon_card' => $pokemonCard,
            'products' => $products,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pokemon_card_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, PokemonCard $pokemonCard): Response
    {
        $form = $this->createForm(PokemonCardFormType::class, $pokemonCard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Supprimer l'ancienne image si elle existe et qu'une nouvelle est uploadée
                if ($pokemonCard->getImage()) {
                    $this->mediaUploadService->removeImage($pokemonCard->getImage());
                }
                $media = $this->mediaUploadService->uploadImage($imageFile, 'pokemon_cards');
                $pokemonCard->setImage($media);
            }

            $this->em->flush();

            $this->addFlash('success', 'La carte Pokémon a été mise à jour avec succès.');
            return $this->redirectToRoute('app_pokemon_card_index');
        }

        return $this->render('pokemon_card/edit.html.twig', [
            'pokemon_card' => $pokemonCard,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_pokemon_card_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, PokemonCard $pokemonCard): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pokemonCard->getId(), $request->request->get('_token'))) {
            // Vérifier si des produits sont liés
            $relatedProducts = $this->em->getRepository(\App\Entity\Products::class)->findBy(['pokemonCard' => $pokemonCard]);
            if (count($relatedProducts) > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette carte car des annonces y sont liées.');
                return $this->redirectToRoute('app_pokemon_card_index');
            }
            
            // Supprimer l'image associée
            if ($pokemonCard->getImage()) {
                $this->mediaUploadService->removeImage($pokemonCard->getImage());
            }

            $this->em->remove($pokemonCard);
            $this->em->flush();
            $this->addFlash('success', 'La carte Pokémon a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_pokemon_card_index');
    }

    // API endpoints for dynamic forms
    #[Route('/api/extensions_by_serie', name: 'api_extensions_by_serie', methods: ['GET'])]
    public function getExtensionsBySerie(Request $request): JsonResponse
    {
        $serieId = $request->query->get('serie_id');
        $extensions = $this->em->getRepository(\App\Entity\Extension::class)->findBy(['serie' => $serieId], ['name' => 'ASC']);
        
        $data = [];
        foreach ($extensions as $extension) {
            $data[] = ['id' => $extension->getId(), 'name' => $extension->getName()];
        }
        
        return $this->json($data);
    }
} 