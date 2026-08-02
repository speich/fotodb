<?php

namespace PhotoDatabase\Search;



/**
 * Class SqlImagesSource
 * Creates the query to populate the fts4 image search index.
 */
class SqlImagesSource extends SqlIndexerSource
{
    /** @var string[] the list of columns to index for the fts search.  */
    private array $columns = [
        'ImgId'      => 'i.Id',
        'ImgFolder'  => 'i.ImgFolder',
        'ImgName'    => 'i.ImgName',
        'ImgTitle'   => 'i.ImgTitle',
        'ImgDesc'    => 'i.ImgDesc',
        'ThemeDe'    => 't.NameDe',
        'ThemeEn'    => 't.NameEn',
        'SubjectDe' => 'sj.NameDe',
        'SubjectEn' => 'sj.NameEn',
        'CountryDe'  => 'c.NameDe',
        'CountryEn'  => 'c.NameEn',
        'KeywordsDe' => '(SELECT GROUP_CONCAT(k.NameDe) FROM Keywords k INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId WHERE ik.ImgId = i.Id)',
        'KeywordsEn' => '(SELECT GROUP_CONCAT(k.NameEn) FROM Keywords k INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId WHERE ik.ImgId = i.Id)',
        'CommonNamesDe' => '(SELECT GROUP_CONCAT(s.NameDe) FROM ScientificNames s INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId WHERE isc.ImgId = i.Id)',
        'CommonNamesEn' => '(SELECT GROUP_CONCAT(s.NameEn) FROM ScientificNames s INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId WHERE isc.ImgId = i.Id)',
        'ScientificNames' => '(SELECT GROUP_CONCAT(s.NameLa) FROM ScientificNames s INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId WHERE isc.ImgId = i.Id)',
        'Rating' => 'r.Value',
    ];

    /**
     * Columns that should not be tokenized.
     * @var string[]
     */
    private array $prefixExclusions = ['ImgId', 'ImgFolder', 'ImgName', 'CountryDe', 'CountryEn', 'ThemeDe', 'ThemeEn', 'ScientificNames', 'Rating'];

    /**
     * Returns the columns that need prefix processing.
     * Automatically excludes internal columns like ImgId or ImgFolder.
     * @return array<int, string>
     */
    public function getColPrefixes(): array
    {
        $cols = array_keys($this->columns);

        return array_values(array_diff($cols, $this->prefixExclusions));
    }

    /**
     * Return the column names for the FTS table.
     * @return array<int, string>
     */
    public function getColNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Return the list part of the SQL.
     * @return string
     */
    public function getList(): string
    {
        $selects = [];
        foreach ($this->columns as $alias => $expression) {
            // Using AS ensures the column name perfectly matches the array key
            $selects[] = "$expression AS $alias";
        }

        return implode(", ", $selects);
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return 'Images i
            LEFT JOIN Images_Themes it ON i.Id = it.ImgId
            LEFT JOIN Themes t ON it.ThemeId = t.Id
            LEFT JOIN SubjectAreas sj ON t.SubjectAreaId = sj.Id
            LEFT JOIN Countries c ON c.Id = i.CountryId
            LEFT JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
            LEFT JOIN ScientificNames s ON isc.ScientificNameId = s.Id
            INNER JOIN Rating r ON i.RatingId = r.Id';
    }
}