<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

//$sql = "SELECT * FROM $TableHuizen,$TableResultaat WHERE $TableHuizen.$HuizenID = $TableResultaat.$ResultaatID AND ($TableResultaat.$ResultaatZoekID = '33' OR $TableResultaat.$ResultaatZoekID = '32' OR $TableResultaat.$ResultaatZoekID = '31') AND $TableHuizen.$HuizenOffline = '0' AND $TableHuizen.$HuizenWijk like '' GROUP BY $TableHuizen.$HuizenID ORDER BY $TableHuizen.$HuizenEind ASC LIMIT 0, 25";
$sql = "SELECT * FROM $TableHuizen WHERE $HuizenDetails = '1' AND $HuizenOffline = '0' ORDER BY $HuizenEind ASC LIMIT 0, 25";
$result	= mysql_query($sql);	
if($row = mysql_fetch_array($result)) {
	do {
		$url = 'http://www.funda.nl/'.$row[$HuizenID];
		
		$HTML[] = '<tr>';
		$HTML[] = '	<td><b>'. urldecode($row[$HuizenAdres]) ."</b> (". urldecode($row[$HuizenPlaats]) .")</td>";
		$HTML[] = '	<td>&nbsp;</td>';
		$HTML[] = "	<td><a href='$url' target='_blank'>open op funda.nl</a></td>";
		$HTML[] = '	<td width=20>&nbsp;</td>';
		//$HTML[] = "	<td><a href='edit.php?id=". $row[$HuizenID] ."' target='funda_detail'>details</a></td>";
		//$HTML[] = '	<td>&nbsp;</td>';
		$HTML[] = "	<td>zet <a href='changeState.php?state=offline&id=". $row[$HuizenID] ."' target='funda_state'>offline</a></td>";
		//$HTML[] = "	<td>zet <a href='changeState.php?state=verkocht&id=". $row[$HuizenID] ."' target='funda_state'>verkocht</a></td>";
		$HTML[] = '</tr>';
	} while($row = mysql_fetch_array($result));
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='8%'>&nbsp;</td>\n";
echo "<td width='84%' valign='top' align='center'>\n";
echo showBlock('<table>'.implode("\n", $HTML).'</table>');
echo "</td>\n";
echo "<td width='8%'>&nbsp;</td>\n";
echo "</tr>\n";
echo "</tr>\n";
echo $HTMLFooter;
