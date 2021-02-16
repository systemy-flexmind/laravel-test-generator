<?php

namespace Sofa\LaravelTestGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Sofa\LaravelTestGenerator\Filter;
use Sofa\LaravelTestGenerator\Generator;
use Sofa\LaravelTestGenerator\RouteParser;
use Sofa\LaravelTestGenerator\TestMethod;

class GeneratorCommand extends Command
{
    public $signature = 'generate:feature-tests';
    public $description = 'Generate tests for all the routes in your app';

    public function handle(Generator $generator, Router $router)
    {
        $routes = collect($router->getRoutes()->getRoutes());

        $routes
            // 1. build configuration for tests
            ->map(function (Route $route) {
                return new RouteParser($route);
            })
            ->map(function (RouteParser $route) => {
                $generator->generateHappyPath($route);
                return $route->needsFailingPath() ? $generator->generateFailingPath($route) : null;
            })
            ->collapse()->filter()
            // 2. filter out non-applicable routes
            ->filter(new Filter)
            // 4. group by test class
            ->groupBy(function (TestMethod $test) { 
                return $test->classname;
            })
            ->each(function (Collection $tests, string $classname) {
                return $generator->generateTestFile($classname, $tests);
            });
    }
}
