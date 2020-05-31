<?php

namespace PhotoDatabase\Sql;

use ReflectionObject;
use ReflectionProperty;


abstract class SqlExtended extends SqlFull
{
    /**
     * Bind the values to the placeholders in the SQL.
     * @param callable $fnc
     */
    public function bind($fnc): void
    {
        foreach ($this->getPublicVars() as $name => $val) {
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
            $arr[$name] = $this->{$name};
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
        return $this->get().' LIMIT :limit OFFSET :offset';
    }
}