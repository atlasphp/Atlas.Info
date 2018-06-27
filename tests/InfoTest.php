<?php
namespace Atlas\Info;

use Atlas\Pdo\Connection;

abstract class InfoTest extends \PHPUnit\Framework\TestCase
{
    protected $info;
    protected $connection;
    protected $schemaName1 = 'atlas_test_info_1';
    protected $schemaName2 = 'atlas_test_info_2';
    protected $tableName = 'atlas_test_table';

    protected function setUp()
    {
        $class = get_class($this);

        // Atlas\Info\FooTest => foo
        $driver = strtolower(substr($class, 11, -8));
        $extension = "pdo_{$driver}";
        if (! extension_loaded($extension)) {
            $this->markTestSkipped("{$extension} extension not loaded");
            return;
        }

        $this->connection = Connection::new(
            $GLOBALS["{$class}:dsn"],
            $GLOBALS["{$class}:username"],
            $GLOBALS["{$class}:password"]
        );

        $this->drop();
        $this->create();
        $this->insert();

        $this->info = Info::new($this->connection);
    }

    abstract protected function drop();

    abstract protected function create();

    protected function insert()
    {
        $names = [
            'Anna', 'Betty', 'Clara', 'Donna', 'Fiona',
            'Gertrude', 'Hanna', 'Ione', 'Julia', 'Kara',
        ];

        $stm = "INSERT INTO {$this->tableName} (name) VALUES (:name)";
        foreach ($names as $name) {
            $sth = $this->connection->prepare($stm);
            $sth->execute(['name' => $name]);
        }
    }

    /**
     * @dataProvider provideFetchTableNames
     */
    public function testFetchTableNames($info, $expect)
    {
        $actual = $this->info->fetchTableNames($info);
        $this->assertEquals($expect, $actual);
    }

    public function provideFetchTableNames()
    {
        return [
            ['', [$this->tableName]],
            [$this->schemaName1, [$this->tableName]],
            [$this->schemaName2, [$this->tableName]],
        ];
    }

    /**
     * @dataProvider provideFetchColumns
     */
    public function testFetchColumns($table, $expectCols)
    {
        $actualCols = $this->info->fetchColumns($table);
        $this->assertSame(count($expectCols), count($actualCols));
        $keys = [
            'name',
            'type',
            'size',
            'scale',
            'notnull',
            'default',
            'autoinc',
            'primary',
        ];
        foreach ($actualCols as $colName => $actualCol) {
            $expectCol = $expectCols[$colName];
            foreach ($keys as $key) {
                $expect = $expectCol[$key];
                $actual = $actualCol[$key];
                $this->assertSame(
                    $expect,
                    $actual,
                    "Expected {$colName}[{$key}] " . var_export($expect, true) . " actually " . var_export($actual, true)
                );
            }
        }
    }

    abstract public function provideFetchColumns();


    public function testFetchAutoincSequence()
    {
        $actual = $this->info->fetchAutoincSequence($this->tableName);
        $this->assertNull($actual);
    }
}
