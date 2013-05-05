<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../../general_include/class.phpmailer.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
setlocale(LC_ALL, 'nl_NL');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

connect_db();

if(!isset($_REQUEST['bDag']) OR !isset($_REQUEST['bMaand']) OR !isset($_REQUEST['bJaar'])) {
	$logShift = 24*60*60;
	$bDag = date('d', time() - $logShift);
	$bMaand = date('m', time() - $logShift);
	$bJaar = date('Y', time() - $logShift);
} else {
	$bDag = $_REQUEST['bDag'];
	$bMaand = $_REQUEST['bMaand'];
	$bJaar = $_REQUEST['bJaar'];
}

if(!isset($_REQUEST['eDag']) OR !isset($_REQUEST['eMaand']) OR !isset($_REQUEST['eJaar'])) {
	$eDag = date('d');
	$eMaand = date('m');
	$eJaar = date('Y');	
} else {
	$eDag = $_REQUEST['eDag'];
	$eMaand = $_REQUEST['eMaand'];
	$eJaar = $_REQUEST['eJaar'];
}

if(isset($_REQUEST['opdracht']) AND $_REQUEST['opdracht'] != '') {
	$opdracht = $_REQUEST['opdracht'];	
}

if(isset($_REQUEST['huis']) AND $_REQUEST['huis'] != '') {
	$huis = $_REQUEST['huis'];
}

if(isset($_REQUEST['debug']) AND $_REQUEST['debug'] != '') {
	$debug = $_REQUEST['debug'];
} else {
	$debug = 'nee';
}

if(isset($_REQUEST['info']) AND $_REQUEST['info'] != '') {
	$info = $_REQUEST['info'];	
} elseif(!isset($_REQUEST['bDag'])) {
	$info = 'ja';
} else {
	$info = 'nee';
}

if(isset($_REQUEST['error']) AND $_REQUEST['error'] != '') {
	$error = $_REQUEST['error'];	
} elseif(!isset($_REQUEST['bDag'])) {
	$error = 'ja';
} else {
	$error = 'nee';
}

$begin	= mktime(0, 0, 0, $bMaand, $bDag, $bJaar);
$eind		= mktime(23, 59, 59, $eMaand, $eDag, $eJaar);


$sql		= "SELECT * FROM $TableLog WHERE $LogTime BETWEEN $begin AND $eind";
if($debug == 'ja')		$sql_OR[] = "$LogType = 'debug'";
if($info == 'ja')			$sql_OR[] = "$LogType = 'info'";
if($error == 'ja')		$sql_OR[] = "$LogType = 'error'";
if(is_array($sql_OR))	$sql .= " AND (". implode(" OR ", $sql_OR) .")";
//if(isset($opdracht) AND !isset($huis))	$sql .= " AND $LogOpdracht = '$opdracht'";
if(isset($opdracht))	$sql .= " AND $LogOpdracht = '$opdracht'";
if(isset($huis))			$sql .= " AND $LogHuis = '$huis'";

//echo $sql;

$result	= mysql_query($sql);
$aantal	= mysql_num_rows($result);
$row		= mysql_fetch_array($result);

$deel_1 = "<table>";
$deel_2 = "<table>";

do {
	$fundaData = getFundaData($row[$LogHuis]);
	$opdrachtData = getOpdrachtData($row[$LogOpdracht]);	
	$i++;
	
	$rij = "<tr>";
	$rij .= "	<td>". date("d-m H:i:s", $row[$LogTime]) ."</td>";
	$rij .= "	<td>&nbsp;</td>\n";
	$rij .= "	<td><a href='http://www.funda.nl". $fundaData['url'] ."' title='". $opdrachtData['naam'] .'; '. $fundaData['adres'] ."'>". $row[$LogHuis] ."</a></td>";
	$rij .= "	<td>&nbsp;</td>\n";
	$rij .= "	<td>". $row[$LogMessage] ."</td>";
	$rij .= "</tr>";
	if($i > $aantal/2) {
		$deel_2 .= $rij;
	} else {
		$deel_1 .= $rij;
	}
} while($row = mysql_fetch_array($result));

$deel_1 .= "</table>";
$deel_2 .= "</table>";

$dateSelection = makeDateSelection($bDag, $bMaand, $bJaar, $eDag, $eMaand, $eJaar);

$zoekScherm[] = "<form method='post'>";
$zoekScherm[] = "<table border=0 align='center'>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><b>Begindatum</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Einddatum</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Zoekopdracht</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Huis</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td rowspan='4'><input type='submit' value='Zoeken' name='submit'></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td>". $dateSelection[0] ."</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td>". $dateSelection[1] ."</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td>". makeSelectionSelection(true, true) ."</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
if(isset($opdracht)) {
	$Huizen			= getHuizen($opdracht);

	$zoekScherm .= "	<td><select name='huis'>";
	$zoekScherm .= "	<option value=''>Alle</option>";
	foreach($Huizen as $huisID) {
		$HuisData = getFundaData($huisID, $opdracht);
		$zoekScherm[] = "	<option value='$huisID'". ($huis == $huisID ? ' selected' : '') .">". $HuisData['adres'] ."</option>";
	}
	
	$zoekScherm[] = "	</select></td>";
} else {
	$zoekScherm[] = "	<td>&nbsp;</td>";
}
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td colspan='7'>";
$zoekScherm[] = "	<input type='checkbox' name='error' value='ja' ". ($error == 'ja' ? ' checked' : '') ."> Error&nbsp;&nbsp;&nbsp;";
$zoekScherm[] = "	<input type='checkbox' name='info' value='ja' ". ($info == 'ja' ? ' checked' : '') ."> Info&nbsp;&nbsp;&nbsp;";
$zoekScherm[] = "	<input type='checkbox' name='debug' value='ja' ". ($debug == 'ja' ? ' checked' : '') ."> Debug";
$zoekScherm[] = "	</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "</table>";
$zoekScherm[] = "</form>";

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td valign='top' align='center' colspan=2>". showBlock(implode("\n", $zoekScherm)) ."</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td width='50%' valign='top' align='center'>". showBlock($deel_1) ."</td>\n";
echo "	<td width='50%' valign='top' align='center'>". showBlock($deel_2) ."</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

?>