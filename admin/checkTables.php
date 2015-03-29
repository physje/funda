<?php
include_once(__DIR__. '../include/config.php');

include_once('../include/HTML_TopBottom.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

$error = array();

# Kijken of alle huizen uit de kenmerken-database ook bestaande huizen zijn
$sql		= "SELECT * FROM $TableKenmerken GROUP BY $KenmerkenID";
$result	= mysql_query($sql);
$row = mysql_fetch_array($result);
do {
	$huisID = $row[$KenmerkenID];
	$data = getFundaData($huisID);
	
	if(!is_array($data)) {
		$error[] = "<a href='HouseDetails.php?id=$huisID'>". $huisID . "</a> uit kenmerken-database niet gevonden in de huizen-database<br>";
	} else {
		$Kenmerken[] = $huisID;
	}
} while($row = mysql_fetch_array($result));

$melding[] = "Alle huizen uit de kenmerken-database zijn gecontroleerd<br>";



# Kijken of alle huizen uit de prijzen-database ook bestaande huizen zijn
$sql		= "SELECT * FROM $TablePrijzen GROUP BY $PrijzenID";
$result	= mysql_query($sql);
$row = mysql_fetch_array($result);
do {
	$huisID = $row[$PrijzenID];
	$data = getFundaData($huisID);
	
	if(!is_array($data)) {
		$error[] = "<a href='HouseDetails.php?id=$huisID'>". $huisID . "</a> uit prijzen-database niet gevonden in de huizen-database<br>";
	} else {
		$Prijzen[] = $huisID;
	}
} while($row = mysql_fetch_array($result));

$melding[] = "Alle huizen uit de prijzen-database zijn gecontroleerd<br>";



# Kijken of alle huizen uit de zoekresultaten ook bestaande huizen zijn
$sql		= "SELECT * FROM $TableResultaat GROUP BY $ResultaatID";
$result	= mysql_query($sql);
$row = mysql_fetch_array($result);
do {
	$huisID = $row[$ResultaatID];
	$data = getFundaData($huisID);
	
	if(!is_array($data)) {
		$error[] = "<a href='HouseDetails.php?id=$huisID'>". $huisID . "</a> uit resultaten-database niet gevonden in de huizen-database<br>";
	} else {
		$Resultaat[] = $huisID;
	}
} while($row = mysql_fetch_array($result));

$melding[] = "Alle huizen uit de resultaten-database zijn gecontroleerd<br>";

# Kijken of alle huizen uit de lijst-database ook bestaande huizen zijn
$sql		= "SELECT * FROM $TableListResult GROUP BY $ListResultHuis";
$result	= mysql_query($sql);
$row = mysql_fetch_array($result);
do {
	$huisID = $row[$ListResultHuis];
	$data = getFundaData($huisID);
	
	if(!is_array($data)) {
		$error[] = "<a href='HouseDetails.php?id=$huisID'>". $huisID . "</a> uit lijsten-database niet gevonden in de huizen-database<br>";
	}
} while($row = mysql_fetch_array($result));

$melding[] = "Alle huizen uit de lijsten-database zijn gecontroleerd<br>";


# Kijken of alle huizen uit de huizen-database ook in de resultaten-, prijzen- en kenmerken-database voorkomen
$sql		= "SELECT * FROM $TableHuizen GROUP BY $HuizenID";
$result	= mysql_query($sql);
$row = mysql_fetch_array($result);
do {
	$huisID = $row[$HuizenID];
	
	if(!in_array($huisID, $Resultaat)) {
		$error[] = "<a href='HouseDetails.php?id=$huisID' target='_blank'>". $huisID . "</a> is niet gevonden in de resultaten-database | <a href='delete.php?id=$huisID' target='_blank'>verwijder</a><br>";
	}
	
	if(!in_array($huisID, $Prijzen)) {
		$error[] = "<a href='HouseDetails.php?id=$huisID' target='_blank'>". $huisID . "</a> is niet gevonden in de prijzen-database<br>";
	}
	
	if(!in_array($huisID, $Kenmerken)) {
		$error[] = "<a href='HouseDetails.php?id=$huisID' target='_blank'>". $huisID . "</a> is niet gevonden in de kenmerken-database<br>";
	}
} while($row = mysql_fetch_array($result));

$melding[] = "Alle huizen uit de huizen-database zijn in andere databases opgezocht<br>";



# Kijken of alle huizen die in de huizen-db open huis hebben ook in de open huis database staan
$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenOpenHuis = '1'";
$result	= mysql_query($sql);
$row = mysql_fetch_array($result);
do {
	$huisID = $row[$ResultaatID];
	
	$result_2	= mysql_query("SELECT * FROM $TableCalendar WHERE $CalendarHuis like '$huisID' AND $CalendarEnd > ". mktime(0,0,0));
	if(mysql_num_rows($result_2) == 0) {		
		removeOpenHuis($huisID);
		$error[] = "<a href='HouseDetails.php?id=$huisID' target='_blank'>". $huisID . "</a> is niet gevonden in de open huis-database<br>";
	}	
} while($row = mysql_fetch_array($result));

$melding[] = "Alle open-huizen zijn gecontroleerd<br>";

if(count($error) == 0) {
	$error[] = "Geen foutmeldingen";
}

# Uitkomst netjes op het scherm tonen
echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $error));
echo "</td><td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $melding));
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
