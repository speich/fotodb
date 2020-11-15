<?php

namespace PhotoDatabase\Search;

use PDO;
use PDOException;
use PhotoDatabase\Sql\Sql;
use Vanderlee\Syllable\Syllable;


/**
 * Class Indexer
 * @package PhotoDatabase\Search
 */
abstract class Indexer implements Fts4Indexer
{
    /** @var PDO */
    public PDO $db;

    /** @var bool */
    private bool $tokenizerUnicode61;

    /** @var Sql sql query returning data to create index from */
    protected Sql $sqlSource;

    /** @var string language tag for Enchant library with underscore */
    private string $langTagEnchant = 'de_CH';

    /** @var string language tag for Syllable calss with hyphen */
    private string $langTagSyllable = 'de-ch-1901';

    /** @var resource dictionary */
    private $dict;

    /** @var int minimum length of a word to be hyphenated */
    private int $minWordLength;

    /** @var Syllable */
    private Syllable $syll;

    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     * @param Sql $sqlSource
     */
    public function __construct(PDO $db, Sql $sqlSource)
    {
        $this->db = $db;
        $this->sqlSource = $sqlSource;
        $this->tokenizerUnicode61 = $this->hasTokenizerUnicode61();
        if ($this->tokenizerUnicode61 === false) {
            $this->db->sqliteCreateFunction('REMOVE_DIACRITICS', [FtsFunctions::class, 'removeDiacritics']);
        }
    }

    /**
     * Init the enchant library to work with spelling libraries.
     */
    protected function initEnchant(): void
    {
        $broker = enchant_broker_init();
        $this->dict = enchant_broker_request_dict($broker, $this->langTagEnchant);
    }

    /**
     * Initialize the class to syllabify word.
     */
    protected function initSyllable(): void
    {
        $this->syll = new Syllable($this->langTagSyllable);
        $this->syll->setMinWordLength($this->minWordLength);
    }

    /**
     * Check if the word is in the dictionary.
     * Check if a word is in the dictionary and return it or false. Fixes the case of the word.
     * @param string $word
     * @return bool|string
     */
    public function inDictionary(string $word) {
        $trueWord = false;
        if (enchant_dict_check($this->dict, $word)) {
            $trueWord = $word;
        }
        elseif (enchant_dict_check($this->dict, ucfirst($word))) {
            $trueWord = ucfirst($word);
        }

        return $trueWord;
    }

    /**
     * @param $word
     * @return array
     */
    public function createPrefixes($word): array
    {
        $syllables = $this->syll->splitWord($word);
        $prefixes = [];
        if (\count($syllables) > 1) {
            foreach ($syllables as $token) {
                array_shift($syllables);
                $part = implode("", $syllables);
                $part = $this->inDictionary($part);
                if ($part !== false && mb_strlen($part, 'utf-8') > 3) {
                    $prefixes[] = $part;
                }
            }
        }

        return $prefixes;
    }

    /**
     * Check if sqlite supports using the tokenizer unicode61 in FTS4 tables.
     * @return bool
     */
    private function hasTokenizerUnicode61(): bool
    {
        $db = $this->db;
        $sql = 'CREATE VIRTUAL TABLE Test_fts USING fts4(Keyword, tokenize=unicode61)';
        try {
            $db->exec($sql);
            $db->exec('DROP TABLE Test_fts');
            $hasTokenizer = true;
        } catch (PDOException $error) {
            $hasTokenizer = false;
        }

        return $hasTokenizer;
    }

    /**
     * @return bool
     */
    public function isTokenizerUnicode61(): bool
    {
        return $this->tokenizerUnicode61;
    }
}