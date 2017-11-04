<?php

namespace PhotoDatabase;

use RecursiveDirectoryIterator;
use RecursiveFilterIterator;


/**
 * Class RecursiveFilterIteratorXmp
 * @package PhotoDatabase
 */
class FilterFilesXmp extends RecursiveFilterIterator
{
    private $validExtension = 'xmp';

    /**
     * Create a RecursiveFilterIterator from a RecursiveDirectoryIterator
     * @link http://php.net/manual/en/recursivefilteriterator.construct.php
     * @param RecursiveDirectoryIterator $iterator
     */
    public function __construct(RecursiveDirectoryIterator $iterator)
    {
        parent::__construct($iterator);
    }

    /**
     * Check whether the current element of the iterator is acceptable
     * @link http://php.net/manual/en/filteriterator.accept.php
     * @return bool true if the current element is acceptable, otherwise false.
     * @since 5.1.0
     */
    public function accept()
    {
        return $this->isDir() || strtolower($this->getExtension()) === $this->validExtension;
    }
}