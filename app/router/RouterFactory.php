<?php

namespace App;

use Nette;
use Nette\Routing\Router;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use App\Router\GetRoute;
use App\Router\PostRoute;

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

        $router[] = self::createLoginRoutes("login");
        $router[] = self::createUsersRoutes("users");

        return $router;
    }

    private static function createLoginRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router[] = new PostRoute("$prefix", "Login:default");
        $router[] = new PostRoute("$prefix/refresh", "Login:refresh");
        return $router;
    }

    private static function createUsersRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router[] = new GetRoute("$prefix/<id>", "Users:default");
        return $router;
    }
}
