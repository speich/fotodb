<?php

namespace PhotoDatabase\Search;



/**
 * Class SqlImagesSource
 * Creates the query to populate the fts4 image search index.
 * @package PhotoDatabase\Search
 */
class SqlImagesSource extends SqlIndexerSource
{
    /** @var string[] columns to index */
    private array $colNames = [
        'ImgId',
        'ImgFolder',
        'ImgName',
        'ImgTitle',
        'ImgDesc',
        'Theme',
        'Country',
        'Keywords',
        'Locations',
        'CommonName',
        'ScientificName',
        'Subject',
        'Rating'
    ];

    /** @var array scoring weight of each column excluding id column */
    private array $colWeights = [1, 1, 2, 1, 2, 0.25, 1, 0.5, 1, 1, 0.25];

    /**
     * @var string[]
     */
    private array $colPrefixes = ['ImgTitle', 'ImgDesc', 'Keywords', 'CommonName'];

    /**
     * @return array
     */
    public function getColWeights(): array
    {
        return $this->colWeights;
    }

    /**
     * @return array|string[]
     */
    public function getColPrefixes(): array
    {
        return $this->colPrefixes;
    }

    /**
     * @return string[]
     */
    public function getColNames(): array
    {
        return $this->colNames;
    }

    /**
     * @return string
     */
    public function getList(): string
    {
        return 'i.Id ImgId, i.ImgFolder, i.ImgName, i.ImgTitle, i.ImgDesc,
            t.NameDe Theme,
            c.NameDe Country,
            (SELECT GROUP_CONCAT(k.Name) FROM Keywords k
                INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId
                WHERE ik.ImgId = i.Id) Keywords,
            (SELECT GROUP_CONCAT(l.Name) FROM Locations l
                INNER JOIN Images_Locations il ON l.id = il.LocationId
                WHERE il.ImgId = i.Id) Locations,
            s.NameDe CommonName, s.NameLa ScientificName,
            sj.NameDe Subject,
            r.Value Rating';
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