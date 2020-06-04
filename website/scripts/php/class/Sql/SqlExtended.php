<?php

namespace PhotoDatabase\Sql;

use ReflectionObject;
use ReflectionProperty;


/**
 * Class SqlExtended
 * Provides additional methods
 * @package PhotoDatabase\Sql
 */
abstract class SqlExtended extends SqlFull
{
    /** @var int limit upper bound of number of records returned */
    public $limit;

    /** @var int offset number of records to omit from the result set */
    public $offset;

    /** @var string character used to prefix placeholders */
    private $prefix = ':';

    /**
     * Binds all public properties of this instance to the placeholders in the SQL.
     * Uses the name of the property as the name of the placeholder and its value as the value to bind.
     * @param callable $fnc function that binds the property values to the placeholders
     */
    public function bind($fnc): void
    {
        $vars = $this->getPublicVars();
        foreach ($vars as $name => $val) {
            if ($val !== null && $val !== 'sort') {
                // remember variable is passed by reference
                $fnc($name, $this->{$name});
            }
        }
    }

    /**
     * Returns an associative array of defined public non-static properties of this class no matter the scope. If a property has not been assigned a value, it will be returned with a NULL value.
     * @see https://stackoverflow.com/questions/13124072/how-to-programatically-find-public-properties-of-a-class-from-inside-one-of-its#13124184
     * @return array
     */
    public function getPublicVars(): array
    {
        $arr = [];
        $refl = new ReflectionObject($this);
        $props = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $name = $prop->getName();
            // $prop->getName() throws error if property is not initialized (e.g. undefined)
            $arr[$name] = $this->{$name} ?? null;
        }

        return $arr;
    }

    /**
     * Return the SQL to query the data paged.
     * Appends a LIMIT OFFSET to the SQL with the bind vars limit and offset.
     * @return string SQL
     */
    public function getPaged(): string
    {
        return $this->get()." LIMIT {$this->prefix}limit OFFSET {$this->prefix}offset";
    }
}