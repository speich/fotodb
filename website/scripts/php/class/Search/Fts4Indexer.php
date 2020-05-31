<?php

namespace PhotoDatabase\Search;

use PDO;
use PhotoDatabase\Sql\Sql;


/**
 * Interface Fts4Indexer
 * This serves as the interface for creating the different search indexes in the photo database.
 * @package PhotoDatabase\Search
 */
interface Fts4Indexer
{
    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     * @param Sql $sqlSource sql query providing data to create index from.
     */
    public function __construct(PDO $db, Sql $sqlSource);

    /**
     * Creates the database structure to hold the index data.
     */
    public function init();

    /**
     * Method to populate the index with data.
     */
    public function populate();
}