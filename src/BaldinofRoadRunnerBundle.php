<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle;

use Paysera\RoadRunnerBundle\DependencyInjection\CompilerPass\GrpcServiceCompilerPass;
use Paysera\RoadRunnerBundle\DependencyInjection\CompilerPass\MiddlewareCompilerPass;
use Paysera\RoadRunnerBundle\DependencyInjection\CompilerPass\RemoveConfigureVarDumperListenerPass;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PayseraRoadRunnerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RemoveConfigureVarDumperListenerPass());
        $container->addCompilerPass(new MiddlewareCompilerPass());
        if (interface_exists(ServiceInterface::class)) {
            $container->addCompilerPass(new GrpcServiceCompilerPass());
        }
    }
}
