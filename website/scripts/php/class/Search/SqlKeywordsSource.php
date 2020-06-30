<?php

namespace PhotoDatabase\Search;

use PhotoDatabase\Sql\Sql;


class SqlKeywordsSource extends Sql
{

    public function getList(): string
    {
        return "ImgName";
    }

    public function getFrom(): string
    {
        return "Images WHERE Public = 1
          UNION
          SELECT ImgTitle FROM Images WHERE Public = 1 AND ImgTitle != ''
          UNION
          SELECT ImgDesc FROM Images WHERE Public = 1 AND ImgDesc != ''
          UNION
          SELECT c.NameDe FROM Images i
            INNER JOIN Countries c ON i.CountryId = c.Id
            WHERE i.Public = 1 AND c.NameDe != ''
          UNION
          SELECT k.Name FROM Images i
            INNER JOIN Images_Keywords ik ON i.Id = ik.ImgId
            INNER JOIN Keywords k ON ik.KeywordId = k.Id
            WHERE i.Public = 1 AND k.Name != ''
          UNION
          SELECT l.Name FROM Images i
              INNER JOIN Images_Locations il ON il.ImgId = i.Id
              INNER JOIN Locations l ON il.LocationId = l.Id
              WHERE i.Public = 1 AND l.name != ''
          UNION
          SELECT s.NameDe FROM Images i
              INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
              INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
              WHERE i.Public = 1 AND s.NameDe != ''
          UNION
          SELECT s.NameLa FROM Images i
              INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
              INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
              WHERE i.Public = 1 AND s.NameLa != ''
          UNION
          SELECT t.NameDe FROM Images i
              INNER JOIN Images_Themes it ON i.Id = it.ImgId
              INNER JOIN Themes t ON it.ThemeId = t.Id
              WHERE i.Public = 1 AND t.NameDe != ''
          UNION
          SELECT a.NameDe FROM Images i
              INNER JOIN Images_Themes it ON i.Id = it.ImgId
              INNER JOIN Themes t ON it.ThemeId = t.Id
              INNER JOIN SubjectAreas a ON t.SubjectAreaId = a.Id
              WHERE i.Public = 1 AND a.NameDe != '';";
    }
}