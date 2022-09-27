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
} elseif(!isset($_REQUEST['close'])) {
	$i = 0;
	$p = 1;
}	

echo "<html>\n";
echo "<head>\n";

if(!isset($_REQUEST['close'])) {
	$Opdrachten = getZoekOpdrachten($_SESSION['account'], '', true);
	$OpdrachtID = $Opdrachten[$i];

	$Huizen = getHuizen($OpdrachtID, true, true);
	$aantal = count($Huizen);

	$OpdrachtData = getOpdrachtData($OpdrachtID);
	$URL = $OpdrachtData['url']."p$p/";

	if(($aantal/15) > $p) {
		$p++;
	} else {
		$i++;
		$p=1;
	}

	if(count($Opdrachten) > $i) {	
		echo "<meta http-equiv=\"refresh\" content=\"0;URL=openAllZoekopdrachten.php?i=$i&p=$p\" />";
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
echo $aantal ."<br>\n";
echo "</body>\n";
echo "</html>\n";

?>
