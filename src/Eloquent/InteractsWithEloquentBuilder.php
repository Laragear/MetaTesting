<?php

namespace Laragear\MetaTesting\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use ReflectionClass;
use ReflectionObject;

use function get_class;
use function is_object;

/**
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait InteractsWithEloquentBuilder
{
    /**
     * Restore the original Eloquent Builder for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public function unmockQueryFor(Model|string $model): void
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        if (isset(PendingBuilderTestProxy::$originalBuilders[$model])) {
            (new ReflectionClass($model))->setStaticPropertyValue(
                'builder', PendingBuilderTestProxy::$originalBuilders[$model]
            );

            unset(PendingBuilderTestProxy::$originalBuilders[$model]);
        }
    }

    /**
     * Return a Pending Eloquent Builder Test.
     *
     * @param  \Illuminate\Database\Eloquent\Model|class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  \Closure(\Mockery\MockInterface):void|null  $callback
     */
    public function mockQueryFor(Model|string $model, ?Closure $callback = null): PendingBuilderTest
    {
        if (is_object($model)) {
            $model = get_class($model);
        }

        $reflection = new ReflectionObject($instance = new $model);

        // Save the original eloquent builder instance.
        PendingBuilderTestProxy::$originalBuilders[$model] = $reflection->getStaticPropertyValue('builder');

        $this->beforeApplicationDestroyed(function () use ($model): void {
            $this->unmockQueryFor($model);
        });

        $reflection->setStaticPropertyValue('builder', PendingBuilderTestProxy::class);

        $pending = PendingBuilderTestProxy::$builders[$model] = new PendingBuilderTest(Mockery::mock(
            PendingBuilderTestProxy::$originalBuilders[$model]
        ));

        // Ensure it mocks the eager relations and count.
        $pending->mock()->expects('with')->atLeast()->once()
            ->with($reflection->getProperty('with')->getValue($instance))
            ->andReturnSelf();
        $pending->mock()->expects('withCount')->atLeast()->once()
            ->with($reflection->getProperty('withCount')->getValue($instance))
            ->andReturnSelf();

        if ($callback) {
            $callback($pending->mock());
        }

        return $pending;
    }
}
