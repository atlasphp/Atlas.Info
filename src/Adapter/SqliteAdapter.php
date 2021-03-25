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

class SqliteAdapter extends Adapter
{
    public function fetchCurrentSchema() : string
    {
        return 'main';
    }

    public function fetchTableNames(string $schema) : array
    {
        $schema = $this->quoteName($schema);
        $stm = "
            SELECT name FROM {$schema}.sqlite_master WHERE type = 'table'
            ORDER BY name
        ";
        return $this->connection->fetchColumn($stm);
    }

    public function fetchColumns(string $schema, string $table) : array
    {
        $qs = $this->quoteName($schema);
        $qt = $this->quoteName($table);
        $rows = $this->connection->fetchAll("PRAGMA {$qs}.table_info({$qt})");
        return $this->extractColumns($schema, $table, $rows);
    }

    public function extractColumns(string $schema, string $table, array $rows) : array
    {
        $info = [];
        foreach ($rows as $row) {
            $info[] = $this->extractColumn($schema, $table, $row);
        }

        $create = $this->fetchCreateTable($schema, $table);
        $defs = [];
        while ($curr = array_shift($info)) {
            $this->fixDefault($curr, $create, current($info));
            $this->fixAutoinc($curr, $create);
            $defs[$curr['name']] = $curr;
        }

        return $defs;
    }

    public function extractColumn(string $schema, string $table, array $row) : array
    {
        preg_match(
            "/^([^\(]*)(\(([\d\s]+)(,([\d\s]+))?\))?/",
            trim($row['type']),
            $matches
        );
        $type = trim($matches[1]);
        $size = isset($matches[3]) ? (int) $matches[3]: null;
        $scale = isset($matches[5]) ? (int) $matches[5]: null;

        return [
            'name' => $row['name'],
            'type' => $type,
            'size' => $size,
            'scale' => $scale,
            'notnull' => (bool) ($row['notnull']),
            'default' => $this->extractDefault($row['dflt_value'], $type, ! $row['notnull']),
            'autoinc' => null,
            'primary' => (bool) ($row['pk']),
            'options' => null,
        ];
    }

    public function getAutoincSql() : string
    {
        return 'INTEGER\s+(?:NULL\s+|NOT NULL\s+)?PRIMARY\s+KEY\s+AUTOINCREMENT';
    }

    public function fetchCreateTable(string $schema, string $table) : string
    {
        $schema = $this->quoteName($schema);
        $cmd = "
            SELECT sql FROM {$schema}.sqlite_master
            WHERE type = 'table' AND name = :table
        ";
        return $this->connection->fetchValue($cmd, ['table' => $table]);
    }

    public function getDefault(mixed $default, string $type, bool $nullable) : mixed
    {
        return $default;
    }

    public function fixDefault(array &$curr, string $create, array|false $next) : void
    {
        // For defaults using keywords, SQLite always reports the keyword
        // *value*, not the keyword itself (e.g., '2007-03-07' instead of
        // 'CURRENT_DATE').
        //
        // The allowed keywords are CURRENT_DATE, CURRENT_TIME, and
        // CURRENT_TIMESTAMP.
        //
        //   <http://www.sqlite.org/lang_createtable.html>
        //
        // Check the table-creation SQL for the default value to see if it's
        // a keyword and report 'null' in those cases.

        if (is_string($curr['default']) === false) {
            return;
        }

        $curr['default'] = trim($curr['default'], "'");
        if ($curr['default'] === 'NULL') {
            $curr['default'] = null;
            return;
        }

        // look for `:curr_col :curr_type . DEFAULT CURRENT_(...)` --
        // if not at the end, don't look further than the next coldef
        $find = "{$curr['name']}\s+{$curr['type']}.*\s+DEFAULT\s+CURRENT_(DATE|TIME|TIMESTAMP)";

        if ($next !== false) {
            $find .= ".*{$next['name']}\s+{$next['type']}";
        }

        if (preg_match("/$find/ims", $create, $matches)) {
            $curr['default'] = null;
            return;
        }

        // do nothing!
        return;
    }

    public function fixAutoinc(array &$curr, string $create) : void
    {
        $name = $curr['name'];
        $find = "(\"$name\"|\'$name\'|`$name`|\[$name\]|\\b$name)\s+" . $this->getAutoincSql();
        $curr['autoinc'] = (bool) preg_match("/{$find}/Ui", $create);
    }

    public function quoteName(string $name) : string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
