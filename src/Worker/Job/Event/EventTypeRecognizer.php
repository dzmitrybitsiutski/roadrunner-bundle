<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Worker\Job\Event;

use Exception;
use Psr\Log\LoggerInterface;

use function implode;

/**
 * Generates event type based on api event class name.
 */
class EventTypeRecognizer
{
    /**
     * @var string
     */
    private const SEPARATOR = '_';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @throws Exception
     */
    public function decodeEventTypeToEventClass(string $eventType): string
    {
        $context = ['event_type_recognizer' => 'decode_event_type'];
        $eventTypeParts = explode('.', $eventType);

        $event = [];
        foreach ($eventTypeParts as $eventTypePart) {
            $event[] = $this->joinComposite($eventTypePart);
        }

        $eventClass = implode('\\', $event);

        $this->logger->debug(sprintf('event_type %s TO event_class %s', $eventType, $eventClass), $context);

        return $eventClass;
    }

    public function decodeEventClassToEventType(string $eventClass): string
    {
        $context = ['event_type_recognizer' => 'decode_event_class'];

        $namespaceArray = explode('\\', $eventClass);
        $event = [];
        foreach ($namespaceArray as $namespace) {
            if (empty($namespace)) {
                continue;
            }

            $event[] = mb_strtolower($this->explodeComposite($namespace));
        }

        $eventStr = implode('.', $event);

        $this->logger->debug(sprintf('event_class %s TO event_type %s', $eventStr, $eventClass), $context);

        return $eventStr;
    }

    private function joinComposite(string $value): string
    {
        $parts = explode(self::SEPARATOR, $value);
        if (\count($parts) === 1) {
            return ucfirst($value);
        }

        $result = '';
        foreach ($parts as $part) {
            $result .= ucfirst($part);
        }

        return $result;
    }

    private function explodeComposite(string $value): string
    {
        return trim(
            preg_replace('#([A-Z])#', self::SEPARATOR . '$1', $value) ?? $value,
            self::SEPARATOR
        );
    }
}
