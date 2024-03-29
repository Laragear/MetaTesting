<?php

namespace Laragear\MetaTesting;

use Closure;
use LogicException;
use Mockery\MockInterface;

use function tap;

/**
 * @internal
 */
trait InteractsWithServices
{
    /**
     * Returns a service instance.
     */
    protected function service(string $service, Closure $callback = null): mixed
    {
        $instance = $this->app->make($service);

        if ($callback) {
            $callback($instance, $this->app);
        }

        return $instance;
    }

    /**
     * Invokes a service, runs a callback, and forgets the instance.
     */
    protected function serviceOnce(string $service, Closure $callback): mixed
    {
        return tap($this->service($service, $callback), function () use ($service) {
            $this->app->forgetInstance($service);
        });
    }

    /**
     * Runs a callback over a real service while mocked.
     */
    protected function unmock(string $service, Closure $callback): void
    {
        $mocked = $this->app->make($service);

        if (! $mocked instanceof MockInterface) {
            throw new LogicException("The service '$service' was not mocked to be unmocked.");
        }

        $this->app->forgetInstance($service);

        $callback($this->app->make($service));

        $this->app->instance($service, $mocked);
    }
}
