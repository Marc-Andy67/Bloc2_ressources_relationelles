<?php

namespace App\Security\Voter;

use App\Entity\Ressource;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;

class RessourceVoter extends Voter
{
    public function __construct(private Security $security) {}

    public const EDIT = 'RESSOURCE_EDIT';
    public const DELETE = 'RESSOURCE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof Ressource;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, \Symfony\Component\Security\Core\Authorization\Voter\Vote|null $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Ressource $ressource */
        $ressource = $subject;

        return match ($attribute) {
            self::EDIT, self::DELETE => $this->canEditOrDelete($ressource, $user),
            default => false,
        };
    }

    private function canEditOrDelete(Ressource $ressource, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return $ressource->getAuthor() === $user;
    }
}
