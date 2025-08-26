<?php

namespace App;

use Nette;
use Nette\Routing\Router;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;
use App\Router\GetRoute;
use App\Router\PostRoute;
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

        $router[] = self::createLoginRoutes("login");
        $router[] = self::createUsersRoutes("users");
        $router[] = self::createTermsRoutes("terms");
        $router[] = self::createCoursesRoutes("courses");
        $router[] = self::createGroupsRoutes("groups");

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
        $router[] = new PostRoute("$prefix/<id>/sisuser", "Users:sisuser");
        $router[] = new PostRoute("$prefix/<id>/sync", "Users:syncSis");
        return $router;
    }

    private static function createTermsRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router[] = new GetRoute("$prefix", "Terms:default");
        $router[] = new PostRoute("$prefix", "Terms:create");
        $router[] = new GetRoute("$prefix/<id>", "Terms:detail");
        $router[] = new PostRoute("$prefix/<id>", "Terms:update");
        $router[] = new DeleteRoute("$prefix/<id>", "Terms:remove");
        return $router;
    }

    private static function createCoursesRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router[] = new PostRoute("$prefix", "Courses:default");
        return $router;
    }

    private static function createGroupsRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router[] = new GetRoute("$prefix", "Groups:default");
        $router[] = new PostRoute("$prefix", "Groups:create");
        $router[] = new PostRoute("$prefix/<id>/bind/<eventId>", "Groups:bind");
        $router[] = new DeleteRoute("$prefix/<id>/bind/<eventId>", "Groups:unbind");
        $router[] = new PostRoute("$prefix/<id>/join", "Groups:join");
        return $router;
    }
}
