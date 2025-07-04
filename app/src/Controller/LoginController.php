<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur de connexion utilisateur (version alternative)
 * 
 * Note : Ce contrôleur semble dupliquer certaines fonctionnalités de SecurityController.
 * Il pourrait être utile de consolider les fonctionnalités d'authentification dans un seul endroit
 * pour éviter la duplication et améliorer la maintenance du code.
 */
class LoginController extends AbstractController
{
    /**
     * Affiche la page de connexion (route alternative)
     * 
     * Cette méthode fournit une alternative à la route de connexion principale
     * définie dans SecurityController. Elle gère :
     * - La redirection automatique si l'utilisateur est déjà connecté
     * - L'affichage du formulaire de connexion
     * - La récupération des erreurs d'authentification
     * - Le pré-remplissage du champ email en cas d'erreur
     * 
     * @param AuthenticationUtils $authenticationUtils Service Symfony pour les utilitaires d'auth
     * @return Response La page de connexion ou une redirection
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers l'accueil
        // Évite d'afficher la page de connexion inutilement
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
      
        // Récupérer l'erreur de connexion s'il y en a une (ex: "mot de passe invalide")
        // Cette erreur provient de la dernière tentative de connexion échouée
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier nom d'utilisateur (email) saisi par l'utilisateur
        // Permet de pré-remplir le champ pour améliorer l'UX en cas d'erreur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
} 