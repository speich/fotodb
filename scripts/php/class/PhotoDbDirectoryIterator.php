<?php

namespace PhotoDatabase;

use RecursiveDirectoryIterator;


class PhotoDbDirectoryIterator extends RecursiveDirectoryIterator
{
    private $dbRecords;
    private $pathPrefix;

    /**
     * PhotoDbDirectoryIterator constructor.
     * @param string $path
     * @param array $dbRecords
     * @param string $pathPrefix
     * @param $flags
     */
    public function __construct($path, $dbRecords, $pathPrefix, $flags)
    {
        parent::__construct($path, $flags);
        $this->dbRecords = $dbRecords;
        $this->pathPrefix = $pathPrefix;
    }

    public function getChildren()
    {

       return new self($this->getPath(), $this->dbRecords, $this->pathPrefix, $this->getFlags());
    }

}