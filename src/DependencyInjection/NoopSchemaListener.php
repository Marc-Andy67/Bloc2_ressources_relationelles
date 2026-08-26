<?php

namespace App\DependencyInjection;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * Remplace temporairement les listeners Doctrine cassés par un no-op.
 * Voir RemoveBrokenSchemaListenersPass pour le contexte.
 */
class NoopSchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs): void
    {
        // Ne fait rien volontairement.
    }
}