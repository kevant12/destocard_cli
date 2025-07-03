<?php

namespace App\Security;

use App\Entity\Users;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Le UserChecker est un service appelé automatiquement par le pare-feu de Symfony
 * APRÈS que l'utilisateur a été authentifié avec succès (mot de passe correct),
 * mais AVANT qu'il ne soit considéré comme pleinement connecté.
 *
 * C'est l'endroit idéal pour vérifier des règles métier personnalisées comme :
 * - Le compte a-t-il été vérifié par email ?
 * - Le compte a-t-il été banni ?
 * - L'utilisateur doit-il changer son mot de passe ?
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * Cette méthode est appelée juste avant que le Token d'authentification ne soit créé.
     * C'est ici qu'on vérifie si le compte est actif.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        // On s'assure que l'objet est bien une instance de notre classe Users.
        // C'est une bonne pratique si on gère plusieurs types d'utilisateurs.
        if (!$user instanceof Users) {
            return;
        }

        // LA VÉRIFICATION CLÉ : le compte doit être vérifié.
        if (!$user->isVerified()) {
            // Si le compte n'est pas vérifié, on lance une exception spéciale.
            // CustomUserMessageAccountStatusException permet d'afficher un message
            // personnalisé et convivial à l'utilisateur sur la page de connexion,
            // au lieu d'une erreur générique "Bad credentials".
            throw new CustomUserMessageAccountStatusException('Pour vous connecter, vous devez d\'abord activer votre compte. Un e-mail de vérification vous a été adressé. Pensez à vérifier votre dossier de courriers indésirables.');
        }
    }

    /**
     * Cette méthode est appelée juste APRÈS que le Token d'authentification a été créé.
     * Elle est rarement utilisée, mais pourrait servir pour des actions post-connexion
     * comme enregistrer la date de dernière connexion, par exemple.
     * Dans notre cas, nous n'en avons pas besoin.
     */
    public function checkPostAuth(UserInterface $user): void
    {
        // Cette méthode peut rester vide pour notre cas.
        // Elle est appelée après l'authentification (par exemple, pour vérifier si le mot de passe doit être mis à jour).
    }
} 