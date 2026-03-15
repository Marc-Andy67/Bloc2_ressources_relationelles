<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();
        $payload['roles'] = $user->getRoles();
        $payload['email'] = $user->getUserIdentifier();
        $event->setData($payload);
    }
}
