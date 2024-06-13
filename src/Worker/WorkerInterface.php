<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Worker;

interface WorkerInterface
{
    public function serve(): void;
}
