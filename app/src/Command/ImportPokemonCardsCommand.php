<?php

namespace App\Command;

use App\Entity\PokemonCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:import-pokemon-cards',
    description: 'Imports Pokémon cards from JSON files.',
)]
class ImportPokemonCardsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private string $projectDir;

    public function __construct(EntityManagerInterface $entityManager, #[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $jsonFilePath = $this->projectDir . '/app/api-pokemon/bd_zenith/extension_zenith';

        if (!file_exists($jsonFilePath)) {
            $io->error('JSON file not found at: ' . $jsonFilePath);
            return Command::FAILURE;
        }

        $content = file_get_contents($jsonFilePath);
        // Extract JSON part from the file content
        $jsonStart = strpos($content, '[');
        $jsonEnd = strrpos($content, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            $io->error('Could not find JSON array in the file.');
            return Command::FAILURE;
        }

        $jsonString = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Error decoding JSON: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        if (!is_array($data)) {
            $io->error('JSON data is not an array.');
            return Command::FAILURE;
        }

        $io->progressStart(count($data));

        foreach ($data as $cardData) {
            $card = new PokemonCard();
            $card->setNumber($cardData['numero']);
            $card->setName($cardData['nom']['fr']);
            // $card->setNomEn($cardData['nom']['en'] ?? null);
            // $card->setNomJp($cardData['nom']['jp'] ?? null);
            $card->setRarity($cardData['rarete']);
            $card->setStarRating($cardData['stars']);
            $card->setHolo($cardData['holo']);
            $card->setReverse($cardData['reverse']);

            $this->entityManager->persist($card);
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('Imported %d Pokémon cards.', count($data)));

        return Command::SUCCESS;
    }
}
