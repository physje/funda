<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
setlocale(LC_ALL, 'nl_NL');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$db = connect_db();

if(!isset($_REQUEST['bDag']) OR !isset($_REQUEST['bMaand']) OR !isset($_REQUEST['bJaar']) OR !isset($_REQUEST['bUur']) OR !isset($_REQUEST['bMin'])) {
	$bMin = 0;
	$bUur = 0;
	$bDag = date('d');
	$bMaand = date('m');
	$bJaar = date('Y');	
} else {
	$bMin = $_REQUEST['bMin'];
	$bUur = $_REQUEST['bUur'];
	$bDag = $_REQUEST['bDag'];
	$bMaand = $_REQUEST['bMaand'];
	$bJaar = $_REQUEST['bJaar'];
}

if(!isset($_REQUEST['eDag']) OR !isset($_REQUEST['eMaand']) OR !isset($_REQUEST['eJaar']) OR !isset($_REQUEST['eUur']) OR !isset($_REQUEST['eMin'])) {
	$eMin = date('i');
	$eUur = date('H');
	$eDag = date('d');
	$eMaand = date('m');
	$eJaar = date('Y');	
} else {
	$eMin = $_REQUEST['eMin'];
	$eUur = $_REQUEST['eUur'];
	$eDag = $_REQUEST['eDag'];
	$eMaand = $_REQUEST['eMaand'];
	$eJaar = $_REQUEST['eJaar'];
}

if(isset($_REQUEST['selectie']) AND $_REQUEST['selectie'] != '') {
	$selectie	= $_REQUEST['selectie'];
	$opdracht = substr($selectie, 1);
} else {
	$selectie	= '';
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

$begin	= mktime($bUur, $bMin, 0, $bMaand, $bDag, $bJaar);
$eind		= mktime($eUur, $eMin, 59, $eMaand, $eDag, $eJaar);
$huis = null;
$sql_OR = array();

$sql		= "SELECT * FROM $TableLog WHERE $LogTime BETWEEN $begin AND $eind";
if($debug == 'ja')			$sql_OR[] = "$LogType = 'debug'";
if($info == 'ja')				$sql_OR[] = "$LogType = 'info'";
if($error == 'ja')			$sql_OR[] = "$LogType = 'error'";
if(count($sql_OR) > 0)	$sql .= " AND (". implode(" OR ", $sql_OR) .")";
if(isset($opdracht))		$sql .= " AND $LogOpdracht = '$opdracht'";
if(isset($huis))				$sql .= " AND $LogHuis = '$huis'";

$result	= mysqli_query($db, $sql);
$aantal	= mysqli_num_rows($result);
$row		= mysqli_fetch_array($result);
$i = 0;
$deel_1 = $deel_2 = '';

do {
	$i++;
	$queryData = $title = array();
		
	if($row[$LogOpdracht] > 0) {
		$opdrachtData = getOpdrachtData($row[$LogOpdracht]);
		$queryData['selectie'] = 'Z'. $row[$LogOpdracht];
		$title[] = $opdrachtData['naam'];
	}
	
	if($row[$LogHuis] > 0) {
		$fundaData = getFundaData($row[$LogHuis]);
		$queryData['huis'] = $row[$LogHuis];
		$title[] = $fundaData['adres'];
	}
		
	$queryData['bDag'] = $bDag;
	$queryData['bMaand'] = $bMaand;
	$queryData['bJaar'] = $bJaar;
	$queryData['eDag'] = $eDag;
	$queryData['eMaand'] = $eMaand;
	$queryData['eJaar'] = $eJaar;
	
	$rij = "<tr>";
	$rij .= "	<td>". date("d-m H:i:s", $row[$LogTime]) ."</td>";
	$rij .= "	<td>&nbsp;</td>\n";	
	$rij .= "	<td><a href='log.php?". http_build_query($queryData) ."' title='". implode('; ', $title) ."'>". $row[$LogHuis] ."</a></td>";
	$rij .= "	<td>&nbsp;</td>\n";
	$rij .= "	<td>". $row[$LogMessage] ."</td>";
	$rij .= "</tr>";
	if($i > $aantal/2) {
		$deel_2 .= $rij;
	} else {
		$deel_1 .= $rij;
	}
} while($row = mysqli_fetch_array($result));

$dateSelection = makeDateSelection($bUur, $bMin, $bDag, $bMaand, $bJaar, $eUur, $eMin, $eDag, $eMaand, $eJaar);

$zoekScherm[] = "<form method='post' action='$_SERVER[PHP_SELF]'>";
$zoekScherm[] = "<table border=0 align='center'>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><b>Begindatum</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Einddatum</b></td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Zoekopdracht</b></td>";
if(isset($opdracht)) {
	$zoekScherm[] = "	<td>&nbsp;</td>";
	$zoekScherm[] = "	<td><b>Huis</b></td>";
}	
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td rowspan='3'><input type='submit' value='Zoeken' name='submit'></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td>". $dateSelection[0] ."</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td>". $dateSelection[1] ."</td>";
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "	<td>". makeSelectionSelection(true, true, $selectie) ."</td>";
if(isset($opdracht)) {
	$Huizen			= getHuizen($opdracht);

	$zoekScherm[] = "	<td>&nbsp;</td>";
	$zoekScherm[] = "	<td><select name='huis'>";
	$zoekScherm[] = "	<option value=''>Alle</option>";
	foreach($Huizen as $huisID) {
		$HuisData = getFundaData($huisID);
		$zoekScherm[] = "	<option value='$huisID'". ($huis == $huisID ? ' selected' : '') .">". $HuisData['adres'] ."</option>";
	}
	
	$zoekScherm[] = "	</select></td>";
}
$zoekScherm[] = "	<td>&nbsp;</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td colspan='4'>";
$zoekScherm[] = "	<input type='checkbox' name='error' value='ja' ". ($error == 'ja' ? ' checked' : '') ."> Error&nbsp;&nbsp;&nbsp;";
$zoekScherm[] = "	<input type='checkbox' name='info' value='ja' ". ($info == 'ja' ? ' checked' : '') ."> Info&nbsp;&nbsp;&nbsp;";
$zoekScherm[] = "	<input type='checkbox' name='debug' value='ja' ". ($debug == 'ja' ? ' checked' : '') ."> Debug";
$zoekScherm[] = "	</td>";
if(isset($opdracht)) {
	if(isset($huis)) {
		$zoekScherm[] = "	<td colspan='3' align='right'>huis op <a href='http://funda.nl/$huis'>funda.nl</a> | <a href='edit.php?id=$huis'>lokaal</a></td>";
	} else {
		$zoekScherm[] = "	<td colspan='3'>&nbsp;</td>";	
	}
	$zoekScherm[] = "	<td>&nbsp;</td>";	
} else {
	$zoekScherm[] = "	<td colspan='2'>&nbsp;</td>";	
}
$zoekScherm[] = "</tr>";
$zoekScherm[] = "</table>";
$zoekScherm[] = "</form>";

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td valign='top' align='center' colspan=2>". showBlock(implode("\n", $zoekScherm)) ."</td>\n";
echo "</tr>\n";
echo "<tr>\n";

if($deel_1 != '') {
	echo "	<td width='50%' valign='top' align='center'>". showBlock("<table>". $deel_1 ."</table>") ."</td>\n";
	echo "	<td width='50%' valign='top' align='center'>". showBlock("<table>". $deel_2 ."</table>") ."</td>\n";
} else {
	echo "	<td width='100%' valign='top' align='center'>". showBlock("<table>". $deel_2 ."</table>") ."</td>\n";
}
echo "</tr>\n";
echo $HTMLFooter;
