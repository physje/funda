<?php
include_once(__DIR__.'/../include/config.php');
include_once(__DIR__.'/../include/HTML_TopBottom.php');

$db = connect_db();
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['i']) AND !isset($_REQUEST['close'])) {
	$i = $_REQUEST['i'];
	$p = $_REQUEST['p'];
	$c = $_REQUEST['c'];
	$v = $_REQUEST['v'];
} elseif(isset($_REQUEST['opdracht']) AND !isset($_REQUEST['close'])) {
	$opdracht = $_REQUEST['opdracht'];	
	$p = getParam('p', 1);
	$c = $_REQUEST['c'];
	$close = false;
} elseif(!isset($_REQUEST['close'])) {
	$i = 0;
	$p = 1;
	$c = 0;
	$v = 0;
}	

echo "<html>\n";
echo "<head>\n";

$filename = 'debug.txt';

if(!isset($_REQUEST['close'])) {
	if(!isset($_REQUEST['opdracht'])) {
		$Opdrachten = getZoekOpdrachten($_SESSION['account'], '', true);
		$OpdrachtID = $Opdrachten[$i];
	} else {
		$OpdrachtID = $opdracht;
	}

	$Huizen = getHuizen($OpdrachtID, true, true);
	$aantal = count($Huizen);

	$OpdrachtData = getOpdrachtData($OpdrachtID);

	# We beginnen met de URL voor de 1ste pagina (p = 1)
	$URL = $OpdrachtData['url']."&search_result=$p";
	
	# Vervolgens kijken wij wat de nieuwe URL moet worden
	#	- Er zijn meer dan 15 hits -> pagina ophogen
	# - v = 0 (betekent dat de verkochte huizen getoond moeten worden)
	# Geen van dat alles -> ga naar de volgende zoekopdracht (i++), begin weer bij pagina 1 (p=1), vraag de verkochte huizen op		
	if(($aantal/15) > $p) {
		$p++;
	} elseif($v == 0) {	
		$URL = $OpdrachtData['url'].'&availability=%5B%22unavailable%22%5D';
		$v=1;		
	} else {
		$i++;
		$p=1;		
		$v=0;
		$close = true;				
	}
		
	$c++;

	if(count($Opdrachten) > $i OR (isset($_REQUEST['opdracht']) AND !$close)) {
		if($c < 10) {
			if(isset($_REQUEST['opdracht'])) {
				echo "<meta http-equiv=\"refresh\" content=\"0;URL=openAllZoekopdrachten.php?opdracht=$opdracht&p=$p&c=$c\" />";
			} else {
				echo "<meta http-equiv=\"refresh\" content=\"0;URL=openAllZoekopdrachten.php?i=$i&p=$p&c=$c&v=$v\" />";
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

echo "Pagina ". ($p-1) ." van ". $OpdrachtData['naam'] .' ('. $aantal ." hits)<br>\n";
if(isset($_REQUEST['opdracht'])) {
	echo "<a href='openAllZoekopdrachten.php?opdracht=$opdracht&p=$p&c=$c'>Open de volgende opdrachten</a>\n";
} else {
	echo "<a href='openAllZoekopdrachten.php?i=$i&p=$p&c=0&v=$v'>Open de volgende opdrachten</a>\n";
}
echo "</body>\n";
echo "</html>\n";

?>
