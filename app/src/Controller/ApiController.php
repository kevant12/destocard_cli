<?php

namespace App\Controller;

use App\Repository\ExtensionRepository;
use App\Repository\PokemonCardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/cards', name: 'api_cards_by_extension', methods: ['GET'])]
    public function getCardsByExtension(Request $request, PokemonCardRepository $pokemonCardRepository): Response
    {
        $extensionId = $request->query->get('extensionId');
        if (!$extensionId) {
            return $this->json([], 400);
        }

        $cards = $pokemonCardRepository->findBy(['extension' => $extensionId], ['name' => 'ASC']);

        return $this->json($cards, 200, [], ['groups' => 'product:read']);
    }

    #[Route('/card', name: 'api_card_by_number', methods: ['GET'])]
    public function getCardByNumber(Request $request, PokemonCardRepository $pokemonCardRepository): Response
    {
        $extensionId = $request->query->get('extensionId');
        $cardNumber = $request->query->get('cardNumber');

        if (!$extensionId || !$cardNumber) {
            return $this->json([], 400);
        }

        $card = $pokemonCardRepository->findOneBy(['extension' => $extensionId, 'number' => $cardNumber]);

        if (!$card) {
            return $this->json(null, 404);
        }

        return $this->json($card, 200, [], ['groups' => 'product:read']);
    }
}
