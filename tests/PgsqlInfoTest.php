<?php
namespace Atlas\Info;

use Atlas\Info\InfoTest;

class PgsqlInfoTest extends InfoTest
{
    protected function create()
    {
        $this->connection->query("CREATE SCHEMA {$this->schemaName1}");
        $this->connection->query("CREATE SCHEMA {$this->schemaName2}");
        $this->connection->query("SET search_path TO {$this->schemaName1}");

        $this->connection->query("
            CREATE TABLE {$this->tableName} (
                id                     SERIAL PRIMARY KEY,
                name                   VARCHAR(50) NOT NULL,
                test_size_scale        NUMERIC(7,3),
                test_default_null      CHAR(3) DEFAULT NULL,
                test_default_string    VARCHAR(7) DEFAULT 'string',
                test_default_number    NUMERIC(5) DEFAULT 12345,
                test_default_ignore    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->connection->query("
            CREATE TABLE {$this->schemaName2}.{$this->tableName} (
                id                     SERIAL PRIMARY KEY,
                name                   VARCHAR(50) NOT NULL,
                test_size_scale        NUMERIC(7,3),
                test_default_null      CHAR(3) DEFAULT NULL,
                test_default_string    VARCHAR(7) DEFAULT 'string',
                test_default_number    NUMERIC(5) DEFAULT 12345,
                test_default_ignore    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    protected function drop()
    {
        $this->connection->query("DROP SCHEMA IF EXISTS {$this->schemaName1} CASCADE");
        $this->connection->query("DROP SCHEMA IF EXISTS {$this->schemaName2} CASCADE");
    }

    public function provideFetchColumns()
    {
        $columns = [
            'id' => [
                'name' => 'id',
                'type' => 'integer',
                'size' => 32,
                'scale' => 0,
                'notnull' => true,
                'default' => null,
                'autoinc' => true,
                'primary' => true
            ],
            'name' => [
                'name' => 'name',
                'type' => 'character varying',
                'size' => 50,
                'scale' => null,
                'notnull' => true,
                'default' => null,
                'autoinc' => false,
                'primary' => false
            ],
            'test_size_scale' => [
                'name' => 'test_size_scale',
                'type' => 'numeric',
                'size' => 7,
                'scale' => 3,
                'notnull' => false,
                'default' => null,
                'autoinc' => false,
                'primary' => false
            ],
            'test_default_null' => [
                'name' => 'test_default_null',
                'type' => 'character',
                'size' => 3,
                'scale' => null,
                'notnull' => false,
                'default' => null,
                'autoinc' => false,
                'primary' => false
            ],
            'test_default_string' => [
                'name' => 'test_default_string',
                'type' => 'character varying',
                'size' => 7,
                'scale' => null,
                'notnull' => false,
                'default' => 'string',
                'autoinc' => false,
                'primary' => false
            ],
            'test_default_number' => [
                'name' => 'test_default_number',
                'type' => 'numeric',
                'size' => 5,
                'scale' => 0,
                'notnull' => false,
                'default' => '12345',
                'autoinc' => false,
                'primary' => false
            ],
            'test_default_ignore' => [
                'name' => 'test_default_ignore',
                'type' => 'timestamp without time zone',
                'size' => null,
                'scale' => null,
                'notnull' => false,
                'default' => null,
                'autoinc' => false,
                'primary' => false
            ],
        ];

        return [
            [$this->tableName, $columns],
            ["{$this->schemaName2}.{$this->tableName}", $columns],
        ];
    }

    public function testFetchAutoincSequence()
    {
        $actual = $this->info->fetchAutoincSequence($this->tableName);
        $this->assertSame('atlas_test_table_id_seq', $actual);
    }
}
