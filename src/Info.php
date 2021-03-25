<?php
/**
 *
 * This file is part of Atlas for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
declare(strict_types=1);

namespace Atlas\Info;

use Atlas\Pdo\Connection;
use Atlas\Info\Adapter\Adapter;

class Info
{
    public static function new(Connection $connection) : Info
    {
        $driver = $connection->getDriverName();
        $adapter = __NAMESPACE__ . '\Adapter\\' . ucfirst($driver) . 'Adapter';
        return new static(new $adapter($connection));
    }

    public function __construct(protected Adapter $adapter)
    {
    }

    public function fetchCurrentSchema() : string
    {
        return $this->adapter->fetchCurrentSchema();
    }

    public function fetchTableNames(string $schema = null) : array
    {
        if ($schema === null) {
            $schema = $this->fetchCurrentSchema();
        }

        return $this->adapter->fetchTableNames($schema);
    }

    public function fetchColumns(string $ref) : array
    {
        list($schema, $table) = $this->adapter->listSchemaTable($ref);
        return $this->adapter->fetchColumns($schema, $table);
    }

    public function fetchAutoincSequence(string $ref) : ?string
    {
        list($schema, $table) = $this->adapter->listSchemaTable($ref);
        return $this->adapter->fetchAutoincSequence($schema, $table);
    }
}
