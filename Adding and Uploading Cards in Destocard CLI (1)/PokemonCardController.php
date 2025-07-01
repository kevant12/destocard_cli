<?php

namespace App\Controller;

use App\Entity\PokemonCard;
use App\Entity\Media;
use App\Entity\Extension;
use App\Entity\Serie;
use App\Form\PokemonCard\PokemonCardFormType;
use App\Repository\PokemonCardRepository;
use App\Repository\ExtensionRepository;
use App\Repository\SerieRepository;
use App\Service\MediaUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/pokemon-card')]
class PokemonCardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PokemonCardRepository $pokemonCardRepository,
        private ExtensionRepository $extensionRepository,
        private SerieRepository $serieRepository,
        private PaginatorInterface $paginator,
        private MediaUploadService $mediaUploadService
    ) {
    }

    /**
     * Affiche toutes les cartes Pokémon
     */
    #[Route('/', name: 'app_pokemon_card_index')]
    public function index(Request $request): Response
    {
        $query = $this->pokemonCardRepository->createQueryBuilder('pc')
            ->leftJoin('pc.extension', 'e')
            ->leftJoin('e.serie', 's')
            ->orderBy('s.name', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->addOrderBy('pc.number', 'ASC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('pokemon_card/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * Ajoute une nouvelle carte Pokémon
     */
    #[Route('/add', name: 'app_pokemon_card_add')]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request): Response
    {
        $pokemonCard = new PokemonCard();
        
        $form = $this->createForm(PokemonCardFormType::class, $pokemonCard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Gestion de l'upload d'image
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $media = $this->mediaUploadService->uploadImage($imageFile, 'pokemon_cards');
                    $pokemonCard->setImage($media);
                }

                $this->em->persist($pokemonCard);
                $this->em->flush();

                $this->addFlash('success', 'Carte Pokémon ajoutée avec succès !');
                return $this->redirectToRoute('app_pokemon_card_show', ['id' => $pokemonCard->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'ajout de la carte : ' . $e->getMessage());
            }
        }

        return $this->render('pokemon_card/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Affiche une carte Pokémon
     */
    #[Route('/{id}', name: 'app_pokemon_card_show', methods: ['GET'])]
    public function show(PokemonCard $pokemonCard): Response
    {
        return $this->render('pokemon_card/show.html.twig', [
            'pokemon_card' => $pokemonCard,
        ]);
    }

    /**
     * Modifie une carte Pokémon existante
     */
    #[Route('/{id}/edit', name: 'app_pokemon_card_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, PokemonCard $pokemonCard): Response
    {
        $form = $this->createForm(PokemonCardFormType::class, $pokemonCard);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Gestion de l'upload d'image
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    // Supprimer l'ancienne image si elle existe
                    if ($pokemonCard->getImage()) {
                        $this->mediaUploadService->deleteImage($pokemonCard->getImage());
                    }
                    
                    $media = $this->mediaUploadService->uploadImage($imageFile, 'pokemon_cards');
                    $pokemonCard->setImage($media);
                }

                $this->em->flush();

                $this->addFlash('success', 'Carte Pokémon modifiée avec succès !');
                return $this->redirectToRoute('app_pokemon_card_show', ['id' => $pokemonCard->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification de la carte : ' . $e->getMessage());
            }
        }

        return $this->render('pokemon_card/edit.html.twig', [
            'form' => $form->createView(),
            'pokemon_card' => $pokemonCard
        ]);
    }

    /**
     * Supprime une carte Pokémon
     */
    #[Route('/{id}/delete', name: 'app_pokemon_card_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, PokemonCard $pokemonCard): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pokemonCard->getId(), $request->request->get('_token'))) {
            try {
                // Vérifier si la carte est utilisée dans des produits
                $products = $this->em->getRepository(\App\Entity\Products::class)
                    ->findBy(['pokemonCard' => $pokemonCard]);
                
                if (!empty($products)) {
                    $this->addFlash('error', 'Impossible de supprimer cette carte car elle est utilisée dans des produits.');
                    return $this->redirectToRoute('app_pokemon_card_index');
                }

                // Supprimer l'image associée
                if ($pokemonCard->getImage()) {
                    $this->mediaUploadService->deleteImage($pokemonCard->getImage());
                }

                $this->em->remove($pokemonCard);
                $this->em->flush();

                $this->addFlash('success', 'Carte Pokémon supprimée avec succès !');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de la carte : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_pokemon_card_index');
    }

    /**
     * API pour récupérer les extensions d'une série
     */
    #[Route('/api/extensions-by-serie/{serieId}', name: 'api_extensions_by_serie', methods: ['GET'])]
    public function getExtensionsBySerie(int $serieId): JsonResponse
    {
        $serie = $this->serieRepository->find($serieId);
        if (!$serie) {
            return new JsonResponse(['error' => 'Série non trouvée'], 404);
        }

        $extensions = $this->extensionRepository->findBy(['serie' => $serie]);

        $data = [];
        foreach ($extensions as $extension) {
            $data[] = [
                'id' => $extension->getId(),
                'name' => $extension->getName(),
                'totalCardsMain' => $extension->getTotalCardsMain(),
                'totalCardsSecret' => $extension->getTotalCardsSecret(),
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * API pour vérifier si une carte existe déjà
     */
    #[Route('/api/check-card-exists', name: 'api_check_card_exists', methods: ['POST'])]
    public function checkCardExists(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $extensionId = $data['extensionId'] ?? null;
        $number = $data['number'] ?? null;

        if (!$extensionId || !$number) {
            return new JsonResponse(['error' => 'Extension et numéro requis'], 400);
        }

        $extension = $this->extensionRepository->find($extensionId);
        if (!$extension) {
            return new JsonResponse(['error' => 'Extension non trouvée'], 404);
        }

        $existingCard = $this->pokemonCardRepository->findOneBy([
            'extension' => $extension,
            'number' => $number
        ]);

        return new JsonResponse([
            'exists' => $existingCard !== null,
            'card' => $existingCard ? [
                'id' => $existingCard->getId(),
                'name' => $existingCard->getName(),
                'category' => $existingCard->getCategory()
            ] : null
        ]);
    }
}

