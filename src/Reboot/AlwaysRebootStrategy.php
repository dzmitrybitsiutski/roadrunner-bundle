<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Reboot;

class AlwaysRebootStrategy implements KernelRebootStrategyInterface
{
    public function shouldReboot(): bool
    {
        return true;
    }

    public function clear(): void
    {
    }
}
