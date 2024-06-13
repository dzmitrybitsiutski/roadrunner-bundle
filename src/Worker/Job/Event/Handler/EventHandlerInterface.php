<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Worker\Job\Event\Handler;

interface EventHandlerInterface
{
    public function handle(object $event);
}
