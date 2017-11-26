<?php

namespace PhotoDatabase\Iterator;

use DateTime;


/**
 * Class FilterSyncXmp
 * @package PhotoDatabase
 */
class FilterSync extends FilterFilesXmp
{
    const XMP = 1;

    const EXIF = 2;

    public function __construct(\PhotoDatabase\Iterator\PhotoDbDirectoryIterator $iterator)
    {
        parent::__construct($iterator);
    }

    private function getSyncDate() {

    }

    /**
     * Checks if the xml data from the file has to be updated in the the database.
     * @param integer $type FilterSync::XMP or FilterSync::EXIF
     * @return bool
     */
    public function checkSyncDate()
    {
        $date2 = $this->current()->getSyncDateXmp();
        return null;
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
    public function accept()
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