<?php

namespace IntelligentIntern\LokiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LogServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('App\Factory.LogServiceFactory')) {
            return;
        }

        $definition = $container->findDefinition('App\Factory.LogServiceFactory');

        $taggedServices = $container->findTaggedServiceIds('log.strategy');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addStrategy', [new Reference($id)]);
        }
    }
}
