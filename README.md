# Meta Testing
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/meta-testing.svg)](https://packagist.org/packages/laragear/meta-testing)
[![Latest stable test run](https://github.com/Laragear/MetaTesting/workflows/Tests/badge.svg)](https://github.com/Laragear/MetaTesting/actions)
[![Codecov coverage](https://codecov.io/gh/Laragear/MetaTesting/branch/1.x/graph/badge.svg?token=bogXap7Rjn)](https://codecov.io/gh/Laragear/MetaTesting)
[![Maintainability](https://api.codeclimate.com/v1/badges/70970a4557ebd90484fd/maintainability)](https://codeclimate.com/github/Laragear/MetaTesting/maintainability)
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

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **[spread the word!](http://twitter.com/share?text=I%20am%20using%20this%20cool%20PHP%20package&url=https://github.com%2FLaragear%2FMetaTesting&hashtags=PHP,Laravel)**

## Requirements

* PHP 8.1 or later.
* Laravel 10.x or later.

## Installation

Require this package into your project using Composer:

```bash
composer require --dev laragear/meta-testing
```

**DO NOT** install this package outside `require-dev`, unless you plan to use this package in production environments. 

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

| Methods                       |                           |                               |
|-------------------------------|---------------------------|-------------------------------|
| `assertServices()`            | `assertBladeComponent()`  | `assertGlobalMiddleware()`    |
| `assertSingletons()`          | `assertBladeDirectives()` | `assertMiddlewareInGroup()`   |
| `assertConfigMerged()`        | `assertValidationRules()` | `assertGateHasPolicy()`       |
| `assertPublishes()`           | `assertRouteByName()`     | `assertScheduledTask()`       |
| `assertPublishesMigrations()` | `assertRouteByUri()`      | `assertScheduledTaskRunsAt()` |
| `assertTranslations()`        | `assertRouteByAction()`   | `assertMacro()`               |
| `assertViews()`               | `assertMiddlewareAlias()` |                               |

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

This meta package includes a `InteractsWithValidation` trait, that assert if a rule passes or fails using minimal data. This is useful when creating validation rules and testing them without too much boilerplate.

```php
public function test_validation_rule(): void
{
    // Assert the validation rule passes.
    $this->assertValidationPasses(['test' => 'foo'],['test' => 'my_rule']);
    
    // Assert the validation rule fails.
    $this->assertValidationFails(['test' => 'bar'],['test' => 'my_rule']);
}
```

### Middleware

You can test a middleware easily using the `InteractsWithMiddleware` trait and its `middleware()` method. It creates an on-demand route for the given path before sending a test Request to it, so there is no need to register a route.

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

## Laravel Octane compatibility

- There are no singletons using a stale application instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties being written.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2024 Laravel LLC.
