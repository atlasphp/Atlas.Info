<?php
namespace Atlas\Info;

use Atlas\Info\InfoTest;

class SqlsrvInfoTest extends InfoTest
{
    protected function create()
    {
        $this->connection->query("CREATE SCHEMA {$this->schemaName1}");
        $this->connection->query("CREATE SCHEMA {$this->schemaName2}");

        $this->connection->query("
            CREATE TABLE {$this->schemaName1}.{$this->tableName} (
                id                     INT IDENTITY PRIMARY KEY,
                name                   VARCHAR(50) NOT NULL,
                test_size_scale        NUMERIC(7,3),
                test_default_null      CHAR(3) DEFAULT NULL,
                test_default_string    VARCHAR(7) DEFAULT 'string',
                test_default_number    NUMERIC(5) DEFAULT 12345,
                test_default_integer   INT DEFAULT 233,
                test_default_ignore    DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->connection->query("
            CREATE TABLE {$this->schemaName2}.{$this->tableName} (
                id                     INT IDENTITY PRIMARY KEY,
                name                   VARCHAR(50) NOT NULL,
                test_size_scale        NUMERIC(7,3),
                test_default_null      CHAR(3) DEFAULT NULL,
                test_default_string    VARCHAR(7) DEFAULT 'string',
                test_default_number    NUMERIC(5) DEFAULT 12345,
                test_default_integer   INT DEFAULT 233,
                test_default_ignore    DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    protected function insert()
    {
        $names = [
            'Anna', 'Betty', 'Clara', 'Donna', 'Fiona',
            'Gertrude', 'Hanna', 'Ione', 'Julia', 'Kara',
        ];

        $stm = "INSERT INTO {$this->schemaName1}.{$this->tableName} (name) VALUES (:name)";
        foreach ($names as $name) {
            $sth = $this->connection->prepare($stm);
            $sth->execute(['name' => $name]);
        }
    }

    protected function drop()
    {
        $this->connection->query("DROP TABLE IF EXISTS {$this->schemaName1}.{$this->tableName}");
        $this->connection->query("DROP SCHEMA IF EXISTS {$this->schemaName1}");

        $this->connection->query("DROP TABLE IF EXISTS {$this->schemaName2}.{$this->tableName}");
        $this->connection->query("DROP SCHEMA IF EXISTS {$this->schemaName2}");
    }

    public function provideFetchTableNames()
    {
        return [
            [
                '',
                [
                    'MSreplication_options',
                    'spt_fallback_db',
                    'spt_fallback_dev',
                    'spt_fallback_usg',
                    'spt_monitor'
                ],
            ],
            [
                $this->schemaName1,
                [
                    $this->tableName,
                ],
            ],
            [
                $this->schemaName2,
                [
                    $this->tableName,
                ],
            ],
        ];
    }

    public function provideFetchColumns()
    {
        $columns = [
            'id' => [
                'name' => 'id',
                'type' => 'int',
                'size' => 10,
                'scale' => 0,
                'notnull' => true,
                'default' => null,
                'autoinc' => true,
                'primary' => true,
                'options' => null,
            ],
            'name' => [
                'name' => 'name',
                'type' => 'varchar',
                'size' => 50,
                'scale' => null,
                'notnull' => true,
                'default' => null,
                'autoinc' => false,
                'primary' => false,
                'options' => null,
            ],
            'test_size_scale' => [
                'name' => 'test_size_scale',
                'type' => 'numeric',
                'size' => 7,
                'scale' => 3,
                'notnull' => false,
                'default' => null,
                'autoinc' => false,
                'primary' => false,
                'options' => null,
            ],
            'test_default_null' => [
                'name' => 'test_default_null',
                'type' => 'char',
                'size' => 3,
                'scale' => null,
                'notnull' => false,
                'default' => null,
                'autoinc' => false,
                'primary' => false,
                'options' => null,
            ],
            'test_default_string' => [
                'name' => 'test_default_string',
                'type' => 'varchar',
                'size' => 7,
                'scale' => null,
                'notnull' => false,
                'default' => 'string',
                'autoinc' => false,
                'primary' => false,
                'options' => null,
            ],
            'test_default_number' => [
                'name' => 'test_default_number',
                'type' => 'numeric',
                'size' => 5,
                'scale' => 0,
                'notnull' => false,
                'default' => '12345',
                'autoinc' => false,
                'primary' => false,
                'options' => null,
            ],
            'test_default_integer' => [
                'name' => 'test_default_integer',
                'type' => 'int',
                'size' => 10,
                'scale' => 0,
                'notnull' => false,
                'default' => 233,
                'autoinc' => false,
                'primary' => false,
                'options' => null,
            ],
            'test_default_ignore' => [
                'name' => 'test_default_ignore',
                'type' => 'datetime',
                'size' => null,
                'scale' => null,
                'notnull' => false,
                'default' => null,
                'autoinc' => false,
                'primary' => false,
                'options' => null,
            ],
        ];

        return [
            [$this->tableName, []], // MUST have a schema name to work
            ["{$this->schemaName1}.{$this->tableName}", $columns],
            ["{$this->schemaName2}.{$this->tableName}", $columns],
        ];
    }
}
