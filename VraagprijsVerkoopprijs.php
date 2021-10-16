<?php
include_once(__DIR__.'/include/config.php');
include_once(__DIR__ .'/include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.MobileDetect.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

echo $HTMLHeader;

if(isset($_REQUEST['selectie'])) {
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

	$knownHuizen = array();
		
	$max_percentage = 33;
		
	echo "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center' colspan='4'><h1>Verkoopprijs versus Vraagprijs '$Name'</h1></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='4'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td width='25%'>&nbsp;</td>\n";
	echo "	<td width='34%' align='left'>$max_percentage %</td>\n";
	echo "	<td width='34%' align='right'>$max_percentage %</td>\n";
	echo "	<td width='7%'>&nbsp;</td>\n";
	echo "</tr>\n";
	
	# Doorloop alle huizen
	foreach($dataset as $huisID) {
		$data 				= getFundaData($huisID);
		$adres				= convertToReadable($data['adres']);
		$prijzen			= getPriceHistory($huisID);
		$WOZ					= getWOZHistory($huisID);
							
		$vraagprijs		= end($prijzen);
		if(date('n', $data['eind']) < 7) {
			$verkoopJaar	= date('Y', $data['eind']);
		} else {
			$verkoopJaar	= (date('Y', $data['eind'])+1);
		}
		
		if(isset($WOZ[$verkoopJaar])) {
			$verkoopprijs = $WOZ[$verkoopJaar];
			$afname = ((($vraagprijs-$verkoopprijs)/$vraagprijs)*100);
			$breedte = round(100*$afname/$max_percentage);
		} else {
			$breedte = 0;
		}
						
		if($data['offline'] == '1') {
			if($data['verkocht'] != '1') {
				$TextClass = 'offline';
			} else {
				$TextClass = 'offlineVerkocht';
			}			
		} elseif($data['verkocht'] == '1') {
			$TextClass = 'onlineVerkocht';
		} else {
			$TextClass = 'online';
		}

		echo "<tr>\n";
		echo "	<td width='25%'>";		
		echo "<a href='admin/HouseDetails.php?selectie=". $_REQUEST['selectie'] ."&id=$huisID' target='_blank'><img src='images/details.gif' title='Toon opties voor $adres'></a>";
		echo "<a id='$huisID'> <a href='http://funda.nl/". $huisID ."' target='_blank' class='$TextClass' title='Bezoek $adres op funda.nl'>$adres</a></td>\n";
				
		if($breedte > 0) {			
			echo "	<td>\n";
			echo "	<table width='100%' border=0><tr>\n";			
			echo "		<td width='". (100-$breedte) ."%'>&nbsp;</td>\n";
			echo "		<td width='". $breedte ."%' bgcolor='#FF6D6D' title='Te koop voor ". formatPrice($vraagprijs) .", verkocht voor ". formatPrice($verkoopprijs) ."; ". round($afname, 1) ."% onder de vraagprijs'>&nbsp;</td>\n";
			echo "	</tr></table>\n";
			echo "	</td>\n";	
			echo "	<td>&nbsp;</td>\n";
		} elseif($breedte < 0) {
			echo "	<td>&nbsp;</td>\n";
			echo "	<td>\n";
			echo "	<table width='100%' border=0><tr>\n";
			echo "		<td width='". abs($breedte) ."%' bgcolor='#FF6D6D' title='Te koop voor ". formatPrice($vraagprijs) .", verkocht voor ". formatPrice($verkoopprijs) ."; ". round(abs($afname), 1) ."% boven de vraagprijs'>&nbsp;</td>\n";
			echo "		<td width='". (100-abs($breedte)) ."%'>&nbsp;</td>\n";
			echo "	</tr></table>\n";
			echo "	</td>\n";						
		} else {
			echo "	<td>&nbsp;</td>\n";
			echo "	<td>&nbsp;</td>\n";			
		}
		
		echo "	<td width='7%'>&nbsp;</td>\n";		
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
	echo showBlock(implode("\n", $HTML), $mobile);
	echo "</td>\n";
	echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
	echo "</tr>\n";	
}

echo $HTMLFooter;