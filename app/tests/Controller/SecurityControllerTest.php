<?php

namespace App\Tests\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testRegisterPageIsAccessible(): void
    {
        $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="register_form"]');
    }

    public function testRegisterWithValidData(): void
    {
        $this->client->request('GET', '/register');

        $this->client->submitForm('S\'inscrire', [
            'register_form[email]' => 'test@example.com',
            'register_form[firstname]' => 'John',
            'register_form[lastname]' => 'Doe',
            'register_form[civility]' => 'Mr',
            'register_form[phoneNumber]' => '0123456789',
            'register_form[password][first]' => 'password123',
            'register_form[password][second]' => 'password123',
        ]);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // Vérifier que l'utilisateur a été créé
        $userRepository = static::getContainer()->get(UsersRepository::class);
        $user = $userRepository->findOneBy(['email' => 'test@example.com']);
        
        $this->assertNotNull($user);
        $this->assertEquals('John', $user->getFirstname());
        $this->assertEquals('Doe', $user->getLastname());
        $this->assertEquals('Mr', $user->getCivility());
        $this->assertFalse($user->isVerified());
        $this->assertNotNull($user->getVerificationToken());
    }

    public function testRegisterWithInvalidEmail(): void
    {
        $this->client->request('GET', '/register');

        $this->client->submitForm('S\'inscrire', [
            'register_form[email]' => 'invalid-email',
            'register_form[firstname]' => 'John',
            'register_form[lastname]' => 'Doe',
            'register_form[civility]' => 'Mr',
            'register_form[phoneNumber]' => '0123456789',
            'register_form[password][first]' => 'password123',
            'register_form[password][second]' => 'password123',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.form-error-message');
    }

    public function testRegisterWithMismatchedPasswords(): void
    {
        $this->client->request('GET', '/register');

        $this->client->submitForm('S\'inscrire', [
            'register_form[email]' => 'test@example.com',
            'register_form[firstname]' => 'John',
            'register_form[lastname]' => 'Doe',
            'register_form[civility]' => 'Mr',
            'register_form[phoneNumber]' => '0123456789',
            'register_form[password][first]' => 'password123',
            'register_form[password][second]' => 'differentpassword',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.form-error-message');
    }

    public function testResetPasswordRequestPageIsAccessible(): void
    {
        $this->client->request('GET', '/reset-password-request');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="reset_password_request_form"]');
    }

    public function testResetPasswordRequestWithValidEmail(): void
    {
        // Créer un utilisateur de test
        $user = new Users();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setCivility('Mr');
        $user->setPhoneNumber('0123456789');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/reset-password-request');

        $this->client->submitForm('Envoyer', [
            'reset_password_request_form[email]' => 'test@example.com',
        ]);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // Vérifier que le token a été généré
        $userRepository = static::getContainer()->get(UsersRepository::class);
        $user = $userRepository->findOneBy(['email' => 'test@example.com']);
        
        $this->assertNotNull($user->getResetToken());
        $this->assertNotNull($user->getResetTokenExpiresAt());
    }

    public function testResetPasswordRequestWithInvalidEmail(): void
    {
        $this->client->request('GET', '/reset-password-request');

        $this->client->submitForm('Envoyer', [
            'reset_password_request_form[email]' => 'nonexistent@example.com',
        ]);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success'); // Même message pour la sécurité
    }

    public function testVerifyEmailWithValidToken(): void
    {
        // Créer un utilisateur avec token de vérification
        $user = new Users();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setCivility('Mr');
        $user->setPhoneNumber('0123456789');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);
        $user->setVerificationToken('valid-token');
        $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/verify-email/valid-token');

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');

        // Vérifier que l'utilisateur est maintenant vérifié
        $userRepository = static::getContainer()->get(UsersRepository::class);
        $user = $userRepository->findOneBy(['email' => 'test@example.com']);
        
        $this->assertTrue($user->isVerified());
        $this->assertNull($user->getVerificationToken());
    }

    public function testVerifyEmailWithInvalidToken(): void
    {
        $this->client->request('GET', '/verify-email/invalid-token');

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    public function testVerifyEmailWithExpiredToken(): void
    {
        // Créer un utilisateur avec token expiré
        $user = new Users();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setCivility('Mr');
        $user->setPhoneNumber('0123456789');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);
        $user->setVerificationToken('expired-token');
        $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('-1 hour'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/verify-email/expired-token');

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Nettoyer la base de données de test
        $userRepository = static::getContainer()->get(UsersRepository::class);
        $users = $userRepository->findAll();
        
        foreach ($users as $user) {
            $this->entityManager->remove($user);
        }
        
        $this->entityManager->flush();
    }
} 