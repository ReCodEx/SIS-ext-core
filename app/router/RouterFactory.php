<?php

namespace App;

use Nette;
use Nette\Routing\Router;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use App\Router\GetRoute;
use App\Router\PostRoute;
use App\Router\PutRoute;
use App\Router\DeleteRoute;

/**
 * Router factory for the whole application.
 */
class RouterFactory
{
    use Nette\StaticClass;

    /**
     * Create router with all routes.
     */
    public static function createRouter(): Router
    {
        $router = new RouteList();

        $router[] = new Route('', "Default:default");

        $router[] = self::createTODORoutes("todo");

        return $router;
    }

    private static function createTODORoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router[] = new PostRoute("$prefix/check", "Security:check");
        return $router;
    }
}
