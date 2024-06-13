<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Worker\Job\Event\Handler;

use Paysera\RoadRunnerBundle\Worker\Job\Event\Exception\EventHandlerException;
use Psr\Log\LoggerInterface;
use Throwable;

class EventHandler implements EventHandlerInterface
{
    /**
     * @param array<string, callable> $configMap
     */
    public function __construct(
        private readonly array $configMap,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws EventHandlerException
     * @throws \ErrorException
     */
    public function handle(object $event): void
    {
        $eventClass = $event::class;

        $handlerClass = $this->configMap[$eventClass] ?? null;

        if (empty($handlerClass)) {
            $this->logger->debug(sprintf('skipped for the event: %s, reason: %s', $eventClass, 'not found handler'));

            return;
        }

        if (!\is_callable($handlerClass, false, $callableName)) {
            throw new EventHandlerException(sprintf('failed to call the handler for event %s is not callable: %s', $event::class, $callableName));
        }

        try {
            $handlerClass($event);
        } catch (Throwable $throwable) {
            throw new EventHandlerException(sprintf('failed to handle the event %s in handler %s', $event::class, $callableName), 0, $throwable);
        }
    }
}
