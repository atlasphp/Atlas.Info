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

class PgsqlInfo extends Info
{
    public function fetchCurrentSchema() : string
    {
        return $this->connection->fetchValue('SELECT CURRENT_SCHEMA');
    }

    public function fetchAutoincSequence(string $table) : ?string
    {
        $pos = strpos($table, '.');
        if ($pos === false) {
            $schema = $this->fetchCurrentSchema();
        } else {
            $schema = substr($table, 0, $pos);
            $table = substr($table, $pos + 1);
        }

        $stm = "
            SELECT
                columns.column_name,
                columns.column_default
            FROM information_schema.columns
                LEFT JOIN information_schema.key_column_usage
                    ON columns.table_schema = key_column_usage.table_schema
                    AND columns.table_name = key_column_usage.table_name
                    AND columns.column_name = key_column_usage.column_name
                LEFT JOIN information_schema.table_constraints
                    ON key_column_usage.table_schema = table_constraints.table_schema
                    AND key_column_usage.table_name = table_constraints.table_name
                    AND key_column_usage.constraint_name = table_constraints.constraint_name
            WHERE
                columns.table_schema = :schema
                AND table_constraints.constraint_type = 'PRIMARY KEY'
            AND columns.table_name = :table
            ORDER BY columns.ordinal_position
        ";

        $cols = $this->connection->fetchKeyPair(
            $stm,
            ['schema' => $schema, 'table' => $table]
        );

        foreach ($cols as $name => $default) {
            if ($default !== null && substr($default, 0, 9) == "nextval('"
            ) {
                $pos = strrpos($default, "'");
                $end = strlen($default) - $pos;
                return substr($default, 9, -1 * $end);
            }
        }

        return null;
    }

    protected function getAutoincSql() : string
    {
        return "CASE
                    WHEN SUBSTRING(columns.COLUMN_DEFAULT FROM 1 FOR 7) = 'nextval' THEN 1
                    ELSE 0
                END";
    }

    protected function getDefault($default)
    {
        // null?
        if ($default === null || strtoupper($default) === 'NULL') {
            return null;
        }

        // numeric literal?
        if (is_numeric($default)) {
            return $default;
        }

        // string literal?
        $k = substr($default, 0, 1);
        if ($k == '"' || $k == "'") {
            // find the trailing :: typedef
            $pos = strrpos($default, '::');
            // also remove the leading and trailing quotes
            return substr($default, 1, $pos-2);
        }

        return null;
    }
}
