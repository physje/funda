<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(!isset($_REQUEST['bDag']) OR !isset($_REQUEST['bMaand']) OR !isset($_REQUEST['bJaar'])) {
	$bDag = date('d');
	$bMaand = date('m');
	$bJaar = date('Y');	
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

$dateSelection = makeDateSelection('', '', $bDag, $bMaand, $bJaar, '', '', $eDag, $eMaand, $eJaar);

if(isset($_REQUEST['prijs'])) {
	$price_2 = formatPrice(corrigeerPrice(mktime(0,0,0,$bMaand, $bDag, $bJaar), $_REQUEST['prijs'], mktime(0,0,0,$eMaand, $eDag, $eJaar), $_REQUEST['regio']));
} else {
	$price_2 = '&nbsp;';
}
#$regios = array('Totaal', 'Amsterdam', 'Drenthe', 'Flevoland', 'Friesland', 'Gelderland', 'Gravenhage', 'Groningen', 'Limburg', 'Midden-Nederland', 'Noord-Brabant', 'Noord-Holland', 'Noord-Nederland', 'Overijssel', 'Rotterdam', 'Utrecht', 'West-Nederland', 'Zeeland', 'Zuid-Holland', 'Zuid-Nederland');

$zoekScherm[] = "<form method='post' action='". $_SERVER['PHP_SELF'] ."'>";
$zoekScherm[] = "<table border=0 align='center'>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><b>Van</b></td>";
$zoekScherm[] = "	<td rowspan='3'>&nbsp;</td>";
$zoekScherm[] = "	<td rowspan='2'><input type='submit' value='Corrigeer' name='submit'></td>";
$zoekScherm[] = "	<td rowspan='3'>&nbsp;</td>";
$zoekScherm[] = "	<td><b>Naar</b></td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td>". $dateSelection[0] ."</td>";
$zoekScherm[] = "	<td>". $dateSelection[1] ."</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "<tr>";
$zoekScherm[] = "	<td><input type='text' name='prijs' value='". (isset($_REQUEST['prijs']) ? $_REQUEST['prijs'] : '') ."'></td>";
$zoekScherm[] = "	<td>";

# Kijk of er PBK-data aanwezig is
# Zo niet, geef dan link naar inladen PBK-data
$sql_empty	= "SELECT * FROM $TablePBK WHERE $PBKCategorie LIKE 'Totaal'";
$result_empty	= mysqli_query($db, $sql_empty);
if(mysqli_num_rows($result_empty) > 0) {
	$zoekScherm[] = "	<select name='regio'>";
	$zoekScherm[] = "		<option value='Totaal'". ((isset($_REQUEST['regio']) AND $_REQUEST['regio'] == 'Totaal') ? ' selected' : '') .">Heel Nederland</option>";
	
	# Vraag de verschillende groepen op
	$sql_groepen	= "SELECT $PBKCategorie FROM $TablePBK WHERE $PBKCategorie NOT LIKE '' AND $PBKCategorie NOT LIKE 'Totaal' GROUP BY $PBKCategorie";
	$result_group	= mysqli_query($db, $sql_groepen);
	
	if($row_groep = mysqli_fetch_array($result_group)) {
		do {
			$zoekScherm[] = "	<optgroup label='". ucfirst($row_groep[$PBKCategorie]) ."'>";
			
			# Zoek welke regio's er binnen deze groep bestaan
			$sql = "SELECT $PBKRegio FROM $TablePBK WHERE $PBKCategorie like '". $row_groep[$PBKCategorie] ."' AND $PBKRegio NOT LIKE '' GROUP BY $PBKRegio ORDER BY $PBKRegio";		
			$result = mysqli_query($db, $sql);
			$row = mysqli_fetch_array($result);
			do {
				$regio = $row[$PBKRegio];
				$zoekScherm[] = "		<option value='$regio'". ((isset($_REQUEST['regio']) AND $regio == $_REQUEST['regio']) ? ' selected' : '') .">$regio</option>";
			} while($row = mysqli_fetch_array($result));
			
			$zoekScherm[] = "	</optgroup>";
		} while($row_groep = mysqli_fetch_array($result_group));
	}
	$zoekScherm[] = "	</select>";
} else {
	$zoekScherm[] = "<a href='readKadasterePBK.php'>PBK-gegevens</a> ontbreken";
}

$zoekScherm[] = "	</td>";
$zoekScherm[] = "	<td>$price_2</td>";
$zoekScherm[] = "</tr>";
$zoekScherm[] = "</table>";
$zoekScherm[] = "</form>";

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td width='25%'>&nbsp;</td>\n";
echo "	<td valign='top' align='center' colspan=2>". showBlock(implode("\n", $zoekScherm)) ."</td>\n";
echo "	<td width='25%'>&nbsp;</td>\n";
echo "</tr>\n";
echo $HTMLFooter;