<?php

namespace PhotoDatabase\Iterator;

use PhotoDatabase\Iterator\PhotoDbDirectoryIterator;
use RecursiveFilterIterator;


/**
 * Class FilterFilesXmp
 * Only returns XMP files from directories and subdirectory recursively.
 * @package PhotoDatabase
 */
class FilterFilesXmp extends RecursiveFilterIterator
{
    protected $validExtension = 'xmp';

    /**
     * Create a RecursiveFilterIterator from a RecursiveDirectoryIterator
     * @link http://php.net/manual/en/recursivefilteriterator.construct.php
     * @param PhotoDbDirectoryIterator $iterator
     */
    public function __construct($iterator)
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
        $accept = $this->isDir() || strtolower($this->getExtension()) === $this->validExtension;

        return $accept;
    }
}