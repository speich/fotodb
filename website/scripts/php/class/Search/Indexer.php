<?php

namespace PhotoDatabase\Search;

use PDO;
use PDOException;
use Vanderlee\Syllable\Syllable;
use function count;


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

    /** @var SqlIndexerSource sql query returning data to create index from */
    protected SqlIndexerSource $sqlSource;

    /** @var string language tag for Enchant library with underscore */
    private string $langTagEnchant = 'de_CH';

    /** @var string language tag for Syllable class with hyphen */
    private string $langTagSyllable = 'de-ch-1901';

    /** @var resource dictionary */
    private $dict;

    /** @var int minimum word length to be hyphenated */
    private int $minHyphenatedWordLength = 4;

    /** @var int minimum word length to create prefixes from */
    private int $minWordLength = 6;

    /** @var Syllable */
    private Syllable $syll;

    /**
     * Fts4Indexer constructor.
     * @param PDO $db
     * @param SqlIndexerSource $sqlSource
     */
    public function __construct(PDO $db, SqlIndexerSource $sqlSource)
    {
        $this->initEnchant();
        $this->initSyllable();
        $this->db = $db;
        $this->sqlSource = $sqlSource;
        $this->tokenizerUnicode61 = $this->hasTokenizerUnicode61();
        if ($this->tokenizerUnicode61 === false) {
            $this->db->sqliteCreateFunction('REMOVE_DIACRITICS', [FtsFunctions::class, 'removeDiacritics'], 1);
        }
    }

    /**
     * Init the enchant library to work with spelling libraries.
     */
    protected function initEnchant(): void
    {
        $broker = enchant_broker_init();
        $this->dict = enchant_broker_request_dict($broker, $this->langTagEnchant);
        unset($broker);
    }

    /**
     * Initialize the class to syllabify a word.
     */
    protected function initSyllable(): void
    {
        $this->syll = new Syllable($this->langTagSyllable);
        $this->syll->setMinWordLength($this->minHyphenatedWordLength);
    }

    public function cleanup(): void
    {
        unset($this->dict);
    }

    /**
     * Check if the word is in the dictionary.
     * Check if a word is in the dictionary and return it or false. Fixes the case of the word.
     * @param string $word
     * @return bool|string
     */
    public function isInDictionary(string $word)
    {
        $trueWord = false;
        if (enchant_dict_check($this->dict, $word)) {
            $trueWord = $word;
        } elseif (enchant_dict_check($this->dict, ucfirst($word))) {
            $trueWord = ucfirst($word);
        }

        return $trueWord;
    }

    /**
     * @param string $text Text or word to create prefixes from.
     * @param bool $checkDict only add word to returned prefixes if it is in dictionary
     * @return array
     */
    public function createPrefixes(string $text, bool $checkDict = true): array
    {
        $prefixes = [];
        $text = FtsFunctions::removePunctuation($text);
        $words = SearchQuery::extractWords($text, $this->minWordLength);
        foreach ($words as $word) {
            $syllables = $this->syll->splitWord($word);
            if (count($syllables) > 1) {
                foreach ($syllables as $token) {
                    array_shift($syllables);
                    $part = implode('', $syllables);
                    if ($checkDict === true) {
                        $part = $this->isInDictionary($part);
                    }
                    if ($part !== false && mb_strlen($part, 'utf-8') > 3) {
                        $prefixes[] = $part;
                    }
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