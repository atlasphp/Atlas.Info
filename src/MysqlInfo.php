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

class MysqlInfo extends Info
{
    protected $maria = false;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);

        $vars = $connection->fetchKeyPair("SHOW VARIABLES LIKE '%version%'");
        if (isset($vars['version']) && stripos($vars['version'], 'maria')) {
            $this->maria = true;
        }
    }

    public function fetchCurrentSchema() : string
    {
        return $this->connection->fetchValue('SELECT DATABASE()');
    }

    protected function getAutoincSql() : string
    {
        return "CASE
                    WHEN LOCATE('auto_increment', columns.EXTRA) > 0 THEN 1
                    ELSE 0
                END";
    }

    protected function getExtendedSql() : string
    {
        return ',
                columns.column_type as _extended';
    }

    protected function extractColumn(string $schema, string $table, array $def) : array
    {
        $column = parent::extractColumn($schema, $table, $def);

        if (
            $this->maria
            && $column['notnull'] == 0
            && $column['default'] === 'NULL'
        ) {
            $column['default'] = null;
        }

        if (
            $this->maria
            && (in_array($column['type'], ['char', 'varchar', 'text']))
            && $column['default'] === '\'\''
        ) {
            $column['default'] = '';
        }

        $extended = trim($def['_extended']);

        $pos = stripos($extended, 'unsigned');
        if ($pos !== false) {
            $column['type'] .= ' ' . substr($extended, $pos, 8);
            return $column;
        }

        $pos = stripos($extended, 'enum');
        if ($pos === 0) {
            $input = trim(substr($extended, 4), '()');
            $column['options'] = str_getcsv($input);
            return $column;
        }

        return $column;
    }

    protected function getDefault($default)
    {
        if ($default === null) {
            return null;
        }

        if (strtoupper($default) == 'CURRENT_TIMESTAMP') {
            // the only non-literal allowed by MySQL is "CURRENT_TIMESTAMP"
            return null;
        }

        return $default;
    }
}
