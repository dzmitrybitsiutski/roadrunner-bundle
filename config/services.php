<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Paysera\RoadRunnerBundle\DependencyInjection\PayseraRoadRunnerExtension;
use Paysera\RoadRunnerBundle\Grpc\GrpcServiceProvider;
use Paysera\RoadRunnerBundle\Helpers\RPCFactory;
use Paysera\RoadRunnerBundle\Http\KernelHandler;
use Paysera\RoadRunnerBundle\Http\MiddlewareStack;
use Paysera\RoadRunnerBundle\Http\RequestHandlerInterface;
use Paysera\RoadRunnerBundle\Reboot\KernelRebootStrategyInterface;
use Paysera\RoadRunnerBundle\RoadRunnerBridge\HttpFoundationWorker;
use Paysera\RoadRunnerBundle\RoadRunnerBridge\HttpFoundationWorkerInterface;
use Paysera\RoadRunnerBundle\Worker\GrpcWorker as InternalGrpcWorker;
use Paysera\RoadRunnerBundle\Worker\HttpDependencies;
use Paysera\RoadRunnerBundle\Worker\HttpWorker as InternalHttpWorker;
use Paysera\RoadRunnerBundle\Worker\Job\JobWorker as InternalJobWorker;
use Paysera\RoadRunnerBundle\Worker\WorkerRegistry;
use Paysera\RoadRunnerBundle\Worker\WorkerRegistryInterface;
use Psr\Log\LoggerInterface;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\EnvironmentInterface;
use Spiral\RoadRunner\GRPC\Invoker as GrpcInvoker;
use Spiral\RoadRunner\GRPC\Server as GrpcServer;
use Spiral\RoadRunner\GRPC\ServiceInterface as GrpcServiceInterface;
use Spiral\RoadRunner\Http\HttpWorker;
use Spiral\RoadRunner\Http\HttpWorkerInterface;
use Spiral\RoadRunner\Metrics\Metrics;
use Spiral\RoadRunner\Metrics\MetricsInterface;
use Spiral\RoadRunner\Worker as RoadRunnerWorker;
use Spiral\RoadRunner\WorkerInterface as RoadRunnerWorkerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('paysera_road_runner.intercept_side_effect', true);

    $services = $container->services();

    // RoadRuner services
    $services->set(EnvironmentInterface::class)
        ->factory([Environment::class, 'fromGlobals']);

    $services->set(RoadRunnerWorkerInterface::class, RoadRunnerWorker::class)
        ->factory([RoadRunnerWorker::class, 'createFromEnvironment'])
        ->args([service(EnvironmentInterface::class), '%paysera_road_runner.intercept_side_effect%']);

    $services->set(HttpWorkerInterface::class, HttpWorker::class)
        ->args([service(RoadRunnerWorkerInterface::class)]);

    $services->set(RPCInterface::class)
        ->factory([RPCFactory::class, 'fromEnvironment'])
        ->args([service(EnvironmentInterface::class)]);

    $services->set(MetricsInterface::class, Metrics::class)
        ->args([service(RPCInterface::class)]);

    // Bundle services
    $services->set(HttpFoundationWorkerInterface::class, HttpFoundationWorker::class)
        ->args([service(HttpWorkerInterface::class)]);

    $services->set(WorkerRegistryInterface::class, WorkerRegistry::class)
        ->public();

    $services->set(InternalHttpWorker::class)
        ->public() // Manually retrieved on the DIC in the Worker if the kernel has been rebooted
        ->tag('monolog.logger', ['channel' => PayseraRoadRunnerExtension::MONOLOG_CHANNEL])
        ->args([
            service('kernel'),
            service(LoggerInterface::class),
            service(HttpFoundationWorkerInterface::class),
        ]);

    $services
        ->get(WorkerRegistryInterface::class)
        ->call('registerWorker', [
            Environment\Mode::MODE_HTTP,
            service(InternalHttpWorker::class),
        ]);

    $services->set(HttpDependencies::class)
        ->public() // Manually retrieved on the DIC in the Worker if the kernel has been rebooted
        ->args([
            service(MiddlewareStack::class),
            service(KernelRebootStrategyInterface::class),
            service(EventDispatcherInterface::class),
        ]);

    $services->set(KernelHandler::class)
        ->args([
            service('kernel'),
        ]);

    $services->set(MiddlewareStack::class)
        ->args([service(KernelHandler::class)]);

    $services->alias(RequestHandlerInterface::class, MiddlewareStack::class);

    if (interface_exists(GrpcServiceInterface::class)) {
        $services->set(GrpcServiceProvider::class);
        $services->set(GrpcInvoker::class);

        $services->set(GrpcServer::class)
            ->args([
                service(GrpcInvoker::class),
            ]);

        $services->set(InternalGrpcWorker::class)
            ->public() // Manually retrieved on the DIC in the Worker if the kernel has been rebooted
            ->tag('monolog.logger', ['channel' => PayseraRoadRunnerExtension::MONOLOG_CHANNEL])
            ->args([
                service(LoggerInterface::class),
                service(RoadRunnerWorkerInterface::class),
                service(GrpcServiceProvider::class),
                service(GrpcServer::class),
            ]);

        $services
            ->get(WorkerRegistryInterface::class)
            ->call('registerWorker', [
                Environment\Mode::MODE_GRPC,
                service(InternalGrpcWorker::class),
            ]);
    }

    $services
        ->get(WorkerRegistryInterface::class)
        ->call('registerWorker', [
            Environment\Mode::MODE_JOBS,
            service(InternalJobWorker::class),
        ]);

};
