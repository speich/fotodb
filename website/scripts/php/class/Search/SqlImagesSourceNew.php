<?php

namespace PhotoDatabase\Search;



/**
 * Class SqlImagesSource
 * Creates the query to populate the the fts4 image search index.
 * @package PhotoDatabase\Search
 */
class SqlImagesSourceNew extends SqlIndexerSource
{
    /** @var string[] $column column names to index */
    public array $colNames = [
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
        'Subject'
    ];

    /** @var array scoring weight of each column excluding id column */
    public array $weights = [1, 1, 2, 1, 2, 0.25, 1, 0.5, 1, 1, 0.25];

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
            sj.NameDe Subject';
    }

    public function getFrom(): string
    {
        return 'Images i
            LEFT JOIN Images_Themes it ON i.Id = it.ImgId
            LEFT JOIN Themes t ON it.ThemeId = t.Id
            LEFT JOIN SubjectAreas sj ON t.SubjectAreaId = sj.Id
            LEFT JOIN Countries c ON c.Id = i.CountryId
            LEFT JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
            LEFT JOIN ScientificNames s ON isc.ScientificNameId = s.Id';
    }
}