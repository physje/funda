<?php
include_once(__DIR__.'/../include/config.php');
include_once($cfgGeneralIncludeDirectory.'/class.phpmailer.php');
include_once($cfgGeneralIncludeDirectory.'/class.phpPushover.php');
include_once(__DIR__ .'/../include/HTML_TopBottom.php');
include_once(__DIR__.'/../include/excel/excel_reader2.php');
include_once(__DIR__.'/../include/excel/SpreadsheetReader.php');
$db = connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

if(isset($_REQUEST['type'])) { $type = $_REQUEST['type']; } else { $type = 'alles'; }

if($type == 'regio') {
	$jaar       = 1;
	$periode    = 'Q';
	$categorie  = 'R';
	$PBK        = 'PBK-PR';
	toLog('debug', '', '', 'PBK opgevraagd voor alle regios');
} elseif($type == 'prov') {
	$jaar       = 1;
	$periode    = 'Q';
	$categorie  = 'P';
	$PBK        = 'PBK-PR';	
	toLog('debug', '', '', 'PBK opgevraagd voor provincies');
} elseif($type == 'woning') {
	$jaar       = 1;
	$periode    = 'Q';
	$categorie  = 'WT';
	$PBK        = 'PBK-PW';
	toLog('debug', '', '', 'PBK opgevraagd voor woningtypes');
} else {
	$jaar       = 1;
	$periode    = 'M';
	$categorie  = 'T';
	$PBK        = 'PBK';
	toLog('debug', '', '', 'PBK opgevraagd voor heel Nederland');
}

$url = "https://vastgoeddashboard.kadaster.nl/VastgoedProxy/api/v1/$PBK/export?F-P=Y". $jaar ."&F-TG=". $periode ."&C-C=". $categorie ."&filename=". time() .".xlsx";
$data = file_get_contents_retry($url);
file_put_contents('PBK.xlsx', $data);

$newEntry = false;
$PIArray = array();

# URL is gewijzigd. Nieuwe formaat lijkt te zijn 
# 	https://vastgoeddashboard.kadaster.nl/VastgoedProxy/api/v1/{PBK}/export?F-P=Y{jaar}&F-TG={periode}&C-C={categorie}&filename={bestandsnaam}.xlsx
#
#		{PBK} = PBK-PW, PBK-WT, PBK
# 	{jaar} = Y1,Y2,Y3,Y5,Y10,Y20
# 	{periode} = M [maand], Q [kwartaal], Y [jaar], MOM [maand-op-maand], YOY [jaar-op-jaar]
# 	{categorie} = T [Totaal], R [regio], P [provincie], WT [Woningtype]
# 	{bestandsnaam} = vrij te kiezen


$Reader = new SpreadsheetReader('PBK.xlsx');
$i = 0;

foreach ($Reader as $velden) {	
	# Even de lege regels overslaan
	if($i > 3) {
		# Voor later
		$oud_prijsindex = current($PIArray);
	
		unset($PIArray);
		unset($naam);
		
		if($type == 'regio') {
			# Periode (omschrijving)|Periode (kwartaal)|Midden-Nederland|Noord-Nederland|West-Nederland|Zuid-Nederland    	
    	$PIArray[0]	= $velden[2];
    	$PIArray[1]	= $velden[3];
    	$PIArray[2]	= $velden[4];
    	$PIArray[3]	= $velden[5];
    	
    	$naam[0] 		= 'Midden-Nederland';
    	$naam[1] 		= 'Noord-Nederland';
    	$naam[2] 		= 'West-Nederland';
    	$naam[3] 		= 'Zuid-Nederland';    		
    } elseif($type == 'prov') {
    	# Periode (omschrijving)|Periode (kwartaal)|Drenthe|Flevoland|Fryslân|Gelderland|Groningen|Limburg|Noord-Brabant|Noord-Holland|Overijssel|Utrecht|Zeeland|Zuid-Holland
    	$PIArray[0]	= $velden[2];
    	$PIArray[1]	= $velden[3];
    	$PIArray[2]	= $velden[4];
    	$PIArray[3]	= $velden[5];
    	$PIArray[4]	= $velden[6];
    	$PIArray[5]	= $velden[7];
    	$PIArray[6]	= $velden[8];
    	$PIArray[7]	= $velden[9];
    	$PIArray[8]	= $velden[10];
    	$PIArray[9]	= $velden[11];
    	$PIArray[10]	= $velden[12];
    	$PIArray[11]	= $velden[13];    	
    	
			$naam[0] 		= 'Drenthe';
			$naam[1] 		= 'Flevoland';
			$naam[2] 		= utf8_encode('Fryslân');
			$naam[3] 		= 'Gelderland';
			$naam[4] 		= 'Groningen';
			$naam[5] 		= 'Limburg';
			$naam[6] 		= 'Noord-Brabant';
			$naam[7] 		= 'Noord-Holland';
			$naam[8] 		= 'Overijssel';
			$naam[9] 		= 'Utrecht';
			$naam[10] 		= 'Zeeland';
			$naam[11] 		= 'Zuid-Holland';    	
    } elseif($type == 'woning') {
    	$PIArray[0]	= $velden[2];
    	$PIArray[1]	= $velden[3];
    	$PIArray[2]	= $velden[4];
    	$PIArray[3]	= $velden[6];	
    	$PIArray[4]	= $velden[7];    
    
    	$naam[0] 		= '2-onder-1-kap';
    	$naam[1] 		= 'Appartement';
    	$naam[2] 		= 'Hoekwoning';
    	$naam[3] 		= 'Tussenwoning';
    	$naam[4] 		= 'Vrijstaand';    
    } else {
    	# Periode (omschrijving)|Periode (maand)|Prijsindex    	
    	$PIArray[]	= $velden[2];    		
    	$naam[]			= 'Totaal';    	
    }
    
    $periode		= $velden[1];    
    $jaar = substr($periode, 0, 4);
    $kwartaal = substr($periode, 4);
    	
    if($kwartaal == '-1') {
    	$start =  mktime (0, 0, 0, 1, 1, $jaar);
    	$eind	=  mktime (23, 59, 59, 3, 31, $jaar);
    } elseif($kwartaal == '-2') {
    	$start =  mktime (0, 0, 0, 4, 1, $jaar);
    	$eind	=  mktime (23, 59, 59, 6, 30, $jaar);
    } elseif($kwartaal == '-3') {
    	$start =  mktime (0, 0, 0, 7, 1, $jaar);
    	$eind	=  mktime (23, 59, 59, 9, 30, $jaar);
    } elseif($kwartaal == '-4') {
    	$start =  mktime (0, 0, 0, 10, 1, $jaar);
    	$eind	=  mktime (23, 59, 59, 12, 31, $jaar);
    } else {
    	$start =  mktime (0, 0, 0, $kwartaal, 1, $jaar);
    	$eind	=  mktime (0, 0, 0, ($kwartaal+1), 1, $jaar) - 1;
    }
	
	  //echo $jaar .';'. $kwartaal .' ('. date('d-m', $start).' tm '. date('d-m', $eind) .') -> '. $prijsindex.' -> '. $regio .'<br>';
	    
	  foreach($PIArray as $key => $prijsindex) {
	  	$regio = $naam[$key];
	  	$sql_check = "SELECT * FROM $TablePBK WHERE $PBKComment like '". $periode .', '. $regio ."'";
	  		  	
	  	$result = mysqli_query($db, $sql_check);
			if(mysqli_num_rows($result) == 0) {
				$sql = "DELETE FROM $TablePBK WHERE $PBKStart = $start AND $PBKRegio like '$regio'";	
				mysqli_query($db, $sql);
				
				$sql = "INSERT INTO $TablePBK ($PBKStart, $PBKEind, $PBKRegio, $PBKWaarde, $PBKComment) VALUES ($start, $eind, '$regio', $prijsindex, '". $periode .', '. $regio ."')";				
				if(!mysqli_query($db, $sql)) {
					echo $sql;
				}				
				
				if($type == 'regio') {					
					toLog('info', '', '', 'Nieuwe PBK voor de verschillende regios');
				} elseif($type == 'prov') {
					toLog('info', '', '', 'Nieuwe PBK voor de verschillende provincies');
				} elseif($type == 'woning') {
					toLog('info', '', '', 'Nieuwe PBK voor de verschillende woningtypes');
				} else {
					$newEntry = true;
					toLog('info', '', '', 'Nieuwe PBK voor heel Nederland');
				}				
			}
		}
  }
  $i++;
}

toLog('info', '', '', 'Kadaster PBK-ingelezen');

# Als de ingelezen data "nieuwer" is dan de data in de dB, is er nieuwe data en moet er een pushover-bericht worden gestuurd.
if($newEntry) {
	$percentage = (100*($prijsindex-$oud_prijsindex))/$prijsindex;
	if($percentage > 0) {
		$percentageString = '+'.number_format ($percentage,1);
	} else {
		$percentageString = number_format ($percentage,1);
	}
	send2Pushover(array('title' => 'Prijsindex', 'message' => "In ". strtolower(strftime ('%B %Y', $start)) ." is de prijsindex van $oud_prijsindex naar $prijsindex gegaan (". $percentageString .'%)'), array(1));
}

# Tijdelijk bestand weer verwijderen
unlink('PBK.xlsx');

?>