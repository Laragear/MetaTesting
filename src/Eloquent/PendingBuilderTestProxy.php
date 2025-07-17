<?php

namespace Laragear\MetaTesting\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Mockery\MockInterface;
use RuntimeException;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class PendingBuilderTestProxy
{
    /**
     * @var array<class-string<\Illuminate\Database\Eloquent\Model>, \Laragear\MetaTesting\Eloquent\PendingBuilderTest>
     */
    public static array $models = [];

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
        if (isset(static::$models[$model::class])) {
            return static::$models[$model::class]->mock();
        }

        throw new RuntimeException('Pending Builder Test was not expected.');
    }
}
