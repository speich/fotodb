<?php

namespace PhotoDatabase\Search;

use PDO;


/**
 * Interface Fts4Indexer
 * This serves as the interface for indexing keywords in the database.
 * @package PhotoDatabase\Search
 */
interface Fts4Indexer
{
    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     */
    public function __construct(PDO $db);

    public function init();

    public function populate();

}