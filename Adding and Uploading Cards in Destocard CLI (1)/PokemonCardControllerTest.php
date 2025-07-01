<?php

namespace App\Tests\Controller;

use App\Entity\PokemonCard;
use App\Entity\Extension;
use App\Entity\Serie;
use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PokemonCardControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testIndexPage(): void
    {
        $this->client->request('GET', '/pokemon-card/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Cartes Pokémon');
    }

    public function testAddPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/pokemon-card/add');
        
        // Doit rediriger vers la page de connexion
        $this->assertResponseRedirects();
    }

    public function testAddPokemonCardWithValidData(): void
    {
        // Créer un utilisateur de test et se connecter
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        // Créer des données de test
        $serie = $this->createTestSerie();
        $extension = $this->createTestExtension($serie);

        // Données du formulaire
        $formData = [
            'pokemon_card_form[serie]' => $serie->getId(),
            'pokemon_card_form[extension]' => $extension->getId(),
            'pokemon_card_form[number]' => '001',
            'pokemon_card_form[name]' => 'Pikachu Test',
            'pokemon_card_form[category]' => 'Pokemon',
            'pokemon_card_form[raritySymbol]' => 'common',
            'pokemon_card_form[rarityText]' => 'Common',
            'pokemon_card_form[isReversePossible]' => false,
        ];

        $this->client->request('POST', '/pokemon-card/add', $formData);

        // Vérifier la redirection après succès
        $this->assertResponseRedirects();

        // Vérifier que la carte a été créée en base
        $pokemonCard = $this->entityManager
            ->getRepository(PokemonCard::class)
            ->findOneBy(['name' => 'Pikachu Test']);

        $this->assertNotNull($pokemonCard);
        $this->assertEquals('001', $pokemonCard->getNumber());
        $this->assertEquals('Pokemon', $pokemonCard->getCategory());
    }

    public function testAddPokemonCardWithImage(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $serie = $this->createTestSerie();
        $extension = $this->createTestExtension($serie);

        // Créer un fichier image de test
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $imagePath = sys_get_temp_dir() . '/test_image.png';
        file_put_contents($imagePath, $imageContent);

        $uploadedFile = new UploadedFile(
            $imagePath,
            'test_image.png',
            'image/png',
            null,
            true
        );

        $formData = [
            'pokemon_card_form[serie]' => $serie->getId(),
            'pokemon_card_form[extension]' => $extension->getId(),
            'pokemon_card_form[number]' => '002',
            'pokemon_card_form[name]' => 'Charizard Test',
            'pokemon_card_form[category]' => 'Pokemon',
            'pokemon_card_form[raritySymbol]' => 'rare',
            'pokemon_card_form[rarityText]' => 'Rare',
        ];

        $this->client->request('POST', '/pokemon-card/add', $formData, [
            'pokemon_card_form[imageFile]' => $uploadedFile
        ]);

        $this->assertResponseRedirects();

        // Vérifier que la carte et l'image ont été créées
        $pokemonCard = $this->entityManager
            ->getRepository(PokemonCard::class)
            ->findOneBy(['name' => 'Charizard Test']);

        $this->assertNotNull($pokemonCard);
        $this->assertNotNull($pokemonCard->getImage());
        $this->assertNotNull($pokemonCard->getImage()->getImageUrl());

        // Nettoyer
        unlink($imagePath);
    }

    public function testApiExtensionsBySerie(): void
    {
        $serie = $this->createTestSerie();
        $extension1 = $this->createTestExtension($serie, 'Extension 1');
        $extension2 = $this->createTestExtension($serie, 'Extension 2');

        $this->client->request('GET', '/pokemon-card/api/extensions-by-serie/' . $serie->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertCount(2, $responseData);
        $this->assertEquals('Extension 1', $responseData[0]['name']);
        $this->assertEquals('Extension 2', $responseData[1]['name']);
    }

    public function testApiCheckCardExists(): void
    {
        $serie = $this->createTestSerie();
        $extension = $this->createTestExtension($serie);
        
        // Créer une carte existante
        $existingCard = $this->createTestPokemonCard($extension, '003', 'Existing Card');

        // Tester avec une carte existante
        $this->client->request('POST', '/pokemon-card/api/check-card-exists', [], [], [], 
            json_encode([
                'extensionId' => $extension->getId(),
                'number' => '003'
            ])
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertTrue($responseData['exists']);
        $this->assertEquals('Existing Card', $responseData['card']['name']);

        // Tester avec une carte inexistante
        $this->client->request('POST', '/pokemon-card/api/check-card-exists', [], [], [], 
            json_encode([
                'extensionId' => $extension->getId(),
                'number' => '999'
            ])
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertFalse($responseData['exists']);
        $this->assertNull($responseData['card']);
    }

    public function testShowPokemonCard(): void
    {
        $serie = $this->createTestSerie();
        $extension = $this->createTestExtension($serie);
        $pokemonCard = $this->createTestPokemonCard($extension, '004', 'Show Test Card');

        $this->client->request('GET', '/pokemon-card/' . $pokemonCard->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Show Test Card');
        $this->assertSelectorTextContains('.info-value', '004');
    }

    public function testEditRequiresAdminRole(): void
    {
        $user = $this->createTestUser(); // Utilisateur normal
        $this->client->loginUser($user);

        $serie = $this->createTestSerie();
        $extension = $this->createTestExtension($serie);
        $pokemonCard = $this->createTestPokemonCard($extension, '005', 'Edit Test Card');

        $this->client->request('GET', '/pokemon-card/' . $pokemonCard->getId() . '/edit');

        // Doit être refusé pour un utilisateur normal
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteRequiresAdminRole(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);

        $serie = $this->createTestSerie();
        $extension = $this->createTestExtension($serie);
        $pokemonCard = $this->createTestPokemonCard($extension, '006', 'Delete Test Card');

        $this->client->request('POST', '/pokemon-card/' . $pokemonCard->getId() . '/delete', [
            '_token' => $this->client->getContainer()->get('security.csrf.token_manager')
                ->getToken('delete' . $pokemonCard->getId())->getValue()
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    // Méthodes utilitaires pour créer des données de test

    private function createTestUser()
    {
        $user = new \App\Entity\Users();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setCivility('M');
        $user->setPhoneNumber('0123456789');
        $user->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestSerie(string $name = 'Test Serie'): Serie
    {
        $serie = new Serie();
        $serie->setName($name);

        $this->entityManager->persist($serie);
        $this->entityManager->flush();

        return $serie;
    }

    private function createTestExtension(Serie $serie, string $name = 'Test Extension'): Extension
    {
        $extension = new Extension();
        $extension->setName($name);
        $extension->setSerie($serie);
        $extension->setApiId('test-api-id-' . uniqid());
        $extension->setTotalCardsMain(151);
        $extension->setTotalCardsSecret(10);

        $this->entityManager->persist($extension);
        $this->entityManager->flush();

        return $extension;
    }

    private function createTestPokemonCard(Extension $extension, string $number, string $name): PokemonCard
    {
        $pokemonCard = new PokemonCard();
        $pokemonCard->setExtension($extension);
        $pokemonCard->setNumber($number);
        $pokemonCard->setName($name);
        $pokemonCard->setApiId('test-api-' . uniqid());
        $pokemonCard->setCategory('Pokemon');
        $pokemonCard->setRaritySymbol('common');
        $pokemonCard->setRarityText('Common');
        $pokemonCard->setIsReversePossible(false);

        $this->entityManager->persist($pokemonCard);
        $this->entityManager->flush();

        return $pokemonCard;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyer la base de données de test
        $this->entityManager->close();
        $this->entityManager = null;
    }
}

