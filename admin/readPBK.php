<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.
	
$url = "https://app.kpilibrary.com/a/governanceobserver/perf-kpi-modal.asp?id=6049725&tc=wr%2FjQMkX18DQka%2FMt1cM82KsdarjlBs9SHeykeXeBco%3D&db=171667&print=1";

$content = file_get_contents_retry($url);

$tabel	= getString('align="left">Note</th></tr>', '</table></td></tr></table><br />', $content, 0);

$rijen = explode('"><td class="d-tbl-', $tabel[0]);

$rijen = array_slice ($rijen, 1);

if(count($rijen) > 100) {
	mysql_query("TRUNCATE TABLE $TablePBK");
}	

foreach($rijen as $rij) {
	$tijd	= getString('" align="left">', '</td>', $rij, 0);
	$perc	= getString('" align="right">', '</td>', $rij, 0);
	
	$maand = getString('', ', ', $tijd[0], 0);
	$jaar	= getString(', ', '', $tijd[0], 0);
	
	switch ($maand[0]) {
		case "December";
			$Mnd = 12;
			break;
		case "November";
			$Mnd = 11;
			break;
		case "October";
			$Mnd = 10;
			break;
		case "September";
			$Mnd = 9;
			break;
		case "August";
			$Mnd = 8;
			break;
		case "July";
			$Mnd = 7;
			break;
		case "June";
			$Mnd = 6;
			break;
		case "May";
			$Mnd = 5;
			break;
		case "April";
			$Mnd = 4;
			break;
		case "March";
			$Mnd = 3;
			break;
		case "February";
			$Mnd = 2;
			break;
		case "January";
			$Mnd = 1;
			break;
	}		
			
	//echo  date("d-m-Y", mktime(0,0,0,$Mnd,1,$jaar[0])) .'|'. date("d-m-Y", ) .' -> '. $perc[0] .'<br>';
	$sql = "INSERT INTO $TablePBK ($PBKStart, $PBKEind, $PBKWaarde, $PBKComment) VALUES ('". mktime(0,0,0,$Mnd,1,$jaar[0]) ."', '". mktime(23,59,59,($Mnd+1),0,$jaar[0]) ."', '". $perc[0] ."', '". $maand[0] .' '. $jaar[0] ."')";
	mysql_query($sql);
}

toLog('info', '', '', 'Kadaster PBK-ingelezen');

?>