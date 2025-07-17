<?php

namespace Laragear\MetaTesting\Eloquent;

use Mockery\CompositeExpectation;
use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\MockInterface;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Mockery\Expectation
 */
class PendingBuilderTest
{
    /**
     * Create a new Pending Builder Test instance.
     */
    public function __construct(
        protected MockInterface $mock,
        protected bool $continue = true,
        protected ExpectationInterface|Expectation|CompositeExpectation|null $last = null,
    ) {
        //
    }

    /**
     * Returns the currently mocked Eloquent Builder Mock instance.
     */
    public function mock(): MockInterface
    {
        return $this->mock;
    }

    /**
     * Makes the builder next method not return self.
     *
     * @return $this
     */
    public function and(): static
    {
        $this->continue = false;

        return $this;
    }

    /**
     * Dynamically handle all property access to the Mock Interface instance.
     */
    public function __get(string $name): static
    {
        return $this->__call($name, []);
    }

    /**
     * Dynamically handle all calls to the Mock Interface instance.
     */
    public function __call(string $name, array $arguments): ExpectationInterface|Expectation|CompositeExpectation|static
    {
        $this->last = $this->mock->expects($name)->with(...$arguments); // @phpstan-ignore-line

        if ($this->continue) {
            $this->last->andReturnSelf();  // @phpstan-ignore-line

            return $this;
        }

        [$last, $this->last] = [$this->last, null];

        return $last;
    }
}
