SELECT i.Id ImgId, i.ImgFolder, i.ImgName, i.ImgTitle, i.ImgDesc,
            t.NameDe Theme,
            c.NameDe Country,
            (SELECT GROUP_CONCAT(k.Name) FROM Keywords k
                INNER JOIN Images_Keywords ik ON k.Id = ik.KeywordId
                WHERE ik.ImgId = i.Id) Keywords,
            (SELECT GROUP_CONCAT(l.Name) FROM Locations l
                INNER JOIN Images_Locations il ON l.id = il.LocationId
                WHERE il.ImgId = i.Id) Locations,
            (SELECT GROUP_CONCAT(s.NameDe) FROM ScientificNames s
                INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId
                WHERE isc.ImgId = i.Id) CommonNames,
            (SELECT GROUP_CONCAT(s.NameLa) FROM ScientificNames s
                INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId
                WHERE isc.ImgId = i.Id) ScientificNames,
            sj.NameDe Subject,
            r.Value Rating

FROM Images i
            LEFT JOIN Images_Themes it ON i.Id = it.ImgId
            LEFT JOIN Themes t ON it.ThemeId = t.Id
            LEFT JOIN SubjectAreas sj ON t.SubjectAreaId = sj.Id
            LEFT JOIN Countries c ON c.Id = i.CountryId
            LEFT JOIN Images_ScientificNames isc ON i.Id = isc.ImgId
            LEFT JOIN ScientificNames s ON isc.ScientificNameId = s.Id
            INNER JOIN Rating r ON i.RatingId = r.Id
;

SELECT GROUP_CONCAT(s.NameDe) FROM ScientificNames s
                                       INNER JOIN Images_ScientificNames isc ON s.Id = isc.ScientificNameId