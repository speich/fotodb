DELETE FROM Images_Themes WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ('2013-01-Guyana-0468.jpg', '2013-01-Guyana-0520.jpg', '2013-01-Guyana-0607.jpg'));
DELETE FROM Exif WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ('2013-01-Guyana-0468.jpg', '2013-01-Guyana-0520.jpg', '2013-01-Guyana-0607.jpg'));
DELETE FROM Xmp WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ('2013-01-Guyana-0468.jpg', '2013-01-Guyana-0520.jpg', '2013-01-Guyana-0607.jpg'));
DELETE FROM Images_Keywords WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ('2013-01-Guyana-0468.jpg', '2013-01-Guyana-0520.jpg', '2013-01-Guyana-0607.jpg'));
DELETE FROM Images_Locations WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ('2013-01-Guyana-0468.jpg', '2013-01-Guyana-0520.jpg', '2013-01-Guyana-0607.jpg'));
DELETE FROM Images_ScientificNames WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ('2013-01-Guyana-0468.jpg', '2013-01-Guyana-0520.jpg', '2013-01-Guyana-0607.jpg'));
DELETE FROM Images_ScientificNames WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ('2013-01-Guyana-0468.jpg', '2013-01-Guyana-0520.jpg', '2013-01-Guyana-0607.jpg'));
DELETE FROM Images WHERE ImgName IN ('2013-01-Guyana-0468.jpg', '2013-01-Guyana-0520.jpg', '2013-01-Guyana-0607.jpg');