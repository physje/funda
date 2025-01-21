<?php
include_once(__DIR__.'/../include/config.php');
include_once(__DIR__.'/../include/HTML_TopBottom.php');

$db = connect_db();
#$minUserLevel = 3;
#$cfgProgDir = 'auth/';
#include($cfgProgDir. "secure.php");

$filename = 'debug.txt';

if(!isset($_REQUEST['close'])) {
	$pagina		= getParam('p', 1);
	$counter	= getParam('c', 0);
	$verkocht	= getParam('v', 0);
	$opdracht	= getParam('o', 0);
	
	if(!isset($_REQUEST['opdracht'])) {
		$Opdrachten = getZoekOpdrachten('');
		$OpdrachtID = $Opdrachten[$opdracht];
	} else {
		$OpdrachtID = $opdracht;
	}

	$Huizen = getHuizen($OpdrachtID, true, true);
	$aantal = count($Huizen);

	$OpdrachtData = getOpdrachtData($OpdrachtID);

	# We beginnen met de URL voor de 1ste pagina (p = 1)
	$URL = $OpdrachtData['url']."&search_result=$pagina";
	
	# Vervolgens kijken wij wat de nieuwe URL moet worden
	#	- Er zijn meer dan 15 hits -> pagina ophogen
	# - v = 0 (betekent dat de verkochte huizen getoond moeten worden)
	# Geen van dat alles -> ga naar de volgende zoekopdracht (i++), begin weer bij pagina 1 (p=1), vraag de verkochte huizen op		
	if(($aantal/15) > $pagina) {
		$pagina++;
	#} elseif($verkocht == 0) {	
	#	$URL = $OpdrachtData['url'].'&availability=%5B%22unavailable%22%5D';
	#	$verkocht=1;		
	} else {
		$opdracht++;
		$pagina=1;		
		$verkocht=0;
		$close = true;				
	}
		
	$counter++;

	if(count($Opdrachten) > $opdracht OR (isset($_REQUEST['opdracht']) AND !$close)) {
		if($counter < 10) {
			if(isset($_REQUEST['opdracht'])) {
				echo "<meta http-equiv=\"refresh\" content=\"0;URL=openAllZoekopdrachten.php?o=$opdracht&p=$pagina&c=$counter\" />";
			} else {
				echo "<meta http-equiv=\"refresh\" content=\"0;URL=openAllZoekopdrachten.php?o=$opdracht&p=$pagina&c=$counter&v=$verkocht\" />";
			}
		}
	} else {
		echo "<meta http-equiv=\"refresh\" content=\"0;URL=openAllZoekopdrachten.php?close\" />";
	}
}
   
echo "</head>\n";

if(isset($_REQUEST['close'])) {
	echo "<body onload=\"window.close();\">\n";
} else {	
	echo "<body onload=\"window.open('". $URL ."', '_blank');\">\n";
}

echo "Pagina ". ($pagina-1) ." van ". $OpdrachtData['naam'] .' ('. $aantal ." hits)<br>\n";
if(isset($_REQUEST['opdracht'])) {
	echo "<a href='openAllZoekopdrachten.php?o=$opdracht&p=$pagina'>Open de volgende opdrachten</a>\n";
} else {
	echo "<a href='openAllZoekopdrachten.php?o=$opdracht&p=$pagina&v=$verkocht'>Open de volgende opdrachten</a>\n";
}
echo "</body>\n";
echo "</html>\n";

?>
