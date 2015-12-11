<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

$HTML[] = "<table>";

for($h=0 ; $h<24 ; $h++){
	$data = $Opdrachten = $termen = array();
	$Opdrachten = getZoekOpdrachten('', $h);
	
	foreach($Opdrachten as $opdracht) {
		$data = getOpdrachtData($opdracht);
		$termen[] = "<a href='edit_opdrachten.php?id=$opdracht'>". $data['naam'] ."</a>";
	}
	
	if(is_array($termen)) {
		$temp = implode('<br>', $termen);
	} else {
		$temp = "&nbsp;";
	}
	
	$HTML[] = "<tr>";
	$HTML[] = "	<td valign='top'>$h</td>";
	$HTML[] = "	<td>$temp</td>";
	$HTML[] = "</tr>";		
}

$HTML[] = "</table>";

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td>". showBlock(implode("\n", $HTML)) ."</td>\n";
echo "</tr>\n";
echo $HTMLFooter;