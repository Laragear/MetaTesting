<?php

namespace Laragear\MetaTesting\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use ReflectionObject;

use function get_class;
use function is_object;

/**
 * @mixin \Illuminate\Foundation\Testing\TestCase
 */
trait InteractsWithEloquentBuilder
{
    /**
     * The original Eloquent Builder of the model to query so it can be restored later.
     *
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>,class-string>
     */
    protected array $originalEloquentBuilder = [];

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
        $this->originalEloquentBuilder[$model] ??= $reflection->getStaticPropertyValue('builder');

        $this->beforeApplicationDestroyed(function () use ($model, $reflection): void {
            $reflection->setStaticPropertyValue('builder', $this->originalEloquentBuilder[$model]);
        });

        $reflection->setStaticPropertyValue('builder', PendingBuilderTestProxy::class);

        $pending = PendingBuilderTestProxy::$models[$model] = new PendingBuilderTest(Mockery::mock(Builder::class));

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
