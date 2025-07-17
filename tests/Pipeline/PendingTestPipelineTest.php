<?php

namespace Tests\Pipeline;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Laragear\MetaTesting\Pipeline\PendingTestPipeline;
use Mockery\MockInterface;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

use function is_object;
use function property_exists;

class PendingTestPipelineTest extends TestCase
{
    public function test_assert_via_with_default_handle_method(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
        ]));

        $pending->assertVia();
    }

    public function test_assert_via_with_custom_handle_method(): void
    {
        $pipeline = new PendingTestPipelineTestInstance($this->app, [
            TestingPipeThird::class,
        ]);

        $pipeline->via('via');

        $pending = new PendingTestPipeline($this->app, $pipeline);

        $pending->assertVia('via');
    }

    public function test_assert_via_fails_if_method_not_same(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
        ]));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The pipeline method [doesnt_exists] is not set in the pipeline.');

        $pending->assertVia('doesnt_exists');
    }

    public function test_assert_via_fails_if_pipe_does_not_have_method(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ]));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The pipe [Tests\Pipeline\TestingPipeThird] does not have method [handle].');

        $pending->assertVia();
    }

    public function test_assert_pipe_order(): void
    {
        $order = [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ];

        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, $order));

        $pending->assertPipes($order);
    }

    public function test_assert_pipe_order_fails_if_order_not_same(): void
    {
        $order = [
            TestingPipeFirst::class,
            TestingPipeThird::class,
            TestingPipeSecond::class,
        ];

        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ]));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The pipeline has a different pipe list.');

        $pending->assertPipes($order);
    }

    public function test_assert_pipe_order_fails_if_pipes_empty(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The pipeline has no pipes.');

        $pending->assertPipes([]);
    }

    public function test_assert_has_pipe(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ]));

        $pending->assertHasPipe(TestingPipeFirst::class);
    }

    public function test_assert_has_pipe_fails_if_pipe_missing(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ]));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The pipe [invalid] is not contained in the list of pipes.');

        $pending->assertHasPipe('invalid');
    }

    public function test_isolate_pipe(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ]));

        $pending->isolatePipe(TestingPipeSecond::class);

        $pending->assertPipes([TestingPipeSecond::class]);
    }

    public function test_isolate_pipe_fails_if_pipe_missing(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeThird::class,
        ]));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The pipe [invalid] is not set in the pipeline array.');

        $pending->isolatePipe('invalid');
    }

    public function test_removes_pipes(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ]));

        $pending->removePipes(TestingPipeThird::class, TestingPipeFirst::class);

        $pending->assertPipes([TestingPipeSecond::class]);
    }

    public function test_removes_pipes_doesnt_fail_if_pipe_doesnt_exist(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            $zero = fn() => true,
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ]));

        $pending->removePipes(TestingPipeSecond::class, 'invalid');

        $pending->assertPipes([$zero, TestingPipeFirst::class, TestingPipeThird::class]);
    }

    public function test_with_mocked_service(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
            TestingPipeThird::class,
        ]));

        $pending->withMockedService(Repository::class, function ($mock) {
            static::assertInstanceOf(MockInterface::class, $mock);
        });

        static::assertInstanceOf(MockInterface::class, $this->app->make(Repository::class));
    }

    public function test_send(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
        ]));

        $passable = (object) [
            'foo' => 'bar',
        ];

        $pending->send($passable);

        static::assertSame('baz', $passable->foo);
    }

    public function test_send_mock(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
        ]));

        $pending->sendMock(TestingPassable::class, function ($mock) {
            $mock->expects('toQuz');
        });
    }

    public function test_send_spy(): void
    {
        $pending = new PendingTestPipeline($this->app, new PendingTestPipelineTestInstance($this->app, [
            TestingPipeFirst::class,
            TestingPipeSecond::class,
        ]));

        $mocked = $pending->sendSpy(TestingPassable::class, function ($mock) {
            static::assertInstanceOf(MockInterface::class, $mock);
        });

        $mocked->shouldHaveReceived('toQuz');
    }
}

class PendingTestPipelineTestInstance extends Pipeline
{
    public function __construct(?Container $container = null, array $pipes = [])
    {
        parent::__construct($container);

        $this->pipes = $pipes;
    }
}

class TestingPipeFirst
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        return $next($passable);
    }
}

class TestingPipeSecond
{
    public function handle(mixed $passable, Closure $next): mixed
    {
        if (is_object($passable) && property_exists($passable, 'foo')) {
            $passable->foo = 'baz';
        }

        if ($passable instanceof TestingPassable) {
            $passable->toQuz();
        }

        return $next($passable);
    }
}

class TestingPipeThird
{
    public function via(): void
    {
    }
}

class TestingPassable
{
    public function __construct(public string $foo = 'bar')
    {
    }

    public function toQuz(): void
    {
        $this->foo = 'quz';
    }
}
