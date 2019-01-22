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

class SqliteInfo extends Info
{
    public function fetchCurrentSchema() : string
    {
        return 'main';
    }

    public function fetchTableNames(string $schema = '') : array
    {
        if ($schema === '') {
            $schema = $this->fetchCurrentSchema();
        }

        $schema = $this->quoteName($schema);
        $stm = "
            SELECT name FROM {$schema}.sqlite_master WHERE type = 'table'
            ORDER BY name
        ";
        return $this->connection->fetchColumn($stm);
    }

    public function fetchColumns(string $table) : array
    {
        $pos = strpos($table, '.');
        if ($pos === false) {
            $schema = $this->fetchCurrentSchema();
        } else {
            $schema = substr($table, 0, $pos);
            $table = substr($table, $pos + 1);
        }

        $qs = $this->quoteName($schema);
        $qt = $this->quoteName($table);
        $rows = $this->connection->fetchAll("PRAGMA {$qs}.table_info({$qt})");
        return $this->extractColumns($schema, $table, $rows);
    }

    protected function extractColumns(string $schema, string $table, array $rows) : array
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

    protected function extractColumn(string $schema, string $table, array $row) : array
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
            'default' => $this->extractDefault($row['dflt_value'], $type),
            'autoinc' => null,
            'primary' => (bool) ($row['pk']),
            'options' => null,
        ];
    }

    protected function getAutoincSql() : string
    {
        return 'INTEGER\s+(?:NULL\s+|NOT NULL\s+)?PRIMARY\s+KEY\s+AUTOINCREMENT';
    }

    protected function fetchCreateTable(string $schema, string $table) : string
    {
        $schema = $this->quoteName($schema);
        $cmd = "
            SELECT sql FROM {$schema}.sqlite_master
            WHERE type = 'table' AND name = :table
        ";
        return $this->connection->fetchValue($cmd, ['table' => $table]);
    }

    protected function getDefault($default)
    {
        return $default;
    }

    protected function fixDefault(array &$curr, string $create, $next)
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
            return null;
        }

        $curr['default'] = trim($curr['default'], "'");
        if ($curr['default'] === 'NULL') {
            $curr['default'] = null;
            return null;
        }

        // look for `:curr_col :curr_type . DEFAULT CURRENT_(...)` --
        // if not at the end, don't look further than the next coldef
        $find = "{$curr['name']}\s+{$curr['type']}.*\s+DEFAULT\s+CURRENT_(DATE|TIME|TIMESTAMP)";
        if ($next) {
            $find .= ".*{$next['name']}\s+{$next['type']}";
        }
        if (preg_match("/$find/ims", $create, $matches)) {
            $curr['default'] = null;
            return;
        }

        // do nothing!
        return;
    }

    protected function fixAutoinc(array &$curr, string $create)
    {
        $name = $curr['name'];
        $find = "(\"$name\"|\'$name\'|`$name`|\[$name\]|\\b$name)\s+" . $this->getAutoincSql();
        $curr['autoinc'] = (bool) preg_match("/{$find}/Ui", $create);
    }

    protected function quoteName($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
