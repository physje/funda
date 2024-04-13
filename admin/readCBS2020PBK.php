<?php
include_once(__DIR__.'/../include/config.php');
include_once($cfgGeneralIncludeDirectory.'/class.phpPushover.php');
$db = connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

if(isset($_REQUEST['type'])) { $type = $_REQUEST['type']; } else { $type = 'alles'; }

$newEntry = $sendPushover = false;
$debug = false;
$temp = array();

if($type == 'regio') {
	# Locatie van PBK-data per kwartaal, gemiddeld over de verschillende regio's. Genormeerd op 2015
	$url = "https://opendata.cbs.nl/ODataApi/odata/83913NED/UntypedDataSet";
	toLog('debug', '0', '0', 'PBK opvragen voor regios, provincies & steden');
	$numberItems = 21;	
} elseif($type == 'woning') {
	# Locatie van PBK-data per kwartaal, gemiddeld over de verschillende woning-type's. Genormeerd op 2015
	$url = "https://opendata.cbs.nl/ODataApi/odata/83910NED/UntypedDataSet";
	toLog('debug', '0', '0', 'PBK opvragen voor woningtypes');
	$numberItems = 8;
} else {
	# Locatie van PBK-data per maand, gemiddeld over heel Nederland. Genormeerd op 2020
	$url = "https://opendata.cbs.nl/ODataApi/odata/85773NED/UntypedDataSet";
	toLog('debug', '0', '0', 'PBK opvragen voor heel Nederland');
	$numberItems = 1;
}



$data = file_get_contents_retry($url, 3, true);
$aPBK = json_decode($data, true);

/*
# Om niet alle data elke keer in te lezen, knippen wij de array met CBS-data in stukken
# Vervolgens maken we een array aan met alleen de laatste 3 items per regio/type
# De eerste keer kan het handig zijn om de hele array in te lezen
$aantal = count($aPBK["value"]);
$deel = $aantal/$numberItems;
	
for($i=1 ; $i <= $numberItems ; $i++) {
	$slice = array_slice($aPBK["value"], (($deel*$i)-5), 5);
	$temp = array_merge($temp, $slice);
}
	
$aPBK["value"] = $temp;
*/
$aRegio = array(
	'PV20' => 'Groningen',
	'PV21' => 'Friesland',
	'PV22' => 'Drenthe',
	'PV23' => 'Overijssel',
	'PV24' => 'Flevoland',
	'PV25' => 'Gelderland',
	'PV26' => 'Utrecht',
	'PV27' => 'Noord-Holland',
	'PV28' => 'Zuid-Holland',
	'PV29' => 'Zeeland',
	'PV30' => 'Noord-Brabant',
	'PV31' => 'Limburg',
	'LD01' => 'Noord-Nederland',
	'LD02' => 'Oost-Nederland',
	'LD03' => 'West-Nederland',
	'LD04' => 'Zuid-Nederland',
	'GM0363' => 'Amsterdam',
	'GM0518' => 'Den Haag',
	'GM0599' => 'Rotterdam',
	'GM0344' => 'Utrecht'
);

$aWoningtype = array(
    'T001100' => 'Totalen',
    'ZW10290' => 'Eengezinswoning',
    'ZW25805' => 'Tussenwoning',
    'ZW25806' => 'Hoekwoning',
    'ZW10300' => '2-onder-1-kapwoning',
    'ZW10320' => 'Vrijstaande woning',
    'ZW25809' => 'Appartement',
    'ZW25810' => 'Onbekend'
);


foreach ($aPBK["value"] as $rij) {
	if($debug) echo '<hr>';
	
	$jaar = $maand = $kwartaal = $start = $eind = $regio = $woningtype = '';
	$meenemen = false;
	
	$id         = trim($rij['ID']);
	$Periode    = trim($rij['Perioden']);
	$TOVPeriode = trim($rij['OntwikkelingTOVVoorgaandePeriode_2']);
	$TOVJaar    = trim($rij['OntwikkelingTOVEenJaarEerder_3']);
	if($type == 'alles') {
		#$PBK        = trim($rij['PrijsindexBestaandeKoopwoningen_1']);
		$PBK        = trim($rij['PrijsindexVerkoopprijzen_1']);		
	} elseif($type == 'regio') {
		$PBK	= trim($rij['PrijsindexVerkoopprijzen_1']);
		$RegioS = trim($rij['RegioS']);
		$regio	= $aRegio[$RegioS];    
	} else {
		$PBK	= trim($rij['PrijsindexVerkoopprijzen_1']);
		$TypeWoning = trim($rij['TypeWoning']);
		$woningtype = $aWoningtype[$TypeWoning];   	
	}
	
	$prijsindex		= $PBK;
	$arrayPBK[] = $PBK;
	
	if(strpos($Periode, 'MM')) {
		$jaar = substr($Periode, 0, 4);
		$maand = substr($Periode, 6, 2);
		
		$start =  mktime (0, 0, 0, $maand, 1, $jaar);
		$eind	=  mktime (0, 0, 0, ($maand+1), 1, $jaar) - 1;		
		
		if($type == 'alles')	$meenemen = true;
	} elseif(strpos($Periode, 'KW')) {
		$jaar = substr($Periode, 0, 4);
		$kwartaal = substr($Periode, 7, 1);
		
		$dummy = (($kwartaal-1)*3)+1;
		
		$start =  mktime (0, 0, 0, $dummy, 1, $jaar);
		$eind	=  mktime (0, 0, 0, ($dummy+3), 1, $jaar) - 1;
		
		if($type == 'regio' OR $type == 'woning')	$meenemen = true;
	}	
	
	if($type == 'alles' AND $meenemen) {
		$groep	    = 'totaal';
    $regio	    = 'Totaal';
    $comment    = $jaar.$maand.', Totaal';
	} elseif($type == 'regio' AND $meenemen) {
  	if(substr($RegioS, 0, 2) == 'PV') {
  		$groep		= 'provincie';
      $comment	= $jaar.'Q'.$kwartaal.', '. $regio;
		} elseif(substr($RegioS, 0, 2) == 'LD') {
    	$groep		= 'regio';
    	$comment	= $jaar.'Q'.$kwartaal.', '. $regio;
    } elseif(substr($RegioS, 0, 2) == 'GM') {
    	$groep		= 'steden';
    	$comment	= $jaar.'Q'.$kwartaal.', '. $regio;
    } else {
    	#  echo '['.$RegioS .']';
    	$meenemen = false;
		}
	} elseif($type == 'woning' AND $meenemen) {
		$groep	= 'woningtype';
		$regio	= $woningtype;
		$comment	= $jaar.'Q'.$kwartaal.', '. $woningtype;
	}
	
	if($type == 'woning' AND ($TypeWoning == 'T001100' OR $PBK == '.'))	$meenemen = false;
	
	if($debug) {
		echo 'jaar:'.$jaar.'<br>';
		echo 'maand:'. $maand.'<br>';
		echo 'kwartaal:'.$kwartaal.'<br>';
		echo 'periode: ('. date('d-m', $start).' tm '. date('d-m', $eind) .')<br>';
		echo 'prijsindex:'. $prijsindex.'<br>';
		echo 'regio:'. $regio .'<br>';
		if(!$meenemen) echo 'NIET MEEGENOMEN<br>';
	}
		
	if($meenemen) {
		$sql_check = "SELECT * FROM $TablePBK WHERE $PBKComment like '". $comment ."'";
		if($debug) { echo $sql_check .'<br>'; } else { $result = mysqli_query($db, $sql_check); }
		
		if(mysqli_num_rows($result) == 0) {
			$sql = "DELETE FROM $TablePBK WHERE $PBKStart = $start AND $PBKRegio like '$regio'";	
			if($debug) { echo $sql .'<br>'; } else { mysqli_query($db, $sql); }
			
			$sql = "INSERT INTO $TablePBK ($PBKStart, $PBKEind, $PBKRegio, $PBKCategorie, $PBKWaarde, $PBKComment) VALUES ($start, $eind, '$regio', '$groep', $prijsindex, '$comment')";
			if(!mysqli_query($db, $sql)) {
				echo $sql .'<br>';
			}
			
			$newEntry = true;
		} elseif(mysqli_num_rows($result) > 1) {
			$sql_delete = "DELETE FROM $TablePBK WHERE $PBKComment like '". $comment ."'";
			if($debug) { echo $sql_delete .'<br>'; } else { mysqli_query($db, $sql_delete); }
		} else {
			$sql_update = "UPDATE $TablePBK SET $PBKRegio = '$regio', $PBKCategorie = '$groep' WHERE $PBKComment like '". $comment ."'";
			if($debug) { echo $sql_update .'<br>'; } else { mysqli_query($db, $sql_update); }
		}
	}
}

# Als de ingelezen data "nieuwer" is dan de data in de dB, is er nieuwe data en moet er een pushover-bericht worden gestuurd.
if($newEntry) {
	if($type == 'regio') {
		toLog('info', '0', '0', 'Nieuwe PBK voor de regios, provincies en steden');
	} elseif($type == 'woning') {
		toLog('info', '0', '0', 'Nieuwe PBK voor de woningtypes');
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
		
		if($debug) {
			echo "In ". strtolower(strftime ('%B %Y', $start)) ." is de prijsindex naar $prijsindex gegaan. Dat is een ". ($percentage_mnd > 0 ? 'stijging' : 'daling' )." van ". number_format(abs($percentage_mnd),1) .'% tov vorige maand (toen was de index '. $prijsindex_mnd ."). Ten opzichte van een jaar geleden is het een ". ($percentage_jaar > 0 ? 'stijging' : 'daling' )." van ". number_format(abs($percentage_jaar),1) .'% (toen was de index '. $prijsindex_jaar. ').';
		} else {
			send2Pushover(array('title' => 'Prijsindex', 'message' => "In ". strtolower(strftime ('%B %Y', $start)) ." is de prijsindex naar $prijsindex gegaan. Dat is een ". ($percentage_mnd > 0 ? 'stijging' : 'daling' )." van ". number_format(abs($percentage_mnd),1) .'% tov vorige maand (toen was de index '. $prijsindex_mnd ."). Ten opzichte van een jaar geleden is het een ". ($percentage_jaar > 0 ? 'stijging' : 'daling' )." van ". number_format(abs($percentage_jaar),1) .'% (toen was de index '. $prijsindex_jaar .').'), array(1));
		}
	}
}

?>