<?php

namespace PhotoDatabase\Search;

use PDO;


/**
 * Interface Fts4Indexer
 * This serves as the interface for creating the different search indexes in the photo database.
 * For each column to be indexed, an additional column with prefixes is created.
 * @package PhotoDatabase\Search
 */
interface Fts4Indexer
{
    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     * @param SqlIndexerSource $sqlSource sql query providing data to create index from.
     */
    public function __construct(PDO $db, SqlIndexerSource $sqlSource);

    /**
     * Creates the database structure to hold the index data.
     */
    public function init();

    /**
     * Method to populate the index with data.
     */
    public function populate();
}