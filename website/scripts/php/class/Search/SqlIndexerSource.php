<?php


namespace PhotoDatabase\Search;

use PhotoDatabase\Sql\SqlFull;


/**
 * Class SqlIndexerSource
 * @package PhotoDatabase\Search
 */
abstract class SqlIndexerSource extends SqlFull
{
    /**
     * Returns the weights of the columns to index.
     * @return array
     */
    abstract public function getColWeights(): array;

    /**
     * Returns the columns to create and store prefixes from.
     * @return array
     */
    abstract public function getColPrefixes(): array;

    /**
     * Returns the columns to index.
     * @return array
     */
    abstract public function getColNames(): array;

    public function getWhere(): string
    {
        return '';//LastChange > DatePublished OR DatePublished IS NULL';
    }

    /**
     * Returns the GROUP BY clause of the SQL.
     * @return string SQL
     */
    public function getGroupBy(): string
    {
        return '';
    }

    /**
     * Returns the ORDER BY clause of the SQL.
     * @return string SQL
     */
    public function getOrderBy(): string
    {
        return '';
    }
}