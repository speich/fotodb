<?php

namespace PhotoDatabase\Iterator;



/**
 * Class FilterFilesXmp
 * Custom filter to exclude non-XMP files from directories and subdirectories.
 */
class FilterFilesXmp extends FilterSync
{
    protected string $validExtension = 'xmp';

    /**
     * Check whether the current element of the iterator is acceptable
     * @link http://php.net/manual/en/filteriterator.accept.php
     * @return bool true if the current element is acceptable, otherwise false.
     */
    public function accept(): bool
    {
        return parent::accept() && strtolower($this->current()->getExtension()) === $this->validExtension;
    }
}