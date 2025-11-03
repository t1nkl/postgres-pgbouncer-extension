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
     */
    public function register(): void
    {
        $options = config('database.connections.' . config('database.default', 'pgsql') . '.options', []);

        $shouldUseCustomConnection = false;
        if (is_array($options)) {
            // Support both associative style: [PDO::ATTR_EMULATE_PREPARES => true]
            // and numeric style: [PDO::ATTR_EMULATE_PREPARES]
            $assocEnabled = array_key_exists(PDO::ATTR_EMULATE_PREPARES, $options) && (bool) $options[PDO::ATTR_EMULATE_PREPARES];
            $numericPresent = in_array(PDO::ATTR_EMULATE_PREPARES, $options, true);
            $shouldUseCustomConnection = $assocEnabled || $numericPresent;
        }

        if ($shouldUseCustomConnection) {
            Connection::resolverFor(
                'pgsql',
                static function ($connection, $database, $prefix, $config) {
                    return new PostgresConnection($connection, $database, $prefix, $config);
                }
            );
        }
    }

    /**
     * Register the config for publishing.
     */
    public function boot(): void
    {
        //
    }
}
