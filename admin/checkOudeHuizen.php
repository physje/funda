<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'jaar') {
	$startdag = mktime(0, 0, 0, date("n"), date("j"), date("Y")-1);
	$einddag	= mktime(0, 0, 0, date("n"), date("j"), date("Y"));
} elseif(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'kwartaal') {
	$startdag = mktime(0, 0, 0, date("n")-3, date("j"), date("Y"));
	$einddag	= mktime(0, 0, 0, date("n"), date("j"), date("Y"));
} elseif(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'maand') {
	$startdag = mktime(0, 0, 0, date("n")-1, date("j"), date("Y"));
	$einddag	= mktime(0, 0, 0, date("n"), date("j"), date("Y"));	
} elseif(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'week') {
	$startdag = mktime(0, 0, 0, date("n"), date("j")-7, date("Y"));
	$einddag	= mktime(0, 0, 0, date("n"), date("j"), date("Y"));
} elseif(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'dag') {
	$startdag = mktime(0, 0, 0, date("n"), date("j")-1, date("Y"));
	$einddag	= mktime(0, 0, 0, date("n"), date("j"), date("Y"));	
} else {
	$sql_straat			= "SELECT min($StratenLastCheck) as min FROM $TableStraten WHERE $StratenActive like '1'";
	$result_straat	= mysqli_query($db, $sql_straat);
	$row_straat			= mysqli_fetch_array($result_straat);
	
	$sql_wijk				= "SELECT min($WijkenLastCheck) as min FROM $TableWijken WHERE $WijkenActive like '1'";
	$result_wijk		= mysqli_query($db, $sql_wijk);
	$row_wijk				= mysqli_fetch_array($result_wijk);
	
	$startdag				= mktime(0, 0, 0, 1, 1, date("Y"));
	$einddag				= min(array($row_straat['min'], $row_wijk['min']));
}

$bDag			= getParam('bDag', date("d", $startdag));
$bMaand		= getParam('bMaand', date("m", $startdag));
$bJaar		= getParam('bJaar', date("Y", $startdag));
$bUur			= getParam('bUur', date("H", $startdag));
$bMin			= getParam('bMin', date("i", $startdag));
$eDag			= getParam('eDag', date("d", $einddag));
$eMaand 	= getParam('eMaand', date("m", $einddag));
$eJaar		= getParam('eJaar', date("Y", $einddag));
$eUur			= getParam('eUur', date("H", $einddag));
$eMin			= getParam('eMin', date("i", $einddag));

$selectie	= getParam('selectie', '');

$HTML = $Debug = array();
if(!isset($_POST['submit']) AND !isset($_REQUEST['id'])) {
	$dateSelection = makeDateSelection($bUur,$bMin,$bDag,$bMaand,$bJaar , $eUur,$eMin,$eDag,$eMaand,$eJaar);
		
	$HTML[] = "<form method='post' action='". $_SERVER['PHP_SELF'] ."'>";
	$HTML[] = "<input type='hidden' name='datum' value='1'>";
	$HTML[] = "<table>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Begin Datum</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>Eind Datum</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>Groep</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td rowspan='2'><input type='submit' name='submit' value='Weergeven'></td>";
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>". $dateSelection[0] ."</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>". $dateSelection[1] ."</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>". makeSelectionSelection(true, true) ."</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "</tr>";
	$HTML[] = "	<td colspan=7><a href='". $_SERVER['PHP_SELF'] ."?tijd=dag'>dag</a> | <a href='". $_SERVER['PHP_SELF'] ."?tijd=week'>week</a> | <a href='". $_SERVER['PHP_SELF'] ."?tijd=maand'>maand</a> | <a href='". $_SERVER['PHP_SELF'] ."?tijd=kwartaal'>kwartaal</a> | <a href='". $_SERVER['PHP_SELF'] ."?tijd=jaar'>jaar</a></td>\n";
	$HTML[] = "</tr>";
	$HTML[] = "</table>";
	$HTML[] = "</form>";
} else {
	$opdracht	= substr($_REQUEST['selectie'], 1);
	
	if(isset($_REQUEST['id'])) {
		$sql_array[] = "SELECT * FROM $TableHuizen WHERE $HuizenID like ". $_REQUEST['id'];
		$HTML[] = '<h1>'. $_REQUEST['id'] ."</h1><br>\n";
	} elseif(isset($_POST['submit'])) {		
		$beginGrens = mktime($_POST['bUur'], $_POST['bMin'], 0, $_POST['bMaand'], $_POST['bDag'], $_POST['bJaar']);
		$eindGrens	= mktime($_POST['eUur'], $_POST['eMin'], 59, $_POST['eMaand'], $_POST['eDag'], $_POST['eJaar']);
		$titel = 'Huizen voor het laatst gezien tussen '. date('d-m-y', $beginGrens) .' en '. date('d-m-y', $eindGrens);
		
		$from[] = $TableHuizen;
		
		$where[] = "$TableHuizen.$HuizenVerkocht NOT like '1'";
		$where[] = "$TableHuizen.$HuizenOffline like '0'";
		$where[] = "(($TableHuizen.$HuizenEind BETWEEN $beginGrens AND $eindGrens))";
		
		if($_REQUEST['selectie'] != '') {
			$OpdrachtData = getOpdrachtData($opdracht);
			
			$from[] = $TableVerdeling;
			$from[] = $TableResultaat;
			
			$where[] = "$TableResultaat.$ResultaatZoekID = $TableVerdeling.$VerdelingOpdracht";
			$where[] = "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID";
			$where[] = "$TableResultaat.$ResultaatZoekID like '$opdracht'";
									
			$titel .= " voor ". $OpdrachtData['naam'];
		}
		
		$sql = "SELECT * FROM ". implode(', ', $from) ." WHERE ". implode(' AND ', $where) ." GROUP BY $TableHuizen.$HuizenID";
				
		$HTML[] = "<h1>$titel</h1><br>\n";
	}
		
	$result	= mysqli_query($db, $sql);
	
	$Debug[] = $sql ."<br>\n";  
	$Debug[] = mysqli_num_rows($result) ." resultaten<br>\n";  
		
	$result	= mysqli_query($db, $sql);	
	if($row = mysqli_fetch_array($result)) {
		do {
			$url = 'http://www.funda.nl/'.$row[$HuizenID];
			
			$HTML[] = '<b>'. urldecode($row[$HuizenAdres]) ."</b> (". urldecode($row[$HuizenPlaats]) .")<br>";
			$HTML[] = "[van ". date("d-m-Y", $row[$HuizenStart]) ." tot ". date("d-m-Y", $row[$HuizenEind]) ."]<br>";
			$HTML[] = "<a href='$url' target='funda_huis'>funda.nl</a> | <a href='edit.php?id=". $row[$HuizenID] ."' target='funda_detail'>details</a> | zet <a href='changeState.php?state=available&id=". $row[$HuizenID] ."' target='funda_state'>beschikbaar</a>, <a href='changeState.php?state=offline&id=". $row[$HuizenID] ."' target='funda_state'>offline</a>, <a href='changeState.php?state=optie&id=". $row[$HuizenID] ."' target='funda_state'>onder optie</a>, <a href='changeState.php?state=voorbehoud&id=". $row[$HuizenID] ."' target='funda_state'>onder voorbehoud</a>, <a href='changeState.php?state=verkocht&id=". $row[$HuizenID] ."' target='funda_state'>verkocht</a><br>";
			
			if($row[$HuizenOffline] != 0) {
				$HTML[] = ' -> niet aan beginnen, is offline<br>';
			}

		} while($row = mysqli_fetch_array($result));
	}
}

echo $HTMLHeader;
echo "<tr>\n";
if(count($Debug) == 0) {
	echo "<td width='8%'>&nbsp;</td>\n";
	echo "<td width='84%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $HTML));
	echo "</td>\n";
	echo "<td width='8%'>&nbsp;</td>\n";
	echo "</tr>\n";
} else {
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $HTML));
	echo "</td>";
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $Debug));
	echo "</td>";
}
echo "</tr>\n";
echo $HTMLFooter;
