<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('../general_include/class.phpmailer.php');
include_once('../general_include/class.html2text.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
connect_db();

/*
$fundaID	= 48748024;
$data			= getFundaData($fundaID);
$open			= getNextOpenhuis($fundaID);

$Item  = "<table width='100%'>\n";
$Item .= "<tr>\n";
$Item .= "	<td align='center'><img src='". $data['thumb'] ."'></td>\n";
$Item .= "	<td align='center'><a href='http://funda.nl/". $fundaID ."'>". $data['adres'] ."</a>, ". $data['plaats'] ."<br>\n";
$Item .= 		$data['PC_c'].$data['PC_l'] ." (". $data['wijk'] .")<br>\n";
//$Item .= '	<b>'. strftime("%a %e %b %k:%M", $open[0]) ." - ". strftime("%k:%M", $open[1]) ."</b> (<a href='". $ScriptURL ."admin/makeCalendar.php?id=". $fundaID ."'>iCal</a>)</td>\n";
$Item .= '	<b>'. date("d-m-Y H:i", $open[0]) ." - ". date("H:i", $open[1]) ."</b> (<a href='". $ScriptURL ."admin/makeCalendar.php?id=". $fundaID ."'>iCal</a>)</td>\n";
//$Item .= '	<b>'. $open[0] ." - ". $open[1] ."</b> (<a href='". $ScriptURL ."admin/makeCalendar.php?id=". $fundaID ."'>iCal</a>)</td>\n";
$Item .= "</tr>\n";
$Item .= "</table>\n";

echo showBlock($Item);
*/

$testarray[] = 'een';
//$testarray[] = 'twee';
//$testarray[] = 'drie';
//$testarray[] = 'vier';

echo implode2(', ',' & ',$testarray);

?>