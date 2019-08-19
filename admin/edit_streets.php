<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['doorgaan'])) {
	if(isset($_REQUEST['id']) AND $_REQUEST['id'] != 0) {
		$sql = "UPDATE $TableStraten SET ";
		$sql .= "$StratenActive = '". (isset($_POST['active']) ? '1' : '0') ."', ";
		$sql .= "$StratenStrLeesbaar = '". $_POST['leesbaar'] ."', ";
		$sql .= "$StratenStrFunda = '". $_POST['straat'] ."', ";
		$sql .= "$StratenStad = '". $_POST['plaats'] ."', ";
		$sql .= "$StratenLastCheck = '". mktime($_POST['uur'], $_POST['minuut'], 0, $_POST['maand'], $_POST['dag'], $_POST['jaar']) ."' ";
		$sql .= "WHERE $StratenID = ". $_POST['id'];
		
		if(!mysqli_query($db, $sql)) {
			$HTML[] = $_POST['leesbaar'] ." kon niet worden opgeslagen";
			$HTML[] = $sql;
		} else {
			$HTML[] = $_POST['leesbaar'] ." opgeslagen";
		}
	} 
	
	$HTML[] = "<p><a href='". $_SERVER["PHP_SELF"] ."'>Start</a>";
} elseif(isset($_REQUEST['delete'])) {
	if(isset($_POST['delete_yes'])) {				
		$sql_delete_straat = "DELETE FROM $TableStraten WHERE $StratenID like ". $_POST['streetID'];
		if(!mysqli_query($db, $sql_delete_straat)) $HTML[] = $sql_delete_straat.'<br>';	
				
		$HTML[] = "De straat is verwijderd";
	} elseif(isset($_POST['delete_no'])) {	
		$HTML[] = "Gelukkig !";
		
	# Weet je het heeeel zeker
	} else {
		$HTML[] = "Weet u zeker dat u deze straat wilt verwijderen ?";
		$HTML[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
		$HTML[] = "<input type='hidden' name='delete' value='true'>\n";
		$HTML[] = "<input type='hidden' name='streetID' value='". $_REQUEST['id'] ."'>\n";
		$HTML[] = "<input type='submit' name='delete_yes' value='Ja'> <input type='submit' name='delete_no' value='Nee'>";
		$HTML[] = "</form>";
	}
	
	if(isset($_POST['delete_yes']) || isset($_POST['delete_no'])) {
		$HTML[] = "<p><a href='". $_SERVER["PHP_SELF"] ."'>Start</a>";
	}
} elseif(isset($_REQUEST['id'])) {
	$streetID = $_REQUEST['id'];
	$straatData = getStreetByID($streetID);
	
	$HTML[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
	
	if($streetID != 0) {
		$HTML[] = "<input type='hidden' name='id' value='$streetID'>\n";
	}
		
	$HTML[] = "<table border=0>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td><input type='checkbox' name='active'". ($straatData['active'] == 1 ? ' checked' : '') ."></td>\n";
	$HTML[] = "	<td>Actief</td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Straatnaam :</td>\n";
	$HTML[] = "	<td><input type='text' name='leesbaar' value='". $straatData['leesbaar'] ."'> (<a href='http://www.funda.nl/koop/". convert2FundaStyle($straatData['plaats']) ."/straat-". $straatData['straat'] ."/'>funda.nl</a>)</td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Funda straatnaam :</td>\n";
	$HTML[] = "	<td><input type='text' name='straat' value='". $straatData['straat'] ."'></td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Plaats :</td>\n";
	$HTML[] = "	<td><input type='text' name='plaats' value='". $straatData['plaats'] ."'></td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Laatste controle :</td>\n";
	$HTML[] = "	<td><select name='dag'>\n";
	for($d=1 ; $d<=31 ; $d++)	{	$HTML[] = "<option value='$d'". ($d == date("d", $straatData['last']) ? ' selected' : '') .">$d</option>\n";	}
	$HTML[] = "	</select><select name='maand'>\n";
	for($m=1 ; $m<=12 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("m", $straatData['last']) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
	$HTML[] = "	</select><select name='jaar'>\n";
	for($j=2018 ; $j<=(date('Y')) ; $j++)	{	$HTML[] = "<option value='$j'". ($j == date("Y", $straatData['last']) ? ' selected' : '') .">$j</option>\n";	}
	$HTML[] = "	</select> <select name='uur'>\n";
	for($h=0 ; $h<=24 ; $h++)	{	$HTML[] = "<option value='$h'". ($h == date("H", $straatData['last']) ? ' selected' : '') .">$h</option>\n";	}
	$HTML[] = "	</select><select name='minuut'>\n";
	for($m=0 ; $m<=59 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("i", $straatData['last']) ? ' selected' : '') .">$m</option>\n";	}
	$HTML[] = "	</select></td>\n";
	$HTML[] = "</tr>\n";		
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td colspan='2'>&nbsp;</td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td align='left'><input type='submit' name='doorgaan' value='Opslaan'></td>\n";
	$HTML[] = "	<td align='right'><input type='submit' name='delete' value='Verwijderen'></td></tr></table></td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "</table>\n";
	$HTML[] = "</form>\n";
} else  {	
	if(!isset($_REQUEST['sort'])) {
		$sort = 'time';
	} else {
		$sort = $_REQUEST['sort'];
	}
	
	if(isset($_REQUEST['asc'])) {
		$richting = '';
	} else {
		$richting = '&asc';
	}
		
	
	$Kop[] = "<tr>\n";
	$Kop[] = "	<td><b><a href='". $_SERVER['PHP_SELF']."?sort=street$richting'>Straat</a><b></td>";
	$Kop[] = "	<td><b><a href='". $_SERVER['PHP_SELF']."?sort=city$richting'>Plaats</a></b></td>";
	$Kop[] = "	<td><b><a href='". $_SERVER['PHP_SELF']."?sort=time$richting'>Check</a></td>";
	$Kop[] = "</tr>\n";
	
	$sql = "SELECT * FROM $TableStraten";
	
	if($sort == 'time') {
		$sql .= " ORDER BY $StratenLastCheck ". (!isset($_REQUEST['asc']) ? 'DESC' : 'ASC');
	} elseif($sort == 'city') {
		$sql .= " ORDER BY $StratenStad ". (!isset($_REQUEST['asc']) ? 'DESC' : 'ASC') .", $StratenStrLeesbaar ". (!isset($_REQUEST['asc']) ? 'DESC' : 'ASC');
	} elseif($sort == 'street') {
		$sql .= " ORDER BY $StratenStrLeesbaar ". (!isset($_REQUEST['asc']) ? 'DESC' : 'ASC');
	}
	
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	do {
		$regel = array();
		$streetID = $row[$StratenID];
		$straatData = getStreetByID($streetID);
		
		if($row['active'] == 0) {
			$class = 'offline';
		} else {
			$class = 'online';
		}
		
		$regel[] = "<tr>";
		$regel[] = "	<td><a href='?id=$streetID' class='$class'>". $straatData['leesbaar'] ."</a></td>";
		$regel[] = "	<td>". $straatData['plaats'] ."</td>";
		$regel[] = "	<td>". date('d-m H:i', $straatData['last']) ."</td>";
		$regel[] = "</tr>";
		
		$rij[] = implode(NL, $regel);
	} while($row = mysqli_fetch_array($result));
}

echo $HTMLHeader;
echo "<tr>\n";
if(isset($rij)) {
	$aantal_rijen = count($rij);
	
	$links = array_slice($rij, 0, round($aantal_rijen/2));
	$rechts = array_slice($rij, round($aantal_rijen/2));
	
	echo "	<td width='50%' valign='top'>". showBlock('<table>'.implode(NL, $Kop).implode(NL, $links).'</table>')."</td>\n";
	echo "	<td width='50%' valign='top'>". showBlock('<table>'.implode(NL, $Kop).implode(NL, $rechts).'</table>')."</td>\n";
} else {
	echo "	<td>". showBlock(implode('', $HTML)) ."</td>\n";
}
echo "</tr>\n";
echo $HTMLFooter;
