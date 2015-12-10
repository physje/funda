<?php
include_once(__DIR__. '/../include/config.php');

include_once('../include/HTML_TopBottom.php');
setlocale(LC_ALL, 'nl_NL');
$minUserLevel = 2;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();
$data = array();

if(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
				
	if(isset($_POST['save'])) {
		$begin_tijd = mktime(0, 0, 1, $_POST['bMaand'], $_POST['bDag'], $_POST['bJaar']);
		$eind_tijd = mktime(23, 59, 59, $_POST['eMaand'], $_POST['eDag'], $_POST['eJaar']);
		
		$sql  = "UPDATE $TableHuizen SET ";
		$sql .= "$HuizenAdres = '". urlencode($_POST['adres']) ."', ";
		$sql .= "$HuizenPC_c = '". $_POST['PC_cijfers'] ."', ";
		$sql .= "$HuizenPC_l =  '". $_POST['PC_letters'] ."', "; 
		$sql .= "$HuizenPlaats = '". $_POST['plaats'] ."', ";
		$sql .= "$HuizenLat = '". $_POST['latitude'] ."', ";
		$sql .= "$HuizenLon = '". $_POST['longitude'] ."', ";
		$sql .= "$HuizenVerkocht = '". $_POST['verkocht'] ."', ";
		$sql .= "$HuizenOffline = '". $_POST['offline'] ."', ";
		$sql .= "$HuizenStart = $begin_tijd, ";
		$sql .= "$HuizenEind = $eind_tijd WHERE $HuizenID = $id";
		
		if(!mysql_query($sql)) {
			$HTML[] = $sql;
		} else {
			$HTML[] = $_POST['adres'] ." is opgeslagen";
		}
	} else {		
		$data = getFundaData($id);
		
		$HTML[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
		$HTML[] = "<input type='hidden' name='id' value='$id'>";
		$HTML[] = "<table>";	
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Adres</td>";
		$HTML[] = "	<td><input type='text' name='adres' value='". $data['adres'] ."' size='35'></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td></td>";
		$HTML[] = "	<td><input type='text' name='PC_cijfers' value='". $data['PC_c'] ."' size='4'> <input type='text' name='PC_letters' value='". $data['PC_l'] ."' size='2'><div class='float_rechts'><a href='http://funda.nl/$id' target='_blank'>funda.nl</a></div></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Plaats</td>";
		$HTML[] = "	<td><input type='text' name='plaats' value='". $data['plaats'] ."'></td>";
		$HTML[] = "</tr>";	
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Coordinaten</td>";
		$HTML[] = "	<td><input type='text' name='latitude' value='". $data['lat'] ."' size='7'>,<input type='text' name='longitude' value='". $data['long'] ."' size=7'><div class='float_rechts'><a href='../extern/redirect.php?id=$id' target='_blank'>Google Maps</a></div></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Makelaar</td>";
		$HTML[] = "	<td>". $data['makelaar'] ."</td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Wijk</td>";
		$HTML[] = "	<td>". $data['wijk'] ."</td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Begin</td>";
		$HTML[] = "	<td><select name='bDag'>\n";
		for($d=1 ; $d<=31 ; $d++)	{	$HTML[] = "<option value='$d'". ($d == date("d", $data['start']) ? ' selected' : '') .">$d</option>\n";	}
		$HTML[] = "	</select><select name='bMaand'>\n";
		for($m=1 ; $m<=12 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("m", $data['start']) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
		$HTML[] = "	</select><select name='bJaar'>\n";
		for($j=1995 ; $j<=(date('Y')) ; $j++)	{	$HTML[] = "<option value='$j'". ($j == date("Y", $data['start']) ? ' selected' : '') .">$j</option>\n";	}
		$HTML[] = "	</select></td>\n";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";	
		$HTML[] = "	<td>Eind</td>";
		$HTML[] = "	<td><select name='eDag'>\n";
		for($d=1 ; $d<=31 ; $d++)	{	$HTML[] = "<option value='$d'". ($d == date("d", $data['eind']) ? ' selected' : '') .">$d</option>\n";	}
		$HTML[] = "	</select><select name='eMaand'>\n";
		for($m=1 ; $m<=12 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("m", $data['eind']) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
		$HTML[] = "	</select><select name='eJaar'>\n";
		for($j=1995 ; $j<=(date('Y')) ; $j++)	{	$HTML[] = "<option value='$j'". ($j == date("Y", $data['eind']) ? ' selected' : '') .">$j</option>\n";	}
		$HTML[] = "	</select></td>\n";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Offline</td>";
		$HTML[] = "	<td><input type='radio' name='offline' value='0'". ($data['offline'] == '0' ? ' checked' : '') .">Nee&nbsp;<input type='radio' name='offline' value='1'". ($data['offline'] == '1' ? ' checked' : '') .">Ja</td>";
		$HTML[] = "</tr>";		
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Verkocht</td>";
		$HTML[] = "	<td><input type='radio' name='verkocht' value='0'". ($data['verkocht'] == '0' ? ' checked' : '') .">Nee&nbsp;<input type='radio' name='verkocht' value='1'". ($data['verkocht'] == '1' ? ' checked' : '') .">Ja&nbsp;<input type='radio' name='verkocht' value='2'". ($data['verkocht'] == '2' ? ' checked' : '') .">Onder voorbehoud</td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td colspan='2'>&nbsp;</td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td colspan='2'><input type='submit' value='Opslaan' name='save'></td>";
		$HTML[] = "</tr>";		
		$HTML[] = "</table>";
		$HTML[] = "</form>";
	}

	if(isset($_POST['save_prijs'])) {
		$Kenmerk[] = "Prijzen opslaan";
		
		$sql = "DELETE FROM $TablePrijzen WHERE $PrijzenID = $id";	
		if(mysql_query($sql)) {
			foreach($_POST['pDag'] as $key => $value) {				
				if($_POST['pPrijs'][$key] != 'leeg') {
					$tijd = mktime(0, 0, 0, $_POST['pMaand'][$key], $_POST['pDag'][$key], $_POST['pJaar'][$key]);
					$prijs = $_POST['pPrijs'][$key];
					updatePrice($id, $prijs, $tijd);
				}
			}
		}
	} else {
		# Prijshistorie
		$Prijzen = getPriceHistory($id);
	
		$PrijsHistory[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
		$PrijsHistory[] = "<input type='hidden' name='id' value='$id'>";
		$PrijsHistory[] = "<table>";
		$PrijsHistory[] = "<tr>";
		$PrijsHistory[] = "	<td>Datum</td>";
		$PrijsHistory[] = "	<td>&nbsp;</td>";
		$PrijsHistory[] = "	<td>Prijs</td>";
		$PrijsHistory[] = "	<td>&nbsp;</td>";
		$PrijsHistory[] = "	<td>Correctie</td>";
		$PrijsHistory[] = "</tr>";
		
		foreach($Prijzen as $key => $value)	{
			if($key != 0) {
				$PrijsHistory[] = "<tr>";
				$PrijsHistory[] = "	<td><select name='pDag[]'>\n";
				for($d=1 ; $d<=31 ; $d++)	{	$PrijsHistory[] = "<option value='$d'". ($d == date("d", $key) ? ' selected' : '') .">$d</option>\n";	}
				$PrijsHistory[] = "	</select><select name='pMaand[]'>\n";
				for($m=1 ; $m<=12 ; $m++)	{	$PrijsHistory[] = "<option value='$m'". ($m == date("m", $key) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
				$PrijsHistory[] = "	</select><select name='pJaar[]'>\n";
				for($j=1995 ; $j<=(date('Y')) ; $j++)	{	$PrijsHistory[] = "<option value='$j'". ($j == date("Y", $key) ? ' selected' : '') .">$j</option>\n";	}
				$PrijsHistory[] = "	</select></td>\n";
				$PrijsHistory[] = "<td> -> </td>";
				$PrijsHistory[] = "<td><input type='text' size='5' name='pPrijs[]' value='$value'></td>";
				$PrijsHistory[] = "<td>&nbsp;</td>";
				
				$prijs	= $value;
				$bDag		= date('d', $key);
				$bMaand = date('m', $key);
				$bJaar	= date('Y', $key);				
				if($data['verkocht'] == '1' || $data['offline'] == '1') {
					$nieuwePrijs	= corrigeerPrice($key, $value, $data['eind']);					
					$eDag		= date('d', $data['eind']);
					$eMaand	= date('m', $data['eind']);
					$eJaar	= date('Y', $data['eind']);
				} else {
					$nieuwePrijs	= corrigeerPrice($key, $value);
					$eDag		= date('d');
					$eMaand	= date('m');
					$eJaar	= date('Y');
				}
				
				# Percentage berekenen en even een + teken voor het percentage indien de prijs gestegen is
				$percentage		= number_format ((100*($nieuwePrijs-$value)/$value), 1);				
				if($percentage > 0)	$percentage = '+'.$percentage;
				
				$PrijsHistory[] = "<td><a href='determineCorrectPrice.php?prijs=$prijs&bDag=$bDag&bMaand=$bMaand&bJaar=$bJaar&eDag=$eDag&eMaand=$eMaand&eJaar=$eJaar' target='_blank'>". ($data['verkocht'] == '1' || $data['offline'] == '1' ? '<i>' : '') . formatPrice($nieuwePrijs) . ($data['verkocht'] == '1' || $data['offline'] == '1' ? '</i>' : '') . "</a> ($percentage %)</td>";
				$PrijsHistory[] = "</tr>";
			}
		}
		
		$PrijsHistory[] = "<tr>";
		$PrijsHistory[] = "	<td colspan='2'>&nbsp;</td>";
		$PrijsHistory[] = "</tr>";	
		$PrijsHistory[] = "<tr>";
		$PrijsHistory[] = "	<td colspan='2'><input type='submit' value='Prijs Opslaan' name='save_prijs'></td>";
		$PrijsHistory[] = "</tr>";		
		$PrijsHistory[] = "</table>";
		$PrijsHistory[] = "</form>";
	}
	
	# Zoekresultaten
	$sql		= "SELECT $ResultaatZoekID FROM $TableResultaat WHERE $ResultaatID like $id";
	$result	= mysql_query($sql);
	if($row	=	mysql_fetch_array($result)) {
		$Resultaten[] = "Gevonden met :\n";
		$Resultaten[] = "<ul>\n";
	
		do {
			$opdrachtData = getOpdrachtData($row[$ResultaatZoekID]);
			$Resultaten[] = '<li>'. $opdrachtData['naam'] ."</li>\n";
		} while($row =	mysql_fetch_array($result));
		$Resultaten[] = "</ul>\n";
	}
	
	# Kenmerken
	$KenmerkData = getFundaKenmerken($id);
	
	$Kenmerken[] = "<table>";
	
	foreach($KenmerkData as $key => $value)	{
		if($key != 'foto') {
			$Kenmerken[] = "<tr>";
			$Kenmerken[] = "	<td valign='top'>". ucfirst($key) ."</td>";
			$Kenmerken[] = "	<td valign='top'>$value</td>";	
			$Kenmerken[] = "</tr>";
		}
	}
	
	$Kenmerken[] = "</table>";
	
	# Thumbnail
	if($data['offline'] == 1 || $data['verkocht'] == 1) {
		$Thumb[] = "<img src='". changeThumbLocation($data['thumb']) ."'>";
	} else {
		$Thumb[] = "<img src='". $data['thumb'] ."'>";
	}
	
	# Foto's
	$fotos = explode('|', $KenmerkData['foto']);
	
	foreach($fotos as $key => $value)	{
		if($data['offline'] == 1) {
			$url = str_replace('klein', 'grotere', $value);
		} else {
			$url = 'http://www.funda.nl/'. $data['url'] .'fotos/#groot&foto-'.($key+1);
		}
			
		$Foto[] = "<a href='$url' target='_blank'><img src='". $value ."'></a>";
	}
	
	$soldBefore			= soldBefore($id);
	$alreadyOnline	= alreadyOnline($id);
	$onlineBefore		= onlineBefore($id);
	
	if(is_numeric($soldBefore)) {
		$extraData = getFundaData($soldBefore);
		$extraString = "<a href='http://funda.nl/$soldBefore'>Al eens verkocht op ". date("d-m-Y", $extraData['eind']) ."</a>";
	} elseif(is_numeric($alreadyOnline)) {
		$extraData = getFundaData($alreadyOnline);
		$extraString = "<a href='http://funda.nl/$alreadyOnline'>Ook online bij ". $extraData['makelaar'] ."</a>";
	} elseif(is_numeric($onlineBefore)) {
		$extraData = getFundaData($onlineBefore);
		$extraString = implode(" & ", getTimeBetween($extraData['eind'], $data['start'])) ." offline geweest";
	}
	
	# Open huis
	$open			= getNextOpenhuis($id);
	if($open[0] > 0) {
		//$OpenHuis = strftime("%a %e %b %k:%M", $open[0]) ." - ". strftime("%k:%M", $open[1]);
		$OpenHuis = date("d M Y H:i", $open[0]) ." - ". date("H:i", $open[1]);
	}
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $HTML));
echo "<p>";
echo showBlock(implode("\n", $Kenmerken));
echo "</td>";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $Thumb));
echo "<p>";

if(count($Resultaten) > 0) {
	echo showBlock(implode("\n", $Resultaten));
	echo "<p>";
}

if($OpenHuis != '') {
	echo showBlock($OpenHuis);
	echo "<p>";
}

if($extraString != '') {
	echo showBlock($extraString);
	echo "<p>";
}

echo showBlock(implode("\n", $PrijsHistory));
echo "<p>";
echo showBlock(implode("\n", $Foto));
echo "</td>";
echo "</tr>\n";
echo $HTMLFooter;
