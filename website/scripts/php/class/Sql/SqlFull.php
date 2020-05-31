<?php

namespace PhotoDatabase\Sql;

/**
 * This defines a template to access different parts of an SQL.
 * You can add public properties as variables and then using getPublicVars() in your bind method
 * Often you need an SQL in slightly different reincarnations such as a limited list of records, filtered list or the number of records.
 */
abstract class SqlFull extends Sql
{
    /**
     * Returns the WHERE clause of the SQL.
     * @return string SQL
     */
    abstract public function getWhere(): string;

    /**
     * Returns the GROUP BY clause of the SQL.
     * @return string SQL
     */
    abstract public function getGroupBy(): string;

    /**
     * Returns the ORDER BY clause of the SQL.
     * @return string SQL
     */
    abstract public function getOrderBy(): string;

    /**
     * Returns the full SQL.
     * @return string SQL
     */
    public function get(): string
    {
        $sql = parent::get();
        $where = $this->getWhere();
        $groupBy = $this->getGroupBy();
        $orderBy = $this->getOrderBy();
        $sql .= $where === '' ? '' : " WHERE $where";
        $sql .= $groupBy === '' ? '' : " GROUP BY $groupBy";
        $sql .= $orderBy === '' ? '' : " ORDER BY $orderBy";

        return $sql;
    }
}