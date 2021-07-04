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

$jaar       = 2;

if($type == 'regio') {
	$periode    = 'Q';
	$categorie  = 'R';
	$PBK        = 'PBK-PR';
	$groep			= "regio";
	toLog('debug', '0', '0', 'PBK opvragen voor alle regios');
} elseif($type == 'prov') {
	$periode    = 'Q';
	$categorie  = 'P';
	$PBK        = 'PBK-PR';	
	$groep			= "provincie";
	toLog('debug', '0', '0', 'PBK opvragen voor provincies');
} elseif($type == 'woning') {	
	$periode    = 'Q';
	$categorie  = 'WT';
	$PBK        = 'PBK-PW';
	$groep			= "woningtype";
	toLog('debug', '0', '0', 'PBK opvragen voor woningtypes');
} elseif($type == 'steden') {	
	$periode    = 'Q';
	$categorie  = 'GG';
	$PBK        = 'PBK-PR';
	$groep			= "steden";
	toLog('debug', '0', '0', 'PBK opvragen voor steden');
} else {
	$periode    = 'M';
	$categorie  = 'T';
	$PBK        = 'PBK';
	$groep			= "totaal";
	toLog('debug', '0', '0', 'PBK opvragen voor heel Nederland');
}

# URL is gewijzigd. Nieuwe formaat lijkt te zijn 
# 	https://vastgoeddashboard.kadaster.nl/VastgoedProxy/api/v1/{PBK}/export?F-P=Y{jaar}&F-TG={periode}&C-C={categorie}&filename={bestandsnaam}.xlsx
#
#		{PBK} = PBK-PW [woningtype], PBK-PR [regio / provincie], PBK [totaal]
# 	{jaar} = 1,2,3,5,10,20
# 	{periode} = M [maand], Q [kwartaal], Y [jaar], MOM [maand-op-maand], YOY [jaar-op-jaar]
# 	{categorie} = T [Totaal], R [regio], P [provincie], WT [Woningtype]
# 	{bestandsnaam} = vrij te kiezen

$url = "https://vastgoeddashboard.kadaster.nl/VastgoedProxy/api/v1/$PBK/export?F-P=Y". $jaar ."&F-TG=". $periode ."&C-C=". $categorie ."&filename=". time() .".xlsx";
$data = file_get_contents_retry($url);
file_put_contents('PBK.xlsx', $data);

$newEntry = $sendPushover = false;
$PIArray = array();

$Reader = new SpreadsheetReader('PBK.xlsx');
$i = 0;

foreach ($Reader as $velden) {	
	# Even de lege regels overslaan
	if($i > 3) {		
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
			$naam[2] 		= 'Friesland';
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
    } elseif($type == 'steden') {
    	$PIArray[0]	= $velden[2];
    	$PIArray[1]	= $velden[3];
    	$PIArray[2]	= $velden[4];
    	$PIArray[3]	= $velden[5];    
    
    	$naam[0] 		= 'Amsterdam';
    	$naam[1] 		= 'Gravenhage';
    	$naam[2] 		= 'Rotterdam';
    	$naam[3] 		= 'Utrecht (stad)';
    } else {
    	# Periode (omschrijving)|Periode (maand)|Prijsindex    	
    	$PIArray[0]	= $velden[2];    		
    	$naam[0]			= 'Totaal';    	
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
	    
		foreach($PIArray as $key => $prijsindex_en) {
	  	$regio = $naam[$key];
	  	$prijsindex = number_format($prijsindex_en, 1, '.', '');
	  	
	  	$sql_check = "SELECT * FROM $TablePBK WHERE $PBKComment like '". $periode .', '. $regio ."'";
	  	$result = mysqli_query($db, $sql_check);
			if(mysqli_num_rows($result) == 0) {
				$sql = "DELETE FROM $TablePBK WHERE $PBKStart = $start AND $PBKRegio like '$regio'";	
				mysqli_query($db, $sql);
				
				$sql = "INSERT INTO $TablePBK ($PBKStart, $PBKEind, $PBKRegio, $PBKCategorie, $PBKWaarde, $PBKComment) VALUES ($start, $eind, '$regio', '$groep', $prijsindex, '". $periode .', '. $regio ."')";				
				if(!mysqli_query($db, $sql)) {
					echo $sql;
				}
				$newEntry = true;
			} else {
				$sql_update = "UPDATE $TablePBK SET $PBKCategorie = '$groep' WHERE $PBKComment like '". $periode .', '. $regio ."'";
				mysqli_query($db, $sql_update);
			} 
		}
		
		# array met PBK aanleggen zodat later een jaar teruggekeken kan worden
		$arrayPBK[] = number_format($PIArray[0], 1, '.', '');		
  }
  $i++;
}

# Als de ingelezen data "nieuwer" is dan de data in de dB, is er nieuwe data en moet er een pushover-bericht worden gestuurd.
if($newEntry) {
	if($type == 'regio') {
		toLog('info', '0', '0', 'Nieuwe PBK voor de regios');
	} elseif($type == 'prov') {
		toLog('info', '0', '0', 'Nieuwe PBK voor de provincies');
	} elseif($type == 'woning') {
		toLog('info', '0', '0', 'Nieuwe PBK voor de woningtypes');
	} elseif($type == 'steden') {	
		toLog('info', '0', '', 'Nieuwe PBK voor de grote steden');
	} else {
		toLog('info', '0', '0', 'Nieuwe PBK voor heel Nederland');
		$sendPushover = true;
	}

	if($sendPushover) {
		$index_maand = (count($arrayPBK)-2);
		$index_jaar = (count($arrayPBK)-13);

		$prijsindex_mnd = $arrayPBK[$index_maand];
		$prijsindex_jaar = $arrayPBK[$index_jaar];
		
		$percentage_mnd = 100*(($prijsindex-$prijsindex_mnd)/$prijsindex);				
		$percentage_jaar = 100*(($prijsindex-$prijsindex_jaar)/$prijsindex);
		
		send2Pushover(array('title' => 'Prijsindex', 'message' => "In ". strtolower(strftime ('%B %Y', $start)) ." is de prijsindex naar $prijsindex gegaan. Dat is een ". ($percentage_mnd > 0 ? 'stijging' : 'daling' )." van ". number_format(abs($percentage_mnd),1) .'% tov vorige maand (toen was de index '. $prijsindex_mnd ."). Ten opzichte van een jaar geleden is het een ". ($percentage_jaar > 0 ? 'stijging' : 'daling' )." van ". number_format(abs($percentage_jaar),1) .'% (toen was de index '. $prijsindex_jaar .').'), array(1));
	}
}

# Tijdelijk bestand weer verwijderen
unlink('PBK.xlsx');

?>