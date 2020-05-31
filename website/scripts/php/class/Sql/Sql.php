<?php

namespace PhotoDatabase\Sql;

/**
 * Class Sql defines a template to access different parts of an SQL.
 */
abstract class Sql
{
    /**
     * Returns the SELECT list part of the SQL.
     * @return string SQL
     */
    abstract public function getList(): string;

    /**
     * Returns the FROM clause of the SQL.
     * @return string SQL
     */
    abstract public function getFrom(): string;

    /**
     * Returns the full SQL.
     * @return string SQL
     */
    public function get(): string
    {
        $sql = 'SELECT '.$this->getList();
        $sql .= ' FROM '.$this->getFrom();

        return $sql;
    }
}