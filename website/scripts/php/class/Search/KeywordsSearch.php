<?php

namespace PhotoDatabase\Search;

use PDO;
use Pdo\Sqlite;

/**
 * Class SearchKeywords
 * Provides a list of (key)words found in all relevant columns of all relevant tables to display
 * @package PhotoDatabase\Database
 */
class KeywordsSearch
{
    /**
     * @var Sqlite
     */
    public Sqlite $db;

    /**
     * Keywords constructor.
     * @param Sqlite $db
     */
    public function __construct(Sqlite $db)
    {
        $this->db = $db;
        $this->db->createFunction('OFFSETWORD', [FtsFunctions::class, 'offsetWord']);
        $this->db->createFunction('NUMMATCHES', [FtsFunctions::class, 'numMatches']);
    }

    /**
     * @param $text
     * @return string
     */
    public function prepareQuery($text): string
    {
        $search = '';
        /* TODO
        if (nounicode) {
            $text = FtsFunctions::removeDiacritics($text);
        }
        */
        $words = SearchQuery::extractWords($text);
        foreach ($words as $word) {
            $search .= $word.'* ';
        }

        return rtrim($search);
    }

    /**
     * Full-text search returning a list of found keywords in the database.
     * @param string $text
     * @return array keywords
     */
    public function search(string $text): array
    {
        // TODO: non-unicode version as in KeywordsIndexer
        $sql = 'SELECT rowid, Keyword FROM SearchKeywords_fts WHERE (Keyword MATCH :text)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':text', $text, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
