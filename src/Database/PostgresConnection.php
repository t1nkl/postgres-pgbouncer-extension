<?php

declare(strict_types=1);

namespace PostgresPgbouncerExtension\Database;

use DateTimeInterface;
use Illuminate\Database\PostgresConnection as IlluminatePostgresConnection;
use PDO;
use PDOStatement;

use function is_bool;
use function is_int;
use function is_resource;
use function is_string;

class PostgresConnection extends IlluminatePostgresConnection
{
    /**
     * @param PDOStatement $statement
     * @param array $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $pdoParam = PDO::PARAM_STR;

            if (is_int($value)) {
                $pdoParam = PDO::PARAM_INT;
            }

            if (is_resource($value)) {
                $pdoParam = PDO::PARAM_LOB;
            }

            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $value,
                $pdoParam
            );
        }
    }

    /**
     * @param array $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = $value ? 'true' : 'false';
            }
        }

        return $bindings;
    }
}
