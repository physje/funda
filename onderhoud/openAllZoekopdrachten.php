<?php
include_once(__DIR__.'/../include/config.php');
include_once(__DIR__.'/../include/HTML_TopBottom.php');

$db = connect_db();
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$filename = 'debug.txt';

$meta_URL = '';
$maxPages = 10;

if(!isset($_REQUEST['p']) AND !isset($_REQUEST['close'])) {	
	$debug = fopen($filename, 'w+');
	fwrite($debug, "-------------------------\n   ". date('d-m-Y H:i:s') ."\n-------------------------\n");
	fclose($debug);
}

if(!isset($_REQUEST['close'])) {
	$pagina		= getParam('p', 1);
	$counter	= getParam('c', 0);
	$verkocht	= getParam('v', 0);
	$opdracht	= getParam('o', 0);
	
	$debug = fopen($filename, 'a+');
	#fwrite($debug, 'P : '. $pagina ."\n");
	#fwrite($debug, 'C : '. $counter ."\n");
	#fwrite($debug, 'V : '. $verkocht ."\n");
	#fwrite($debug, 'O : '. $opdracht ."\n");
		
	$Opdrachten = getZoekOpdrachten($_SESSION['account'], '', true);
	$OpdrachtID = $Opdrachten[$opdracht];	
	$Huizen = getHuizen($OpdrachtID, true, true);	
	# Om beetje speling te houden tel ik 10% op bij het aantal huizen in de dB
	# Mochten er bv 45 huizen zijn (wat net 3 pagina's past) dan is er een kans dat er
	# ondertussen 46 huizen te koop staan en pagina 4 ook geopend moet worden.
	# Daarom paar huizen meer om niet te weinig pagina's te openen.
	$nrHuizen = count($Huizen);
	$nrPaginas = ceil(1.1*$nrHuizen/15);

	//if($opdracht >= count($Opdrachten)) {
	//	$close = true;
	//}
	
	if($verkocht == 0) {		
		$OpdrachtData = getOpdrachtData($OpdrachtID);
	
		if($nrPaginas >= $pagina) {		
			$open_URL = $OpdrachtData['url']."&search_result=$pagina";
			$pagina++;
		} else {			
			$open_URL = $OpdrachtData['url'].'&availability=%5B%22unavailable%22%5D';
			$verkocht = 1;
			$pagina = 1;	
		}
	} else {
		$opdracht++;
		$OpdrachtID = $Opdrachten[$opdracht];
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		$open_URL = $OpdrachtData['url']."&search_result=$pagina";
		
		$verkocht = 0;		
		$pagina++;
	}
	
	if($opdracht >= count($Opdrachten)) {
		$close = true;
	}
	
	if(!$close) {
		$counter++;	
		
		$data['o'] = $opdracht;
		$data['v'] = $verkocht;
		$data['p'] = $pagina;
		$data['c'] = $counter;
	} else {
		$data['close'] = true;
	}
			
	$meta_URL = $_SERVER['PHP_SELF'] ."?". http_build_query($data);
			
	fwrite($debug, $pagina.'|'.$counter.'|'.$verkocht.'|'.$opdracht.'|'.$close.'|'.$open_URL ."\n");
	#fwrite($debug, 'Open : '. $open_URL ."\n");
	fwrite($debug, 'Meta : '. $meta_URL ."\n");
	#fwrite($debug, "---\n");
	fclose($debug);	
} else {
	$meta_URL = $_SERVER['PHP_SELF'] ."?close";
}

echo "<html>\n";
echo "<head>\n";
if($counter < $maxPages AND !isset($_REQUEST['close'])) {
	echo "<meta http-equiv=\"refresh\" content=\"0;URL=". $meta_URL ."\" />";
}
echo "</head>\n";

if(isset($_REQUEST['close'])) {
	echo "<body onload=\"window.close();\">\n";
} else {	
	echo "<body onload=\"window.open('". $open_URL ."', '_blank');\">\n";

	#foreach($data as $key => $value) {
	#	echo $key .":". $value ."<br>\n";
	#}
	#echo "nr Paginas : ". $nrPaginas ."<br>\n";
	#echo $open_URL ."<br>\n";
	echo ($verkocht == 0 ? "Pagina ". ($pagina-1) ."/". $nrPaginas : "Verkochte huizen") ." van ". $OpdrachtData['naam'].($verkocht == 0 ? ' ('. $nrHuizen .' hits)' : '')."<br>\n";

	if($counter == $maxPages) {
		$data['c'] = 0;
		echo "<a href='". $_SERVER['PHP_SELF'] ."?". http_build_query($data) ."'>Open de volgende opdrachten</a>\n"; 
	}
}

echo "</body>\n";
echo "</html>\n";


/*
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
*/

?>
