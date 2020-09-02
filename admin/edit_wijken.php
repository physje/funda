<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['doorgaan'])) {
	if(isset($_REQUEST['id']) AND $_REQUEST['id'] != 0) {
		$sql = "UPDATE $TableWijken SET ";
		$sql .= "$WijkenActive = '". (isset($_POST['active']) ? '1' : '0') ."', ";
		$sql .= "$WijkenLeesbaar = '". $_POST['leesbaar'] ."', ";
		$sql .= "$WijkenFunda = '". $_POST['straat'] ."', ";
		$sql .= "$WijkenStad = '". $_POST['plaats'] ."', ";
		$sql .= "$WijkenLastCheck = '". mktime($_POST['uur'], $_POST['minuut'], 0, $_POST['maand'], $_POST['dag'], $_POST['jaar']) ."' ";
		$sql .= "WHERE $WijkenID = ". $_POST['id'];
		
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
		$sql_delete_wijk = "DELETE FROM $TableWijken WHERE $WijkenID like ". $_POST['wijkID'];
		if(!mysqli_query($db, $sql_delete_wijk)) $HTML[] = $sql_delete_wijk.'<br>';	
				
		$HTML[] = "De wijk is verwijderd";
	} elseif(isset($_POST['delete_no'])) {	
		$HTML[] = "Gelukkig !";
		
	# Weet je het heeeel zeker
	} else {
		$HTML[] = "Weet u zeker dat u deze wijk wilt verwijderen ?";
		$HTML[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
		$HTML[] = "<input type='hidden' name='delete' value='true'>\n";
		$HTML[] = "<input type='hidden' name='wijkID' value='". $_REQUEST['id'] ."'>\n";
		$HTML[] = "<input type='submit' name='delete_yes' value='Ja'> <input type='submit' name='delete_no' value='Nee'>";
		$HTML[] = "</form>";
	}
	
	if(isset($_POST['delete_yes']) || isset($_POST['delete_no'])) {
		$HTML[] = "<p><a href='". $_SERVER["PHP_SELF"] ."'>Start</a>";
	}
} elseif(isset($_REQUEST['id'])) {
	$wijkID = $_REQUEST['id'];
	$wijkData = getWijkByID($wijkID);
	
	$HTML[] = "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
	
	if($wijkID != 0) {
		$HTML[] = "<input type='hidden' name='id' value='$wijkID'>\n";
	}
		
	$HTML[] = "<table border=0>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td><input type='checkbox' name='active'". ($wijkData['active'] == 1 ? ' checked' : '') ."></td>\n";
	$HTML[] = "	<td>Actief</td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Wijk :</td>\n";
	$HTML[] = "	<td><input type='text' name='leesbaar' value='". $wijkData['leesbaar'] ."'></td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Funda wijknaam :</td>\n";
	$HTML[] = "	<td><input type='text' name='straat' value='". $wijkData['wijk'] ."'></td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Plaats :</td>\n";
	$HTML[] = "	<td><input type='text' name='plaats' value='". $wijkData['plaats'] ."'></td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Laatste controle :</td>\n";
	$HTML[] = "	<td><select name='dag'>\n";
	for($d=1 ; $d<=31 ; $d++)	{	$HTML[] = "<option value='$d'". ($d == date("d", $wijkData['last']) ? ' selected' : '') .">$d</option>\n";	}
	$HTML[] = "	</select><select name='maand'>\n";
	for($m=1 ; $m<=12 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("m", $wijkData['last']) ? ' selected' : '') .">". strftime("%b", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
	$HTML[] = "	</select><select name='jaar'>\n";
	for($j=2018 ; $j<=(date('Y')) ; $j++)	{	$HTML[] = "<option value='$j'". ($j == date("Y", $wijkData['last']) ? ' selected' : '') .">$j</option>\n";	}
	$HTML[] = "	</select> <select name='uur'>\n";
	for($h=0 ; $h<=24 ; $h++)	{	$HTML[] = "<option value='$h'". ($h == date("H", $wijkData['last']) ? ' selected' : '') .">". substr('0'.$h, -2) ."</option>\n";	}
	$HTML[] = "	</select><select name='minuut'>\n";
	for($m=0 ; $m<=59 ; $m++)	{	$HTML[] = "<option value='$m'". ($m == date("i", $wijkData['last']) ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>\n";	}
	$HTML[] = "	</select></td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>&nbsp;</td>\n";
	$HTML[] = "	<td><a href='http://www.funda.nl/koop/". convert2FundaStyle($wijkData['plaats']) ."/". $wijkData['wijk'] ."/'>funda.nl</a> | <a href='../check.php?wijkID=$wijkID'>check</a></td>\n";
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
	$Kop[] = "	<td><b><a href='". $_SERVER['PHP_SELF']."?sort=wijk$richting'>Wijk</a><b></td>";
	$Kop[] = "	<td><b><a href='". $_SERVER['PHP_SELF']."?sort=city$richting'>Plaats</a></b></td>";
	$Kop[] = "	<td><b><a href='". $_SERVER['PHP_SELF']."?sort=time$richting'>Check</a></td>";
	$Kop[] = "</tr>\n";
	
	$sql = "SELECT * FROM $TableWijken";
	
	if($sort == 'time') {
		$sql .= " ORDER BY $WijkenLastCheck ". (!isset($_REQUEST['asc']) ? 'DESC' : 'ASC');
	} elseif($sort == 'city') {
		$sql .= " ORDER BY $WijkenStad ". (!isset($_REQUEST['asc']) ? 'DESC' : 'ASC') .", $WijkenLeesbaar ". (!isset($_REQUEST['asc']) ? 'DESC' : 'ASC');
	} elseif($sort == 'wijk') {
		$sql .= " ORDER BY $WijkenLeesbaar ". (!isset($_REQUEST['asc']) ? 'DESC' : 'ASC');
	}
	
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	do {
		$regel = array();
		$wijkID = $row[$WijkenID];
		$wijkData = getWijkByID($wijkID);
		
		if($row['active'] == 0) {
			$class = 'offline';
		} else {
			$class = 'online';
		}
		
		$regel[] = "<tr>";
		$regel[] = "	<td><a href='?id=$wijkID' class='$class'>". $wijkData['leesbaar'] ."</a></td>";
		$regel[] = "	<td>". $wijkData['plaats'] ."</td>";
		$regel[] = "	<td>". ago($wijkData['last']) ."</td>";
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
