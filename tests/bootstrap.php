<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;

$__container = Container::getInstance();
if (!$__container) {
    $__container = new Container();
    Container::setInstance($__container);
}
if (!$__container->bound('config')) {
    $__container->instance('config', new ConfigRepository());
}
unset($__container);

// Provide a fallback global helper `config()` if Laravel helpers aren't loaded.
if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        $container = Container::getInstance();

        /** @var ConfigRepository $repository */
        $repository = $container->make('config');

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $repository->set($k, $v);
            }

            return null;
        }

        return $repository->get($key, $default);
    }
}
