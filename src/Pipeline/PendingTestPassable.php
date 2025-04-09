<?php

namespace Laragear\MetaTesting\Pipeline;

use Closure;
use Illuminate\Container\Container;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\Constraint\IsTrue;
use PHPUnit\Framework\Constraint\LogicalNot;
use function data_get;
use function func_num_args;

/**
 * @template TPassable
 */
class PendingTestPassable
{
    /**
     * Create a new Pending Test Passable instance.
     *
     * @param  TPassable  $passable
     */
    public function __construct(protected Container $container, protected mixed $passable)
    {
        //
    }

    /**
     * Assert the passable result is what is expected
     *
     * @param  \Closure(TPassable, \Illuminate\Container\Container):bool  $callback
     * @return $this
     */
    public function assertPassable(Closure $callback): static
    {
        PHPUnit::assertThat(
            (bool) $callback($this->passable, $this->container),
            new IsTrue(),
            'The passable is not what is expected.',
        );

        return $this;
    }

    /**
     * Assert the passable has the given key or property in "dot.notation".
     *
     * @param  (\Closure(mixed, TPassable):bool)|mixed  $expected
     * @return $this
     */
    public function assertPassableHas(string $key, mixed $expected = null, bool $same = true): static
    {
        $value = data_get($this->passable, $key, $default = (object) []);

        if (func_num_args() === 1) {
            PHPUnit::assertThat(
                $value, new LogicalNot(new IsIdentical($default)),
                "The passable does not contain the key or property [$key].",
            );
        } else {
            $message = "The passable key or property [$key] does not match the expected value.";

            if ($expected instanceof Closure) {
                PHPUnit::assertThat((bool) $expected($value, $this->passable), new IsTrue(), $message);
            } else {
                PHPUnit::assertThat(
                    $value, $same ? new IsIdentical($expected) : new IsEqual($expected), $message,
                );
            }
        }

        return $this;
    }

    /**
     * Assert the passable doesn't have the given key or property in "dot.notation".
     *
     * @param  (\Closure(mixed, TPassable):bool)|mixed  $expected
     * @return $this
     */
    public function assertPassableMissing(string $key, mixed $expected = null, bool $same = true): static
    {
        $value = data_get($this->passable, $key, $default = (object) []);

        if (func_num_args() === 1) {
            PHPUnit::assertThat(
                $value, new IsIdentical($default), "The passable contains the key or property [$key].",
            );
        } else {
            $message = "The passable key or property [$key] matches the expected value.";

            if ($expected instanceof Closure) {
                PHPUnit::assertThat((bool) $expected($value, $this->passable), new IsTrue(), $message);
            } else {
                PHPUnit::assertThat(
                    $value, new LogicalNot($same ? new IsIdentical($expected) : new IsEqual($expected)), $message,
                );
            }
        }

        return $this;
    }
}
