<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
setlocale(LC_ALL, 'nl_NL');
connect_db();

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
		
		$HTML[] = "<form method='post'>";
		$HTML[] = "<input type='hidden' name='id' value='$id'>";
		$HTML[] = "<table>";	
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Adres</td>";
		$HTML[] = "	<td><input type='text' name='adres' value='". $data['adres'] ."' size='35'></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td></td>";
		$HTML[] = "	<td><input type='text' name='PC_cijfers' value='". $data['PC_c'] ."' size='4'> <input type='text' name='PC_letters' value='". $data['PC_l'] ."' size='2'><div class='float_rechts'><a href='http://www.funda.nl". $data['url'] ."' target='_blank'>funda.nl</a></div></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Plaats</td>";
		$HTML[] = "	<td><input type='text' name='plaats' value='". $data['plaats'] ."'></td>";
		$HTML[] = "</tr>";	
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Coordinaten</td>";
		$HTML[] = "	<td><input type='text' name='latitude' value='". $data['lat'] ."' size='7'>,<input type='text' name='longitude' value='". $data['long'] ."' size=7'><div class='float_rechts'><a href='http://maps.google.nl/maps?q=". $data['lat'] .",". $data['long'] ."' target='_blank'>Google Maps</a></div></td>";
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
		for($j=(date('Y') - 3) ; $j<=(date('Y')) ; $j++)	{	$HTML[] = "<option value='$j'". ($j == date("Y", $data['start']) ? ' selected' : '') .">$j</option>\n";	}
		$HTML[] = "	</select></td>\n";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";	
		$HTML[] = "	<td>Eind</td>";
		$HTML[] = "	<td><select name='eDag'>\n";
		for($d=1 ; $d<=31 ; $d++)	{	$HTML[] = "<option value='$d'". ($d == date("d", $data['eind']) ? ' selected' : '') .">$d</option>\n";	}
		$HTML[] = "	</select><select name='eMaand'>\n";
		for($m=1 ; $m<=12 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("m", $data['eind']) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
		$HTML[] = "	</select><select name='eJaar'>\n";
		for($j=(date('Y') - 3) ; $j<=(date('Y')) ; $j++)	{	$HTML[] = "<option value='$j'". ($j == date("Y", $data['eind']) ? ' selected' : '') .">$j</option>\n";	}
		$HTML[] = "	</select></td>\n";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Offline</td>";
		$HTML[] = "	<td><input type='radio' name='offline' value='0'". ($data['offline'] == '0' ? ' checked' : '') .">Nee&nbsp;<input type='radio' name='offline' value='1'". ($data['offline'] == '1' ? ' checked' : '') .">Ja</td>";
		$HTML[] = "</tr>";		
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Verkocht</td>";
		$HTML[] = "	<td><input type='radio' name='verkocht' value='0'". ($data['verkocht'] == '0' ? ' checked' : '') .">Nee&nbsp;<input type='radio' name='verkocht' value='1'". ($data['verkocht'] == '1' ? ' checked' : '') .">Ja</td>";
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
				$tijd = mktime(0, 0, 0, $_POST['pMaand'][$key], $_POST['pDag'][$key], $_POST['pJaar'][$key]);
				$prijs = $_POST['pPrijs'][$key];			
				updatePrice($id, $prijs, $tijd);
			}
		}
	} else {
		$Prijzen = getPriceHistory($id);
	
		$Kenmerk[] = "<form method='post'>";
		$Kenmerk[] = "<input type='hidden' name='id' value='$id'>";
		$Kenmerk[] = "<table>";
		
		foreach($Prijzen as $key => $value)	{
			if($key != 0) {
				$Kenmerk[] = "<tr>";
				$Kenmerk[] = "	<td><select name='pDag[]'>\n";
				for($d=1 ; $d<=31 ; $d++)	{	$Kenmerk[] = "<option value='$d'". ($d == date("d", $key) ? ' selected' : '') .">$d</option>\n";	}
				$Kenmerk[] = "	</select><select name='pMaand[]'>\n";
				for($m=1 ; $m<=12 ; $m++)	{	$Kenmerk[] = "<option value='$m'". ($m == date("m", $key) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
				$Kenmerk[] = "	</select><select name='pJaar[]'>\n";
				for($j=(date('Y') - 3) ; $j<=(date('Y')) ; $j++)	{	$Kenmerk[] = "<option value='$j'". ($j == date("Y", $key) ? ' selected' : '') .">$j</option>\n";	}
				$Kenmerk[] = "	</select></td>\n";
				$Kenmerk[] = "<td> -> </td>";
				$Kenmerk[] = "<td><input type='text' size='5' name='pPrijs[]' value='$value'></td>";
				$Kenmerk[] = "</tr>";
			}
		}
	
		$Kenmerk[] = "<tr>";
		$Kenmerk[] = "	<td colspan='2'>&nbsp;</td>";
		$Kenmerk[] = "</tr>";	
		$Kenmerk[] = "<tr>";
		$Kenmerk[] = "	<td colspan='2'><input type='submit' value='Prijs Opslaan' name='save_prijs'></td>";
		$Kenmerk[] = "</tr>";		
		$Kenmerk[] = "</table>";
		$Kenmerk[] = "</form>";
	}
	
	if($data['offline'] == 1 || $data['verkocht'] == 1) {
		$Foto[] = "<img src='". changeThumbLocation($data['thumb']) ."'>";
	} else {
		$Foto[] = "<img src='". $data['thumb'] ."'>";
	}
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $HTML));
echo "</td>";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $Foto));
echo "<p>";
echo showBlock(implode("\n", $Kenmerk));
echo "</td>";
echo "</tr>\n";
echo $HTMLFooter;

?>