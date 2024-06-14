<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Runtime;

use Paysera\RoadRunnerBundle\Worker\WorkerRegistryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;

class Runner implements RunnerInterface
{
    public function __construct(
        private KernelInterface $kernel,
        private string $mode
    ) {
    }

    public function run(): int
    {
        $this->kernel->boot();

        /** @var WorkerRegistryInterface $registry */
        $registry = $this->kernel->getContainer()->get(WorkerRegistryInterface::class);
        $worker = $registry->getWorker($this->mode);

        if (null === $worker) {
            error_log(
                sprintf('Missing RR worker implementation for %s mode', $this->mode)
            );

            return 1;
        }

        try {
            $worker->serve();
        } catch (\Throwable $e) {
            error_log(
                sprintf('Error during request processing for %s mode. Error: %s', $this->mode, $e->getMessage())
            );

            return 1;
        }

        return 0;
    }
}
