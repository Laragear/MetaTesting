<?php

namespace Laragear\MetaTesting\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Mockery\MockInterface;
use ReflectionClass;
use RuntimeException;
use function get_class;
use function is_object;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class PendingBuilderTestProxy
{
    /**
     * The proxy builders registered.
     *
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, \Laragear\MetaTesting\Eloquent\PendingBuilderTest>
     */
    public static array $builders = [];

    /**
     * The original builders of the registered models.
     *
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, class-string<\Illuminate\Database\Eloquent\Builder>
     */
    public static array $originalBuilders = [];

    /**
     * Create a new Pending Builder Test instance.
     */
    public function __construct(protected mixed $builder)
    {
        //
    }

    /**
     * Set a model instance for the model being queried.
     */
    public function setModel(Model $model): MockInterface
    {
        if (isset(static::$builders[$model::class])) {
            return static::$builders[$model::class]->mock();
        }

        $class = get_class($model);

        throw new RuntimeException("The Pending Builder Test was not set up for [$class].");
    }
}
