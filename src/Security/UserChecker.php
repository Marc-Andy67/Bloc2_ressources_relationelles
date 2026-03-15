<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été suspendu par un administrateur. Vous ne pouvez plus vous connecter.');
        }

        if ($user->getLockedUntil() && $user->getLockedUntil() > new \DateTime()) {
            throw new CustomUserMessageAccountStatusException(sprintf('Trop de tentatives de connexion échouées. Veuillez réessayer après %s.', $user->getLockedUntil()->format('d/m/Y H:i')));
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof User) {
            return;
        }

        // account is deleted, account is expired, etc.
    }
}
