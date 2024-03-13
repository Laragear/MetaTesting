<?php

namespace Laragear\MetaTesting;

use DateTimeInterface;
use Illuminate\Auth\AuthManager;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function implode;
use function is_array;
use function now;
use function preg_replace;
use function realpath;
use function strtolower;
use function strtoupper;

/**
 * @mixin \Orchestra\Testbench\TestCase
 */
trait InteractsWithServiceProvider
{
    /**
     * Assert that a service manager contains a given driver.
     */
    protected function assertHasDriver(string $service, string $driver, string $class = null): void
    {
        $manager = $this->app->make($service);

        try {
            $instance = $manager instanceof AuthManager ? $manager->guard($driver) : $manager->driver($driver);
        } catch (InvalidArgumentException) {
            static::fail("The '$service' service doesn't have the driver '$driver'.");
        }

        static::assertNotNull($instance, "The '$driver' for '$service' returns null.");

        if ($class) {
            static::assertInstanceOf($class, $instance, "The the driver '$driver' is not an instance of '$class'.");
        }
    }

    /**
     * Assert the services are registered in the Service Container.
     */
    protected function assertHasServices(string ...$services): void
    {
        foreach ($services as $service) {
            static::assertThat(
                $this->app->bound($service),
                static::isTrue(),
                "The '$service' was not registered in the Service Container.",
            );
        }
    }

    /**
     * Assert a service is registered as a shared instance.
     */
    protected function assertHasSingletons(string ...$services): void
    {
        $this->assertHasServices(...$services);

        foreach ($services as $service) {
            static::assertThat(
                $this->app->isShared($service),
                static::isTrue(),
                "The '$service' is registered as a shared instance in the Service Container.",
            );
        }
    }

    /**
     * Assert a service is registered as a shared instance.
     */
    protected function assertHasShared(string ...$services): void
    {
        $this->assertHasSingletons(...$services);
    }

    /**
     * Assert that the config file is merged into the application using the given key.
     */
    protected function assertConfigMerged(string $file, string $configKey = null): void
    {
        $configKey ??= Str::of($file)->beforeLast('.php')->afterLast('/')->afterLast('\\')->toString();

        static::assertThat(
            $this->app->make('config')->has($configKey),
            static::isTrue(),
            "The configuration file was not merged as '$configKey'.",
        );

        static::assertSame(
            $this->app->make('files')->getRequire($file),
            $this->app->make('config')->get($configKey),
            "The configuration file in '$file' is not the same for '$configKey'.",
        );
    }

    /**
     * Asserts that the given files are set to be published.
     */
    protected function assertPublishes(string $file, string $tag): void
    {
        static::assertArrayHasKey($tag, ServiceProvider::$publishGroups, "The '$tag' is not a publishable tag.");

        static::assertContains(
            realpath($file) ?: $file,
            ServiceProvider::$publishGroups[$tag],
            "The '$file' is not publishable in the '$tag' tag.",
        );
    }

    /**
     * Assert that the migration files in the given path are published.
     *
     * @deprecated Use "assertPublishes" instead.
     */
    protected function assertPublishesMigrations(string $dir, string $tag = 'migrations'): void
    {
        static::assertArrayHasKey($tag, ServiceProvider::$publishGroups, "The '$tag' is not a publishable tag.");

        $files = $this->app->make('files')->files($dir);

        static::assertNotEmpty($files, "The '$dir' has no migration files.");

        $this->travelTo(now()->startOfSecond(), function () use ($tag, $files): void {
            $prefix = now()->format('Y_m_d_His');

            foreach ($files as $file) {
                $filename = $prefix.'_'.preg_replace('/^[\d|_]+/', '', $file->getFilename());

                static::assertContains(
                    $this->app->databasePath("migrations/$filename"),
                    ServiceProvider::$publishGroups[$tag],
                    "The '$filename' is not publishable in the '$tag' tag.",
                );
            }
        });
    }

    /**
     * Assert the translation namespace is registered.
     */
    protected function assertHasTranslations(string $path, string $namespace): void
    {
        $namespaces = $this->app->make('translator')->getLoader()->namespaces();

        static::assertArrayHasKey($namespace, $namespaces, "The '$namespace' translations were not registered.");
        static::assertSame(
            realpath($path) ?: $path,
            $namespaces[$namespace],
            "The '$namespace' does not correspond to the path '$path'."
        );
    }

    /**
     * Assert the view namespace is registered.
     */
    protected function assertHasViews(string $path, string $namespace): void
    {
        $namespaces = $this->app->make('view')->getFinder()->getHints();

        static::assertArrayHasKey($namespace, $namespaces, "The '$namespace' views were not registered.");

        $path = realpath($path) ?: $path;

        static::assertThat($namespaces[$namespace], static::callback(static function (array $paths) use ($path): bool {
            foreach ($paths as $originPath) {
                $originPath = realpath($originPath) ?: $originPath;

                if ($path === $originPath) {
                    return true;
                }
            }

            return false;
        }), "The '$namespace' does not correspond to the path '$path'.");
    }

    /**
     * Assert the blade components are registered.
     */
    protected function assertHasBladeComponent(string $alias, string $component): void
    {
        $aliases = $this->app->make('blade.compiler')->getClassComponentAliases();

        static::assertArrayHasKey($alias, $aliases, "The '$alias' is not registered as component.");
        static::assertSame($component, $aliases[$alias], "The '$component' component is not registered as '$alias'.");
    }

    /**
     * Assert the blade directives are registered.
     */
    protected function assertHasBladeDirectives(string ...$directives): void
    {
        $list = $this->app->make('blade.compiler')->getCustomDirectives();

        foreach ($directives as $directive) {
            static::assertArrayHasKey($directive, $list, "The '$directive' was not registered as a blade directive.");
        }
    }

    /**
     * Assert the validation rules are registered.
     */
    protected function assertHasValidationRules(string ...$rules): void
    {
        $extensions = $this->app->make('validator')->make([], [])->extensions;

        foreach ($rules as $rule) {
            static::assertArrayHasKey($rule, $extensions, "The '$rule' rule was not registered in the validator.");
        }
    }

    /**
     * Assert a route exists for the given name.
     */
    protected function assertRouteByName(string $name): Route
    {
        $route = $this->app->make('router')->getRoutes()->getByName($name);

        static::assertNotNull($route, "There is no route not named '$name'.");

        return $route;
    }

    /**
     * Assert a route exists for the given URI and HTTP verb.
     */
    protected function assertRouteByUri(string $uri, string $verb = 'GET'): Route
    {
        $verb = strtoupper($verb);

        try {
            $route = $this->app->make('router')->getRoutes()->match(Request::create($uri, $verb));
        } catch (NotFoundHttpException) {
            static::fail("There is no route by the URI '$verb:$uri'.");
        }

        static::assertThat($route, static::logicalNot(static::isNull()));

        return $route;
    }

    /**
     * Assert a route exists for the given action.
     *
     * @param  string|string[]|array{class-string,string}  $action
     */
    protected function assertRouteByAction(string|array $action): Route
    {
        if (is_array($action)) {
            $action = implode('@', $action);
        }

        if (strtolower($action) === 'closure') {
            static::fail('Cannot assert a route with a closure-based action.');
        }

        $route = $this->app->make('router')->getRoutes()->getByAction($action);

        static::assertNotNull($route, "There is no route by the action '$action'.");

        return $route;
    }

    /**
     * Assert the middleware are aliased.
     */
    protected function assertHasMiddlewareAlias(string $alias, string $middleware): void
    {
        $registered = $this->app->make('router')->getMiddleware();

        static::assertArrayHasKey($alias, $registered, "The '$alias' alias was not registered as middleware.");
        static::assertSame($middleware, $registered[$alias],
            "The '$middleware' was not aliased as '$alias' middleware.");
    }

    /**
     * Assert the middleware is registered globally.
     */
    protected function assertHasGlobalMiddleware(string ...$middleware): void
    {
        $kernel = $this->app->make(Kernel::class);

        foreach ($middleware as $class) {
            static::assertThat(
                $kernel->hasMiddleware($class),
                static::isTrue(),
                "The '$class' middleware was not registered as global.",
            );
        }
    }

    /**
     * Assert the middleware is registered in a middleware group.
     */
    protected function assertHasMiddlewareInGroup(string $group, string $middleware): void
    {
        $list = $this->app->make(Kernel::class)->getMiddlewareGroups();

        static::assertThat(
            $list, static::arrayHasKey($group), "The middleware group '$group' is not defined by default.",
        );

        static::assertThat(
            $list[$group],
            static::containsEqual($middleware),
            "The middleware '$middleware' is not part of the '$group' group.",
        );
    }

    /**
     * Assert the gate has a given ability.
     */
    protected function assertGateHasAbility(string ...$abilities): void
    {
        $gates = $this->app->make(Gate::class)->abilities();

        foreach ($abilities as $ability) {
            static::assertThat($gates, static::arrayHasKey($ability), "The '$ability' is not registered as a gate.");
        }
    }

    /**
     * Assert that a model has registered a Policy.
     */
    protected function assertGateHasPolicy(string $model, string ...$abilities): void
    {
        $policy = $this->app->make(Gate::class)->getPolicyFor($model);

        static::assertNotNull($policy, "The policy for '$model' does not exist.");

        $target = get_class($policy);

        foreach ($abilities as $ability) {
            try {
                $method = new ReflectionMethod($policy, $ability);
            } catch (ReflectionException) {
                static::fail("The '$ability' ability is not declared in the '$target' policy for '$model'.");
            }

            static::assertThat(
                $method->isPublic() && ! $method->isStatic(),
                static::isTrue(),
                "The '$ability' ability declared in '$target' is private/protected or static.",
            );
        }
    }

    /**
     * Asserts a task is scheduled.
     */
    protected function assertHasScheduledTask(string $task): void
    {
        $contains = Collection::make($this->app->make(Schedule::class)->events())
            ->contains(static function (Event $event) use ($task): bool {
                return Str::of($event->command)->after('artisan')->contains($task)
                    || $event->description === $task;
            });

        static::assertThat($contains, static::isTrue(), "The '$task' is has not been scheduled.");
    }

    /**
     * Assert that a scheduled task will run at the given date.
     */
    protected function assertScheduledTaskRunsAt(string $task, DateTimeInterface $date): void
    {
        $this->assertHasScheduledTask($task);

        $contains = $this->travelTo($date, function () use ($task): bool {
            return $this->app->make(Schedule::class)->dueEvents($this->app)
                ->contains(static function (Event $event) use ($task): bool {
                    return Str::of($event->command)->after('artisan')->contains($task)
                        || $event->description === $task;
                });
        });

        static::assertThat($contains, static::isTrue(), "The '$task' is not scheduled to run at '$date'.");
    }

    /**
     * Assert the given class has registered the given macros.
     *
     * @param  class-string  $macroable
     */
    protected function assertHasMacro(string $macroable, string ...$macros): void
    {
        $call = $macroable === Builder::class ? 'hasGlobalMacro' : 'hasMacro';

        foreach ($macros as $macro) {
            static::assertThat(
                $macroable::{$call}($macro), static::isTrue(), "The macro '$macro' for '$macroable' is missing.",
            );
        }
    }
}
