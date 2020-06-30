<?php

namespace PhotoDatabase\Search;

use PhotoDatabase\Sql\Sql;


/**
 * Class SqlImagesSource
 * Creates the query for the search index.
 * @package PhotoDatabase\Search
 */
class SqlImagesSource extends Sql
{
    public function getList(): string
    {
        return "Id, ImgName, 0.25";
    }

    public function getFrom(): string
    {
        return "Images WHERE Public = 1
          UNION
          SELECT i.Id, NameDe, 2 FROM Images i
            INNER JOIN Images_Themes it ON i.Id = it.ImgId
            INNER JOIN Themes t ON it.ThemeId = t.Id 
          UNION
          SELECT Id, ImgTitle, 2 FROM Images WHERE Public = 1 AND ImgTitle != ''
          UNION
          SELECT Id, ImgDesc, 1 FROM Images WHERE Public = 1 AND ImgDesc != ''
          UNION
          SELECT i.Id, c.NameDe, 0.25 FROM Images i
            INNER JOIN Countries c ON i.CountryId = c.Id
            WHERE i.Public = 1 AND c.NameDe != ''
          UNION
          SELECT i.Id, k.Name, 1 FROM Images i
            INNER JOIN Images_Keywords ik ON i.Id = ik.ImgId
            INNER JOIN Keywords k ON ik.KeywordId = k.Id
            WHERE i.Public = 1 AND k.Name != ''
          UNION
          SELECT i.Id, l.Name, 0.5 FROM Images i
              INNER JOIN Images_Locations il ON il.ImgId = i.Id
              INNER JOIN Locations l ON il.LocationId = l.Id
              WHERE i.Public = 1 AND l.name != ''
          UNION
          SELECT i.Id, s.NameDe, 1 FROM Images i
              INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
              INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
              WHERE i.Public = 1 AND s.NameDe != ''
          UNION
          SELECT i.Id, s.NameLa, 1 FROM Images i
              INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
              INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
              WHERE i.Public = 1 AND s.NameLa != ''
          UNION
          SELECT i.Id, t.NameDe, 0.5 FROM Images i
              INNER JOIN Images_Themes it ON i.Id = it.ImgId
              INNER JOIN Themes t ON it.ThemeId = t.Id
              WHERE i.Public = 1 AND t.NameDe != ''
          UNION
          SELECT i.Id, a.NameDe, 0.25 FROM Images i
              INNER JOIN Images_Themes it ON i.Id = it.ImgId
              INNER JOIN Themes t ON it.ThemeId = t.Id
              INNER JOIN SubjectAreas a ON t.SubjectAreaId = a.Id
              WHERE i.Public = 1 AND a.NameDe != '';";
    }
}