<?php

namespace IntelligentIntern\LokiBundle;

use IntelligentIntern\LokiBundle\DependencyInjection\Compiler\LogServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class LokiBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new LogServiceCompilerPass());
    }
}
