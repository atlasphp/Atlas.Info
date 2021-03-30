<?php
/**
 *
 * This file is part of Atlas for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
declare(strict_types=1);

namespace Atlas\Info\Adapter;

use Atlas\Pdo\Connection;

class MariaAdapter extends MysqlAdapter
{
    public function extractColumn(string $schema, string $table, array $def) : array
    {
        $column = parent::extractColumn($schema, $table, $def);

        if ($column['notnull'] == 0 && $column['default'] === 'NULL') {
            $column['default'] = null;
        }

        if (
            (in_array($column['type'], ['char', 'varchar', 'text']))
            && $column['default'] === '\'\''
        ) {
            $column['default'] = '';
        }

        return $column;
    }

    public function getDefault(mixed $default, string $type, bool $nullable) : mixed
    {
        $default = parent::getDefault($default, $type, $nullable);

        if ($nullable && $default === 'NULL') {
            return null;
        }

        return $default;
    }
}
