<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

connect_db();

echo $HTMLHeader;

if(isset($_REQUEST['selectie'])) {
	$groep	= substr($_REQUEST['selectie'], 0, 1);
	$id			= substr($_REQUEST['selectie'], 1);
	
	if($groep == 'Z') {		
		$opdrachtData	= getOpdrachtData($id);
		$Name					= $opdrachtData['naam'];
		$from					= "$TableResultaat, $TableHuizen";
		$where				= "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $id";
	} else {
		$LijstData		= getLijstData($id);
		$Name					= $LijstData['naam'];
		$from					= "$TableListResult, $TableHuizen";
		$where				= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $id";
	}
	
	switch ($_REQUEST['periode']) {
		case 0:
			$periode = 'week';
			break;
		case 1:
			$periode = 'maand';
			break;	
		case 3:
			$periode = 'jaar';
			break;
		default:
			$periode = 'kwartaal';			
	} 
	
	$sql		= "SELECT min($TableHuizen.$HuizenStart) FROM $from WHERE $where";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	$start_tijd = $row[0];
	
	$sql		= "SELECT max($TableHuizen.$HuizenEind) FROM $from WHERE $where";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	$eind_tijd = $row[0];
	
	$startJaar	= date("Y", $start_tijd);
	$startMaand	= date("n", $start_tijd);
	$startDag		= date("d", $start_tijd);
	$eindJaar		= date("Y", $eind_tijd);
		
	if($periode == 'week') {
		$start	= mktime(0,0,0,$startMaand,($startDag-date("w", $start_tijd)),$startJaar);
		$stap_d = 7;
		$stap_m = 0;
	}
	
	if($periode == 'maand') {
		$start = mktime(0,0,0,$startMaand,1,$startJaar);
		$stap_d = 0;
		$stap_m = 1;
	}
	
	if($periode == 'kwartaal') {		
		$start = mktime(0,0,0,(3*(floor(($startMaand-1)/3))+1),1,$startJaar);		
		$stap_d = 0;
		$stap_m = 3;
	}
	
	if($periode == 'jaar') {		
		$start = mktime(0,0,0,1,1,$startJaar);
		$stap_d = 0;
		$stap_m = 12;
	}
		
	$i=0;
	while($start < $eind_tijd) {
		$eind		= (mktime(0,0,0,(date('n',$start)+$stap_m),(date('d',$start)+$stap_d), date('Y', $start))-1);
		$sql		= "SELECT COUNT(*) FROM $from WHERE $where AND ($TableHuizen.$HuizenStart BETWEEN $start AND $eind)";
		$result	= mysql_query($sql);
		$row		= mysql_fetch_array($result);
		$tekoop[$i] = $row[0];
		$sql_1[$i] = $sql;
		
		$sql		= "SELECT COUNT(*) FROM $from WHERE $where AND $TableHuizen.$HuizenVerkocht = '1' AND ($TableHuizen.$HuizenEind BETWEEN $start AND $eind)";
		$result	= mysql_query($sql);
		$row		= mysql_fetch_array($result);
		$verkocht[$i] = $row[0];
		$sql_2[$i] = $sql;
		
		$sql		= "SELECT COUNT(*) FROM $from WHERE $where AND $TableHuizen.$HuizenOffline = '1' AND ($TableHuizen.$HuizenEind BETWEEN $start AND $eind)";
		$result	= mysql_query($sql);
		$row		= mysql_fetch_array($result);
		$offline[$i] = $row[0];
		$sql_2[$i] = $sql;
		
		
		
		if($periode == 'week') {
			$titel[$i] = 'week '. date('W', ($start+(24*60*60)));
		}
		
		if($periode == 'maand') {
			$titel[$i] = date('M', $start) .' \''.date('y', $start);;
		}
		
		if($periode == 'kwartaal') {
			$titel[$i] = 'Q'. (floor(date('n',$start)/3)+1) .' \''.date('y', $start);
		}
		
		if($periode == 'jaar') {
			$titel[$i] = date('Y', $start);
		}
		
		//echo date('d-m-Y', $start) .' -> '. date('d-m-Y', $eind) .'<br>';
		
		$start	= $eind+1;
		$i++;
	}
	
	$max_value = max(array_merge($tekoop, $verkocht));
	
	echo "<table width='100%' border=0>\n";
	echo "<tr>\n";
	echo "	<td align='center' colspan='2'><h1>Balans '$Name'</h1></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan='2'>&nbsp;</td>\n";
	echo "</tr>\n";
	
	foreach($titel as $key=>$value) {
		$breedte_tekoop		= 100*($tekoop[$key]/$max_value);
		$rest_tekoop			= 100 - $breedte_tekoop;
		
		$breedte_verkocht = 100*($verkocht[$key]/$max_value);
		$rest_verkocht		= 100 - $breedte_verkocht;
		
		$breedte_offline	= 100*($offline[$key]/$max_value);
		$rest_offline			= 100 - $breedte_offline;
		
		
		# Nieuwe huizen
		echo "<tr>\n";
		echo "	<td rowspan='3' width='15%' align='right'>$value</td>\n";
		echo "	<td>";
		echo "	<table width='100%' border=0><tr>\n";
		if($breedte_tekoop > 0) {
			echo "		<td width='$breedte_tekoop%' bgcolor='#FF6D6D' align='left' title='". ($tekoop[$key] == 1 ? "1 huis" : $tekoop[$key]. " huizen") ." in de verkoop'>". $tekoop[$key] ."</td>\n";
			echo "		<td width='$rest_tekoop%'>&nbsp;</td>\n";
		} else {
			echo "		<td colspan='2'>0</td>\n";
		}		
		echo "	</tr></table>";
		echo "	</td>\n";
		echo "</tr>\n";
		
		# Verkochte huizen
		echo "<tr>\n";
		echo "	<td>\n";
		echo "	<table width='100%' border=0><tr>\n";
		if($breedte_verkocht > 0) {
			echo "		<td width='$breedte_verkocht%' bgcolor='#6DFF6D' align='left' title='". ($verkocht[$key] == 1 ? "1 huis" : $verkocht[$key]. " huizen") ." verkocht'>". $verkocht[$key] ."</td>\n";
			echo "		<td width='$rest_verkocht%'>&nbsp;</td>\n";
		} else {
			echo "		<td colspan='2'>0</td>\n";
		}
		echo "	</tr></table>\n";
		echo "	</td>\n";
		echo "</tr>\n";
		
		# Offline huizen
		echo "<tr>\n";
		echo "	<td>";
		echo "	<table width='100%' border=0><tr>\n";
		if($breedte_offline > 0) {
			echo "		<td width='$breedte_offline%' bgcolor='#6D6DFF' align='left' title='". ($offline[$key] == 1 ? "1 huis" : $offline[$key]. " huizen") ." verdwenen'>". $offline[$key] ."</td>\n";
			echo "		<td width='$rest_offline%'>&nbsp;</td>\n";
		} else {
			echo "		<td colspan='2'>0</td>\n";
		}
		echo "	</tr></table>";
		echo "	</td>\n";
		echo "</tr>\n";
		
		# Witregel
		echo "<tr>\n";
		echo "	<td colspan='2'>&nbsp;</td>";
		echo "</tr>\n";		
	}
	echo "</table>\n";
} else {	
	$HTML[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
	$HTML[] = "<table>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Selectie</td>";	
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>". makeSelectionSelection(false, false) ."</td>";
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Periode</td>";	
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td><select name='periode'>";
	$HTML[] = "	<option value='0'>week</option>";
	$HTML[] = "	<option value='1'>maand</option>";
	$HTML[] = "	<option value='2' selected>kwartaal</option>";
	$HTML[] = "	<option value='3'>jaar</option>";
	$HTML[] = "	</select></td>";
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
?>
