<?php

namespace App\Tests\Controller;

use App\Entity\Products;
use App\Entity\Users;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProductControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;
    private $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        // Créer un utilisateur de test
        $this->testUser = new Users();
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setFirstname('John');
        $this->testUser->setLastname('Doe');
        $this->testUser->setCivility('Mr');
        $this->testUser->setPhoneNumber('0123456789');
        $this->testUser->setPassword($this->passwordHasher->hashPassword($this->testUser, 'password123'));
        $this->testUser->setRoles(['ROLE_USER']);
        $this->testUser->setIsVerified(true);

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testProductIndexIsAccessible(): void
    {
        $this->client->request('GET', '/product/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testProductNewRequiresAuthentication(): void
    {
        $this->client->request('GET', '/product/new');

        $this->assertResponseRedirects('/login');
    }

    public function testProductNewWithAuthenticatedUser(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request('GET', '/product/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="product_form"]');
    }

    public function testProductNewWithValidData(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request('GET', '/product/new');

        $this->client->submitForm('Créer', [
            'product_form[title]' => 'Test Product',
            'product_form[description]' => 'Test Description',
            'product_form[category]' => 'cards',
            'product_form[quantity]' => 10,
            'product_form[price]' => 25.50,
            'product_form[number]' => 123,
            'product_form[extension]' => 'Test Extension',
            'product_form[rarity]' => 'rare',
            'product_form[type]' => 'creature',
        ]);

        $this->assertResponseRedirects('/product/');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // Vérifier que le produit a été créé
        $productRepository = static::getContainer()->get(ProductsRepository::class);
        $product = $productRepository->findOneBy(['title' => 'Test Product']);
        
        $this->assertNotNull($product);
        $this->assertEquals('Test Product', $product->getTitle());
        $this->assertEquals('Test Description', $product->getDescription());
        $this->assertEquals('cards', $product->getCategory());
        $this->assertEquals(10, $product->getQuantity());
        $this->assertEquals(25.50, $product->getPrice());
        $this->assertEquals($this->testUser, $product->getUsers());
    }

    public function testProductNewWithInvalidData(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request('GET', '/product/new');

        $this->client->submitForm('Créer', [
            'product_form[title]' => '', // Titre vide
            'product_form[description]' => 'Test Description',
            'product_form[category]' => 'cards',
            'product_form[quantity]' => -5, // Quantité négative
            'product_form[price]' => -10, // Prix négatif
            'product_form[number]' => 123,
            'product_form[extension]' => 'Test Extension',
            'product_form[rarity]' => 'rare',
            'product_form[type]' => 'creature',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.form-error-message');
    }

    public function testProductShowIsAccessible(): void
    {
        // Créer un produit de test
        $product = new Products();
        $product->setTitle('Test Product');
        $product->setDescription('Test Description');
        $product->setCategory('cards');
        $product->setQuantity(10);
        $product->setPrice(25.50);
        $product->setNumber(123);
        $product->setExtension('Test Extension');
        $product->setRarity('rare');
        $product->setType('creature');
        $product->setUsers($this->testUser);
        $product->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->client->request('GET', '/product/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Product');
    }

    public function testProductEditRequiresAuthentication(): void
    {
        // Créer un produit de test
        $product = new Products();
        $product->setTitle('Test Product');
        $product->setDescription('Test Description');
        $product->setCategory('cards');
        $product->setQuantity(10);
        $product->setPrice(25.50);
        $product->setNumber(123);
        $product->setExtension('Test Extension');
        $product->setRarity('rare');
        $product->setType('creature');
        $product->setUsers($this->testUser);
        $product->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->client->request('GET', '/product/' . $product->getId() . '/edit');

        $this->assertResponseRedirects('/login');
    }

    public function testProductEditWithOwner(): void
    {
        $this->client->loginUser($this->testUser);

        // Créer un produit appartenant à l'utilisateur de test
        $product = new Products();
        $product->setTitle('Test Product');
        $product->setDescription('Test Description');
        $product->setCategory('cards');
        $product->setQuantity(10);
        $product->setPrice(25.50);
        $product->setNumber(123);
        $product->setExtension('Test Extension');
        $product->setRarity('rare');
        $product->setType('creature');
        $product->setUsers($this->testUser);
        $product->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->client->request('GET', '/product/' . $product->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="product_form"]');
    }

    public function testProductEditWithValidData(): void
    {
        $this->client->loginUser($this->testUser);

        // Créer un produit appartenant à l'utilisateur de test
        $product = new Products();
        $product->setTitle('Test Product');
        $product->setDescription('Test Description');
        $product->setCategory('cards');
        $product->setQuantity(10);
        $product->setPrice(25.50);
        $product->setNumber(123);
        $product->setExtension('Test Extension');
        $product->setRarity('rare');
        $product->setType('creature');
        $product->setUsers($this->testUser);
        $product->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->client->request('GET', '/product/' . $product->getId() . '/edit');

        $this->client->submitForm('Modifier', [
            'product_form[title]' => 'Updated Product',
            'product_form[description]' => 'Updated Description',
            'product_form[category]' => 'figures',
            'product_form[quantity]' => 20,
            'product_form[price]' => 35.75,
            'product_form[number]' => 456,
            'product_form[extension]' => 'Updated Extension',
            'product_form[rarity]' => 'mythic',
            'product_form[type]' => 'artifact',
        ]);

        $this->assertResponseRedirects('/product/' . $product->getId());
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // Vérifier que le produit a été modifié
        $productRepository = static::getContainer()->get(ProductsRepository::class);
        $updatedProduct = $productRepository->find($product->getId());
        
        $this->assertEquals('Updated Product', $updatedProduct->getTitle());
        $this->assertEquals('Updated Description', $updatedProduct->getDescription());
        $this->assertEquals('figures', $updatedProduct->getCategory());
        $this->assertEquals(20, $updatedProduct->getQuantity());
        $this->assertEquals(35.75, $updatedProduct->getPrice());
    }

    public function testProductDeleteRequiresAuthentication(): void
    {
        // Créer un produit de test
        $product = new Products();
        $product->setTitle('Test Product');
        $product->setDescription('Test Description');
        $product->setCategory('cards');
        $product->setQuantity(10);
        $product->setPrice(25.50);
        $product->setNumber(123);
        $product->setExtension('Test Extension');
        $product->setRarity('rare');
        $product->setType('creature');
        $product->setUsers($this->testUser);
        $product->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->client->request('POST', '/product/' . $product->getId(), [
            '_token' => 'invalid-token'
        ]);

        $this->assertResponseRedirects('/login');
    }

    public function testProductDeleteWithOwner(): void
    {
        $this->client->loginUser($this->testUser);

        // Créer un produit appartenant à l'utilisateur de test
        $product = new Products();
        $product->setTitle('Test Product');
        $product->setDescription('Test Description');
        $product->setCategory('cards');
        $product->setQuantity(10);
        $product->setPrice(25.50);
        $product->setNumber(123);
        $product->setExtension('Test Extension');
        $product->setRarity('rare');
        $product->setType('creature');
        $product->setUsers($this->testUser);
        $product->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $productId = $product->getId();

        // Obtenir le token CSRF
        $crawler = $this->client->request('GET', '/product/' . $productId . '/edit');
        $token = $crawler->filter('input[name="product_form[_token]"]')->attr('value');

        $this->client->request('POST', '/product/' . $productId, [
            '_token' => $token
        ]);

        $this->assertResponseRedirects('/product/');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // Vérifier que le produit a été supprimé
        $productRepository = static::getContainer()->get(ProductsRepository::class);
        $deletedProduct = $productRepository->find($productId);
        
        $this->assertNull($deletedProduct);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données de test
        $productRepository = static::getContainer()->get(ProductsRepository::class);
        $products = $productRepository->findAll();
        
        foreach ($products as $product) {
            $this->entityManager->remove($product);
        }
        
        $this->entityManager->remove($this->testUser);
        $this->entityManager->flush();
    }
} 