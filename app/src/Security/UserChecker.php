<?php

namespace App\Security;

use App\Entity\Users;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Users) {
            return;
        }

        // Bloquer l'utilisateur si son compte n'est pas vérifié.
        if (!$user->isVerified()) {
            // Le message qui sera affiché à l'utilisateur.
            throw new CustomUserMessageAccountStatusException('Pour vous connecter, vous devez d\'abord activer votre compte. Un e-mail de vérification vous a été adressé. Pensez à vérifier votre dossier de courriers indésirables.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Cette méthode peut rester vide pour notre cas.
        // Elle est appelée après l'authentification (par exemple, pour vérifier si le mot de passe doit être mis à jour).
    }
} 