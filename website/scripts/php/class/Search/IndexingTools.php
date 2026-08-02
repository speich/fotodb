<?php

namespace PhotoDatabase\Search;

use EnchantDictionary;
use Vanderlee\Syllable\Syllable;
use function count;

/**
 * Class IndexingTools
 *
 * Provides utility methods for full-text search indexing, including
 * dictionary validation via Enchant and prefix generation via syllable splitting.
 *
 * @package PhotoDatabase\Search
 */
class IndexingTools
{
    /**
     * @var string Default language code used as a fallback if a specific language is not provided or found.
     */
    public const string LANG_DEFAULT = 'de';

    /**
     * @var array<string, string> Mapping of internal language codes to Enchant library language tags.
     */
    public array $langTagsEnchant = [
        'de' => 'de_CH',
        'en' => 'en_US'
    ];

    /**
     * @var array<string, string> Mapping of internal language codes to Syllable class language tags.
     */
    public array $langTagsSyllable = [
        'de' => 'de-ch-1901',
        'en' => 'en-us'
    ];

    /**
     * @var array<string, EnchantDictionary> Active Enchant dictionaries mapped by internal language code.
     */
    public array $dicts = [];

    /**
     * @var array<string, Syllable> Active Syllable instances mapped by internal language code.
     */
    private array $sylls = [];

    /**
     * @var int Minimum length a word must have before it is processed for hyphenation.
     */
    public int $minHyphenatedWordLength = 6;

    /**
     * @var int Minimum length a word must have to be considered for prefix extraction.
     */
    private int $minWordLength = 6;

    /**
     * @var int Minimum length a generated prefix must have to be included in the results.
     */
    public int $minPrefixesLength = 4;

    /**
     * IndexingTools constructor.
     * Initializes the dictionaries and syllable splitters for all configured languages.
     */
    public function __construct()
    {
        $this->initEnchant();
        $this->initSyllable();
    }

    /**
     * Initializes the Enchant library and loads the dictionaries for multiple languages.
     *
     * @return void
     */
    protected function initEnchant(): void
    {
        $broker = enchant_broker_init();
        foreach ($this->langTagsEnchant as $lang => $tag) {
            $this->dicts[$lang] = enchant_broker_request_dict($broker, $tag);
        }
    }

    /**
     * Initializes the Syllable class instances for multiple languages.
     *
     * @return void
     */
    protected function initSyllable(): void
    {
        foreach ($this->langTagsSyllable as $lang => $tag) {
            $this->sylls[$lang] = new Syllable($tag);
            $this->sylls[$lang]->setMinWordLength($this->minHyphenatedWordLength);
        }
    }

    /**
     * Frees resources by unsetting the dictionary objects, triggering PHP's garbage collection.
     *
     * @return void
     */
    public function cleanup(): void
    {
        $this->dicts = [];
    }

    /**
     * Creates prefixes by iteratively removing the first syllable from the word.
     *
     * @param string $text The text or word to create prefixes from.
     * @param string $lang The language code to use for syllabification (defaults to self::LANG_DEFAULT).
     * @param int|null $minWordLength The minimum length of a word to create prefixes from.
     * @param bool|null $checkDict Whether to only return prefixes that exist in the dictionary.
     * @param int|null $minPrefixLength The minimum length of a prefix to be included.
     * @return array<int, string> An array of generated prefixes.
     */
    public function createPrefixesFromSyllables(
        string $text,
        string $lang = self::LANG_DEFAULT,
        ?int $minWordLength = null,
        ?bool $checkDict = null,
        ?int $minPrefixLength = null
    ): array {
        $minPrefixLength = $minPrefixLength ?? $this->minPrefixesLength;

        return $this->createPrefixes($text, $lang, [$this, 'prefixesFromSyllables'], $minWordLength, $checkDict, $minPrefixLength);
    }

    /**
     * Core method to process text into words, tokenize them into prefixes, and optionally validate against a dictionary.
     *
     * @param string $text The full text to process.
     * @param string $lang The language code applied to tokenization and dictionary checks.
     * @param callable $tokenizer The tokenization callback to use (e.g., characters or syllables).
     * @param int|null $minWordLength The minimum length for words to be extracted.
     * @param bool|null $checkDict If true, filters out generated prefixes that are not valid words in the dictionary.
     * @param int|null $minPrefixLength The minimum allowed length for generated prefixes.
     * @return array<int, string> A flat array of all valid prefixes found in the text.
     */
    private function createPrefixes(
        string $text,
        string $lang,
        callable $tokenizer,
        ?int $minWordLength = null,
        ?bool $checkDict = null,
        ?int $minPrefixLength = null
    ): array {
        $prefixes = [];
        $minWordLength = $minWordLength ?? $this->minWordLength;
        $text = FtsFunctions::removePunctuation($text);
        $words = SearchQuery::extractWords($text, $minWordLength);

        foreach ($words as $word) {
            $tokens = $tokenizer($word, $minPrefixLength, $lang);
            if ($checkDict === true) {
                $tokens = array_reduce($tokens, function ($arr, $token) use ($lang) {
                    $val = $this->isInDictionary($token, $lang);
                    if ($val !== false) {
                        $arr[] = $val;
                    }

                    return $arr;
                }, []);
            }
            $prefixes[] = $tokens;
        }

        // Return a flattened array using argument unpacking
        return empty($prefixes) ? [] : array_merge(...$prefixes);
    }

    /**
     * Checks if a word exists in the dictionary for a specific language.
     * Also checks the capitalized version of the word if the lowercase version fails.
     *
     * @param string $word The word to check.
     * @param string $lang The language code to use (defaults to self::LANG_DEFAULT).
     * @return bool|string Returns the correctly cased word if found, or false if not found.
     */
    public function isInDictionary(string $word, string $lang = self::LANG_DEFAULT): bool|string
    {
        $trueWord = false;
        $dict = $this->dicts[$lang] ?? $this->dicts[self::LANG_DEFAULT];

        if (enchant_dict_check($dict, $word)) {
            $trueWord = $word;
        } elseif (enchant_dict_check($dict, ucfirst($word))) {
            $trueWord = ucfirst($word);
        }

        return $trueWord;
    }

    /**
     * Creates prefixes by iteratively removing the first character from the word.
     *
     * @param string $text The text or word to create prefixes from.
     * @param string $lang The language code to use (defaults to self::LANG_DEFAULT).
     * @param int|null $minWordLength The minimum length of a word to create prefixes from.
     * @param bool|null $checkDict Whether to only return prefixes that exist in the dictionary.
     * @param int|null $minPrefixLength The minimum length of a prefix to be included.
     * @return array<int, string> An array of generated prefixes.
     */
    public function createPrefixesFromAll(
        string $text,
        string $lang = self::LANG_DEFAULT,
        ?int $minWordLength = null,
        ?bool $checkDict = null,
        ?int $minPrefixLength = null
    ): array {
        $minPrefixLength = $minPrefixLength ?? $this->minPrefixesLength;

        return $this->createPrefixes($text, $lang, [$this, 'prefixesFromChars'], $minWordLength, $checkDict, $minPrefixLength);
    }

    /**
     * Generates prefixes by removing characters one by one from the beginning of the word.
     *
     * @param string $word The word to process.
     * @param int $minPrefixLength The minimum allowed length for a prefix.
     * @return array<int, string>
     */
    private function prefixesFromChars(string $word, int $minPrefixLength): array
    {
        $prefixes = [];
        $prefix = mb_substr($word, 1, null, 'utf-8');
        while (mb_strlen($prefix, 'utf-8') >= $minPrefixLength) {
            $prefixes[] = $prefix;
            $prefix = mb_substr($prefix, 1, null, 'utf-8');
        }

        return $prefixes;
    }

    /**
     * Generates prefixes by removing syllables one by one from the beginning of the word.
     *
     * @param string $word The word to process.
     * @param int $minPrefixLength The minimum allowed length for a prefix.
     * @param string $lang The language code to use for looking up the Syllable instance.
     * @return array<int, string>
     */
    private function prefixesFromSyllables(string $word, int $minPrefixLength, string $lang): array
    {
        $prefixes = [];
        $syll = $this->sylls[$lang] ?? $this->sylls[self::LANG_DEFAULT];
        $syllables = $syll->splitWord($word);

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
}