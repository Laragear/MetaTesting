<?php

namespace Laragear\MetaTesting\Http\Middleware;

use Illuminate\Http\Response;

use function implode;

trait InteractsWithMiddleware
{
    /**
     * Create a new pending test for a middleware.
     */
    protected function middleware(string $middleware, string ...$parameters): PendingTestMiddleware
    {
        if ($parameters) {
            $middleware .= ':'.implode(',', $parameters);
        }

        return new PendingTestMiddleware($this, $this->app->make('router'), $middleware, static function (): Response {
            return new Response();
        });
    }
}
