<?php

namespace App;

use Nette\Configurator;

class Bootstrap
{
    public static function boot(): Configurator
    {
        $configurator = new Configurator();

        $configurator->enableTracy(__DIR__ . '/../log');

        $configurator->setTimeZone('Europe/Prague');
        $configurator->setTempDirectory(__DIR__ . '/../temp');

        $configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();

        $configurator->addConfig(__DIR__ . '/config/config.neon');
        if (file_exists(__DIR__ . '/config/config.local.neon')) {
            $configurator->addConfig(__DIR__ . '/config/config.local.neon');
        }

        return $configurator;
    }
}
