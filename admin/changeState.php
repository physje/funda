<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(!isset($_REQUEST['state']) OR !isset($_REQUEST['id'])) {
	$deel_1 = "Onvoldoende gegevens bekend";
} else {
	if($_REQUEST['state'] == 'verkocht')	$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '1' WHERE $HuizenID = ".$_REQUEST['id'];
	if($_REQUEST['state'] == 'offline')	$sql = "UPDATE $TableHuizen SET $HuizenOffline = '1' WHERE $HuizenID = ".$_REQUEST['id'];
	
	if(mysql_query($sql)) {
		$deel_1 = "Status aangepast";
	}	else {
		$deel_1 = "Status <b>niet</b> aangepast";
	}
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "</td>\n";
if($deel_2 != "") {
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock($deel_2);
	echo "</td>\n";
} else {
	echo "<td width='50%' valign='top' align='center'>\n";
	echo "&nbsp;";
	echo "</td>\n";
}
echo "</tr>\n";
echo $HTMLFooter;