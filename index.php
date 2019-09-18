<?php
include_once(__DIR__.'/include/config.php');
include_once(__DIR__ .'/include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.MobileDetect.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$detect = new Mobile_Detect;
if ($detect->isMobile() ) {
	$mobile = true;
} else {
	$mobile = false;
}

# Initialiseer de variabelen
$blockOnderhoud = $blockOpschonen = $blockLinks = $blockAdmin = $blockOpdrachten = '';

# Vraag "achtergrond" data van de gebruiker op
$UserData = getMemberDetails($_SESSION['UserID']);

# LINKS
$links['TimeLine.php']							= 'Tijdslijn';
$links['PrijsDaling.php']						= 'Prijs-afname';
$links['gallery.php']								= 'Fotoalbum';
$links['dubbeleHuizen.php']					= 'Dubbele huizen';
$links['admin/edit_lijsten.php']		= 'Lijsten';
$links['admin/edit_opdrachten.php']	= 'Zoekopdrachten';	

if($_SESSION['level'] > 2) {
	$links['admin/log.php']						= 'Log-files';
}

$links['extern/poi.xml.php']				= 'POI-Edit XML-file';
$links['extern/showKML.php']				= 'Google Maps (wijk)';
$links['extern/showKML_prijs.php']	= 'Google Maps (prijs)';


# ADMIN
$admin['admin/combineSelections.php']		= 'Maak combinaties van lijsten & opdrachten';
$admin['admin/search4Coord.php']				= 'Selecteer huizen obv coordinaten';
$admin['admin/compareKenmerken.php']		= 'Exporteer kenmerken in CSV-formaat';
$admin['admin/downloadDailyExport.php']	= 'Download XLS-file';

if($_SESSION['level'] > 1) {
	$admin['admin/dailyExport.php']				= 'Genereer excel-bestand';	
	$admin['teKoopVerkocht.php']				= 'Overzicht van nieuwe/verkochte huizen per periode';
	$admin['admin/showVerdeling.php']			= 'Overzicht van welke opdracht wanneer wordt uitgevoerd';
}
$admin['admin/HouseDetails.php']			= 'Bekijk details van een huis';
$admin['admin/determineCorrectPrice.php']		= 'Bepaal gecorrigeerde prijs op specifieke datum';



# ONDERHOUD
if($_SESSION['level'] > 2) {
	$onderhoud['check.php']											= 'Check funda';
	$onderhoud['admin/checkOudeHuizen.php']			= 'Zie welke huizen al even van de radar zijn';
	$onderhoud['admin/details2Download.php']		= 'Overzicht van huizen waar de details van ontbreken';
	$onderhoud['upload_offline.php']						= 'Upload files met funda-HTML';	
	//$onderhoud['check_offline.php']							= 'Check de offline opgeslagen pagina\'s';	
	//$onderhoud['admin/loadOfflineHouses.php']		= 'Check de offline opgeslagen huis-pagina\'s';	
	$onderhoud['admin/edit_streets.php']				= 'Wijzig de straten';
	$onderhoud['admin/addPostcode.php']					= 'Zoek ontbrekende postcode\'s op';
	$onderhoud['admin/addWijk.php']						= 'Vul ontbrekende wijken in';
	$onderhoud['onderhoud/makeGeneralLists.php']	= 'Maak algemene lijsten aan';
	$onderhoud['admin/readKadasterPBK.php']				= 'Lees de prijs-index van het Kadaster in';
	$onderhoud['admin/makeCalendar.php']				= 'Maak iCal-bestand met open huizen';
	
	$opschonen['admin/checkTables.php']					= 'Check de verschillende databases';
	$opschonen['admin/cleanPrice.php']					= 'Prijzen opschonen';
	$opschonen['admin/cleanKenmerk.php']				= 'Kenmerken opschonen';
	$opschonen['admin/cleanOpenhuis.php']				= 'Open huizen opschonen';
	$opschonen['admin/combine_batch.php']				= 'Voeg hits automatisch samen';
	$opschonen['admin/combine_manual.php']			= 'Voeg hits handmatig samen';
	$opschonen['admin/cleanUp.php']							= 'Verwijder oude log-items';	
	
	foreach($onderhoud as $url => $titel) {
		$blockOnderhoud .= "<a href='$url' target='_blank'>$titel</a><br>\n";
	}

	foreach($opschonen as $url => $titel) {
		$blockOpschonen .= "<a href='$url' target='_blank'>$titel</a><br>\n";
	}
}

foreach($links as $url => $titel) {
	$blockLinks .= "<a href='$url' target='_blank'>$titel</a><br>\n";
}

foreach($admin as $url => $titel) {
	$blockAdmin .= "<a href='$url' target='_blank'>$titel</a><br>\n";
}

$blockAccount = "<div class='float_rechts'>Ingelogd als <b>". $UserData['naam'] ."</b></div><a href='". $cfgProgDir ."objects/logout.php'>uitloggen</a><br>\n";
$blockAccount .= "<a href='admin/edit_account.php' target='_blank'>wijzig gegevens</a><br>\n";
if($_SESSION['level'] > 1) {
	$blockAccount .= "<a href='admin/edit_account.php?new' target='_blank'>maak account voor een ander aan</a><br>\n";
}
if($_SESSION['level'] == 3) {
	$blockAccount .= "<a href='admin/edit_account.php?all' target='_blank'>toon alle accounts</a><br>\n";
}

$Opdrachten = getZoekOpdrachten($_SESSION['account'], '');
foreach($Opdrachten as $OpdrachtID) {
	$OpdrachtData = getOpdrachtData($OpdrachtID);
	$blockOpdrachten .= "funda.nl : <a href='". $OpdrachtData['url'] ."' target='_blank'>". $OpdrachtData['naam'] ."</a><br>\n";
}

if(count($Opdrachten) == 0) {
	$blockOpdrachten .= "<i>Maak je eerste <a href='admin/edit_opdrachten.php?id=0' target='_blank'>zoekopdracht</a> aan.</i>";
}

echo $HTMLHeader;
echo "<tr>\n";

if($mobile) {
	echo "<td valign='top' align='left'>\n";
} else {
	echo "<td width='50%' valign='top' align='center'>\n";
}

echo showBlock($blockLinks, $mobile);
echo "<p>\n";
echo showBlock('<b>Admin</b><br>'.$blockAdmin, $mobile);

if($blockOnderhoud != '') {
	echo "<p>\n";
	echo showBlock('<b>Onderhoud</b><br>'.$blockOnderhoud, $mobile);	
}

if(!$mobile)	echo "</td><td width='50%' valign='top' align='center'>\n";

if($blockOpschonen != '') {
	echo showBlock('<b>Opschonen</b><br>'.$blockOpschonen, $mobile);
	echo "<p>\n";
}

echo showBlock($blockAccount, $mobile);
echo "<p>\n";
echo showBlock($blockOpdrachten, $mobile);
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;