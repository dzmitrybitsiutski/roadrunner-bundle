<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle;

if (!\function_exists('Paysera\RoadRunnerBundle\consumes')) {
    /**
     * @internal
     */
    function consumes(\Iterator $gen): void
    {
        foreach ($gen as $_) {
        }
    }
}
