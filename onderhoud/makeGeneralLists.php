<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');

# $minUserLevel = 1;
# $cfgProgDir = '../auth/';
# include($cfgProgDir. "secure.php");
$db = connect_db();

$tijdGrens = mktime(0,0,0,date("n")-1, date("d"), date("Y"));
$langTeKoopGrens = (time() - mktime(0,0,0,date("n"), date("d"), date("Y")-4));

$IDs = array(999, 998, 996);
$namen = array('Open huizen', 'Afgelopen maand online', 'Lang te koop');
$query = array(
	"SELECT * FROM $TableHuizen WHERE $HuizenOpenHuis like '1'",
	"SELECT * FROM $TableHuizen WHERE $HuizenStart > $tijdGrens",
	"SELECT $HuizenID, ($HuizenEind - $HuizenStart) AS tijdsduur FROM $TableHuizen HAVING tijdsduur > $langTeKoopGrens"
);

$Page_1 = '';

for($i = 0 ; $i < count($IDs) ; $i++) {
	$LijstID = $IDs[$i];
	$data = getLijstData($LijstID);
	
	$Page_1 .= '<b>'.$data['naam'] ."</b><br>\n";
	
	if($data['id'] != $LijstID) {
		$sql_insert = "INSERT INTO $TableList ($ListID) VALUES ($LijstID)";
		mysqli_query($db, $sql_insert);
		saveUpdateList($LijstID, $_SESSION['UserID'], 1, $namen[$i]);		
	} else {
		$sql_delete = "DELETE FROM $TableListResult WHERE $ListResultList like $LijstID";
		if(!mysqli_query($db, $sql_delete)) {
			echo $sql_delete;
		}
	}
	
	$sql_toevoegen = $query[$i];	
	$result = mysqli_query($db, $sql_toevoegen);
	$row = mysqli_fetch_array($result);

	do {
		$Page_1 .= addHouse2List($row[$HuizenID], $LijstID) ."\n";
	} while($row = mysqli_fetch_array($result));
	$Page_1 .= "<br>\n";
}

toLog('info', '0', '0', 'Standaard lijsten aangemaakt');

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($Page_1);
echo "</td>\n";
echo "<td width='50%' valign='top' align='center'>&nbsp;</td>\n";
echo "</tr>\n";
echo $HTMLFooter;