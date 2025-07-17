<?php

namespace Laragear\MetaTesting\Pipeline;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\Constraint\IsEmpty;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\IsTrue;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\TraversableContainsIdentical;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;

use function array_diff;
use function array_map;
use function array_values;
use function in_array;
use function is_string;
use function tap;

class PendingTestPipeline
{
    /**
     * Create a new Pending Pipeline Test.
     */
    public function __construct(protected Container $container, protected Pipeline $pipeline)
    {
        //
    }

    /**
     * Assert the pipeline and all pipes have implemented the handler method.
     *
     * @return $this
     */
    public function assertVia(string $method = 'handle'): static
    {
        PHPUnit::assertThat(
            (new ReflectionProperty($this->pipeline, 'method'))->getValue($this->pipeline),
            new IsEqual($method),
            "The pipeline method [$method] is not set in the pipeline."
        );

        foreach ($this->pipesWithoutParameters() as $pipe) {
            if (! is_callable($pipe)) {
                PHPUnit::assertThat(
                    (new ReflectionClass($pipe))->hasMethod($method),
                    new IsTrue(),
                    "The pipe [$pipe] does not have method [$method]."
                );
            }
        }

        return $this;
    }

    /**
     * Assert the pipes are exactly has the given order.
     *
     * @param  array<class-string>  $pipes
     * @return $this
     */
    public function assertPipes(array $pipes): static
    {
        PHPUnit::assertThat(
            $this->pipes(),
            new LogicalNot(new IsEmpty()),
            'The pipeline has no pipes.'
        );

        PHPUnit::assertThat(
            $this->pipes(),
            new IsEqual($pipes),
            'The pipeline has a different pipe list.'
        );

        return $this;
    }

    /**
     * Assert the pipeline contains all the given pipes.
     *
     * @param  class-string  $pipe
     * @return $this
     */
    public function assertHasPipe(string $pipe): static
    {
        PHPUnit::assertThat(
            $this->pipesWithoutParameters(),
            new TraversableContainsIdentical($pipe),
            "The pipe [$pipe] is not contained in the list of pipes.",
        );

        return $this;
    }

    /**
     * Return the pipes of the pipeline.
     *
     * @return array<int, \Closure|object|class-string>
     */
    protected function pipes(): array
    {
        // Because the method is protected, we will use Reflection to call it.
        return (new ReflectionObject($this->pipeline))->getMethod('pipes')->invoke($this->pipeline);
    }

    /**
     * Parse the pipes by removing the arguments from string-based pipes.
     *
     * @return array<int, \Closure|object|class-string>
     */
    protected function pipesWithoutParameters(): array
    {
        return array_map(
            static function (mixed $pipe): mixed {
                return is_string($pipe) ? Str::after($pipe, ':') : $pipe;
            },
            $this->pipes()
        );
    }

    /**
     * Make a new pipeline with a single-pipe to test in isolation.
     *
     * @param  class-string  $pipe
     * @return $this
     */
    public function isolatePipe(string $pipe): static
    {
        if (! in_array($pipe, $this->pipesWithoutParameters(), true)) {
            PHPUnit::fail("The pipe [$pipe] is not set in the pipeline array.");
        }

        $this->pipeline->through([$pipe]);

        return $this;
    }

    /**
     * Removes a pipe present on the pipeline.
     *
     * @param  class-string  ...$pipes
     * @return $this
     */
    public function removePipes(string ...$pipes): static
    {
        $this->pipeline->through(
            Collection::make($this->pipes())->reject(static function (mixed $pipe) use (&$pipes): bool {
                if (is_string($pipe)) {
                    foreach ($pipes as $key => $excludedPipe) {
                        if (Str::before($pipe, ':') === $excludedPipe) {
                            unset($pipes[$key]);
                            return true;
                        }
                    }
                }

                return false;
            })->values()->all()
        );

        return $this;
    }

    /**
     * Mock services that would be required by the pipeline.
     *
     * @template TService
     *
     * @param  class-string<TService>  $service
     * @param  \Closure(TService&\Mockery\MockInterface, \Illuminate\Container\Container):void  $callback
     * @return $this
     */
    public function withMockedService(string $service, Closure $callback): static
    {
        $this->container->instance($service, $mock = Mockery::mock($service));

        $callback($mock, $this->container);

        return $this;
    }

    /**
     * Send the passable and assert the passable is what is expected.
     *
     * @template TPassable
     *
     * @param  TPassable  $passable
     * @return \Laragear\MetaTesting\Pipeline\PendingTestPassable<TPassable>
     */
    public function send(mixed $passable): PendingTestPassable
    {
        return new PendingTestPassable($this->container, $this->pipeline->send($passable)->thenReturn());
    }

    /**
     * Mock the passable class that would be sent to each pipe.
     *
     * @template TPassable
     *
     * @param  class-string<TPassable>  $passable
     * @param  \Closure(TPassable&\Mockery\MockInterface, \Illuminate\Container\Container):void  $callback
     * @return TPassable&\Mockery\MockInterface
     */
    public function sendMock(string $passable, Closure $callback): MockInterface
    {
        $callback($mock = Mockery::mock($passable), $this->container);

        return tap($mock, $this->send(...));
    }

    /**
     * Mock the passable class that would be sent to each pipe.
     *
     * @template TPassable
     *
     * @param  class-string<TPassable>  $passable
     * @param  \Closure(TPassable&\Mockery\MockInterface, \Illuminate\Container\Container):void  $callback
     * @return TPassable&\Mockery\MockInterface
     */
    public function sendSpy(string $passable, Closure $callback): MockInterface
    {
        $callback($mock = Mockery::spy($passable), $this->container);

        return tap($mock, $this->send(...));
    }
}
