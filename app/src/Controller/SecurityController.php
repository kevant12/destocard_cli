<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegisterFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Contrôleur de gestion de l'authentification et de la sécurité
 * 
 * Fonctionnalités principales :
 * - Gestion complète du processus d'inscription avec vérification email
 * - Interface de connexion avec gestion des erreurs d'authentification
 * - Système de réinitialisation de mot de passe sécurisé par token
 * - Vérification des comptes utilisateur via email de confirmation
 * - Intégration complète avec le système de sécurité Symfony
 * 
 * Ce contrôleur gère toutes les logiques de sécurité qui ne sont pas
 * automatiquement interceptées par le pare-feu de Symfony (inscription,
 * vérification email, réinitialisation mot de passe).
 * 
 * Sécurité renforcée :
 * - Tokens CSRF sur tous les formulaires sensibles
 * - Hachage sécurisé des mots de passe avec Symfony PasswordHasher
 * - Tokens de vérification et réinitialisation avec expiration
 * - Protection contre l'énumération d'emails (messages identiques)
 */
class SecurityController extends AbstractController
{
    /**
     * Affiche le formulaire de connexion.
     * Le traitement de la soumission du formulaire est géré par le pare-feu de Symfony,
     * cette méthode n'est donc exécutée que pour afficher la page (requête GET).
     *
     * @param AuthenticationUtils $authenticationUtils Service de Symfony pour obtenir les infos de la dernière tentative de connexion.
     * @return Response
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirige l'utilisateur s'il est déjà connecté.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Récupère l'erreur de connexion s'il y en a une (ex: "mot de passe invalide").
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Récupère le dernier email saisi par l'utilisateur pour pré-remplir le champ.
        $lastUsername = $authenticationUtils->getLastUsername();

        // Rend le template en passant les informations nécessaires.
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Gère la déconnexion.
     * Cette méthode est volontairement vide. Le pare-feu de Symfony intercepte
     * toutes les requêtes vers la route 'app_logout' et gère la déconnexion.
     * La méthode existe uniquement pour que la route puisse être définie.
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Gère le processus d'inscription d'un nouvel utilisateur.
     * Contrairement au login, cette méthode gère à la fois l'affichage (GET) et le traitement (POST) du formulaire.
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator
    ): Response {
        // Redirige si l'utilisateur est déjà connecté.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Crée un nouvel objet utilisateur et le formulaire associé.
        $user = new Users();
        $form = $this->createForm(RegisterFormType::class, $user);

        // Analyse la requête et hydrate l'objet $user avec les données du formulaire.
        $form->handleRequest($request);

        // Traitement uniquement si le formulaire est soumis et valide.
        // La validation inclut les contraintes définies dans le FormType ET sur l'entité (ex: UniqueEntity).
        if ($form->isSubmitted() && $form->isValid()) {
            // Étape 1 : Hasher le mot de passe pour ne JAMAIS le stocker en clair.
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('password')->getData())
            );
            
            // Étape 2 : Créer un token de vérification sécurisé et unique.
            $token = $tokenGenerator->generateToken();
            $user->setVerificationToken($token);
            $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours')); // Le token n'est valide que 24h.
            $user->setIsVerified(false); // Le compte est inactif par défaut.
            
            // Étape 3 : Sauvegarder le nouvel utilisateur en base de données.
            $entityManager->persist($user); // Prépare l'objet pour la sauvegarde.
            $entityManager->flush();        // Exécute la requête SQL pour insérer l'utilisateur.

            // Étape 4 : Envoyer l'email de vérification.
            $this->sendVerificationEmail($user, $mailer);

            // Étape 5 : Afficher un message de succès à l'utilisateur sur la prochaine page.
            $this->addFlash('success', 'Inscription réussie ! Veuillez consulter vos emails pour activer votre compte.');

            // Redirige vers la page de connexion après une inscription réussie (Pattern Post-Redirect-Get).
            return $this->redirectToRoute('app_login');
        }

        // Affiche la page d'inscription (soit la première fois, soit après une erreur de validation).
        return $this->render('security/register.html.twig', [
            'registerForm' => $form,
        ]);
    }

    /**
     * Valide le compte d'un utilisateur via le token reçu par email.
     */
    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        FormLoginAuthenticator $formLoginAuthenticator,
        Request $request
    ): Response {
        // 1. Trouve l'utilisateur grâce au token.
        $user = $usersRepository->findOneBy(['verificationToken' => $token]);

        // 2. Gère les cas d'erreur : token invalide ou expiré.
        if (!$user) {
            $this->addFlash('error', 'Token de vérification invalide.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->getVerificationTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Le token de vérification a expiré.');
            return $this->redirectToRoute('app_login');
        }

        // 3. Active le compte et invalide le token pour qu'il ne soit plus réutilisable.
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);
        
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été activé avec succès ! Vous êtes maintenant connecté.');

        // 4. Authentifie automatiquement l'utilisateur après vérification pour une meilleure UX.
        return $userAuthenticator->authenticateUser(
            $user,
            $formLoginAuthenticator,
            $request
        );
    }

    /**
     * Gère la demande de réinitialisation de mot de passe.
     */
    #[Route('/reset-password-request', name: 'app_reset_password_request')]
    public function resetPasswordRequest(
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $usersRepository->findOneBy(['email' => $email]);

            // Si un utilisateur correspond à cet email, on génère un token et on envoie l'email.
            if ($user) {
                $token = $tokenGenerator->generateToken();
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                
                $entityManager->flush();
                $this->sendResetPasswordEmail($user, $mailer);
            }

            // Pour des raisons de sécurité, on affiche toujours le même message, que l'email existe ou non.
            // Cela empêche un attaquant de deviner quels emails sont enregistrés.
            $this->addFlash('success', 'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_request.html.twig', [
            'resetForm' => $form,
        ]);
    }

    /**
     * Gère la réinitialisation effective du mot de passe via le token.
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $usersRepository->findOneBy(['resetToken' => $token]);

        // Vérifie si le token est valide et non expiré.
        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Token de réinitialisation invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le nouveau mot de passe.
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);
            
            // Invalider le token après utilisation.
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès !');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form,
        ]);
    }

    /**
     * Méthode privée et réutilisable pour construire et envoyer l'email de vérification.
     */
    private function sendVerificationEmail(Users $user, MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from('noreply@votre-site.com')
            ->to($user->getEmail())
            ->subject('Vérification de votre compte')
            ->html($this->renderView('emails/verification.html.twig', [
                'user' => $user,
                'verificationUrl' => $this->generateUrl('app_verify_email', [
                    'token' => $user->getVerificationToken()
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            ]))
            ->text($this->renderView('emails/verification.txt.twig', [
                'user' => $user,
                'verificationUrl' => $this->generateUrl('app_verify_email', [
                    'token' => $user->getVerificationToken()
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            ]));

        $mailer->send($email);
    }

    /**
     * Méthode privée et réutilisable pour construire et envoyer l'email de réinitialisation de mot de passe.
     */
    private function sendResetPasswordEmail(Users $user, MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from('noreply@votre-site.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->renderView('emails/reset_password.html.twig', [
                'user' => $user,
                'resetUrl' => $this->generateUrl('app_reset_password', [
                    'token' => $user->getResetToken()
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            ]))
            ->text($this->renderView('emails/reset_password.txt.twig', [
                'user' => $user,
                'resetUrl' => $this->generateUrl('app_reset_password', [
                    'token' => $user->getResetToken()
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            ]));

        $mailer->send($email);
    }
} 