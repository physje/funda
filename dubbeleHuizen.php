<?php
include_once(__DIR__.'/include/config.php');
include_once(__DIR__ .'/include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

echo $HTMLHeader;
$sql_overview = "SELECT count(*) as totaal, $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging FROM $TableHuizen WHERE $HuizenStraat NOT like '' AND $HuizenStraat NOT like 'Bouwnummer%' GROUP BY $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging HAVING totaal > 1";
$result_overview	= mysqli_query($db, $sql_overview);
if($row_overview = mysqli_fetch_array($result_overview)) {
	$i = 0;
	do {
		$ids = array();
		
		$sql_houses = "SELECT $HuizenID FROM $TableHuizen WHERE $HuizenStraat like '". $row_overview[$HuizenStraat] ."' AND $HuizenNummer = ". $row_overview[$HuizenNummer] ." AND $HuizenLetter like '". $row_overview[$HuizenLetter] ."' AND $HuizenToevoeging = ". $row_overview[$HuizenToevoeging] ." ORDER BY $HuizenStart ASC";
		$result_houses	= mysqli_query($db, $sql_houses);
		$row_houses = mysqli_fetch_array($result_houses);
				
		do {
			$ids[] = $row_houses[$HuizenID];
		} while($row_houses = mysqli_fetch_array($result_houses));
		
		$aantal[$i] = count($ids);
		$houses[$i] = $ids;		
		$i++;
	} while($row_overview = mysqli_fetch_array($result_overview));
}

$aantalCols = max($aantal);

echo $HTMLHeader;
echo "<table width='100%' border=0>\n";
echo "<tr>\n";
echo "	<td align='center' colspan='$aantalCols'><h1>Dubbele huizen</h1></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td colspan='$aantalCols'>&nbsp;</td>\n";
echo "</tr>\n";

foreach($houses as $dataset) {
	echo "<tr>\n";

	# Doorloop alle huizen
	foreach($dataset as $huisID) {
		$data 			= getFundaData($huisID);
		$kenmerken	= getFundaKenmerken($huisID);
		$adres			= convertToReadable($data['adres']);
		$adresShort	= makeTextBlock($adres, 24, true);
		$image			= str_replace('_klein.jpg', '_middel.jpg', changeThumbLocation($data['thumb']));
		//$relPrize		= getFullPriceHistory($huisID);
				
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
		$Foto .= "	<div class='float_rechts'>". getDoorloptijd($huisID) ."</div><a href='http://funda.nl/$huisID' target='_blank' class='$TextClass' title='Ga naar $adres op funda.nl'>$adresShort</a><br>\n";
		$Foto .= "	<div class='float_rechts'><b>". strftime('%b %y', $data['start']) ."</b></div><b>". formatPrice(getHuidigePrijs($huisID)) ."</b>\n";
		
		echo "	<td align='center'>". showBlock($Foto) ."</td>\n";
		
		# Aan het einde van de rij de rij sluiten en een nieuwe openen
	}
	echo "</tr>\n";
}
	
echo "</tr>\n";
echo "</table>\n";
echo "</form>\n";	
echo $HTMLFooter;