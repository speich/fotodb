<?php

namespace PhotoDatabase\Search;

use EnchantDictionary;
use Vanderlee\Syllable\Syllable;
use function count;


/**
 * Provides linguistic tools to generate searchable word prefixes.
 *
 * Utilizes the Enchant dictionary and Vanderlee Syllable libraries to
 * break down words, process syllables, and validate dictionary terms for
 * more forgiving search queries.
 *
 * @package PhotoDatabase\Search
 */
class IndexingTools
{
    /** @var string Language tag for the Enchant spelling library (e.g., 'de_CH'). */
    private string $langTagEnchant = 'de_CH';

    /** @var string Language tag for the Syllable hyphenation class (e.g., 'de-ch-1901'). */
    private string $langTagSyllable = 'de-ch-1901';

    /** @var resource|EnchantDictionary The active Enchant dictionary broker/resource. */
    private $dict;

    /** @var Syllable The active Syllable instance for word splitting. */
    private Syllable $syll;

    /** @var int Minimum length a word must be to qualify for syllable hyphenation. */
    private int $minHyphenatedWordLength = 6;

    /** @var int Minimum length a word must be to process prefixes from it. */
    private int $minWordLength = 6;

    /** @var int Minimum string length an extracted prefix must have to be kept. */
    private int $minPrefixesLength = 4;

    /**
     * Initializes the required linguistic libraries.
     *
     * @param string|null $langTagEnchant Language tag for Enchant library (default: 'de_CH').
     * @param string|null $langTagSyllable Language tag for Syllable class (default: 'de-ch-1901').
     * @param int|null $minHyphenatedWordLength Min word length to be hyphenated (default: 6).
     */
    public function __construct(
        ?string $langTagEnchant = null,
        ?string $langTagSyllable = null,
        ?int $minHyphenatedWordLength = null
    ) {
        $langTagEnchant = $langTagEnchant ?? $this->langTagEnchant;
        $langTagSyllable = $langTagSyllable ?? $this->langTagSyllable;
        $minHyphenatedWordLength = $minHyphenatedWordLength ?? $this->minHyphenatedWordLength;
        $this->initEnchant($langTagEnchant);
        $this->initSyllable($langTagSyllable, $minHyphenatedWordLength);
    }

    /**
     * Initializes the enchant library to work with system spelling libraries.
     *
     * @param string $langTagEnchant The dictionary language tag to request.
     * @return void
     */
    protected function initEnchant(string $langTagEnchant): void
    {
        $broker = enchant_broker_init();
        $this->dict = enchant_broker_request_dict($broker, $langTagEnchant);
        unset($broker);
    }

    /**
     * Initializes the Syllable library to break words into phonetic segments.
     *
     * @param string $langTagSyllable The language model to use for syllabification.
     * @param int $minWordLength The minimum word length required to process.
     * @return void
     */
    protected function initSyllable(string $langTagSyllable, int $minWordLength): void
    {
        $this->syll = new Syllable($langTagSyllable);
        $this->syll->setMinWordLength($minWordLength);
    }

    /**
     * Release memory associated with the dictionary resource.
     *
     * @return void
     */
    public function cleanup(): void
    {
        unset($this->dict);
    }

    /**
     * Return a reference to the active enchant dictionary.
     *
     * @return resource|bool The dictionary resource, or false if not available.
     */
    public function getDict(): bool
    {
        return $this->dict;
    }

    /**
     * Return a reference to the active Syllable instance.
     *
     * @return Syllable
     */
    public function getSyll(): Syllable
    {
        return $this->syll;
    }

    /**
     * Check if a given word exists in the loaded dictionary.
     *
     * Evaluates both the exact string and its ucfirst() variation.
     *
     * @param string $word The word to check.
     * @return string|false The validated true word with correct casing, or false if not found.
     */
    public function isInDictionary(string $word): false|string
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
     * Generates prefixes by stripping characters one by one from the start of the word.
     *
     * @internal
     * @param string $word The individual word to process.
     * @param int $minPrefixLength The length at which to stop generating shorter prefixes.
     * @return array<int, string>
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
     * Generates prefixes by stripping syllables one by one from the start of the word.
     *
     * @internal
     * @param string $word The individual word to process.
     * @param int $minPrefixLength The length at which to stop generating shorter prefixes.
     * @return array<int, string>
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

    /**
     * Core orchestrator method to tokenize words and extract their prefixes.
     *
     * Punctuation is stripped automatically before extraction begins.
     *
     * @internal
     * @param string $text The text block or word to process.
     * @param callable $tokenizer The specific extraction method (Chars or Syllables).
     * @param int|null $minWordLength Threshold length for processing a word.
     * @param bool|null $checkDict Whether to drop prefixes not found in the dictionary.
     * @param int|null $minPrefixLength Threshold length for keeping an extracted prefix.
     * @return array<int, string>
     */
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
                $tokens = array_reduce($tokens, function($arr, $token) {
                    $val = $this->isInDictionary($token);
                    if ($val !== false) {
                        $arr[] = $val;
                    }

                    return $arr;
                }, []);
            }
            $prefixes[] = $tokens;
        }

        return array_merge(...$prefixes);
    }
}