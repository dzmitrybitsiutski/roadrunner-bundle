<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\DependencyInjection\CompilerPass;

use Paysera\RoadRunnerBundle\Integration\Symfony\ConfigureVarDumperListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemoveConfigureVarDumperListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter('kernel.debug') && !$container->has('data_collector.dump')) {
            $container->removeDefinition(ConfigureVarDumperListener::class);
        }
    }
}
