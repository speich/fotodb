<?php

use WebsiteTemplate\html\SelectField;


require_once __DIR__.'/../scripts/php/inc_script.php';

$q = $db->db->query('SELECT * FROM Countries ORDER BY NameEn ASC');
$country = new SelectField($q->fetchAll(PDO::FETCH_NUM), 'CountryId');
$country->setDefaultVal('Land wählen');

$q = $db->db->query("SELECT Id, CASE WHEN Code NOT NULL THEN Name ||' ('||Code||')' ELSE Name END Name FROM FilmTypes ORDER BY Name ASC");
$filmType = new SelectField($q->fetchAll(PDO::FETCH_NUM), 'FilmTypeId');
$filmType->setSelected('10');

$q = $db->db->query('SELECT * FROM Rating ORDER BY Name ASC');
$rating = new SelectField($q->fetchAll(PDO::FETCH_NUM), 'RatingId');
$rating->setDefaultVal(false);
$rating->setSelected('2');

$q = $db->db->query('SELECT * FROM Themes ORDER BY NameDe ASC');
$theme = new SelectField($q->fetchAll(PDO::FETCH_NUM), 'Theme');
$theme->setMultiple();
$theme->setDefaultVal(false);
$theme->setCssStyle('height: 200px');

$q = $db->db->query('SELECT * FROM Locations ORDER BY Name ASC');
$location = new SelectField($q->fetchAll(PDO::FETCH_NUM), 'Location');
$location->setMultiple();
$location->setDefaultVal(false);

$q = $db->db->query('SELECT Id, NameDe FROM Sexes ORDER BY NameDe ASC');
$speciesSex = new SelectField($q->fetchAll(PDO::FETCH_NUM), 'SpeciesSexId');
$speciesSex->setSelected('4');
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Foto Database</title>
    <link type="text/css" rel="stylesheet" href="layout/application.css">
    <link type="text/css" rel="stylesheet" href="layout/explorer.css">
    <link type="text/css" rel="stylesheet" href="layout/form.css">
    <link rel="stylesheet" href="../library/dojo/1.13.0/dijit/themes/tundra/tundra.css">
    <link rel="stylesheet" href="../library/dojo/1.13.0/dojo/resources/dojo.css">
    <style type="text/css">
        #LayoutSplit1 {
            position: absolute;
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body class="tundra">
<form id="FrmDbFoto">
    <div id="LayoutSplit1">
        <div id="Left1">
            <div id="LayoutSplit3">
                <div id="ImgExplTop">View<select id="FEType">
                        <option value="File" selected="selected">file explorer</option>
                        <option value="Image">image explorer</option>
                        <option value="Details">details</option>
                    </select><br>
                    to do<input id="FEFilterNotDone" type="checkbox">
                    done<input id="FEFilterDone" type="checkbox">
                    <div id="ImgExplCont">
                        <div id="ImgExpl"></div>
                    </div>
                </div>
                <div id="PaneLeftExifContent"></div>
            </div>
        </div><!-- End Left1 -->
        <div id="Right1">

            <div id="LayoutSplit2">
                <div id="TabCont">
                    <div id="Tab1">
                        <div id="CanvasImgPreview"><img src="layout/images/spacer.gif" alt="Preview" id="ImgPreview">
                        </div>
                        <label for="ImgFolder">image folder</label>
                        <input name="ImgFolder" id="ImgFolder" type="text">
                        <label for="ImgName">image name</label>
                        <input id="ImgName" type="text">
                        <br>
                        <label for="ImgDateOriginal">date original (from exif data)</label>
                        <input id="ImgDateOriginal" type="text">
                        <br>
                        <label for="DateAdded">date added</label>
                        <input id="DateAdded" type="text">
                        <br>
                        <label for="LastChange">last changed</label>
                        <input id="LastChange" type="text">
                    </div><!-- End Tab1 -->
                    <div id="Tab2">
                        <div id="map-container"></div>
                        <label for="MapAddress">find Address</label>
                        <input id="MapAddress" type="text">
                        <input id="MapFindAddress" type="button" value="find">
                        <div style="float: left;">
                            <label for="CountryId">Country</label><br>
                            <?php echo $country->render(); ?>
                        </div>
                        <div style="float: left;">
                            <label for="ImgLat">latitude</label><br>
                            <input id="ImgLat" type="text">
                        </div>
                        <div>
                            <label for="ImgLng">longitude</label><br>
                            <input id="ImgLng" type="text">
                        </div>
                        <div style="float: left">
                            <label for="LocationName">Ort</label><br>
                            <input type="text" id="LocationName"><br>
                            <?php echo $location->render(); ?>
                        </div>
                        <div id="Locations" class="ContList"></div>
                        Show location<input type="checkbox" id="ShowLoc" checked="false">
                    </div><!-- End Tab2 -->
                </div><!-- End TabCont -->
                <div id="Right2">
                    <div id="DivSpeciesModule">
                        <div style="float: left"><label for="SpeciesSexId">Species sex</label><br>
                            <?php echo $speciesSex->render(); ?>
                        </div>
                        <div style="float: left"><label for="SpeciesNameDe">Species name German</label><br>
                            <input type="text" id="SpeciesNameDe" class="FldSpeciesFilter"></div>
                        <div style="float: left"><label for="SpeciesNameEn">Species name English</label><br>
                            <input type="text" id="SpeciesNameEn" class="FldSpeciesFilter"></div>
                        <div><label for="SpeciesNameLa">Species name Latin</label><br>
                            <input type="text" id="SpeciesNameLa" class="FldSpeciesFilter"></div>
                        <div id="Species" class="ContList"></div>
                    </div>
                    <label for="ImgTitle">image title</label><input id="ImgTitle" type="text">public<input
                            type="checkbox" id="Public" checked="checked"><br>
                    <label for="ImgDesc">image description</label><textarea id="ImgDesc"></textarea><br>
                    <div>
                        <div style="float: left;">
                            <label for="Theme">Theme</label><br>
                            <?php echo $theme->render(); ?>
                            <img src="layout/images/deselect.gif" onclick="d.getElementById('Theme').selectedIndex=-1;"
                                 style="cursor: pointer" alt="deselect icon">
                        </div>
                        <label for="KeywordName">Keyword</label><br>
                        <select id="KeywordName">
                            <option></option>
                        </select>
                        <div id="Keywords" class="ContList"></div>
                    </div>
                    <label for="FilmTypeId">FilmType</label><br>
                    <?php echo $filmType->render(); ?>
                    <div style="float: left">
                        <label for="ImgDateManual">Date</label><br>
                        <input type="text" id="ImgDateManual" class="FldInputTxt">
                    </div>
                    <div style="margin-bottom: 5px;">
                        <label for="RatingId">Rating</label><br>
                        <?php echo $rating->render(); ?>
                    </div>
                    <label for="ImgTechInfo">TechInfo</label>
                    <textarea id="ImgTechInfo"></textarea>
                    <input type="button" id="FncSaveImg" value="save"><a
                            href="http://fotodb/scripts/php/controller/dbfunctions.php?Fnc=Publish">publish</a>
                </div><!-- End Right2 -->

            </div><!-- End LayoutSplit2 -->

        </div><!-- End Right1 -->
    </div><!-- End LayoutSplit1 -->
</form>
<script type="text/javascript">
    var dojoConfig = {
        async: true,
        locale: 'de',
        gmapsApiKey: 'AIzaSyAW3fEVRPQKGe4jxsp4T0pPFVQ9eJ-o86g',
        baseUrl: '/library',
        packages: [
            {name: 'dojo', location: '../library/dojo/1.13.0/dojo'},
            {name: 'dojox', location: '../library/dojo/1.13.0/dojox'},
            {name: 'dijit', location: '../library/dojo/1.13.0/dijit'},
            {name: 'fotodb', location: '../../../scripts/js'}
        ]
    };
</script>
<script src="../library/dojo/1.13.0/dojo/dojo.js" type="text/javascript"></script>
<script type="text/javascript">
require([
    'fotodb/FotoDb',
    'dojo/ready'
], function (FotoDb, ready) {

    ready(function () {
        var app = new FotoDb();
        app.init();
    });
});
</script>
</body>
</html>
