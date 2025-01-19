<?php

namespace IntelligentIntern\LokiBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class LokiBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        echo "LokiBundle loading services...\n"; // Debug-Ausgabe
        $container->import(__DIR__ . '/../config/services.yaml');
    }
}
