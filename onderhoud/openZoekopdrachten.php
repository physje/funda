<?php
include_once(__DIR__.'/../include/config.php');
include_once(__DIR__.'/../include/HTML_TopBottom.php');

$db = connect_db();
$minUserLevel = 3;
$cfgProgDir = '../auth/';
#include($cfgProgDir. "secure.php");

$init = $close = false;

if(isset($_REQUEST['destroy'])) {
	$opdrachtenCookie = array();
	setcookie('zoekopdrachten', json_encode($opdrachtenCookie));
} elseif(isset($_COOKIE['zoekopdrachten'])) {	
	$opdrachtenCookie = json_decode($_COOKIE['zoekopdrachten'], true);
}

if(isset($_REQUEST['close'])) {
	$close = true;
}

# Als er nog geen sessie bestaat, deze aanmaken
if(!isset($opdrachtenCookie['init'])) {
	$opdrachtenCookie['init'] = true;
	$opdrachtenCookie['p'] = 0;
	$opdrachtenCookie['c'] = 0;
	$opdrachtenCookie['v'] = 0;
	$opdrachtenCookie['k'] = 0;
	$opdrachtenCookie['aO'] = array();
	$init = true;
}

# Als het formulier is ingevuld, de sessie-variabelen vullen
if(isset($_POST['openPages'])) {
	$key = 0;
	foreach($_POST['resale'] as $opdracht => $dummy) {
		$opdrachtenCookie['aO'][$key] = $opdracht;
		$opdrachtenCookie['startPSale'][$opdracht] = $_POST['startSale'][$opdracht];
		$opdrachtenCookie['eindPSale'][$opdracht] = $_POST['eindSale'][$opdracht];
		$opdrachtenCookie['startPSold'][$opdracht] = $_POST['startSold'][$opdracht];
		$opdrachtenCookie['eindPSold'][$opdracht] = $_POST['eindSold'][$opdracht];
		$key++;
	}
}

# Sessie-variabelen ophalen
$pagina			= $opdrachtenCookie['p'];
$counter		= $opdrachtenCookie['c'];
$verkocht		= $opdrachtenCookie['v'];
$key				= $opdrachtenCookie['k'];
$aOpdracht	= $opdrachtenCookie['aO'];

if(isset($_REQUEST['resetCounter'])) {
	$counter = 0;
}

setcookie('zoekopdrachten', json_encode($opdrachtenCookie));

echo "<html>\n";
echo "<head>\n";

if(!$init) {
	$opdracht = $aOpdracht[$key];
	
	if($verkocht == 0) {
		$einde = $opdrachtenCookie['eindPSale'][$opdracht];
	} else {
		$einde = $opdrachtenCookie['eindPSold'][$opdracht];
	}
					
	if($pagina < $einde) {
		$pagina++;
	} elseif($verkocht == 1) {
		$key++;
		$opdracht	= $aOpdracht[$key];
		$pagina		= $opdrachtenCookie['startPSale'][$opdracht];
		$verkocht	= 0;		
	} elseif($verkocht == 0) {
		$pagina		= $opdrachtenCookie['startPSold'][$opdracht];
		$verkocht	= 1;
	}	
	
	# Als er geen opdrachten meer zijn kan de pagina gesloten worden
	if(!isset($aOpdracht[$key]))	$close		= true;
	
	$OpdrachtData = getOpdrachtData($opdracht);
	$URL = $OpdrachtData['url']."&search_result=$pagina";
	
	if($verkocht == 1) {		
		$URL .= "&availability=%5B%22unavailable%22%5D";
	}
	
	$counter++;
	
	#echo '$pagina '. $pagina ."<br>\n";
	#echo '$opdracht '. $opdracht ."<br>\n";
	#echo '$verkocht '. $verkocht ."<br>\n";
	#echo '$key '. $key ."<br>\n";
	
	$opdrachtenCookie['p'] = $pagina;
	$opdrachtenCookie['c'] = $counter;
	$opdrachtenCookie['v'] = $verkocht;
	$opdrachtenCookie['k'] = $key;
	$opdrachtenCookie['aO'] = $aOpdracht;
	setcookie('zoekopdrachten', json_encode($opdrachtenCookie));
	
	#var_dump($_COOKIE);
					
	if(!$close) {
		if($counter < 10) {
			echo "<meta http-equiv=\"refresh\" content=\"0;URL=openZoekopdrachten.php\" />";
		}
	}
	
	echo "<title>". ($counter < 10 ? $OpdrachtData['naam'] ." [$pagina]" : "Actie"). "</title>\n";
	echo "</head>\n";
	echo "<body>\n";	
	if($close) {
		echo "<body onload=\"window.close();\">\n";
	} elseif(strlen($OpdrachtData['url']) > 2) {	
		echo "<body onload=\"window.open('". $URL ."', '_blank');\">\n";
	}
	
	echo "Pagina ". $pagina ." van ". $OpdrachtData['naam'] ."<br>\n";
	#echo $URL ."<br>\n";
	echo "<br>\n";
	echo "<a href='openZoekopdrachten.php?resetCounter'>Open de volgende opdrachten</a><br>\n";
	#echo "<br>\n";
	echo "<a href='openZoekopdrachten.php?destroy'>Begin overnieuw</a>\n";
} else {
	$Opdrachten = getZoekOpdrachten('');
	$preChecked = array(3, 14, 32, 33, 34, 35, 38, 41, 42);
	
	echo "<title>Zoekopdrachten</title>\n";
	echo "</head>\n";
	echo "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
	echo "<table border=0>\n";
	echo "<tr>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td colspan='2'><b>Te koop</b></td>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td colspan='2'><b>Verkocht</b></td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td>Eerste</td>\n";
	echo "	<td>Laatste</td>\n";	
	echo "	<td>&nbsp;</td>\n";
	echo "	<td>Eerste</td>\n";
	echo "	<td>Laatste</td>\n";
	echo "</tr>\n";
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData	= getOpdrachtData($OpdrachtID);
		$Huizen				= getHuizen($OpdrachtID, true, true);
		$aantal				= count($Huizen);
		
		$startP	= 0;
		$endPSale = ceil($aantal/15);
		$endPSold = ceil($aantal/150);
		
		echo "<tr>\n";
		echo "	<td><input type='checkbox' name='resale[$OpdrachtID]' value='1'". (in_array($OpdrachtID, $preChecked) ? ' checked' : '') .">". $OpdrachtData['naam'] ."</td>\n";
		echo "	<td><select name='startSale[$OpdrachtID]'>\n";
		for($p = 1 ; $p < ($endPSale+15) ; $p++) {
			echo "	<option value='$p'". ($p == $startP ? ' selected' : '') .">Pagina $p</option>\n";
		}
		echo "	</select></td>\n";
		echo "	<td><select name='eindSale[$OpdrachtID]'>\n";
		for($p = 1 ; $p < ($endPSale+15) ; $p++) {
			echo "	<option value='$p'". ($p == $endPSale ? ' selected' : '') .">Pagina $p</option>\n";
		}
		echo "	</select></td>\n";	
		echo "	<td>&nbsp;</td>\n";
		
		echo "	<td><select name='startSold[$OpdrachtID]'>\n";
		for($p = 1 ; $p < ($endPSold+15) ; $p++) {
			echo "	<option value='$p'". ($p == $startP ? ' selected' : '') .">Pagina $p</option>\n";
		}
		echo "	</select></td>\n";
		echo "	<td><select name='eindSold[$OpdrachtID]'>\n";
		for($p = 1 ; $p < ($endPSold+15) ; $p++) {
			echo "	<option value='$p'". ($p == $endPSold ? ' selected' : '') .">Pagina $p</option>\n";
		}
		echo "	</select></td>\n";			
		echo "</tr>\n";
	}
	echo "<tr>\n";
	echo "	<td colspan=6>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td colspan=6><input type='submit' name='openPages' value='Start'></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</form>\n";
}

echo "</body>\n";
echo "</html>\n";	

?>