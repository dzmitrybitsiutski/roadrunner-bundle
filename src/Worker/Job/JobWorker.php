<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Worker\Job;

use Paysera\RoadRunnerBundle\Worker\Job\Event\Handler\EventHandlerInterface;
use Paysera\RoadRunnerBundle\Worker\WorkerInterface;
use Psr\Log\LoggerInterface;
use Spiral\RoadRunner\Jobs\ConsumerInterface;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class JobWorker implements WorkerInterface
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly LoggerInterface $logger,
        private readonly ConsumerInterface $consumer,
        private readonly EventHandlerInterface $eventHandler,
    ) {
        $container = $this->kernel->getContainer();

        // TODO:
    }

    public function serve(): void
    {
        register_shutdown_function(function (): void {
            $error = error_get_last();
            if (empty($error)) {
                return;
            }

            $this->logger->error($error['message']);
        });

        /** @var Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface $task */
        while ($task = $this->consumer->waitTask()) {
            try {
                $name = $task->getName(); // "ping"
                $payload = $task->getPayload(); // {"site": "https://example.com"}

                $this->logger->debug(sprintf('handling the task: %s', $name));

                // TODO:
                $this->eventHandler->handle($payload['event']);

                $task->complete();

                $this->logger->debug(sprintf('completed the task: %s', $task->getName()));
            } catch (\Throwable $throwable) {
                $this->workerError($throwable);

                $this->failTask($task, $throwable);
            }
        }
    }

    private function workerError(\Throwable $exception): void
    {
        $this->logger->error($exception);
    }

    /**
     * @throws JobsException
     */
    private function failTask(ReceivedTaskInterface $task, \Throwable $throwable): void
    {
        $task->withHeader('attempts', '0')
            ->withDelay(0)
            ->fail($throwable, false);

        $this->logger->error(sprintf('failed the task: %s, rr_id: %s', $task->getName(), $task->getId()));
    }
}
