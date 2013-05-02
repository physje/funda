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
	//$rij .= "	<td>". $row[$LogType] ."</td>";
	//$rij .= "	<td>&nbsp;</td>\n";
	//$rij .= "	<td>". $row[$LogOpdracht] ."</td>";
	//$rij .= "	<td>&nbsp;</td>\n";
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

$Opdrachten	= getZoekOpdrachten('', 1);

$zoekScherm = "<form method='post'>\n";
$zoekScherm .= "<table border=0 align='center'>\n";
$zoekScherm .= "<tr>\n";
$zoekScherm .= "	<td><b>Begindatum</b></td>\n";
$zoekScherm .= "	<td>&nbsp;</td>\n";
$zoekScherm .= "	<td><b>Einddatum</b></td>\n";
$zoekScherm .= "	<td>&nbsp;</td>\n";
$zoekScherm .= "	<td><b>Zoekopdracht</b></td>\n";
$zoekScherm .= "	<td>&nbsp;</td>\n";
$zoekScherm .= "	<td><b>Huis</b></td>\n";
$zoekScherm .= "	<td>&nbsp;</td>\n";
$zoekScherm .= "	<td rowspan='4'><input type='submit' value='Zoeken' name='submit'></td>\n";
$zoekScherm .= "</tr>\n";
$zoekScherm .= "<tr>\n";
$zoekScherm .= "	<td><select name='bDag'>\n";
for($d=1 ; $d<=31 ; $d++)	{	$zoekScherm .= "<option value='$d'". ($d == $bDag ? ' selected' : '') .">$d</option>\n";	}
$zoekScherm .= "	</select><select name='bMaand'>\n";
for($m=1 ; $m<=12 ; $m++)	{	$zoekScherm .= "<option value='$m'". ($m == $bMaand ? ' selected' : '') .">". strftime("%B", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
$zoekScherm .= "	</select><select name='bJaar'>\n";
for($j=(date('Y') - 1) ; $j<=(date('Y') + 1) ; $j++)	{	$zoekScherm .= "<option value='$j'". ($j == $bJaar ? ' selected' : '') .">$j</option>\n";	}
$zoekScherm .= "	</select></td>\n";
$zoekScherm .= "	<td>&nbsp;</td>\n";
$zoekScherm .= "	<td><select name='eDag'>\n";
for($d=1 ; $d<=31 ; $d++)	{	$zoekScherm .= "<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>\n";	}
$zoekScherm .= "	</select><select name='eMaand'>\n";
for($m=1 ; $m<=12 ; $m++)	{	$zoekScherm .= "<option value='$m'". ($m == $eMaand ? ' selected' : '') .">". strftime("%B", mktime(0,0,0,$m,1,2006)) ."</option>\n";	}
$zoekScherm .= "	</select><select name='eJaar'>\n";
for($j=(date('Y') - 1) ; $j<=(date('Y') + 1) ; $j++)	{	$zoekScherm .= "<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>\n";	}
$zoekScherm .= "	</select></td>\n";
$zoekScherm .= "	<td>&nbsp;</td>\n";
$zoekScherm .= "	<td><select name='opdracht'>\n";
$zoekScherm .= "	<option value=''>Alle</option>\n";
foreach($Opdrachten as $OpdrachtID) {
	$OpdrachtData = getOpdrachtData($OpdrachtID);
	$zoekScherm .= "<option value='$OpdrachtID'". ($opdracht == $OpdrachtID ? ' selected' : '') .">". $OpdrachtData['naam'] ."</option>\n";
}
$zoekScherm .= "	</select></td>\n";
$zoekScherm .= "	<td>&nbsp;</td>\n";
if(isset($opdracht)) {
	$Huizen			= getHuizen($opdracht);

	$zoekScherm .= "	<td><select name='huis'>\n";
	$zoekScherm .= "	<option value=''>Alle</option>\n";
	foreach($Huizen as $huisID) {
		$HuisData = getFundaData($huisID, $opdracht);
		$zoekScherm .= "	<option value='$huisID'". ($huis == $huisID ? ' selected' : '') .">". $HuisData['adres'] ."</option>\n";
	}
	
	$zoekScherm .= "	</select></td>\n";
} else {
	$zoekScherm .= "	<td>&nbsp;</td>\n";
}
$zoekScherm .= "	<td>&nbsp;</td>\n";
$zoekScherm .= "</tr>\n";
$zoekScherm .= "<tr>\n";
$zoekScherm .= "	<td colspan='7'>";
$zoekScherm .= "	<input type='checkbox' name='error' value='ja' ". ($error == 'ja' ? ' checked' : '') ."> Error&nbsp;&nbsp;&nbsp;";
$zoekScherm .= "	<input type='checkbox' name='info' value='ja' ". ($info == 'ja' ? ' checked' : '') ."> Info&nbsp;&nbsp;&nbsp;";
$zoekScherm .= "	<input type='checkbox' name='debug' value='ja' ". ($debug == 'ja' ? ' checked' : '') ."> Debug";
$zoekScherm .= "	</td>\n";
$zoekScherm .= "	<td>&nbsp;</td>\n";
$zoekScherm .= "</tr>\n";
$zoekScherm .= "</table>\n";
$zoekScherm .= "</form>\n";

echo $HTMLHeader;
echo "<tr>\n";
echo "<td valign='top' align='center' colspan=2>\n";
echo showBlock($zoekScherm);
echo "</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "</td>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_2);
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

?>