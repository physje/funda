<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['scherm']) AND $_POST['scherm'] == 2) {	
	if(combineMasterSlave($_POST['masterID'], $_POST['slaveID'])) {
		$HTML[] = "<a href='?'>Combineer volgende huis</a>";
	} else {
		$HTML[] = "Helaas";
	}	
	
} elseif(isset($_POST['scherm']) AND $_POST['scherm'] == 1) {
	$slaveData = getFundaData($_POST['slave']);
	
	$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '". urlencode($slaveData['adres']) ."' AND $HuizenID not like '". $_POST['slave'] ."'";
	$result	= mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	$HTML[] = "<form method='post' action=''>";
	$HTML[] = "<input type='hidden' name='scherm' value='2'>";
	$HTML[] = "<input type='hidden' name='slaveID' value='". $_POST['slave'] ."'>";
	$HTML[] = "<table>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td colspan='2'>Selecteer welk huis als master moet fungeren voor <a href='http://www.funda.nl/". $_POST['slave'] ."' target='master'>". $_POST['slave'] ."</a></td>";	
	$HTML[] = "</tr>";	
	
	do {
		$huisID = $row[$HuizenID];
		$masterData = getFundaData($huisID);		
		
		$HTML[] = "<tr>";
		$HTML[] = "	<td><input type='radio' name='masterID' value='$huisID' checked></td>";
		$HTML[] = "	<td><img src='". $masterData['thumb'] ."'><br>$huisID | <a href='http://www.funda.nl/$huisID' target='master'>funda.nl</a></td>";
		$HTML[] = "</tr>";		
	} while($row = mysqli_fetch_array($result));	
	
	$HTML[] = "</table>";
	$HTML[] = "<input type='submit' name='uitvoeren' value='Volgende'>";
	$HTML[] = "</form>";	
} else {
	$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenDetails like '1'";
	#$sql		= "SELECT $HuizenID, $HuizenAdres, $HuizenPlaats, COUNT(*) as aantal FROM $TableHuizen WHERE $HuizenDetails like '1' GROUP BY $HuizenAdres HAVING aantal > 1";
	
	$result	= mysqli_query($db, $sql);
	
	if($row = mysqli_fetch_array($result)) {
		$HTML[] = "<form method='post' action=''>";
		$HTML[] = "<input type='hidden' name='scherm' value='1'>";
		$HTML[] = "<table>";
		$HTML[] = "<tr>";
		$HTML[] = "	<td>Slave</td>";	
		$HTML[] = "</tr>";	
		$HTML[] = "<tr>";
		$HTML[] = "	<td><select name='slave'>";
		
		do {
			$sql_2		= "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '". $row[$HuizenAdres] ."' AND  $HuizenPlaats like '". $row[$HuizenPlaats] ."' AND $HuizenID NOT LIKE ". $row[$HuizenID];
			$result_2	= mysqli_query($db, $sql_2);
			
			#echo $sql_2 .'|'. mysqli_num_rows($result_2) .'<br>';
			
			if(mysqli_num_rows($result_2) > 0) {
				$huisID = $row[$HuizenID];
				$adres = urldecode($row[$HuizenAdres])."; ". urldecode($row[$HuizenPlaats]) ." ($huisID)";
				
				$HTML[] = "<option value='$huisID'". ($huisID == $_REQUEST['id'] ? ' selected' : '') .">$adres</option>";
			}
		} while($row = mysqli_fetch_array($result));
		
		$HTML[] = "	</select></td>";
		$HTML[] = "</tr>";
		$HTML[] = "</table>";
		$HTML[] = "<input type='submit' name='uitvoeren' value='Volgende'>";
		$HTML[] = "</form>";	
	}
}

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td>". showBlock(implode("\n", $HTML)) ."</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
