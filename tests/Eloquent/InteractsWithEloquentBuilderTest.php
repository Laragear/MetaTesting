<?php

namespace Tests\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Laragear\MetaTesting\Eloquent\InteractsWithEloquentBuilder;
use Laragear\MetaTesting\Eloquent\PendingBuilderTestProxy;
use ReflectionClass;
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

        $this->callBeforeApplicationDestroyedCallbacks();

        static::assertSame(Builder::class, (new ReflectionClass(User::class))->getStaticPropertyValue('builder'));
    }
}
