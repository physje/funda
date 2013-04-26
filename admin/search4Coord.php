<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

# Informatie over het zoeken met coordinaten in een MySQL-query heb ik gekopieerd van
# 	https://developers.google.com/maps/articles/phpsqlsearch_v3
#		http://stackoverflow.com/questions/574691/mysql-great-circle-distance-haversine-formula
#
# Informatie over het gebruik van een Google Maps Location Picker heb ik gehaald van
# 	http://www.tytai.com/gmap/

if($_POST[search]) {	
	$sql = "SELECT $HuizenID, (6371*acos(cos(radians($_POST[latitude]))*cos(radians($HuizenLat))*cos(radians($HuizenLon) - radians($_POST[longitude]))+sin(radians($_POST[latitude])) * sin(radians($HuizenLat)))) AS distance FROM $TableHuizen HAVING distance < $_POST[afstand] ORDER BY distance";
	$result = mysql_query($sql);
		
	if($row = mysql_fetch_array($result)) {
		$nieuwNaam = $_POST[afstand] ." km rondom ". $_POST[latitude] .', '. $_POST[longitude];
		$lijstID = saveUpdateList('', 1, $nieuwNaam);		
		do {
			$deel_2 .= addHouse2List($row[$HuizenID], $lijstID);			
		} while($row = mysql_fetch_array($result));
		
		$deel_1 = "<p>Selectie opgeslagen als <a href='edit_lijsten.php?list=$lijstID'>$nieuwNaam</a>";
	} else {
		$deel_1 = "<p>Selectie bevat geen huizen";
	}	
} else {
	$deel_1 .= "<form method='post'>\n";
	$deel_1 .= "<table border=0>\n";
	$deel_1 .= "	<tr>\n";
	$deel_1 .= "		<td valign=top>Latitude:</td>\n";
	$deel_1 .= "		<td><input size=10 type=text value='52.543210' id='latval' name='latitude'></td>\n";
	$deel_1 .= "		<td valign=top>Longitude:</td>\n";
	$deel_1 .= "		<td><input size=10 type=text value='5.258258' id='longval' name='longitude'></td>\n";
	$deel_1 .= "		<td align=center><input type=button value='Ga naar lokatie' onclick='if_gmap_loadpicker();'></td>\n";
	$deel_1 .= "	</tr>\n";
	$deel_1 .= "	<tr>\n";
	$deel_1 .= "		<td colspan=5 id='maparea' style='background-color: #E0E0E0;' align=center>Klik op de kaart of voer boven de coördinaten in</center>\n";
	$deel_1 .= "			<div id='mapitems' style='width: 480px; height: 540px'></div>\n";
	$deel_1 .= "		</td>\n";
	$deel_1 .= "	</tr>\n";
	$deel_1 .= "	<tr>\n";
	$deel_1 .= "		<td colspan=5 align=center>Maximale afstand : <input size=10 name='afstand'> km <div class=''><input type='submit' name='search' value='Huizen zoeken'></div></td>\n";
	$deel_1 .= "	</tr>\n";
	$deel_1 .= "	</table>\n";
	$deel_1 .= "</form>\n";
	$googleMaps = true;	
}

include_once('../include/HTML_TopBottom.php');

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "</td><td width='50%' valign='top' align='center'>\n";
if($deel_2 != "") echo showBlock($deel_2);
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
?>