<?
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
		
		$sql = "UPDATE $TableHuizen SET $HuizenAdres = '". urlencode($_POST['adres']) ."', $HuizenPC_c = '". $_POST['PC_cijfers'] ."', $HuizenPC_l =  '". $_POST['PC_letters'] ."', $HuizenPlaats = '". $_POST['plaats'] ."', $HuizenNdeg = '". $_POST['N_deg'] ."', $HuizenNdec = '". $_POST['N_dec'] ."', $HuizenOdeg = '". $_POST['O_deg'] ."', $HuizenOdec = '". $_POST['O_dec'] ."', $HuizenStart = $begin_tijd, $HuizenEind = $eind_tijd WHERE $HuizenID = $id";
		if(!mysql_query($sql)) {
			$HTML[] = $sql;
		} else {
			$HTML[] = $_POST['adres'] ." is opgeslagen";
		}
	} else {		
		$sql = "SELECT * FROM $TableHuizen WHERE $HuizenID = $id";
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		
		$HTML[] = "<form method='post'>";
		$HTML[] = "<input type='hidden' name='id' value='$id'>";
		$HTML[] = "<table>";	
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Adres</td>";
		$HTML[] = "	<td><input type='text' name='adres' value='". urldecode($row[$HuizenAdres]) ."'> <a href='http://www.funda.nl/". urldecode($row[$HuizenURL]) ."'>funda.nl</a></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td></td>";
		$HTML[] = "	<td><input type='text' name='PC_cijfers' value='". $row[$HuizenPC_c]."' size='4'> <input type='text' name='PC_letters' value='". $row[$HuizenPC_l]."' size='2'></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Plaats</td>";
		$HTML[] = "	<td><input type='text' name='plaats' value='". $row[$HuizenPlaats]."'></td>";
		$HTML[] = "</tr>";	
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Noord</td>";
		$HTML[] = "	<td><input type='text' name='N_deg' value='". $row[$HuizenNdeg]."' size='2'> <input type='text' name='N_dec' value='". $row[$HuizenNdec]."' size='13'></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Oost</td>";
		$HTML[] = "	<td><input type='text' name='O_deg' value='". $row[$HuizenOdeg]."' size='2'> <input type='text' name='O_dec' value='". $row[$HuizenOdec]."' size='13'>&nbsp;<a href='http://maps.google.nl/maps?q=". $row[$HuizenNdeg] .".". $row[$HuizenNdec] .",". $row[$HuizenOdeg] .".". $row[$HuizenOdec] ."'>Google Maps</a></td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Wijk</td>";
		$HTML[] = "	<td>". $row[$HuizenWijk]."</td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Begin</td>";
		$HTML[] = "	<td><select name='bDag'>\n";
		for($d=1 ; $d<=31 ; $d++)	{	$HTML[] = "<option value='$d'". ($d == date("d", $row[$HuizenStart]) ? ' selected' : '') .">$d</option>\n";	}
		$HTML[] = "	</select><select name='bMaand'>\n";
		for($m=1 ; $m<=12 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("m", $row[$HuizenStart]) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
		$HTML[] = "	</select><select name='bJaar'>\n";
		for($j=(date('Y') - 3) ; $j<=(date('Y')) ; $j++)	{	$HTML[] = "<option value='$j'". ($j == date("Y", $row[$HuizenStart]) ? ' selected' : '') .">$j</option>\n";	}
		$HTML[] = "	</select></td>\n";
		//$HTML[] = "	<td>";
		//$HTML[] = "	<input type='text' size='1' name='bDag' value='". date("d", $row[$HuizenStart]) ."'>";
		//$HTML[] = "	<input type='text' size='1' name='bMaand' value='". date("m", $row[$HuizenStart]) ."'>";
		//$HTML[] = "	<input type='text' size='2' name='bJaar' value='". date("Y", $row[$HuizenStart]) ."'>";
		//$HTML[] = "	</td>";
		$HTML[] = "</tr>";
		$HTML[] = "<tr>";	
		$HTML[] = "	<td>Eind</td>";
		$HTML[] = "	<td><select name='eDag'>\n";
		for($d=1 ; $d<=31 ; $d++)	{	$HTML[] = "<option value='$d'". ($d == date("d", $row[$HuizenEind]) ? ' selected' : '') .">$d</option>\n";	}
		$HTML[] = "	</select><select name='eMaand'>\n";
		for($m=1 ; $m<=12 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("m", $row[$HuizenEind]) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
		$HTML[] = "	</select><select name='eJaar'>\n";
		for($j=(date('Y') - 3) ; $j<=(date('Y')) ; $j++)	{	$HTML[] = "<option value='$j'". ($j == date("Y", $row[$HuizenEind]) ? ' selected' : '') .">$j</option>\n";	}
		$HTML[] = "	</select></td>\n";
		//$HTML[] = "	<td>";
		//$HTML[] = "	<input type='text' size='1' name='eDag' value='". date("d", $row[$HuizenEind]) ."'>";
		//$HTML[] = "	<input type='text' size='1' name='eMaand' value='". date("m", $row[$HuizenEind]) ."'>";
		//$HTML[] = "	<input type='text' size='2' name='eJaar' value='". date("Y", $row[$HuizenEind]) ."'>";
		//$HTML[] = "	</td>";		
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
				//$Kenmerk[] = date('d M y', $key) .' : &euro;&nbsp;'. number_format($value,0,',','.').'<br>';
				$Kenmerk[] = "<tr>";
				//$Kenmerk[] = "<td>";
				//$Kenmerk[] = "	<input type='text' size='1' name='pDag[]' value='". date("d", $key) ."'>";
				//$Kenmerk[] = "	<input type='text' size='1' name='pMaand[]' value='". date("m", $key) ."'>";
				//$Kenmerk[] = "	<input type='text' size='2' name='pJaar[]' value='". date("Y", $key) ."'>";
				//$Kenmerk[] = "</td>";
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
}	

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $HTML));
echo "</td>";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $Kenmerk));
echo "</td>";
echo "</tr>\n";
echo $HTMLFooter;

?>