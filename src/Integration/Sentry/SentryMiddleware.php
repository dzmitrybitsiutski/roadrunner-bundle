<?php

declare(strict_types=1);

namespace Paysera\RoadRunnerBundle\Integration\Sentry;

use Paysera\RoadRunnerBundle\Http\MiddlewareInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Sentry\State\HubInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Clear scope and flush transport after each request.
 */
final class SentryMiddleware implements MiddlewareInterface
{
    public function __construct(private HubInterface $hub)
    {
    }

    public function process(Request $request, HttpKernelInterface $next): \Iterator
    {
        $this->hub->pushScope();

        try {
            yield $next->handle($request);
        } finally {
            $result = $this->hub->getClient()?->flush();
            if (class_exists(PromiseInterface::class) && $result instanceof PromiseInterface) {
                $result->wait(false);
            }

            $this->hub->popScope();
        }
    }
}
