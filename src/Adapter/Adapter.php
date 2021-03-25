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

abstract class Adapter
{
    public function __construct(protected Connection $connection)
    {
    }

    abstract public function fetchCurrentSchema() : string;

    public function fetchTableNames(string $schema) : array
    {
        $stm = '
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = :schema
            AND UPPER(table_type) = :type
            ORDER BY table_name
        ';

        return $this->connection->fetchColumn($stm, [
            'schema' => $schema,
            'type' => 'BASE TABLE',
        ]);
    }

    public function listSchemaTable(string $ref) : array
    {
        $pos = strpos($ref, '.');

        if ($pos === false) {
            return [
                $this->fetchCurrentSchema(),
                $ref,
            ];
        }

        return [
            substr($ref, 0, $pos),
            substr($ref, $pos + 1),
        ];
    }

    public function fetchColumns(string $schema, string $table) : array
    {
        $autoinc = $this->getAutoincSql();
        $extended = $this->getExtendedSql();

        $stm = "
            SELECT
                columns.column_name as _name,
                columns.data_type as _type,
                COALESCE(
                    columns.character_maximum_length,
                    columns.numeric_precision
                ) AS _size,
                columns.numeric_scale AS _scale,
                CASE
                    WHEN columns.is_nullable = 'YES' THEN 0
                    ELSE 1
                END AS _notnull,
                columns.column_default AS _default,
                {$autoinc} AS _autoinc,
                CASE
                    WHEN table_constraints.constraint_type = 'PRIMARY KEY' THEN 1
                    ELSE 0
                END AS _primary{$extended}
            FROM information_schema.columns
                LEFT JOIN information_schema.key_column_usage
                    ON columns.table_schema = key_column_usage.table_schema
                    AND columns.table_name = key_column_usage.table_name
                    AND columns.column_name = key_column_usage.column_name
                LEFT JOIN information_schema.table_constraints
                    ON key_column_usage.table_schema = table_constraints.table_schema
                    AND key_column_usage.table_name = table_constraints.table_name
                    AND key_column_usage.constraint_name = table_constraints.constraint_name
            WHERE columns.table_schema = :schema
            AND columns.table_name = :table
            ORDER BY columns.ordinal_position
        ";

        $defs = $this->connection->fetchAll($stm, ['schema' => $schema, 'table' => $table]);
        return $this->extractColumns($schema, $table, $defs);
    }

    public function extractColumns(string $schema, string $table, array $defs) : array
    {
        $columns = [];
        foreach ($defs as $def) {
            if (isset($columns[$def['_name']])) {
                $columns[$def['_name']]['primary'] = $columns[$def['_name']]['primary'] ?: (bool) $def['_primary'];
                continue;
            }
            $columns[$def['_name']] = $this->extractColumn($schema, $table, $def);
        }
        return $columns;
    }

    public function extractColumn(string $schema, string $table, array $def) : array
    {
        return [
            'name' => $def['_name'],
            'type' => $def['_type'],
            'size' => isset($def['_size']) ? (int) $def['_size'] : null,
            'scale' => isset($def['_scale']) ? (int) $def['_scale'] : null,
            'notnull' => (bool) $def['_notnull'],
            'default' => $this->extractDefault($def['_default'], $def['_type'], !$def['_notnull']),
            'autoinc' => (bool) $def['_autoinc'],
            'primary' => (bool) $def['_primary'],
            'options' => null,
        ];
    }

    public function extractDefault(mixed $default, string $type, bool $nullable) : mixed
    {
        $type = strtolower($type);
        $default = $this->getDefault($default, $type, $nullable);

        if ($default === null) {
            return $default;
        }

        if (strpos($type, 'int') !== false) {
            return (int) $default;
        }

        if ($type == 'float' || $type == 'double' || $type == 'real') {
            return (float) $default;
        }

        return $default;
    }

    public function fetchAutoincSequence(string $schema, string $table) : ?string
    {
        return null;
    }

    public function getExtendedSql() : string
    {
        return '';
    }

    abstract public function getAutoincSql() : string;

    abstract public function getDefault(mixed $default, string $type, bool $nullable) : mixed;
}
