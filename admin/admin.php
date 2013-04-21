<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

//$links['../check_prices.php']	= 'Check funda (zonder prijsdrempels)';
//$links['getCoord.php']				= 'Coordinaten ophalen';
//$links['combine_auto.php']		= 'Voeg hits samen';
//$links['../extern/showKML_mail.php']	= 'Google Maps (mail)';

$links['../TimeLine.php']			= 'Tijdslijn';
$links['../PrijsDaling.php']	= 'Prijs-afname';
$links['../gallery.php']			= 'Fotoalbum';
$links['edit_opdrachten.php']	= 'Zoekopdrachten';
$links['edit_lijsten.php']		= 'Lijsten';
$links['log.php']							= 'Log-files';
$links['../extern/poi.xml.php']				= 'POI-Edit XML-file';
$links['../extern/showKML.php']				= 'Google Maps (wijk)';
$links['../extern/showKML_prijs.php']	= 'Google Maps (prijs)';

$admin['../check.php']						= 'Check funda';
$admin['combine_batch.php']				= 'Voeg hits automatisch samen';
$admin['combine_manual.php']			= 'Voeg hits handmatig samen';
$admin['getVerkochteHuizen.php']	= 'Werk verkochte huizen bij';
$admin['cleanPrice.php']					= 'Prijzen opschonen';
$admin['cleanKenmerk.php']				= 'Kenmerken opschonen';
$admin['searchOffline.php']				= 'Zet pagina\'s offline';
$admin['cleanUp.php']							= 'Verwijder oude log-items';

foreach($links as $url => $titel) {
	$deel_1 .= "<a href='$url' target='_blank'>$titel</a><br>\n";
}

foreach($admin as $url => $titel) {
	$deel_2 .= "<a href='$url' target='_blank'>$titel</a><br>\n";
}

$Opdrachten = getZoekOpdrachten(1);
foreach($Opdrachten as $OpdrachtID) {
	$OpdrachtData = getOpdrachtData($OpdrachtID);

	$deel_3 .= "funda.nl : <a href='". $OpdrachtData['url'] ."' target='new'>". $OpdrachtData['naam'] ."</a><br>\n";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "<p>\n";
echo showBlock($deel_2);
echo "</td><td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_3);
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
?>