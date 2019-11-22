<?php
include_once(__DIR__.'/../include/config.php');
include_once($cfgGeneralIncludeDirectory.'/class.phpmailer.php');
include_once($cfgGeneralIncludeDirectory.'/class.phpPushover.php');
include_once(__DIR__ . '/../include/HTML_TopBottom.php');
$db = connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

if(isset($_REQUEST['type'])) { $type = $_REQUEST['type']; } else { $type = 'alles'; }

if($type == 'regio') {
	$url = "https://vastgoeddashboard.kadaster.nl/vgd/woningen/PrijsindexPerRegio.csv?years_back=1";
} else {
	$url = "https://vastgoeddashboard.kadaster.nl/vgd/woningen/prijsindex.csv?years_back=1";
}
//$csvFile = 'local_PBK.csv';
//$fp = fopen($csvFile, 'w+');
//fwrite($fp, file_get_contents_retry($url));
//fclose($fp);

$data = file_get_contents_retry($url);
$rijen = explode("\n", $data);
array_shift($rijen);

foreach($rijen as $rij) {
	// Voor later
	$oud_prijsindex = $prijsindex;
	$velden = explode(';', $rij);
	
	if($type == 'regio') {
		# periode;regio;regio_id;maand_op_maand;jaar_op_jaar;prijsindex;soort
		$periode		= $velden[0];
		$regio			= $velden[1];
		$prijsindex	= $velden[5];
		$soort			= $velden[6];
	} else {
		# periode;maand_op_maand;jaar_op_jaar;prijsindex;soort
		$periode		= $velden[0];
		$prijsindex	= $velden[3];
		$regio			= $velden[4];
	}	
	
	$jaar = substr($periode, 0, 4);
	$kwartaal = substr($periode, 4);
	
	if($kwartaal == 'Q1') {
		$start =  mktime (0, 0, 0, 1, 1, $jaar);
		$eind	=  mktime (23, 59, 59, 3, 31, $jaar);
	} elseif($kwartaal == 'Q2') {
		$start =  mktime (0, 0, 0, 4, 1, $jaar);
		$eind	=  mktime (23, 59, 59, 6, 30, $jaar);
	} elseif($kwartaal == 'Q3') {
		$start =  mktime (0, 0, 0, 7, 1, $jaar);
		$eind	=  mktime (23, 59, 59, 9, 30, $jaar);
	} elseif($kwartaal == 'Q4') {
		$start =  mktime (0, 0, 0, 10, 1, $jaar);
		$eind	=  mktime (23, 59, 59, 12, 31, $jaar);
	} else {
		$start =  mktime (0, 0, 0, $kwartaal, 1, $jaar);
		$eind	=  mktime (0, 0, 0, ($kwartaal+1), 1, $jaar) - 1;
	}
	
	echo $jaar .';'. $kwartaal .' -> '. $prijsindex.' -> '. $regio .'<br>';
	
	$sql_check = "SELECT * FROM $TablePBK WHERE $PBKComment like '". $periode .', '. $regio ."'";
	$result = mysqli_query($db, $sql_check);
	if(mysqli_num_rows($result) == 0) {
		$sql = "DELETE FROM $TablePBK WHERE $PBKStart = $start AND $PBKRegio like '$regio'";			
		mysqli_query($db, $sql);
			
		$sql = "INSERT INTO $TablePBK ($PBKStart, $PBKEind, $PBKRegio, $PBKWaarde, $PBKComment) VALUES ($start, $eind, '$regio', $prijsindex, '". $periode .', '. $regio ."')";
		if(!mysqli_query($db, $sql)) {
			echo $sql;
		}
		
		if($type != 'regio') {
			$newEntry = true;
		}
	}
}

toLog('info', '', '', 'Kadaster PBK-ingelezen');

# Als de ingelezen data "nieuwer" is dan de data in de dB, is er nieuwe data en moet er een mail worden gestuurd.
if($newEntry) {
	$percentage = (100*($prijsindex-$oud_prijsindex))/$prijsindex;
	if($percentage > 0) {
		$percentageString = '+'.number_format ($percentage,1);
	} else {
		$percentageString = number_format ($percentage,1);
	}
	send2Pushover(array('title' => 'Prijsindex', 'message' => "In ". strtolower(strftime ('%B %Y', $start)) ." is de prijsindex van $oud_prijsindex naar $prijsindex gegaan (". $percentageString .'%)'), array(1));
}

?>