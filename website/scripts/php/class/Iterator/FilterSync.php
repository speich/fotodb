<?php

namespace PhotoDatabase\Iterator;

use DateTime;
use RecursiveFilterIterator;


/**
 * Class FilterSyncXmp
 * Filter out file
 * @package PhotoDatabase
 */
class FilterSync extends RecursiveFilterIterator
{
    public const XMP = 1;

    public const EXIF = 2;

    public function __construct(PhotoDbDirectoryIterator $iterator)
    {
        parent::__construct($iterator);
    }

    private function getSyncDate() {

    }

    /**
     * Checks if the xml data from the file has to be updated in the database.
     * @param int $type FilterSync::XMP or FilterSync::EXIF
     * @return bool
     * @throws \Exception
     */
    public function checkSyncDate(): bool
    {
        /** @var FileInfoImage $item */
        $item = $this->current();
        $date2 = $item->getSyncDateXmp();
        if ($date2 === null) {

            return true;
        } else {
            $date1 = DateTime::createFromFormat('U', $this->getMTime());

            return $date1 > $date2;
        }
    }

    /**
     * Check whether the current element of the iterator is acceptable
     * @link http://php.net/manual/en/filteriterator.accept.php
     * @return bool true if the current element is acceptable, otherwise false.
     * @since 5.1.0
     */
    public function accept(): bool
    {
        $accept = false;
        if (parent::accept()) {
            if ($this->isDir()) {
                $accept = true;
            } else if ($this->current()->getImgId() === null) {
                // do not sync a file, which is not in database
                $accept = false;
            } else {
                $accept = $this->checkSyncDate();
            }
        }
        return $accept;
    }
}