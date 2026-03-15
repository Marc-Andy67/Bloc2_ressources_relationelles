<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginAttemptSubscriber implements EventSubscriberInterface
{
    private const MAX_FAILED_ATTEMPTS = 5;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport) {
            return;
        }

        $user = $passport->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->incrementFailedAttempts();

        if ($user->getFailedAttempts() >= self::MAX_FAILED_ATTEMPTS) {
            $user->setLockedUntil(new \DateTime('+15 minutes'));
        }

        $this->entityManager->flush();
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->resetFailedAttempts();
        $user->setLockedUntil(null);
        $user->setLastConnection(new \DateTime());

        $this->entityManager->flush();
    }
}
