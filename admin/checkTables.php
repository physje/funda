<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$error = $delete_kenmerken = $delete_prijzen = $delete_resultaten = $delete_lijsten = array();

$autoDelete['kenmerk'] = false;
$autoDelete['prijs'] = false;
$autoDelete['result'] = false;
$autoDelete['list'] = false;

$check['kenmerk'] = false;
$check['prijs'] = false;
$check['result'] = false;
$check['list'] = true;
$check['open'] = false;

$autoCollect = false;

# Foutieve huizen uit de huizen-database verwijderen.
# Foutief is bijvoorbeeld een funda-id van 0
$sql = "DELETE FROM $TableHuizen WHERE $HuizenID like '' OR $HuizenID like '0'";
mysqli_query($db, $sql);
$melding[] = "Foutieve huizen zijn verwijderd<br>";


if($check['kenmerk']) {
    # Kijken of alle huizen uit de kenmerken-database ook bestaande huizen zijn
    $sql		= "SELECT * FROM $TableKenmerken GROUP BY $KenmerkenID";
    $result	= mysqli_query($db, $sql);
    $row = mysqli_fetch_array($result);
    do {
    	$huisID = $row[$KenmerkenID];
    	$data = getFundaData($huisID);
		
    	if(count($data) == 0) {
    		$error[] = "In de kenmerken-database staat <a href='HouseDetails.php?id=$huisID'>$huisID</a>; is niet bekend als huis<br>";
    		$delete_kenmerken[] = $huisID;
    	} else {
    		$Kenmerken[] = $huisID;
    	}
    } while($row = mysqli_fetch_array($result));
    
    $melding[] = "Alle huizen uit de kenmerken-database zijn gecontroleerd<br>";
}


if($check['prijs']) {
    # Kijken of alle huizen uit de prijzen-database ook bestaande huizen zijn
    $sql		= "SELECT * FROM $TablePrijzen GROUP BY $PrijzenID";
    $result	= mysqli_query($db, $sql);
    $row = mysqli_fetch_array($result);
    do {
    	$huisID = $row[$PrijzenID];
    	$data = getFundaData($huisID);
    		
    	if(count($data) == 0) {
    		$error[] = "In de prijzen-database staat <a href='HouseDetails.php?id=$huisID'>$huisID</a>; is niet bekend als huis<br>";
    		$delete_prijzen[] = $huisID;
    	} else {
    		$Prijzen[] = $huisID;
    	}
    } while($row = mysqli_fetch_array($result));
    
    $melding[] = "Alle huizen uit de prijzen-database zijn gecontroleerd<br>";
}


if($check['result']) {
    # Kijken of alle huizen uit de zoekresultaten ook bestaande huizen zijn
    $sql		= "SELECT * FROM $TableResultaat GROUP BY $ResultaatID";
    $result	= mysqli_query($db, $sql);
    $row = mysqli_fetch_array($result);
    do {
    	$huisID = $row[$ResultaatID];
    	$data = getFundaData($huisID);
		
    	if(count($data) == 0) {
    		$error[] = "In de resultaten-database staat <a href='HouseDetails.php?id=$huisID'>$huisID</a>; is niet bekend als huis<br>";
    		$delete_resultaten[] = $huisID;
    	} else {
    		$Resultaat[] = $huisID;
    	}
    } while($row = mysqli_fetch_array($result));
    
    $melding[] = "Alle huizen uit de resultaten-database zijn gecontroleerd<br>";
}


if($check['list']) {
    # Kijken of alle huizen uit de lijst-database ook bestaande huizen zijn
    $sql		= "SELECT * FROM $TableListResult GROUP BY $ListResultHuis";
    $result	= mysqli_query($db, $sql);
    $row = mysqli_fetch_array($result);
    do {
    	$huisID = $row[$ListResultHuis];
    	$data = getFundaData($huisID);
		
    	if(count($data) == 0) {
    		$error[] = "In een van de lijsten komt <a href='HouseDetails.php?id=$huisID'>$huisID</a> voor; is niet bekend als huis<br>";
    		$delete_lijsten[] = $huisID;
    	}
    } while($row = mysqli_fetch_array($result));
    
    $melding[] = "Alle huizen uit de lijsten-database zijn gecontroleerd<br>";
}


# Kijken of alle huizen uit de huizen-database ook in de resultaten-, prijzen- en kenmerken-database voorkomen
$sql		= "SELECT * FROM $TableHuizen GROUP BY $HuizenID";
$result	= mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);
do {
	$huisID = $row[$HuizenID];
	$data = getFundaData($huisID);
	$adres = $data['adres'];
	$offline = $data['offline'];
	
	if(!in_array($huisID, $Resultaat) AND $check['result']) {
		$error[] = "<a href='HouseDetails.php?id=$huisID' target='_blank'>$adres</a> (". ($offline ? $huisID : "<a href='http://funda.nl/$huisID'>$huisID</a>") .") is niet gevonden in de resultaten-database<br>";		
		if($autoCollect)	mark4Details($huisID);
	}
	
	if(!in_array($huisID, $Prijzen) AND $check['prijs']) {
		$error[] = "<a href='HouseDetails.php?id=$huisID' target='_blank'>$adres</a> (". ($offline ? $huisID : "<a href='http://funda.nl/$huisID'>$huisID</a>") .") is niet gevonden in de prijzen-database<br>";
		if($autoCollect)	mark4Details($huisID);
	}
	
	if(!in_array($huisID, $Kenmerken) AND $check['kenmerk']) {
		$error[] = "<a href='HouseDetails.php?id=$huisID' target='_blank'>$adres</a> (". ($offline ? $huisID : "<a href='http://funda.nl/$huisID'>$huisID</a>") .") is niet gevonden in de kenmerken-database<br>";
		if($autoCollect)	mark4Details($huisID);
	}
} while($row = mysqli_fetch_array($result));

$melding[] = "Alle huizen uit de huizen-database zijn in andere databases opgezocht<br>";

if($check['open']) {
    # Kijken of alle huizen die in de huizen-db open huis hebben ook in de open huis database staan
    $sql		= "SELECT * FROM $TableHuizen WHERE $HuizenOpenHuis = '1'";
    $result	= mysqli_query($db, $sql);
    $row = mysqli_fetch_array($result);
    do {
    	$huisID = $row[$HuizenID];
    	$adres = urldecode($row[$HuizenAdres]);
	
    	$result_2	= mysqli_query($db, "SELECT * FROM $TableCalendar WHERE $CalendarHuis like '$huisID' AND $CalendarEnd > ". mktime(0,0,0));
    	if(mysqli_num_rows($result_2) == 0) {		
    		removeOpenHuis($huisID);
    		$error[] = "<a href='HouseDetails.php?id=$huisID' target='_blank'>$adres</a> ($huisID) is niet gevonden in de open huis-database<br>";
    	}	
    } while($row = mysqli_fetch_array($result));
    
    $melding[] = "Alle open-huizen zijn gecontroleerd<br>";
}

# Kijken of er geen dubbele huizen voorkomen in de resultaten database
$sql = "SELECT COUNT(*) as aantal, $ResultaatZoekID, $ResultaatID FROM $TableResultaat GROUP BY $ResultaatZoekID, $ResultaatID HAVING aantal > 1";
$result	= mysqli_query($db, $sql);
$row = mysqli_fetch_array($result);
do {
	mysqli_query($db, "DELETE FROM $TableResultaat WHERE $ResultaatID like '". $row[$ResultaatID] ."' AND $ResultaatZoekID like '". $row[$ResultaatZoekID] ."' LIMIT ". ($row['aantal'] - 1));
	$error[] = "<a href='HouseDetails.php?id=". $row[$ResultaatID] ."' target='_blank'>". $row[$ResultaatID] . "</a> is ". $row['aantal'] ." keer gevonden in de resultaten-tabel<br>";
} while($row = mysqli_fetch_array($result));

$melding[] = "Dubbele huizen in de resultaten-database verwijderd<br>";


if(count($error) == 0) {
	$error[] = "Geen foutmeldingen";
}

if($autoDelete['kenmerk']) {
	foreach($delete_kenmerken as $id) {
		$sql = "DELETE FROM $TableKenmerken WHERE $KenmerkenID like $id";
		$melding[] = $sql;
		//mysqli_query($db, $sql);
	}
}

if($autoDelete['prijs']) {
	foreach($delete_prijzen as $id) {
		$sql = "DELETE FROM $TablePrijzen WHERE $PrijzenID like $id";
		$melding[] = $sql;
		//mysqli_query($db, $sql);
	}
}

if($autoDelete['result']) {	
	foreach($delete_resultaten as $id) {
		$sql = "DELETE FROM $TableResultaat WHERE $ResultaatID like $id";
		$melding[] = $sql;
		//mysqli_query($db, $sql);
	}
}

if($autoDelete['list']) {	
	foreach($delete_lijsten as $id) {
		$sql = "DELETE FROM $TableListResult WHERE $ListResultHuis like $id";
		$melding[] = $sql;
		//mysqli_query($db, $sql);
	}
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
