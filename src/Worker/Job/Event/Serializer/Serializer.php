<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Worker\Job\Event\Serializer;

use Paysera\RoadRunnerBundle\Worker\Job\Event;
use Psr\Log\LoggerInterface;
use Spiral\RoadRunner\Jobs\Exception\SerializationException;
use Spiral\RoadRunner\Jobs\Serializer\SerializerInterface;
use Throwable;

/**
 * Deserializes data in event message from SQS on RoadRunner side.
 */
final class Serializer implements SerializerInterface
{
    CONST EVENT_TYPE = 'event_type';
    CONST EVENT_DATA = 'event';

    public function __construct(
        private readonly Event\EventTypeRecognizer $eventRecognizer,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws SerializationException
     */
    public function deserialize(string $payload): array
    {
        try {
            $eventData = (array) json_decode($payload);

            if (empty($eventData[self::EVENT_TYPE])) {
                throw new SerializationException('Field "event_type" not found. Unrecognized event.');
            }

            if (empty($eventData[self::EVENT_DATA])) {
                throw new SerializationException('Field "event" not found. Impossible parse event data.');
            }

            $event = $this->createEvent($eventData);
            $eventData[self::EVENT_DATA] = $event;

            $this->logger->debug(sprintf(
                'finished to deserialize: "%s" -> "%s"',
                $payload,
                json_encode([$eventData])
            ));

            return $eventData;
        } catch (Throwable $throwable) {
            throw new SerializationException($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
        }
    }

    public function serialize(array $payload): string
    {
        throw new SerializationException('Serialize does not expect in this place.');
    }

    private function createEvent(array $eventData): ?Message
    {
        $apiMessageClassName = $this->eventRecognizer->decodeEventTypeToEventClass($eventData[self::EVENT_TYPE]);

        if (!class_exists($apiMessageClassName)) {
            return null;
        }

        /** @var Message $event */
        $event = new $apiMessageClassName();

        $event->mergeFromString(base64_decode($eventData[self::EVENT_DATA]));

        return $event;
    }
}
