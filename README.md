# Meta Testing
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/meta-testing.svg)](https://packagist.org/packages/laragear/meta-testing)
[![Latest stable test run](https://github.com/Laragear/MetaTesting/workflows/Tests/badge.svg)](https://github.com/Laragear/MetaTesting/actions)
[![Codecov coverage](https://codecov.io/gh/Laragear/MetaTesting/graph/badge.svg?token=bogXap7Rjn)](https://codecov.io/gh/Laragear/MetaTesting)
[![Maintainability](https://qlty.sh/badges/2cf17cad-2946-4567-9eb3-be4b97d9f804/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/MetaTesting)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_MetaTesting&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_MetaTesting)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/11.x/octane#introduction)

A Laravel Package for testing Laravel Packages.

```php
public function test_has_service_registered(): void
{
    $this->assertHasServices('my-cool-service');
}
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **spread the word on social media**

## Requirements

* PHP 8.3 or later
* Laravel 12 or later.

## Installation

Require this package into your project using Composer:

```bash
composer require --dev laragear/meta-testing
```

> [!CAUTION]
> 
> **DO NOT** install this package outside `require-dev`, unless you plan to use this package in production environments. 

## Testing

### Testing the Service Provider

The `InteractsWithServiceProvider` allows to quickly test if the Service Provider of your package has registered all the needed bits of code into the Service Container.

```php
use Orchestra\Testbench\TestCase
use Laragear\MetaTesting\InteractsWithServiceProvider;

class ServiceProviderTest extends TestCase
{
    use InteractsWithServiceProvider;
    
    public function test_is_registered_as_singleton(): void
    {
        $this->assertHasSingletons(\Vendor\Package\MyService::class);
    }
}
```

The available assertions are in this table:

| Method                         | Description                                                                           | 
|--------------------------------|---------------------------------------------------------------------------------------|
| `assertHasDriver()`            | Assert a service as registered given driver name                                      |
| `assertHasServices()`          | Assert services have been registered into the Service Container                       |
| `assertHasSingletons()`        | Assert services have been registered as a shared instance into the Service Container  |
| `assertHasAlias()`             | Assert a service has been registered with the issued alias                            |
| `assertConfigMerged()`         | Assert the library configuration file has been merged into the application.           |
| `assertPublishes()`            | Assert the library publishes the paths into the application                           |
| `assertPublishesMigrations()`  | Assert the library publishes the migrations into the application                      |
| `assertHasTranslations()`      | Assert the library translations have been registered into the translator              |
| `assertHasViews()`             | Assert the library views have been registered into the view compiler                  |
| `assertHasBladeComponent()`    | Assert the library Blade Components have been registered into the view compiler       |
| `assertHasBladeDirectives()`   | Assert the library Blade Directives have been registered into the view compiler       |
| `assertHasValidationRules()`   | Assert the library Validation Rules have been registered into the validator           |
| `assertRouteByName()`          | Assert the library Routes names have been registered into the router                  |
| `assertRouteByUri()`           | Assert the library Routes URIs have been registered into the router                   |
| `assertRouteByAction()`        | Assert the library Routes actions have been registered into the router                |
| `assertHasMiddlewareAlias()`   | Assert the library Middleware alias has been registered into the router               |
| `assertHasGlobalMiddleware()`  | Assert the library Middleware have been registered globally into the router           |
| `assertHasMiddlewareInGroup()` | Assert the library Middleware have been registered into a middleware group            |
| `assertGateHasAbility()`       | Assert the library abilities have been registered into the authorization gate         |
| `assertGateHasPolicy()`        | Assert the library policies have been registered into the authorization gate          |
| `assertHasCommand()`           | Assert the library commands aliases have been registered into the application console |
| `assertHasCommandParameters()` | Assert the library command has the given options and/or arguments.                    |
| `assertHasScheduledTask()`     | Assert the library scheduled tasks have been registered into the scheduler            |
| `assertScheduledTaskRunsAt()`  | Assert the library scheduled tasks runs at a given moment                             |
| `assertHasListeners()`         | Assert the library has registered listeners for the issued event                      |
| `assertHasMacro()`             | Assert the library macros are registered into the target Macroable class              |

### Service Helpers

The `InteractsWithServices` trait includes helpers to retrieve services from the Service Container and do quick things like checks or preparation.

```php
public function test_something_important(): void
{
    // Get a service from the Service Container, optionally run over a callback.
    $cache = $this->service('cache', fn ($cache) => $cache->set('foo', 'bar', 30));
    
    // Run a service once and forgets it, while running a callback over it.
    $compiler = $this->serviceOnce('blade.compiler', fn($compiler) => $compiler->check('cool'));
    
    // Executes a callback over a REAL service when already mocked.
    $this->unmock('files', function ($files): void {
        $files->copyDirectory('foo', 'bar');
    });
}
```

### Validation

This meta-package includes a `InteractsWithValidator` trait, that assert if a rule passes or fails using minimal data. This is useful when creating validation rules and testing them without too much boilerplate.

```php
use Laragear\MetaTesting\Validation\InteractsWithValidator;

public function test_validation_rule(): void
{
    // Assert the validation rule passes.
    $this->assertValidationPasses(['test' => 'foo'],['test' => 'my_rule']);
    
    // Assert the validation rule fails.
    $this->assertValidationFails(['test' => 'bar'],['test' => 'my_rule']);
}
```

### Middleware

You can test any middleware easily using the `InteractsWithMiddleware` trait and its `middleware()` method. It creates an on-demand route for the given path before sending a test Request to it, so there is no need to register a route.

```php
use Illuminate\Http\Request;
use Vendor\Package\Http\Middleware\MyMiddleware;
use Laragear\MetaTesting\Http\Middleware\InteractsWithMiddleware;

public function test_middleware(): void
{
    $response = $this->middleware(MyMiddleware::class)->using(function (Request $request) {
        // ...
    })->post('test', ['foo' => 'bar']);
    
    $response->assertOk();
}
```

It proxies all `MakesHttpRequest` trait methods, like `get()` or `withUnencryptedCookie()`, so you can get creative with testing your middleware.

```php
$this->middleware(MyMiddleware::class, 'test_argument')
    ->withUnencryptedCookie()
    ->be($this->myTestUser)
    ->post('test/route', ['foo' => 'bar'])
    ->assertSee('John');
```

### Form Request

You can test a Form Request if it passes authorization a validation using different data using the `InteractsWithFormRequests` trait. The `formRequest()` requires the Form Request class, and an `array` with the data to include in the request, to test in isolation.

```php
public function test_form_request()
{
    $this->formRequest(MyFormRequest::class, ['foo' => 'bar'])->assertOk();
}
```

### Authorization

To check if a policy or gate works appropriately, use the `InteractsWithAuthorization` trait to check whether a user _can_ or _cannot_ be authorized to a given action.

```php
public function test_authorization()
{
    $admin = User::factory()->admin()->create();
    
    $this->assertUserCan($admin, 'viewDashboard');
}
```

### Casts

The `InteractsWithCast` trait allows to quickly test if a cast sets values from an attribute appropriately, and can return a given value from an attribute value. It also supports checking on multiple attributes at a time.

```php
public function test_cast()
{
    $this->cast(MyTestCast::class)
        ->assertCastFrom(null, new Cast)
        ->assertCastTo('{"foo":"bar"}', new Cast(['foo' => 'bar']));
}
```

### Eloquent Builder

> [!TIP]
> 
> The Eloquent Builder is only available for Laravel v11.15.0 and later.

To mock the Eloquent Builder of a Model, you may use the `InteractsWithEloquentBuilder` trait and use the `mockQueryFor()` method to make expectations on the builder itself by just calling the methods to chain.

To break the chain, you may use `and()` with the final method to call and the results you want to return through the `andReturn()` or `andReturnUsing()`.

```php
use App\Models\User;

public function test_builder()
{
    $this->mockQueryFor(User::class)->whereKey(1)->and()->get()->shouldReturn(null);
    
    $result = User::query()->whereKey(1)->get();
    
    $this->assertNull($result);
}
```

Alternatively, you may use a function to alter the Builder mock expectations directly, or use the `mock()` helper.

```php
use App\Models\User;
use Mockery\MockInterface;

public function test_builder()
{
    $this->mockQueryFor(User::class, function (MockInterface $mock) {
        $mock->expects('find', 1)->andReturnNull();
    });
    
    $this->assertNull(User::find(1));
}
```

To restore the original Eloquent Builder, you can use `unmockQueryFor($model)` anywhere in your code. For example, it can be part of the last expectation so its restored after a given method call.

```php
use App\Models\User;
use Mockery\MockInterface;
use Laragear\MetaTesting\Eloquent\PendingBuilderTestProxy;

public function test_builder()
{
    // $mock = $this->mockQueryFor(User::class)->mock();

    $this->mockQueryFor(User::class, function (MockInterface $mock) {
        $mock->expects('find', 1)->andReturnUsing(function () {
            $this->unmockQueryFor(User::class);
            
            return null;
        });;
    });
    
    $this->assertNull(User::find(1));
    
    $this->assertNotNull(User::find(1))
}
```

### Pipeline

The `InteractsWithPipeline` trait allows to test and ensure pipes on the pipelines work as expected.

You may use this to test pipelines as a whole, ensuring the pipe order never changes, or test pipes in isolation by mocking services it requires to work. Alternatively, you may also mock the _passable_ to test if some methods are called.

```php
public function test_pipeline()
{
    $pipeline = $this->pipeline(MyPipeline::class)
        // Check the order of pipes
        ->assertPipes([
            FirstPipe::class,
            SecondPipe::class,
            ThirdPipe::class,
        ])
        // Check all pipes have their handler method.
        ->assertVia('handle');

    // Mock the pipeline to be used elsewhere in your application
    $pipeline->mock(function ($mock) {
        $mock->expects('send')->andReturnSelf();
    })

    // Test the passable through all pipes and assert its result.         
    $pipeline->send(new Passable(['value' => 10]))
        ->assertPassable(function (Passable $passable) {
            return $passable->value === 130;
        })
        
    // Test a pipe in isolation and assert the results.
    $pipeline->isolatePipe(SecondClass::class)
        ->send(new Passable(['value' => 10]))
        ->assertPassable(function (Passable $passable) {
            return $passable->value === 100;
        });
        
    // Mock services a pipe or pipeline requires, or mock the passable. 
    $pipeline->isolatePipe(FirstPipe::class)
        ->withMockedService(MyService::class, function ($mock) {
            $mock->expects('sum')->with(0)->andReturn(10);
        })
        ->sendMock(Passable::class, function ($mock) {
            $mock->expects('set')->with(true)->andReturn(10);
        });
}
```

## Laravel Octane compatibility

- There are no singletons using a stale application instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties being written.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security-related issues, issue a [Security Advisory](https://github.com/Laragear/Banpay/security/advisories/new).

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at the time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2026 Laravel LLC.
