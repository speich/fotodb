<?php

namespace PhotoDatabase\Search;

use PDO;


interface Fts4Indexer
{
    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     */
    public function __construct($db);

    public function init();

    public function populate();

}