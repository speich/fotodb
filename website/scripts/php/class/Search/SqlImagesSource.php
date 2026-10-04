<?php

namespace PhotoDatabase\Search;



/**
 * Creates the SQL query and structure definitions to populate the FTS4 image search index.
 *
 * Maps external database structure to the flattened virtual table index.
 */
class SqlImagesSource extends SqlIndexerSource
{
    /**
     * @var array<string, string> Dictionary mapping FTS table columns (keys) to their actual SQL SELECT expressions (values).
     */
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
        'Locations' => '(SELECT GROUP_CONCAT(l.Name) FROM Locations l INNER JOIN Images_Locations il ON l.id = il.LocationId WHERE il.ImgId = i.Id)',
        'KeywordsDe' => '(SELECT GROUP_CONCAT(k.NameDe) FROM Keywords k INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId WHERE ik.ImgId = i.Id)',
        'KeywordsEn' => '(SELECT GROUP_CONCAT(k.NameEn) FROM Keywords k INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId WHERE ik.ImgId = i.Id)',
        'CommonNamesDe' => '(SELECT GROUP_CONCAT(s.NameDe) FROM ScientificNames s INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId WHERE isc.ImgId = i.Id)',
        'CommonNamesEn' => '(SELECT GROUP_CONCAT(s.NameEn) FROM ScientificNames s INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId WHERE isc.ImgId = i.Id)',
        'ScientificNames' => '(SELECT GROUP_CONCAT(s.NameLa) FROM ScientificNames s INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId WHERE isc.ImgId = i.Id)',
        'Rating' => 'r.Value',
    ];

    /**
     * Columns that should not undergo string tokenization/prefix generation.
     *
     * @todo Evaluate making this metadata a property within the $columns array format.
     * @var array<int, string>
     */
    private array $prefixExclusions = ['ImgId', 'ImgFolder', 'ImgName', 'ThemeDe', 'ThemeEn', 'SubjectDe', 'SubjectEn', 'CountryDe', 'CountryEn', 'Locations', 'ScientificNames', 'Rating'];

    /**
     * Returns the array of column names that require prefix processing.
     *
     * Automatically filters out structural or purely numerical columns defined
     * in the prefix exclusions list (like ImgId, CountryDe, etc).
     *
     * @return array<int, string> Flat array of column names.
     */
    public function getColPrefixes(): array
    {
        $cols = array_keys($this->columns);

        return array_values(array_diff($cols, $this->prefixExclusions));
    }

    /**
     * Returns all destination column names for the FTS table structure.
     *
     * @return array<int, string> Array of column aliases used in the virtual table.
     */
    public function getColNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Compiles the SELECT list for the main data extraction query.
     *
     * @return string A comma-separated SQL string of expressions mapping to their aliases.
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
    /**
     * Compiles the FROM/JOIN clause for the main data extraction query.
     *
     * Resolves all relational tables necessary to compile the image metadata into a single searchable row.
     *
     * @return string The SQL FROM clause containing all necessary JOINs.
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