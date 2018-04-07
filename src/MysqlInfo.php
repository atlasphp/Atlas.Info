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

class MysqlInfo extends Info
{
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
