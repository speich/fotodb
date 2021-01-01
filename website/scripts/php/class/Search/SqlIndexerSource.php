<?php


namespace PhotoDatabase\Search;

use PhotoDatabase\Sql\SqlFull;



/**
 * Class SqlIndexerSource
 * @package PhotoDatabase\Search
 */
abstract class SqlIndexerSource extends SqlFull
{
    public array $colNames = [];

    public array $weights = [];

    public function getWhere() :string {
        return '';//LastChange > DatePublished OR DatePublished IS NULL';
    }

    /**
     * Returns the GROUP BY clause of the SQL.
     * @return string SQL
     */
    public function getGroupBy(): string {
        return '';
    }

    /**
     * Returns the ORDER BY clause of the SQL.
     * @return string SQL
     */
    public function getOrderBy(): string {
        return '';
    }
}