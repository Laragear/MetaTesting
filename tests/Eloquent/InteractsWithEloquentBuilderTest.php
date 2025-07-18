<?php

namespace Tests\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Laragear\MetaTesting\Eloquent\InteractsWithEloquentBuilder;
use Laragear\MetaTesting\Eloquent\PendingBuilderTestProxy;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class InteractsWithEloquentBuilderTest extends TestCase
{
    use InteractsWithEloquentBuilder;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! (new ReflectionClass(Model::class))->hasProperty('builder')) {
            static::markTestSkipped('Cannot test Model custom builder as property is not set (v11.15.0).');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        (new ReflectionClass(Model::class))->setStaticPropertyValue('builder', Builder::class);

        PendingBuilderTestProxy::$builders = [];
        PendingBuilderTestProxy::$originalBuilders = [];
    }

    public function test_query_with_builder_methods(): void
    {
        $expected = new Collection(['foo']);

        $this->mockQueryFor(User::class)->where('foo', 'bar')->and()->get(['test'])->andReturn($expected);

        $result = User::where('foo', 'bar')->get(['test']);

        static::assertSame($expected, $result);
    }

    public function test_query_with_function(): void
    {
        $expected = new Collection(['foo']);

        $this->mockQueryFor(User::class, function ($mock) use ($expected) {
            $mock->expects('where')->with('foo', 'bar')->andReturnSelf();
            $mock->expects('get')->with(['test'])->andReturn($expected);
        });

        $result = User::where('foo', 'bar')->get(['test']);

        static::assertSame($expected, $result);
    }

    public function test_query_with_mock_from_pending_builder_test(): void
    {
        $expected = new Collection(['foo']);

        $mock = $this->mockQueryFor(User::class)->mock();

        $mock->expects('where')->with('foo', 'bar')->andReturnSelf();
        $mock->expects('get')->with(['test'])->andReturn($expected);

        $result = User::where('foo', 'bar')->get(['test']);

        static::assertSame($expected, $result);
    }

    public function test_ensures_original_builder_is_restored(): void
    {
        $this->mockQueryFor(User::class);

        User::query();

        static::assertSame(PendingBuilderTestProxy::class, (new ReflectionClass(User::class))->getStaticPropertyValue('builder'));

        $this->beforeApplicationDestroyedCallbacks[0]();

        static::assertSame(Builder::class, (new ReflectionClass(User::class))->getStaticPropertyValue('builder'));

        unset($this->beforeApplicationDestroyedCallbacks[0]);
    }

    public function test_throws_if_uses_pending_builder_without_setting_it_up(): void
    {
        (new ReflectionClass($class = User::class))->setStaticPropertyValue('builder', PendingBuilderTestProxy::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The Pending Builder Test was not set up for [$class].");

        User::query();
    }

    public function test_doesnt_throws_if_restore_builder_without_setting_it_up(): void
    {
        $this->unmockQueryFor(User::class);

        $this->expectNotToPerformAssertions();
    }

    public function test_unmocks_builder(): void
    {
        $this->mockQueryFor(User::class);

        User::query();

        static::assertSame(PendingBuilderTestProxy::class, (new ReflectionClass(User::class))->getStaticPropertyValue('builder'));

        $this->unmockQueryFor(User::class);

        static::assertSame(Builder::class, (new ReflectionClass(User::class))->getStaticPropertyValue('builder'));
    }
}
