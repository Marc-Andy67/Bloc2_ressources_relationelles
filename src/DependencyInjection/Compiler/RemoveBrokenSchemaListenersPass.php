<?php

namespace App\DependencyInjection\Compiler;

use App\DependencyInjection\NoopSchemaListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Neutralise les listeners Doctrine "postGenerateSchema" fournis par symfony/doctrine-bridge
 * qui ne sont pas utilisés dans ce projet (cache Doctrine, remember-me PDO, session PDO, lock store)
 * mais qui font planter make:migration à cause d'un bug connu :
 * https://github.com/symfony/symfony/pull/64617 (incompatibilité doctrine-bridge / doctrine/dbal 4.4.x)
 *
 * On ne supprime pas les services ni leurs arguments (ce sont des ChildDefinition
 * héritant d'une définition parente, donc setArguments() se comporte différemment) :
 * on remplace uniquement leur classe par un no-op. NoopSchemaListener n'ayant pas de
 * constructeur, les arguments existants sont simplement ignorés par PHP.
 *
 * À supprimer une fois le correctif Symfony fusionné et publié.
 */
class RemoveBrokenSchemaListenersPass implements CompilerPassInterface
{
    private const SERVICE_IDS = [
        'doctrine.orm.listeners.doctrine_dbal_cache_adapter_schema_listener',
        'doctrine.orm.listeners.doctrine_token_provider_schema_listener',
        'doctrine.orm.listeners.pdo_session_handler_schema_listener',
        'doctrine.orm.listeners.lock_store_schema_listener',
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::SERVICE_IDS as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setClass(NoopSchemaListener::class);
            }
        }
    }
}