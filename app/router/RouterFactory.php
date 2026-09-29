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

        $router->add(new Route('', "Default:default"));

        $router->add(self::createLoginRoutes("login"));
        $router->add(self::createUsersRoutes("users"));
        $router->add(self::createTermsRoutes("terms"));
        $router->add(self::createCoursesRoutes("courses"));
        $router->add(self::createGroupsRoutes("groups"));

        return $router;
    }

    private static function createLoginRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router->add(new PostRoute("$prefix", "Login:default"));
        $router->add(new PostRoute("$prefix/refresh", "Login:refresh"));
        return $router;
    }

    private static function createUsersRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router->add(new GetRoute("$prefix/<id>", "Users:default"));
        $router->add(new PostRoute("$prefix/<id>/sisuser", "Users:sisuser"));
        $router->add(new PostRoute("$prefix/<id>/sync", "Users:syncSis"));
        return $router;
    }

    private static function createTermsRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router->add(new GetRoute("$prefix", "Terms:default"));
        $router->add(new PostRoute("$prefix", "Terms:create"));
        $router->add(new GetRoute("$prefix/<id>", "Terms:detail"));
        $router->add(new PostRoute("$prefix/<id>", "Terms:update"));
        $router->add(new DeleteRoute("$prefix/<id>", "Terms:remove"));
        return $router;
    }

    private static function createCoursesRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router->add(new PostRoute("$prefix", "Courses:default"));
        return $router;
    }

    private static function createGroupsRoutes(string $prefix): RouteList
    {
        $router = new RouteList();
        $router->add(new GetRoute("$prefix", "Groups:default"));
        $router->add(new GetRoute("$prefix/student", "Groups:student"));
        $router->add(new GetRoute("$prefix/teacher", "Groups:teacher"));
        $router->add(new PostRoute("$prefix/<parentId>/create/<eventId>", "Groups:create"));
        $router->add(new PostRoute("$prefix/<id>/bind/<eventId>", "Groups:bind"));
        $router->add(new DeleteRoute("$prefix/<id>/bind/<eventId>", "Groups:unbind"));
        $router->add(new PostRoute("$prefix/<id>/join", "Groups:join"));
        $router->add(new PostRoute("$prefix/<id>/add-attribute", "Groups:addAttribute"));
        $router->add(new PostRoute("$prefix/<id>/remove-attribute", "Groups:removeAttribute"));
        $router->add(new PostRoute("$prefix/<parentId>/create-term/<term>", "Groups:createTerm"));
        $router->add(new PostRoute("$prefix/<id>/archived", "Groups:setArchived"));
        return $router;
    }
}
