<?php

namespace PhotoDatabase\Search;


class SqlKeywordsSource extends SqlIndexerSource
{
    /**
     * The target columns for the FTS insertion.
     */
    public function getColNames(): array
    {
        return ['Keyword', 'Lang'];
    }

    /**
     * The columns that require syllable/prefix tokenization.
     */
    public function getColPrefixes(): array
    {
        return ['Keyword'];
    }

    /**
     * Returns the SELECT list part of the SQL.
     */
    public function getList(): string
    {
        return 'Keyword, Lang';
    }

    /**
     * Returns the FROM clause of the SQL as a derived table containing the UNIONs.
     * Every sub-select joins back to the Images table to ensure Public = 1.
     */
    public function getFrom(): string
    {
        return "(
            /* --- Themes --- */
            SELECT t.NameDe AS Keyword, 'de' AS Lang FROM Themes t
            INNER JOIN Images_Themes it ON t.Id = it.ThemeId
            INNER JOIN Images i ON it.ImgId = i.Id
            WHERE i.Public = 1 AND t.NameDe IS NOT NULL AND t.NameDe != ''
            UNION
            SELECT t.NameEn AS Keyword, 'en' AS Lang FROM Themes t
            INNER JOIN Images_Themes it ON t.Id = it.ThemeId
            INNER JOIN Images i ON it.ImgId = i.Id
            WHERE i.Public = 1 AND t.NameEn IS NOT NULL AND t.NameEn != ''
            
            UNION
            /* --- SubjectAreas --- */
            SELECT sa.NameDe AS Keyword, 'de' AS Lang FROM SubjectAreas sa
            INNER JOIN Themes t ON sa.Id = t.SubjectAreaId
            INNER JOIN Images_Themes it ON t.Id = it.ThemeId
            INNER JOIN Images i ON it.ImgId = i.Id
            WHERE i.Public = 1 AND sa.NameDe IS NOT NULL AND sa.NameDe != ''
            UNION
            SELECT sa.NameEn AS Keyword, 'en' AS Lang FROM SubjectAreas sa
            INNER JOIN Themes t ON sa.Id = t.SubjectAreaId
            INNER JOIN Images_Themes it ON t.Id = it.ThemeId
            INNER JOIN Images i ON it.ImgId = i.Id
            WHERE i.Public = 1 AND sa.NameEn IS NOT NULL AND sa.NameEn != ''
            
            UNION
            /* --- Keywords --- */
            SELECT k.NameDe AS Keyword, 'de' AS Lang FROM Keywords k
            INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId
            INNER JOIN Images i ON ik.ImgId = i.Id
            WHERE i.Public = 1 AND k.NameDe IS NOT NULL AND k.NameDe != ''
            UNION
            SELECT k.NameEn AS Keyword, 'en' AS Lang FROM Keywords k
            INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId
            INNER JOIN Images i ON ik.ImgId = i.Id
            WHERE i.Public = 1 AND k.NameEn IS NOT NULL AND k.NameEn != ''
            
            UNION
            /* --- ScientificNames (DE, EN) --- */
            SELECT s.NameDe AS Keyword, 'de' AS Lang FROM ScientificNames s
            INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId
            INNER JOIN Images i ON isc.ImgId = i.Id
            WHERE i.Public = 1 AND s.NameDe IS NOT NULL AND s.NameDe != ''
            UNION
            SELECT s.NameEn AS Keyword, 'en' AS Lang FROM ScientificNames s
            INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId
            INNER JOIN Images i ON isc.ImgId = i.Id
            WHERE i.Public = 1 AND s.NameEn IS NOT NULL AND s.NameEn != ''
            
            UNION
            /* --- Lang-Neutral: Scientific Latin Names --- */
            SELECT s.NameLa AS Keyword, 'de' AS Lang FROM ScientificNames s
            INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId
            INNER JOIN Images i ON isc.ImgId = i.Id
            WHERE i.Public = 1 AND s.NameLa IS NOT NULL AND s.NameLa != ''
            UNION
            SELECT s.NameLa AS Keyword, 'en' AS Lang FROM ScientificNames s
            INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId
            INNER JOIN Images i ON isc.ImgId = i.Id
            WHERE i.Public = 1 AND s.NameLa IS NOT NULL AND s.NameLa != ''
            
            UNION
            /* --- Lang-Neutral: Locations --- */
            SELECT l.Name AS Keyword, 'de' AS Lang FROM Locations l
            INNER JOIN Images_Locations il ON l.Id = il.LocationId
            INNER JOIN Images i ON il.ImgId = i.Id
            WHERE i.Public = 1 AND l.Name IS NOT NULL AND l.Name != ''
            UNION
            SELECT l.Name AS Keyword, 'en' AS Lang FROM Locations l
            INNER JOIN Images_Locations il ON l.Id = il.LocationId
            INNER JOIN Images i ON il.ImgId = i.Id
            WHERE i.Public = 1 AND l.Name IS NOT NULL AND l.Name != ''
        ) AS Dictionary";
    }

    /**
     * Returns the WHERE clause of the SQL.
     * (Filtering is done inside the subquery to maximize UNION DISTINCT efficiency)
     */
    public function getWhere(): string
    {
        return '';
    }

    /**
     * Returns the GROUP BY clause of the SQL.
     */
    public function getGroupBy(): string
    {
        return '';
    }

    /**
     * Returns the ORDER BY clause of the SQL.
     */
    public function getOrderBy(): string
    {
        return 'Keyword ASC';
    }
}