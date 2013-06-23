<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

connect_db();

echo $HTMLHeader;

# Als $_POST['add'] bekend is, heeft men huizen aangevinkt die aan de lijst moeten worden toegevoegd
if(isset($_POST['add'])) {
		foreach($_POST['huis'] as $huis) {
		echo addHouse2List($huis, $_POST['lijst']);
	}
	
	echo "Huizen verwerkt.";	
} elseif(isset($_REQUEST['selectie'])) {
	# Als er uit het pulldown menu een keuze is gemaakt moet bepaald worden of het een zoekopdracht
	# of lijst is, en zo ja welk id er dan bijhoort.
	$groep	= substr($_REQUEST['selectie'], 0, 1);
	$id			= substr($_REQUEST['selectie'], 1);
	
	if($groep == 'Z') {		
		$opdrachtData	= getOpdrachtData($id);
		$Name					= $opdrachtData['naam'];
		$dataset				= getHuizen($id);
	} else {
		$LijstData		= getLijstData($id);
		$Name					= $LijstData['naam'];
		$dataset			= getLijstHuizen($id);
	}
	
	# Als $_POST['addHouses'] bekend is, is bekend aan welke lijst de huizen moeten worden toegevoegd
	if($_POST['addHouses'] == '1') {
		$showListAdd = true;
		$knownHuizen = getLijstHuizen($_POST['chosenList']);
	}
	
	$i = 1;	
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	if($showListAdd) echo "<input type='hidden' name='lijst' value='". $_POST['chosenList'] ."'>\n";
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center' colspan='$aantalCols'><h1>Gallery '$Name'</h1></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='$aantalCols'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";

	# Doorloop alle huizen
	foreach($dataset as $huisID) {
		$data 			= getFundaData($huisID);
		$adres			= makeTextBlock(convertToReadable($data['adres']), 24, true);
		$image			= str_replace('_klein.jpg', '_middel.jpg', changeThumbLocation($data['thumb']));
		$relPrize		= getFullPriceHistory($huisID);
		$url				= $data['url'];
	
		if($data['offline'] == '1') {
			$TextClass = 'offline';			
		} elseif($data['verkocht'] == '1') {
			$TextClass = 'onlineVerkocht';
		} else {
			$TextClass = 'online';
		}
		
		# funda.nl verplaatst de thumbs als huizen offline of verkocht zijn
		if($data['verkocht'] == '1' || $data['verkocht'] == '2' || $data['offline'] == '1') {
			//$image	= changeThumbLocation($image);			
			
			$imageClass = 'imageUnavailable';
			$housAvailable = false;
			
			# De tekst voor de banner over de foto
			if($data['verkocht'] == '1') {
				$description = "verkocht";
				$url		= changeURLLocation($url);
			} elseif($data['verkocht'] == '2') {
				$description = "Onder voorbehoud";
			} else {
				$description = "offline";
			}
		} else {
			$imageClass = 'imageAvailable';
			$housAvailable = true;
		}
		
		# Als er geen thumb is dat plaatje van funda laten zien
		if(strpos($image, 'thumbs/thumb-geen-foto')) {
			$image = 'http://www.fundalandelijk.nl/img/thumbs/thumb-geen-foto-middel.gif';
		}
		
		# De HTML van een huis
		# Let op dat de <div class='float_rechts'> eerst staan, en pas daarna de linkertekst.
		# Deze hack zorgt dat het in IE ook werkt
		$Foto  = "	<a href='". $ScriptURL ."extern/redirect.php?id=$huisID' target='_blank' title='Toon ". $data['adres'] ." op Google Maps'><div class='wrapper'><img src='$image' class='$imageClass'></a>";
		if(!$housAvailable)	$Foto .= "<div class='description'><p class='description_content'>". strtoupper($description) ."</p></div>";
		$Foto .= "</div><br>\n";		
		if($showListAdd)	$Foto .= "	<input type='checkbox' name='huis[]' value='$huisID'". (in_array($huisID, $knownHuizen) ? ' checked' : '') .">";
		$Foto .= "	<div class='float_rechts'>". getDoorloptijd($huisID) ."</div><a href='http://www.funda.nl". $url ."' target='_blank' class='$TextClass' title='Ga naar ". $data['adres'] ." op funda.nl'>$adres</a><br>\n";
		$Foto .= "	<div class='float_rechts'><b>". formatPercentage($relPrize[5]) ."</b></div><b>". formatPrice(getHuidigePrijs($huisID)) ."</b>\n";
		
		echo "	<td align='center'>";
		echo showBlock($Foto);
		echo "	</td>\n";
		
		# Aan het einde van de rij de rij sluiten en een nieuwe openen
		if(($i % $aantalCols) == 0) {
			echo "</tr>\n";
			echo "<tr>\n";
			$i=1;
		} else {
			$i++;
		}
	}
	
	echo "</tr>\n";
	
	# Laat een submit-button zien als dat nodig is.
	if($showListAdd) {
		echo "<tr>\n";
		echo "	<td colspan='$aantalCols'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "	<td colspan='$aantalCols' align='center'><input type='submit' name='add' value='Voeg toe'></td>\n";
		echo "</tr>\n";
	}
	
	echo "</table>\n";
	echo "</form>\n";	
} else {
	$HTML[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$HTML[] = "<input type='hidden' name='addHouses' value='". (isset($_REQUEST['addHouses']) ? '1' : '0') ."'>";
	$HTML[] = "<input type='hidden' name='chosenList' value='". $_REQUEST['chosenList'] ."'>";
	$HTML[] = "<table>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Selectie</td>";	
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>". makeSelectionSelection(isset($_REQUEST['addHouses']), false) ."</td>";
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td colspan='3' align='center'><input type='submit' name='submit' value='Weergeven'></td>";
	$HTML[] = "</tr>";
	$HTML[] = "</table>";
	$HTML[] = "</form>";
	
	echo "<tr>\n";
	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $HTML));
	echo "</td>\n";
	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
	echo "</tr>\n";
}

echo $HTMLFooter;
?>
	