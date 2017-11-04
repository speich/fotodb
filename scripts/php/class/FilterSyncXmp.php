<?php

namespace PhotoDatabase;

use DateTime;
use RecursiveFilterIterator;


/**
 * Class FilterSyncXmp
 * @package PhotoDatabase
 */
class FilterSyncXmp extends RecursiveFilterIterator
{
    private $dbRecords;
    private $pathPrefix;

    /**
     * Create a RecursiveFilterIterator from a FilterFilesXmp
     * @link http://php.net/manual/en/recursivefilteriterator.construct.php
     * @param FilterFilesXmp $iterator
     * @param array $dbRecords
     */
    public function __construct(FilterFilesXmp $iterator, $dbRecords, $pathPrefix)
    {
        parent::__construct($iterator);
        $this->dbRecords = $dbRecords;
        $this->pathPrefix = $pathPrefix;
    }

    public function checkSyncDate($record)
    {
        if ($record['SyncDate'] === null) {

            return true;
        } else {
            $date1 = DateTime::createFromFormat('U', $this->getMTime());
            $date2 = new DateTime($record['SyncDate']);

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
        $imageRootLength = mb_strlen($this->pathPrefix) + 1;   // will remove slash
        $pathFileNoRoot = mb_substr($this->getRealPath(), $imageRootLength);
        // note: file can have more than one extension
        $arr = explode('.', $pathFileNoRoot);
        $record = $this->dbRecords[$arr[0]];

        return isset($record) && $this->checkSyncDate($record);
    }
}