<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Worker\Job\Event\Exception;

use Exception;

class EventEmptyException extends Exception
{
    public static function required(): self
    {
        return new self('required the event body from payload');
    }
}
