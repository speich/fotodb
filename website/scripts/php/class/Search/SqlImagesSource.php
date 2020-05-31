<?php

namespace PhotoDatabase\Search;

use PhotoDatabase\Sql\Sql;


class SqlImagesSource extends Sql
{
    public function getList(): string
    {
        return "i.Id rowid, /* when using a view for the content table, only rowid is accepted as a column name */
            i.ImgName, i.ImgTitle, i.ImgDesc,
            c.NameDe Country,
            k.Keywords,
            l.Locations,
            s.CommonNames,
            sc.ScientificNames,
            t.Themes,
            a.SubjectAreas";
    }

    public function getFrom(): string
    {
        return "Images i
            LEFT JOIN Countries c ON i.CountryId = c.Id
            LEFT JOIN (
                SELECT ik.imgId, GROUP_CONCAT(k.Name, ', ') Keywords FROM Images_Keywords ik
                INNER JOIN Keywords k ON ik.KeywordId = k.Id
                WHERE k.Name NOT NULL
                GROUP BY ik.ImgId
            ) k ON i.Id = k.ImgId
            LEFT JOIN (
                SELECT il.ImgId, GROUP_CONCAT(l.Name, ', ') Locations FROM Images_Locations il
                INNER JOIN Locations l ON il.LocationId = l.Id
                WHERE l.Name NOT NULL
                GROUP BY il.ImgId
            ) l ON i.Id = l.ImgId
            LEFT JOIN (
                SELECT sc.ImgId, GROUP_CONCAT(s.NameDe, ', ') CommonNames FROM Images_ScientificNames sc
                INNER JOIN ScientificNames s ON sc.ScientificNameId = s.Id
                WHERE s.NameDe NOT NULL
                GROUP BY sc.ImgId
            ) s ON i.Id = s.ImgId
            LEFT JOIN (
                SELECT sc.ImgId, GROUP_CONCAT(s.NameLa, ', ') ScientificNames FROM Images_ScientificNames sc
                INNER JOIN ScientificNames s ON sc.ScientificNameId = s.Id
                WHERE s.NameLa NOT NULL
                GROUP BY sc.ImgId
            ) sc ON i.Id = sc.ImgId
            LEFT JOIN (
                SELECT it.ImgId, GROUP_CONCAT(t.NameDe, ', ') Themes FROM Images_Themes it
                INNER JOIN Themes t ON it.ThemeId = t.Id
                GROUP BY it.ImgId
            ) t ON i.Id = t.ImgId
            LEFT JOIN (
                SELECT it.ImgId, GROUP_CONCAT(s.NameDe, ', ') SubjectAreas FROM Images_Themes it
                INNER JOIN Themes t ON it.ThemeId = t.Id
                INNER JOIN SubjectAreas s ON t.SubjectAreaId = s.Id
                GROUP BY it.ImgId
            ) a ON i.Id = a.ImgId;";
    }
}