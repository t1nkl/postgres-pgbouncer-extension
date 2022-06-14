<?php

namespace PostgresPgbouncerExtension;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use PDO;
use PostgresPgbouncerExtension\Database\PostgresConnection;

class PostgresPgbouncerExtensionProvider extends IlluminateServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (
            in_array(
                PDO::ATTR_EMULATE_PREPARES,
                config('database.connections.' . config('database.default', 'pgsql') . '.options', [])
            )
        ) {
            Connection::resolverFor(
                'pgsql',
                static function ($connection, $database, $prefix, $config) {
                    return new PostgresConnection($connection, $database, $prefix, $config);
                }
            );
        }
    }

    /**
     * Register the config for publishing
     *
     */
    public function boot()
    {
        //
    }
}
