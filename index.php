<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('../general_include/class.MobileDetect.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

$detect = new Mobile_Detect;

$UserData = getMemberDetails($_SESSION['UserID']);

# LINKS
$links['TimeLine.php']							= 'Tijdslijn';
$links['PrijsDaling.php']						= 'Prijs-afname';
$links['gallery.php']								= 'Fotoalbum';
$links['admin/edit_lijsten.php']		= 'Lijsten';
$links['admin/edit_opdrachten.php']	= 'Zoekopdrachten';	

if($_SESSION['level'] > 2) {
	$links['admin/log.php']						= 'Log-files';
}

$links['extern/poi.xml.php']				= 'POI-Edit XML-file';
$links['extern/showKML.php']				= 'Google Maps (wijk)';
$links['extern/showKML_prijs.php']	= 'Google Maps (prijs)';



# ADMIN
$admin['admin/combineSelections.php']	= 'Maak combinaties van lijsten & opdrachten';
$admin['admin/search4Coord.php']			= 'Selecteer huizen obv coordinaten';
$admin['admin/compareKenmerken.php']	= 'Exporteer kenmerken in CSV-formaat';

if($_SESSION['level'] > 1) {
	$admin['admin/HouseDetails.php']			= 'Bekijk details van een huis';
}



# ONDERHOUD
if($_SESSION['level'] > 2) {
	$onderhoud['check.php']											= 'Check funda';	
	$onderhoud['admin/getVerkochteHuizen.php']	= 'Werk verkochte huizen bij';
	$onderhoud['admin/checkOudeHuizen.php']			= 'Zoek naar gegevens van vermiste huizen';
	$onderhoud['admin/cleanPrice.php']					= 'Prijzen opschonen';
	$onderhoud['admin/cleanKenmerk.php']				= 'Kenmerken opschonen';
	$onderhoud['admin/checkTables.php']					= 'Check de verschillende databases';
	$onderhoud['admin/search4Offline.php']			= 'Zet pagina\'s offline';
	$onderhoud['admin/combine_batch.php']				= 'Voeg hits automatisch samen';
	$onderhoud['admin/combine_manual.php']			= 'Voeg hits handmatig samen';
	$onderhoud['admin/cleanUp.php']							= 'Verwijder oude log-items';
	$onderhoud['extern/export.xls']							= 'Download XLS-file';
	
	foreach($onderhoud as $url => $titel) {
		$blockOnderhoud .= "<a href='$url' target='_blank'>$titel</a><br>\n";
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

$Opdrachten = getZoekOpdrachten($_SESSION['account'], 1);
foreach($Opdrachten as $OpdrachtID) {
	$OpdrachtData = getOpdrachtData($OpdrachtID);
	$blockOpdrachten .= "funda.nl : <a href='". $OpdrachtData['url'] ."' target='_blank'>". $OpdrachtData['naam'] ."</a><br>\n";
}

if(count($Opdrachten) == 0) {
	$blockOpdrachten .= "<i>Maak je eerste <a href='admin/edit_opdrachten.php?id=0' target='_blank'>zoekopdracht</a> aan.</i>";
}

if ($detect->isMobile() ) {
	echo $blockAccount;
	echo "<p>\n";
	echo $blockLinks;
	echo "<p>\n";
	echo $blockAdmin;
	if($blockOnderhoud != '') {
		echo "<p>\n";
		echo $blockOnderhoud;
	}	
	echo $blockOpdrachten;	
} else {
	echo $HTMLHeader;
	echo "<tr>\n";
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock($blockLinks);
	echo "<p>\n";
	echo showBlock($blockAdmin);
	if($blockOnderhoud != '') {
		echo "<p>\n";
		echo showBlock($blockOnderhoud);	
	}
	echo "</td><td width='50%' valign='top' align='center'>\n";
	echo showBlock($blockAccount);
	echo "<p>\n";
	echo showBlock($blockOpdrachten);
	echo "</td>\n";
	echo "</tr>\n";
	echo $HTMLFooter;
}
?>