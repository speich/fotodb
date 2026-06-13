DELETE FROM Images_Themes WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ());
DELETE FROM Exif WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ());
DELETE FROM Xmp WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ());
DELETE FROM Images_Keywords WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ());
DELETE FROM Images_Locations WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ());
DELETE FROM Images_ScientificNames WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN ());
DELETE FROM Images WHERE ImgName IN ();


DELETE FROM Images_Themes WHERE ImgId IN (SELECT Id FROM main.Images WHERE ImgName IN (
'2013-01-Guyana/2013-01-Guyana-0066.jpg',
'2013-01-Guyana/2013-01-Guyana-0068.jpg',
'2013-01-Guyana/2013-01-Guyana-0070.jpg',
'2013-01-Guyana/2013-01-Guyana-0085.jpg',
'2013-01-Guyana/2013-01-Guyana-0088.jpg',
'2013-01-Guyana/2013-01-Guyana-0090.jpg',
'2013-01-Guyana/2013-01-Guyana-0097.jpg',
'2013-01-Guyana/2013-01-Guyana-0098.jpg',
'2013-01-Guyana/2013-01-Guyana-0099.jpg',
'2013-01-Guyana/2013-01-Guyana-0117.jpg',
'2013-01-Guyana/2013-01-Guyana-0123.jpg',
'2013-01-Guyana/2013-01-Guyana-0147.jpg',
'2013-01-Guyana/2013-01-Guyana-0152.jpg',
'2013-01-Guyana/2013-01-Guyana-0188.jpg',
'2013-01-Guyana/2013-01-Guyana-0277.jpg',
'2013-01-Guyana/2013-01-Guyana-0305.jpg',
'2013-01-Guyana/2013-01-Guyana-0317.jpg',
'2013-01-Guyana/2013-01-Guyana-0326.jpg',
'2013-01-Guyana/2013-01-Guyana-0333.jpg',
'2013-01-Guyana/2013-01-Guyana-0453.jpg',
'2013-01-Guyana/2013-01-Guyana-0531.jpg',
'2013-01-Guyana/2013-01-Guyana-0649.jpg',
'2013-01-Guyana/2013-01-Guyana-0652.jpg',
'2013-01-Guyana/2013-01-Guyana-0613.jpg',
'2013-01-Guyana/2013-01-Guyana-0645.jpg',
'2013-01-Guyana/2013-01-Guyana-0657.jpg',
'2013-01-Guyana/2013-01-Guyana-0659.jpg',
'2013-01-Guyana/2013-01-Guyana-0703.jpg',
'2013-01-Guyana/2013-01-Guyana-0739.jpg',
'2013-01-Guyana/2013-01-Guyana-0750.jpg',
'2013-01-Guyana/2013-01-Guyana-0759.jpg',
'2013-01-Guyana/2013-01-Guyana-0768.jpg',
'2013-01-Guyana/2013-01-Guyana-0782.jpg',
'2013-01-Guyana/2013-01-Guyana-0784.jpg',
'2013-01-Guyana/2013-01-Guyana-0792.jpg',
'2013-01-Guyana/2013-01-Guyana-0796.jpg',
'2013-01-Guyana/2013-01-Guyana-0799.jpg',
'2013-01-Guyana/2013-01-Guyana-0800.jpg',
'2013-01-Guyana/2013-01-Guyana-0823.jpg',
'2013-01-Guyana/2013-01-Guyana-0834.jpg',
'2013-01-Guyana/2013-01-Guyana-0838.jpg',
'2013-01-Guyana/2013-01-Guyana-0841.jpg',
'2013-01-Guyana/2013-01-Guyana-0843.jpg',
'2013-01-Guyana/2013-01-Guyana-0847.jpg',
'2013-01-Guyana/2013-01-Guyana-0871.jpg'));