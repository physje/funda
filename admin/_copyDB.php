<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../../general_include/class.phpmailer.php');

include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

$sql = "SELECT * FROM `funda_huizen` GROUP BY `funda_id`";
$result = mysql_query($sql);
if($row = mysql_fetch_array($result)) {
	do {
		$funda_id = $row['funda_id'];
		
		if(!knownHouse($funda_id)) {		
			// Vraag prijshistorie op, die heb ik zo nodig
			$prijzen = getPriceHistory($funda_id);
			
			//echo '['. current($prijzen) .']';
			    	
			// Vraag welke huizen er allemaal in de oude dB staan		
			$sql_huis = "SELECT * FROM `funda_huizen` WHERE `funda_id` like '$funda_id'";
			$result_huis = mysql_query($sql_huis);
			if($row_huis = mysql_fetch_array($result_huis)) {
				$begin	= $eind = $data = $coord = array();
				
				do {				
					$data['id']			= $funda_id;
					$data['prijs']	= current($prijzen);
					
					if(!addHouse($data, $row_huis['opdracht'])) {
						echo 'Toevoegen van '. $funda_id .' aan zoekpdracht '. $row_huis['opdracht'].' ging niet goed<br>';
					}
					
					$begin[]	= $row_huis['start'];
					$eind[]		= $row_huis['eind'];				
				} 
				while($row_huis = mysql_fetch_array($result_huis));			
			}
			
			// Vraag 'extendedDetails op'		
			//$data['id']			=	$funda_id;
			$data['url']		= urldecode($row['url']);
			$data['adres']	= urldecode($row['adres']);
			$data['PC_c']		= urldecode($row['PC_cijfers']);
			$data['PC_l']		= urldecode($row['PC_letters']);
			$data['plaats']	= urldecode($row['plaats']);
			$data['thumb']	= urldecode($row['thumb']);
			$data['begin']	= min($begin);
			$data['eind']		= max($eind);
							
			$coord[0] = $row['N_deg'];
			$coord[1] = $row['N_dec'];
			$coord[2] = $row['O_deg'];
			$coord[3] = $row['O_dec'];
			
			$moreData = extractDetailedFundaData("http://www.funda.nl". $data['url']);
			$moreData['wijk'] = urldecode($row['wijk']);
			
			// Kopieer huis naar db 'new_funda_huizen'
			if(!saveHouse($data, $moreData)) {
				echo 'Toevoegen van '. $funda_id .' aan het script ging niet goed<br>';
			}
			
			if(!addKnowCoordinates($coord, $funda_id)) {
				echo 'Toevoegen van coordinaten van '. $funda_id .' ging niet goed<br>';
			}
		}		
	} while($row = mysql_fetch_array($result));
}

?>