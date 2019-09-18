<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['all'])) {
	$sql = "SELECT * FROM $TableHuizen WHERE ($HuizenDetails = '1' OR $HuizenDetails = '2') AND $HuizenOffline = '0' ORDER BY $HuizenEind ASC LIMIT 0, 60";
} else {	
	$opdrachten = getZoekOpdrachten($_SESSION['account'], '');	
	foreach($opdrachten as $OpdrachtID) {
		$members = getMembers4Opdracht($OpdrachtID, 'push');
		if(count($members) > 0) {
			$resultaat[] = "$TableResultaat.$ResultaatZoekID = '$OpdrachtID'";
		}
	}	
	$sql = "SELECT * FROM $TableHuizen,$TableResultaat WHERE $TableHuizen.$HuizenID = $TableResultaat.$ResultaatID AND (". implode(" OR ", $resultaat) .") AND $HuizenDetails = '1' AND $HuizenOffline = '0' GROUP BY $TableHuizen.$HuizenID ORDER BY $TableHuizen.$HuizenEind ASC LIMIT 0, 25";
}
$result	= mysqli_query($db, $sql);	
if($row = mysqli_fetch_array($result)) {
	$HTML[] = '<ol>';
	$HTML[] = '<table>';
	$rij=0;
	do {
		# Om in een lange lijst toch nog een beetje overzicht te hebben voeg ik
		# om de 20 huizen een balk toe
		# Bij mij is 20 ook het maximaal aantal huizen wat ik in 1x kan uploaden
		if($rij == 20) {
		    $HTML[] = "<tr>";
		    $HTML[] = "	<td colspan='5'><hr></td>";
		    $HTML[] = "</tr>";
		    $rij=1;
		} else {
		    $rij++;
		}
		
		$url = 'http://www.funda.nl/'.$row[$HuizenID];
		$HTML[] = '<tr>';
		$HTML[] = '	<td><li><b>'. urldecode($row[$HuizenAdres]) ."</b> (". urldecode($row[$HuizenPlaats]) .")</li></td>";
		$HTML[] = '	<td>&nbsp;</td>';
		$HTML[] = "	<td><a href='$url' target='_blank'>open op funda.nl</a></td>";
		$HTML[] = '	<td width=20>&nbsp;</td>';
		$HTML[] = "	<td>zet <a href='changeState.php?state=offline&id=". $row[$HuizenID] ."' target='funda_state'>offline</a></td>";
		$HTML[] = '</tr>';
		
	} while($row = mysqli_fetch_array($result));	
	$HTML[] = '</table>';
	$HTML[] = '</ol>';
} else {
	$HTML[] = "<a href='". $_SERVER['PHP_SELF'] ."?all'>Geef overzicht van alle huizen</a>";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='8%'>&nbsp;</td>\n";
echo "<td width='84%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $HTML));
echo "</td>\n";
echo "<td width='8%'>&nbsp;</td>\n";
echo "</tr>\n";
echo "</tr>\n";
echo $HTMLFooter;
