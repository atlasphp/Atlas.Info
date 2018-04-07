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

class SqlsrvInfo extends Info
{
    public function fetchCurrentSchema() : string
    {
        return $this->connection->fetchValue('SELECT SCHEMA_NAME()');
    }

    protected function getAutoincSql() : string
    {
        return "COLUMNPROPERTY(
                    OBJECT_ID(COLUMNS.TABLE_SCHEMA + '.' + COLUMNS.TABLE_NAME),
                    COLUMNS.COLUMN_NAME,
                    'IsIdentity'
                )";
    }

    protected function getDefault($default)
    {
        // no default
        if ($default === null) {
            return null;
        }

        // sql server wraps non-nulls in parens
        while (
            substr($default, 0, 1) == '('
            && substr($default, -1) == ')'
        ) {
            $default = substr($default, 1, -1);
        }

        // sql null
        if (strtoupper($default) === 'NULL') {
            return null;
        }

        // numeric value
        if (is_numeric($default)) {
            return $default;
        }

        // single-quoted string
        if (
            substr($default, 0, 1) == "'"
            && substr($default, -1) == "'"
        ) {
            return substr($default, 1, -1);
        }

        // sql expression, can't do anything with it here
        return null;
    }
}
