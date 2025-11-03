# Laravel PostgreSQL + PgBouncer extension (fix for PDO::ATTR_EMULATE_PREPARES)

A tiny Laravel package that makes working with PostgreSQL through PgBouncer safe and predictable when `PDO::ATTR_EMULATE_PREPARES` is enabled.

Why you might need it:
- PgBouncer in transaction/session pooling mode does not support server‑side prepared statements.
- The common workaround is to enable PDO emulated prepares (`PDO::ATTR_EMULATE_PREPARES => true`).
- With emulation enabled, default binding behavior in Laravel/PDO can cause booleans and some types to be sent in ways PgBouncer/PostgreSQL may not expect.

This package provides a custom `PostgresConnection` that:
- Formats `DateTimeInterface` values using your connection grammar date format.
- Converts PHP booleans to the PostgreSQL-native textual values `'true'` and `'false'` during binding.
- Binds values using explicit PDO parameter types: integers as `PDO::PARAM_INT`, resources as `PDO::PARAM_LOB`, and everything else as `PDO::PARAM_STR` for consistency with emulated prepares.

The package is automatically discovered by Laravel and is only activated for the `pgsql` connection when `PDO::ATTR_EMULATE_PREPARES` is present and enabled in your connection `options`.

---

## Requirements
- PHP: ^8.0
- Laravel Framework: ^10.0 | ^11.0 | ^12.0
- PostgreSQL + PgBouncer (transaction or session pooling scenarios)

## Installation
```bash
composer require t1nkl/postgres-pgbouncer-extension
```
Laravel package auto-discovery will register the service provider; no manual configuration is needed beyond the database option below.

## Configuration
Enable emulated prepares on your PostgreSQL connection. You can declare it either as an associative map or as a numeric flag — this package supports both styles.

`config/database.php`
```php
'connections' => [
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'search_path' => 'public',
        'sslmode' => 'prefer',

        // EITHER associative option style (recommended):
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ],

        // OR numeric option style (also supported):
        // 'options' => [
        //     PDO::ATTR_EMULATE_PREPARES,
        // ],
    ],
],
```
Make sure your default connection is `pgsql` or that you add the `options` to the specific PostgreSQL connection you actually use.

If you modify configuration, remember to clear caches:
```bash
php artisan config:clear
php artisan cache:clear
```

## How it works
- The service provider hooks into Laravel’s connection resolver for `pgsql` only when `PDO::ATTR_EMULATE_PREPARES` is detected in the connection `options`.
- It swaps the default `Illuminate\Database\PostgresConnection` with `PostgresPgbouncerExtension\Database\PostgresConnection`.
- The custom connection overrides:
  - `prepareBindings`: converts `DateTimeInterface` to the grammar date format and casts booleans to `'true'`/`'false'` strings.
  - `bindValues`: binds integers as `PDO::PARAM_INT`, resources as `PDO::PARAM_LOB`, and all other values as `PDO::PARAM_STR` to keep behavior stable with emulated prepares.

## Usage
There is nothing special to do. Once installed and the option is enabled, all queries through the `pgsql` connection will use the safer binding strategy.

Example: boolean filters are sent as `'true'`/`'false'` which PostgreSQL parses natively.
```php
User::where('is_active', true)->get();
```

## Troubleshooting
- The behavior does not change:
  - Ensure `PDO::ATTR_EMULATE_PREPARES` is present in your `options` and set to `true` (or listed numerically).
  - Confirm your app uses the `pgsql` connection you configured (`config('database.default')`).
  - Clear config/cache (`php artisan config:clear && php artisan cache:clear`).
  - Make sure the package is installed and not excluded from auto-discovery.
- Still seeing unexpected parameter types?
  - Double-check any custom connection/resolver code that might override this package.
  - Try a minimal repro calling the query builder and logging SQL + bindings.

## Testing locally
This repository includes a test suite. To run it:
```bash
composer install
composer test
```

## Related work
Original gist/package inspiration: https://github.com/t1nkl/Laravel-PostgreSQL-pgbouncer-Fix

## License
MIT License. See `LICENSE` for details.
