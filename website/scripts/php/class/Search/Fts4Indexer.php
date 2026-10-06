<?php

namespace PhotoDatabase\Search;

use Pdo\Sqlite;


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
     * @param Sqlite $db
     * @param SqlIndexerSource $sqlSource sql query providing data to create index from.
     */
    public function __construct(Sqlite $db, SqlIndexerSource $sqlSource);

    /**
     * Creates the database structure to hold the index data.
     */
    public function init();

    /**
     * Method to populate the index with data.
     */
    public function populate();
}