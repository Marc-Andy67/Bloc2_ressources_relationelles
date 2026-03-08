<?php

namespace App\EventListener;

use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Ressource::class)]
class RessourceEntityListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function prePersist(Ressource $ressource): void
    {
        if ($ressource->getCreationDate() === null) {
            $ressource->setCreationDate(new \DateTime());
        }

        if ($ressource->getAuthor() === null) {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $ressource->setAuthor($user);
            }
        }

        if ($ressource->getStatus() === null || $ressource->getStatus() === 'pending') {
            $user = $this->security->getUser();
            if ($user && $this->security->isGranted('ROLE_MODERATOR')) {
                $ressource->setStatus('validated');
            } else {
                $ressource->setStatus('pending');
            }
        }
    }
}
