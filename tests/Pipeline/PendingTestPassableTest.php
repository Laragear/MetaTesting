<?php

namespace Tests\Pipeline;

use Illuminate\Support\Fluent;
use Laragear\MetaTesting\Pipeline\PendingTestPassable;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

class PendingTestPassableTest extends TestCase
{
    public function test_assert_passable(): void
    {
        $pending = new PendingTestPassable($this->app, $instance = new TestPendingPassable());

        $pending->assertPassable(function ($passable, $container) use ($instance) {
            static::assertSame($passable, $instance);
            static::assertSame($this->app, $container);

            return true;
        });
    }

    public function test_assert_passable_fails(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable());

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The passable is not what is expected.');

        $pending->assertPassable(fn() => false);
    }

    public function test_assert_passable_has(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $pending->assertPassableHas('foo');
    }

    public function test_assert_passable_has_fails_when_key_does_not_exist(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The passable does not contain the key or property [bar].');

        $pending->assertPassableHas('bar');
    }

    public function test_assert_passable_has_with_expectation(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $pending->assertPassableHas('foo', 'bar');
    }

    public function test_assert_passable_has_with_expectation_not_strict(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => '0']));

        $pending->assertPassableHas('foo', 0, false);
    }

    public function test_assert_passable_has_with_expectation_not_strict_fails(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => '0']));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The passable key or property [foo] does not match the expected value.');

        $pending->assertPassableHas('foo', 1, false);
    }

    public function test_assert_passable_has_with_expectation_closure(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $pending->assertPassableHas('foo', function (mixed $value) {
            return $value === 'bar';
        });
    }

    public function test_assert_passable_has_with_expectation_closure_fails(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The passable key or property [foo] does not match the expected value.');

        $pending->assertPassableHas('foo', function (mixed $value) {
            return $value !== 'bar';
        });
    }

    public function test_assert_passable_missing(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $pending->assertPassableMissing('bar');
    }

    public function test_assert_passable_missing_fails_when_key_does_not_exist(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The passable contains the key or property [foo].');

        $pending->assertPassableMissing('foo');
    }

    public function test_assert_passable_missing_with_expectation(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $pending->assertPassableMissing('bar', null);
        $pending->assertPassableMissing('foo', null);
    }

    public function test_assert_passable_missing_with_expectation_not_strict(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => '0']));

        $pending->assertPassableMissing('foo', 1, false);
    }

    public function test_assert_passable_missing_with_expectation_not_strict_fails(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => '0']));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The passable key or property [foo] matches the expected value.');

        $pending->assertPassableMissing('foo', 0, false);
    }

    public function test_assert_passable_missing_with_expectation_closure(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $pending->assertPassableMissing('foo', function (mixed $value) {
            return $value !== 'not-bar';
        });
    }

    public function test_assert_passable_missing_with_expectation_closure_fails(): void
    {
        $pending = new PendingTestPassable($this->app, new TestPendingPassable(['foo' => 'bar']));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The passable key or property [foo] matches the expected value.');

        $pending->assertPassableMissing('foo', function (mixed $value) {
            return $value !== 'bar';
        });
    }
}


class TestPendingPassable extends Fluent
{
    //
}
