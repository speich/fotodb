<?php

namespace PhotoDatabase\Search;

/**
 * Class Indexer
 * @package PhotoDatabase\Search
 */
abstract class Indexer implements Fts4Indexer
{
    // TODO use SQL class/interface from lfi/NAFIDAS
    protected $sqlSource = "SELECT ImgName Keyword FROM Images WHERE Public = 1
          UNION
          SELECT ImgTitle FROM Images WHERE Public = 1
          UNION
          SELECT ImgDesc FROM Images WHERE Public = 1
          UNION
          SELECT c.NameDe FROM Images i
          INNER JOIN Countries c ON i.CountryId = c.Id
          WHERE i.Public = 1
          UNION
          SELECT k.Name FROM Images i
          INNER JOIN Images_Keywords ik ON i.Id = ik.ImgId
          INNER JOIN Keywords k ON ik.KeywordId = k.Id
          WHERE i.Public = 1
          UNION
          SELECT l.Name FROM Images i
          INNER JOIN Images_Locations il ON il.ImgId = i.Id
          INNER JOIN Locations l ON il.LocationId = l.Id
          WHERE i.Public = 1
          UNION
          SELECT s.NameDe FROM Images i
          INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
          INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
          WHERE i.Public = 1
          UNION
          SELECT s.NameLa FROM Images i
          INNER JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
          INNER JOIN ScientificNames s ON isc.ScientificNameId = s.Id
          WHERE i.Public = 1
          UNION
          SELECT t.NameDe FROM Images i
          INNER JOIN Images_Themes it ON i.Id = it.ImgId
          INNER JOIN Themes t ON it.ThemeId = t.Id
          WHERE i.Public = 1
          UNION
          SELECT a.NameDe FROM Images i
          INNER JOIN Images_Themes it ON i.Id = it.ImgId
          INNER JOIN Themes t ON it.ThemeId = t.Id
          INNER JOIN SubjectAreas a ON t.SubjectAreaId = a.Id
          WHERE i.Public = 1";
}