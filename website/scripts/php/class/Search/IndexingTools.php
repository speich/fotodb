<?php

namespace PhotoDatabase\Search;

use EnchantDictionary;
use Vanderlee\Syllable\Syllable;
use function count;


/**
 * Class Indexer
 * @package PhotoDatabase\Search
 */
class IndexingTools
{
    /** @var string language tag for Enchant library with underscore */
    private string $langTagEnchant = 'de_CH';

    /** @var string language tag for Syllable class with hyphen */
    private string $langTagSyllable = 'de-ch-1901';

    /** @var resource dictionary */
    private $dict;

    /** @var Syllable */
    private Syllable $syll;

    /** @var int minimum word length to be hyphenated */
    private int $minHyphenatedWordLength = 6;

    /** @var int minimum word length to create prefixes from */
    private int $minWordLength = 6;

    private int $minPrefixesLength = 4;

    /**
     * IndexingTools constructor.
     * @param int|null $minHyphenatedWordLength default value is 6
     * @param string|null $langTagEnchant
     * @param string|null $langTagSyllable
     */
    public function __construct(
        ?int $minHyphenatedWordLength = null,
        ?string $langTagEnchant = null,
        ?string $langTagSyllable = null
    ) {
        $langTagEnchant = $langTagEnchant ?? $this->langTagEnchant;
        $langTagSyllable = $langTagSyllable ?? $this->langTagSyllable;
        $minHyphenatedWordLength = $minHyphenatedWordLength ?? $this->minHyphenatedWordLength;
        $this->initEnchant($langTagEnchant);
        $this->initSyllable($langTagSyllable, $minHyphenatedWordLength);
    }

    /**
     * Init the enchant library to work with spelling libraries.
     * @param string $langTagEnchant
     */
    protected function initEnchant(string $langTagEnchant): void
    {
        $broker = enchant_broker_init();
        $this->dict = enchant_broker_request_dict($broker, $langTagEnchant);
        unset($broker);
    }

    /**
     * Initialize the class to syllabify a word.
     * @param string $langTagSyllable
     * @param int $minWordLength
     */
    protected function initSyllable(string $langTagSyllable, int $minWordLength): void
    {
        $this->syll = new Syllable($langTagSyllable);
        $this->syll->setMinWordLength($minWordLength);
    }

    public function cleanup(): void
    {
        unset($this->dict);
    }

    /**
     * Returns a reference to the enchant dictionary
     * @return resource|false
     */
    public function getDict(): bool
    {
        return $this->dict;
    }

    /**
     * Returns a reference to the Syllable.
     * @return Syllable
     */
    public function getSyll(): Syllable
    {
        return $this->syll;
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
     * Creates prefixes by iteratively removing first syllable from word and using remainder as prefix.
     * @param string $text Text or word to create prefixes from.
     * @param int|null $minWordLength minimum length of word to create prefixes from
     * @param bool|null $checkDict only add word to returned prefixes if it is in dictionary
     * @param int|null $minPrefixLength minimum length of prefix to be included in returned pref
     * @return array
     */
    public function createPrefixesFromSyllables(
        string $text,
        ?int $minWordLength = null,
        ?bool $checkDict = null,
        ?int $minPrefixLength = null
    ): array {

        $minPrefixLength = $minPrefixLength ?? $this->minPrefixesLength;

        return $this->createPrefixes($text, [$this, 'prefixesFromSyllables'], $minWordLength, $checkDict,
            $minPrefixLength);
    }

    /**
     * Creates prefixes by iteratively removing first character from word and using remainder as prefix.
     * @param string $text Text or word to create prefixes from.
     * @param int|null $minWordLength minimum length of word to create prefixes from
     * @param bool|null $checkDict only add word to returned prefixes if it is in dictionary
     * @param int|null $minPrefixLength minimum length of prefix to be included in returned pref
     * @return array
     */
    public function createPrefixesFromAll(
        string $text,
        ?int $minWordLength = null,
        ?bool $checkDict = null,
        ?int $minPrefixLength = null
    ): array {
        $minPrefixLength = $minPrefixLength ?? $this->minPrefixesLength;

        return $this->createPrefixes($text, [$this, 'prefixesFromChars'], $minWordLength, $checkDict, $minPrefixLength);
    }

    /**
     * @param string $word
     * @param int $minPrefixLength
     * @return array
     */
    private function prefixesFromChars(string $word, int $minPrefixLength): array
    {
        $prefixes = [];
        $prefix = mb_substr($word, 1, null, 'utf-8');
        while (mb_strlen($prefix) >= $minPrefixLength) {
            $prefixes[] = $prefix;
            $prefix = mb_substr($prefix, 1, null, 'utf-8');
        }

        return $prefixes;
    }

    /**
     * @param $word
     * @param $minPrefixLength
     * @return array
     */
    private function prefixesFromSyllables(string $word, int $minPrefixLength): array
    {
        $prefixes = [];
        $syllables = $this->syll->splitWord($word);
        if (count($syllables) > 1) {
            foreach ($syllables as $token) {
                array_shift($syllables);
                $prefix = implode('', $syllables);
                if (mb_strlen($prefix, 'utf-8') >= $minPrefixLength) {
                    $prefixes[] = $prefix;
                }
            }
        }

        return $prefixes;
    }

    private function createPrefixes(
        string $text,
        callable $tokenizer,
        int $minWordLength = null,
        bool $checkDict = null,
        int $minPrefixLength = null
    ): array {
        $prefixes = [];
        $minWordLength = $minWordLength ?? $this->minWordLength;
        $text = FtsFunctions::removePunctuation($text);
        $words = SearchQuery::extractWords($text, $minWordLength);
        foreach ($words as $word) {
            $tokens = $tokenizer($word, $minPrefixLength);
            if ($checkDict === true) {
                $tokens = array_filter($tokens, [$this, 'isInDictionary']);
            }
            $prefixes[] = $tokens;
        }

        return array_merge(...$prefixes);
    }
}