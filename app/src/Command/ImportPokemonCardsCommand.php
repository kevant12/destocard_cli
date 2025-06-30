<?php

namespace App\Command;

use App\Entity\Extension;
use App\Entity\PokemonCard;
use App\Entity\Serie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-pokemon-cards',
    description: 'Imports Pokémon cards from a given JSON file.',
)]
class ImportPokemonCardsCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('json_file_path', InputArgument::REQUIRED, 'The path to the JSON file to import.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jsonFilePath = $input->getArgument('json_file_path');

        if (!file_exists($jsonFilePath)) {
            $io->error('JSON file not found at: ' . $jsonFilePath);
            return Command::FAILURE;
        }

        $content = file_get_contents($jsonFilePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Error decoding JSON: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        $serieName = $data['serie'] ?? 'Default Serie';
        $serie = $this->entityManager->getRepository(Serie::class)->findOneBy(['name' => $serieName]);
        if (!$serie) {
            $serie = new Serie();
            $serie->setName($serieName);
            $this->entityManager->persist($serie);
            $io->info('Creating new serie: ' . $serieName);
        }

        $totalImportedCards = 0;

        foreach ($data['sets'] as $setData) {
            $extensionApiId = $setData['id'];
            $extension = $this->entityManager->getRepository(Extension::class)->findOneBy(['apiId' => $extensionApiId]);

            if (!$extension) {
                $extension = new Extension();
                $extension->setApiId($extensionApiId);
                $io->info('Creating new extension: ' . $setData['name']);
            }

            $extension->setName($setData['name']);
            $extension->setTotalCardsMain($setData['total_cards_main']);
            $extension->setTotalCardsSecret($setData['total_cards_secret']);
            $extension->setSerie($serie);
            $this->entityManager->persist($extension);

            $io->progressStart(count($setData['cards']));

            foreach ($setData['cards'] as $cardData) {
                $cardApiId = $cardData['id_carte'];
                $pokemonCard = $this->entityManager->getRepository(PokemonCard::class)->findOneBy(['apiId' => $cardApiId]);

                if (!$pokemonCard) {
                    $pokemonCard = new PokemonCard();
                    $pokemonCard->setApiId($cardApiId);
                }

                $pokemonCard->setNumber($cardData['numero']);
                $pokemonCard->setName($cardData['nom']);
                $pokemonCard->setCategory($cardData['categorie']);
                $pokemonCard->setSpecialType($cardData['type_special']);
                $pokemonCard->setRaritySymbol($cardData['rarete_symbole']);
                $pokemonCard->setRarityText($cardData['rarete_texte']);
                $pokemonCard->setIsReversePossible($cardData['reverse_possible']);
                $pokemonCard->setSubSerie($cardData['sous_serie']);
                $pokemonCard->setExtension($extension);

                $this->entityManager->persist($pokemonCard);
                $io->progressAdvance();
                $totalImportedCards++;
            }

            $io->progressFinish();
        }

        $io->writeln('Flushing data to the database...');
        $this->entityManager->flush();

        $io->success(sprintf('Import finished! Processed %d cards.', $totalImportedCards));

        return Command::SUCCESS;
    }
}