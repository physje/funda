<?php
include_once(__DIR__.'/include/config.php');
include_once(__DIR__ .'/include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

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
	$knownHuizen = null;

	# Als $_POST['addHouses'] bekend is, is bekend aan welke lijst de huizen moeten worden toegevoegd
	if($_POST['addHouses'] == '1') {
		$showListAdd = true;
		$knownHuizen = getLijstHuizen($_POST['chosenList']);
	}
	
	$i = 1;	
	echo "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
	if(isset($showListAdd) AND $showListAdd) echo "<input type='hidden' name='lijst' value='". $_POST['chosenList'] ."'>\n";
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
		$kenmerken	= getFundaKenmerken($huisID);
		$adres			= convertToReadable($data['adres']);
		$adresShort	= makeTextBlock($adres, 24, true);
		$image			= str_replace('_klein.jpg', '_middel.jpg', changeThumbLocation($data['thumb']));
		$relPrize		= getFullPriceHistory($huisID);
				
		$balk = false;
		$TextClass = 'online';
		$imageClass = 'imageAvailable';
				
		# Even de juiste teksten & kleuren instellen
		# Huizen die van de markt af zijn worden in zwart-wit getoond met onderaan een balk met juiste tekst.
		# Open huizen hebben ook een balk met tekst, maar zijn in kleur
		if($data['verkocht'] == '1' || $data['verkocht'] == '2' || $data['offline'] == '1' || $data['openhuis'] == '1') {
			$imageClass = 'imageUnavailable';
			$balk = true;
			
			# De tekst voor de banner over de foto
			if($data['verkocht'] == '1') {
				$description = "verkocht";
				if($data['offline'] == '1') {
					$TextClass = 'offlineVerkocht';
				} else {
					$TextClass = 'onlineVerkocht';	
				}
			} elseif($data['verkocht'] == '2') {
				$description = "onder voorbehoud";
			} elseif($data['openhuis'] == '1') {
				$imageClass = 'imageAvailable';
				$open = getNextOpenhuis($huisID);
				$description = strftime("%e %b %k:%M", $open[0]) ." - ". strftime("%k:%M", $open[1]);
			} else {
				$description = "offline";
				$TextClass = 'offline';
			}			
		}
		
		# Als er geen thumb is dat plaatje van funda laten zien
		if(strpos($image, 'thumbs/thumb-geen-foto')) {
			$image = 'http://www.fundalandelijk.nl/img/thumbs/thumb-geen-foto-middel.gif';
		}
		
		$hoverText = '';
		if(isset($kenmerken['Wonen (= woonoppervlakte)']))	$hoverText .= "Woonoppervlakte : ". $kenmerken['Wonen (= woonoppervlakte)'] ."\n";
		if(isset($kenmerken['Wonen']))											$hoverText .= "Woonoppervlakte : ". $kenmerken['Wonen'] ."\n";
		if(isset($kenmerken['Perceeloppervlakte']))					$hoverText .= "Perceeloppervlakte : ". $kenmerken['Perceeloppervlakte'] ."\n";
		if(isset($kenmerken['Perceel']))										$hoverText .= "Perceeloppervlakte : ". $kenmerken['Perceel'] ."\n";
		if(isset($kenmerken['Inhoud']))											$hoverText .= "Inhoud : ". $kenmerken['Inhoud'] ."\n";
		if(isset($kenmerken['Bouwjaar']))										$hoverText .= "Bouwjaar : ". $kenmerken['Bouwjaar'];
		
		# De HTML van een huis
		# Let op dat de <div class='float_rechts'> eerst staan, en pas daarna de linkertekst.
		# Deze hack zorgt dat het in IE ook werkt
		$Foto  = "	<a href='". $ScriptURL ."admin/HouseDetails.php?id=$huisID' target='_blank' title='$hoverText'><div class='wrapper'><img src='$image' width='242' class='$imageClass'></a>";
		if($balk)	$Foto .= "<div class='description'><p class='description_content'>". strtoupper($description) ."</p></div>";
		$Foto .= "</div><br>\n";		
		if(isset($showListAdd) AND $showListAdd)	$Foto .= "	<input type='checkbox' name='huis[]' value='$huisID'". (in_array($huisID, $knownHuizen) ? ' checked' : '') .">";
		$Foto .= "	<div class='float_rechts'>". getDoorloptijd($huisID) ."</div><a href='http://funda.nl/$huisID' target='_blank' class='$TextClass' title='Ga naar $adres op funda.nl'>$adresShort</a><br>\n";
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
	if(isset($showListAdd) AND $showListAdd) {
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
	$HTML[] = "<form method='post' action='". $_SERVER['PHP_SELF'] ."'>";
	$HTML[] = "<input type='hidden' name='addHouses' value='". (isset($_REQUEST['addHouses']) ? '1' : '0') ."'>";
	$HTML[] = "<input type='hidden' name='chosenList' value='". (isset($_REQUEST['chosenList']) ? $_REQUEST['chosenList'] : '') ."'>";
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