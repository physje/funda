<?php

# Geef de id's van zoekopdrachten
#
#	INPUT
#		$active : 0 = niet actief, 1 = actief, '' = alle
#
# OUTPUT
#		array met ids van zoekopdracht
function getZoekOpdrachten($user, $uur, $active = true) {
	global $TableZoeken, $TableVerdeling, $VerdelingOpdracht, $VerdelingUur, $ZoekenKey, $ZoekenUser;
	$where = $Opdrachten = array();
	
	$sql = "SELECT $TableZoeken.$ZoekenKey";
		
	if($user != '') {
		$from = $TableZoeken;
		$where[] = "$TableZoeken.$ZoekenUser = '$user'";
	}
	
	if($uur != '' OR $uur == '0') {
		$from = "$TableVerdeling, $TableZoeken";
		$where[] = "$TableVerdeling.$VerdelingOpdracht = $TableZoeken.$ZoekenKey";
		$where[] = "$TableVerdeling.$VerdelingUur = '$uur'";
	}
	
	$sql .= ' FROM '. $from .' WHERE '. implode(" AND ", $where);
		
	$result = mysql_query($sql);	
	if($row = mysql_fetch_array($result)) {
		do {
			if(($active AND count(getOpdrachtUren($row[$ZoekenKey])) > 0) OR !$active) {
				$Opdrachten[] = $row[$ZoekenKey];
			}
		} while($row = mysql_fetch_array($result));
	}
	
	return $Opdrachten;
}


function getOpdrachtUren($opdracht) {
	global $TableVerdeling, $VerdelingUur, $VerdelingOpdracht;
	
	$Uren = array();
	
	$sql = "SELECT * FROM $TableVerdeling WHERE $VerdelingOpdracht = $opdracht";
	$result = mysql_query($sql);	
	if($row = mysql_fetch_array($result)) {
		do {
			$Uren[] = $row[$VerdelingUur];
		} while($row = mysql_fetch_array($result));
	}
	
	return $Uren;
}

# Zoek de gegevens van een zoekopdracht op basis van id
#
#	INPUT
#		$id	id van de zoekopdracht
#
# OUTPUT
#		array met gegevens
function getOpdrachtData($id) {
	global $TableZoeken, $ZoekenKey, $ZoekenActive, $ZoekenUser, $ZoekenNaam, $ZoekenURL;
	
	$data = array();
	
	if($id != '') {
		$sql		= "SELECT * FROM $TableZoeken WHERE $ZoekenKey = $id";
		$result	= mysql_query($sql);
		$row		= mysql_fetch_array($result);
			
		$data['active']	= $row[$ZoekenActive];
		$data['user']		= $row[$ZoekenUser];
		$data['naam']		= urldecode($row[$ZoekenNaam]);
		$data['url']		= urldecode($row[$ZoekenURL]);
	}
	
	return $data;
}


# Probeer de inhoud van een pagina in te lezen in in een string
# Mocht dit niet lukken probeer dan nogmaals
#
#	INPUT
#		$url		String met de url van de in te lezen pagina
#		$maxTry	Maximaal aantal pogingen (default = 3)
#
# OUTPUT
#		String met de inhoud van de pagina
function file_get_contents_retry($url, $maxTry = 3) {
	$contents = false;
	$counter = 0;
	
	while($contents === false AND $counter < $maxTry) {
		if($counter > 0)	{	sleep(2);	}
		$contents	= file_get_contents($url);
		$counter++;
	}
	
	return $contents;
}



function implode2($first, $last, $array) {
	if(count($array) > 2) {
		$element = array_pop ($array);
		return implode($first, $array).$last.$element;
	} elseif(count($array) == 2) {
		return implode($last, $array);
	} else {
		return current($array);
	}
}


# Zoek de coordinaten bij een gegeven straat, postcode en plaats en voeg deze toe
#
#	INPUT
#		$straat		String met straatnaam
#		$postcode	Volledige postcode
#		$plaats		String met plaatsnaam
#		$huisID		ID van huis op funda
#
# OUTPUT
#		boolean of het wel of niet gelukt is
function addCoordinates($straat, $postcode, $plaats, $huisID) {
	$elementen		= explode(' ', urldecode($straat));
	
	if(count($elementen) > 1) {
		$toevoeging		= $elementen[count($elementen) - 1];
	
		$toevoeging = str_ireplace('zwart', '' , $toevoeging);
		$toevoeging = str_ireplace('zw', '' , $toevoeging);
		$toevoeging = str_ireplace('rd', '' , $toevoeging);
		$toevoeging = str_ireplace('rood', '' , $toevoeging);
		$toevoeging = trim($toevoeging, 'A..Za..z-*.');
		$toevoeging = trim($toevoeging);
		
		$elementen[count($elementen) - 1] = $toevoeging;
	}
		
	$GoogleStraat = trim(implode(' ', $elementen));
	
	$coord				= getCoordinates($GoogleStraat, $postcode, $plaats);
	
	if(!addKnowCoordinates($coord, $huisID)) {
		return false;
	} else {
		return true;
	}	
}


# Voeg coordinaten toe aan huis
#
#	INPUT
#		$coord		array met coordinaten [Ndeg,Ndec,Odeg,Odec]
#		$huisID		ID van huis op funda
#
# OUTPUT
#		boolean of het wel of niet gelukt is
function addKnowCoordinates($coord, $huisID) {
	global $TableHuizen, $HuizenLat, $HuizenLon, $HuizenID;
			
	if(is_numeric($coord[1])) {
		$lat = $coord[0].'.'.$coord[1];
		$lng = $coord[2].'.'.$coord[3]; 
		$sql = "UPDATE $TableHuizen SET $HuizenLat = '$lat', $HuizenLon = '$lng' WHERE $HuizenID = '$huisID'";
		if(!mysql_query($sql)) {
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}


# Toon een string in een gekleurd kader
#
#	INPUT
#		$string		De te tonen string
#
# OUTPUT
#		De opgemaakte string
function showBlock($String, $mobile = false) {
	if ($mobile ) {
		$Text = $String;	
	} else {		
		$Text = "<table width='95%' cellpadding='8' cellspacing='1' bgcolor='#636367'>\n";
		$Text .= "<tr>\n";
		$Text .= "	<td bgcolor='#EAEAEA'>\n";
		$Text .= "	<!-- BEGIN BLOK INHOUD -->\n";	
		$Text .= $String;	
		$Text .= "	<!-- EIND BLOK INHOUD -->\n";	
		$Text .= "	</td>\n";	
		$Text .= "</tr>\n";
		$Text .= "</table>\n";
	}
	
	return $Text;
}


# Toon een string en kort hem indien nodig in
#
#	INPUT
#		$string		De te tonen string
#		$length		De maximale lengte van de uiteindelijke string
#
# OUTPUT
#		De al dan niet ingekorte string
function makeTextBlock($string, $length, $reverse = false) {
	if(strlen($string) > $length) {
		if($reverse) {
			$titel = "...".substr($string, -$length+3);
		} else {
			$titel = substr($string, 0, $length-5) . ".....";
		}
	} else {
		$titel = $string;
	}
	
	return $titel;
}

# Extraheer gegevens van een huis uit de ruwe HTML-code van de overzichtspagina van funda.nl
#
#	INPUT
#		$string		De ruwe HTML-code
#
# OUTPUT
#		array met de gegevens van het huis
function extractFundaData($HuisText, $verkocht = false) {	
	# Overzichtspagina
	$HuisURL= getString('<a href="', '"', $HuisText, 0);
	$mappen = explode("/", $HuisURL[0]);
	if($verkocht) {
		$key		= $mappen[4];
	} else {
		$key		= $mappen[3];
	}
	$key_parts = explode("-", $key);
	$id			= $key_parts[1];
	$adres	= getString('<h3 class="search-result-title">', '<small class="search-result-subtitle">', $HuisURL[1], 0);
	$PC			= getString('<small class="search-result-subtitle">', '</small>', $adres[1], 0);
	$prijs	= getString('<span class="search-result-price">', '</span>', $PC[1], 0);
	
	if(strpos($HuisText, 'search-result-makelaar"')) {
		$R_url	= getString('<a class="search-result-makelaar" href="', '">', $PC[1], 0);
		$R_naam	= getString('<span class="search-result-makelaar-name">', '</span>', $PC[1], 0);
	} else {
		$R_url	= array('', '');
		$R_naam	= getString('<span class="search-result-makelaar-name">', '</span>', $PC[1], 0);
	}
			
	$param	= getString('<ul class="labels">', '</ul>', $PC[1], 0);	
	$foto		= getString('calc(100vw - 2rem)" src="', '" srcset="', $HuisText, 0);
	
	# Nu al het knippen geweest is kan de geknipte data "geprocesed" worden		
	if(strpos($param[0], 'Verkocht onder voorbehoud')) {
		$voorbehoud = 1;
	} else {
		$voorbehoud = 0;
	}
	
	if(strpos($param[0], 'label-open-huis') OR strpos($param[0], '<span class="item-open nvm-open-huizen-dag" title="')) {
		$openhuis = 1;
	} else {
		$openhuis = 0;
	}
	
	$postcode = explode(' ', trim($PC[0]));
		
	$data['id']				= $id;
	$data['url']			= trim($HuisURL[0]);
	$data['adres']		= trim($adres[0]);
	$data['PC_c']			= trim($postcode[0]);
	$data['PC_l']			= trim($postcode[1]);
	$data['plaats']		= end($postcode);
	$data['thumb']		= trim($foto[0]);
	$data['makelaar']	= trim($R_naam[0]);
	$data['prijs']		= cleanPrice($prijs[0]);
	$data['vov']			= $voorbehoud;
	$data['openhuis']	= $openhuis;
	
	/*
	foreach($data as $key => $value) {
		echo $key .'|'.makeTextBlock($value, 100) .'<br>';
	}
	echo '------------------------------';
	*/	
	return $data;
}

function cleanPrice($HuisPrijs) {
	$cleanPrice = '';
	for($i=0 ; $i < strlen($HuisPrijs) ; $i++) {
		$waarde = $HuisPrijs[$i];
		if(is_numeric($waarde)) {
			$cleanPrice .= $waarde;
		}
	}
	
	if(!is_numeric($cleanPrice)) {
		$cleanPrice		= '0';
	}	
		
	return $cleanPrice;
}

function extractFundaData_old($HuisText, $verkocht = false) {	
	# Overzichtspagina
	$HuisURL= getString('<a href="', '"', $HuisText, 0);
	$mappen = explode("/", $HuisURL[0]);
	if($verkocht) {
		$key		= $mappen[4];
	} else {
		$key		= $mappen[3];
	}
	$key_parts = explode("-", $key);
	$id			= $key_parts[1];
	$foto		= getString('<img src="', '" alt="" title="" class="', $HuisURL[1], 0);
	$adres	= getString('<a href="'. $HuisURL[0] .'"class="object-street " >', '</a>', $foto[1], 0);
	$PC			= getString('<li>', '<', $adres[1], 0);
	$param	= getString('<li>', '</li>', $adres[1], 0);
		
	if(strpos($HuisText, '<a class="realtor" href="')) {
		$R_url	= getString('<a class="realtor" href="', '">', $PC[1], 0);
		$R_naam	= getString('">', '</a>', $R_url[1], 0);
	} else {
		$R_naam	= getString('<span class="realtor">', '</span>', $PC[1], 0);
	}
	$prijs	= getString('<span class="price">', '</span>', $R_naam[1], 0);
		
	if(strpos($param[0], 'Verkocht onder voorbehoud')) {
		$voorbehoud = 1;
	} else {
		$voorbehoud = 0;
	}
	
	if(strpos($param[0], '<span class="item-open"') OR strpos($param[0], '<span class="item-open nvm-open-huizen-dag" title="')) {
		$openhuis = 1;
	} else {
		$openhuis = 0;
	}
	
	$postcode = explode(' ', trim($PC[0]));
		
	$HuisPrijs		= $prijs[0];			
	$HuisPrijs		= str_ireplace('&euro;&nbsp;', '' , $HuisPrijs);
	$HuisPrijs		= str_ireplace('.', '' , $HuisPrijs);
		
	if(!is_numeric($HuisPrijs)) {
		$HuisPrijs		= '0';
	}
	
	$data['id']				= $id;
	$data['url']			= trim($HuisURL[0]);
	$data['adres']		= trim($adres[0]);
	$data['PC_c']			= trim($postcode[0]);
	$data['PC_l']			= trim($postcode[1]);
	$data['plaats']		= end($postcode);
	$data['thumb']		= trim($foto[0]);
	$data['makelaar']	= trim($R_naam[0]);
	$data['prijs']		= $HuisPrijs;
	$data['vov']			= $voorbehoud;
	$data['openhuis']	= $openhuis;

	return $data;
}



function convertToReadable($string) {
	$string = str_replace('&nbsp;m&sup2;', '', $string);
	$string = str_replace('&nbsp;m&sup3;', '', $string);
	$string = html_entity_decode($string);
	
	return $string;
}


function removeFilenameCharacters($string) {
	$string = str_replace('²', '', $string);
	$string = str_replace(',', '', $string);
	$string = str_replace('!', '', $string);
	$string = str_replace('[', '', $string);
	$string = str_replace(']', '', $string);
	$string = str_replace(' ', '-', $string);
	$string = html_entity_decode($string);
	
	return $string;
}


function makeKMLEntry($id) {
	global $ScriptURL;
	$data			= getFundaData($id);	
	$Prijzen	= getPriceHistory($id);
	
	$temp			= each($Prijzen);
	$label		= $temp[0];
	
	$KML_file[] = '	<Placemark>';
	$KML_file[] = '		<name><![CDATA['. convertToReadable($data['adres']) .' voor '. number_format($Prijzen[$label],0,',','.') .']]></name>';
	$KML_file[] = '		<visibility>0</visibility>';
	$KML_file[] = '		<description><![CDATA[';
	$KML_file[] = '		<table>';
	$KML_file[] = '		<tr>';
	$KML_file[] = '			<td valign="top"><img src="'. $data['thumb'] .'"><br><a href="http://www.funda.nl'. $data['url'] .'">funda.nl</a> | <a href="'. $ScriptURL .'admin/edit.php?id='. $id .'">edit</a></td>';
	$KML_file[] = '			<td valign="top">&nbsp;</td>';
	$KML_file[] = '			<td valign="top"><b>'. $data['PC_c'] .' '. $data['PC_l'] .' '. $data['plaats'] .'</b><p>';
	
	foreach($Prijzen as $key => $value)	{
		if($key != 0) {
			$KML_file[] = '		'. date('d M y', $key) .' : &euro;&nbsp;'. number_format($value,0,',','.').'<br>';
		}
	}
	
	$KML_file[] = '		</td>';
	$KML_file[] = '		</tr>';
	$KML_file[] = '		</table>';
	$KML_file[] = '		]]></description>';		
	$KML_file[] = '		<styleUrl>#style1</styleUrl>';
	$KML_file[] = '		<Point>';
	$KML_file[] = '			<coordinates>'. $data['long'] .','. $data['lat'] .',0</coordinates>';
	$KML_file[] = '		</Point>';
	$KML_file[] = '	</Placemark>';
	
	return implode("\n", $KML_file);
}


function extractDetailedFundaData($URL, $alreadyKnown=false) {
	$contents		= file_get_contents_retry($URL);
			
	# Als het geen string is, is de pagina offline
	# Dan kan gelijk een twee-tal lege arrays teruggegeven worden
	if(!is_string($contents)) {
		return array(array(), array());
	}

	# Als er een class item-sold is, is hij onder voorbehoud verkocht => $verkocht = 2
	# Als er een class item-sold-label-large is, is hij verkocht => $verkocht = 1
	# Als geen van beide het geval is, is hij nog beschikbaar => $verkocht = 0
	if(strpos($contents, '<li class="label-transactie-voorbehoud">')) {
		$verkocht		= 2;
	}elseif(strpos($contents, '<span class="item-sold-label-large" title="Verkocht">')) {
		$verkocht		= 1;
	} else {
		$verkocht		= 0;
	}
	
	if($verkocht == 1) {
		$afmeld			= getString('<span class="txt-sft">&nbsp;|&nbsp;Afmelddatum: ','</span>', $contents, 0);
		$delen = explode('-', $afmeld[0]);
		$data['afmeld'] = mktime(12,0,0,$delen[1],$delen[0],$delen[2]);
	}
		
	# Navigatie-gedeelte
	$navigatie	= getString('<ol class="container breadcrumb-list">', '</ol>', $contents, 0);
	$stappen		= explode('<li class="breadcrumb-listitem">', $navigatie[0]);
	$wijk				= getString('title="', '">', $stappen[(count($stappen)-2)], 0);
	$data['wijk']			= trim($wijk[0]);
	
	# Moeten gegevens die al bekend zijn opnieuw opgevraagd worden
	# Meestal niet, maar soms is dat nodig
	if($alreadyKnown) {
		$adresHTML	= getString('<div class="object-header-info">', '</div>', $contents, 0);
		$prijs			= getString('<strong class="object-header-price ">', '</strong>', $contents, 0);
		$makelHTML	= getString('<h2 class="object-contact-aanbieder-name">', '</h2>', $contents, 0);
		$fotoHTML		=  getString('<div class="object-media-foto">', '</div>', $contents, 0);
		
		/*
		if($verkocht == 1) {
			$foto				=	getString('" src="http:', '"', $adres[1], 0);
		} else {
			$foto				=	getString('" src="http:', '"', $prijs[1], 0);
		}
	
		if(strpos($PC[0], '<span class="item')) {
			$dummy_PC	= getString('', '<span class="item', $PC[0], 0);
			$PC[0] = $dummy_PC[0];							
		}
		*/
		$adres			= getString('">', '<span class="object-header-subtitle">', $adresHTML[0], 0);
		$PC					= getString('<span class="object-header-subtitle">', '</span>', $adresHTML[0], 0);
		$makelaar		= getString('">', '</a>', $makelHTML[0], 0);
		$foto				=	getString('<a href="', '"', $fotoHTML[0], 0);
		
		$postcode		= explode(" ", trim($PC[0]));		
		
		$data['adres']		= trim(str_replace('<span class="item-sold-label-large" title="Verkocht">VERKOCHT</span>', '', $adres[0]));
		$data['PC_c']			= trim($postcode[0]);
		$data['PC_l']			= trim($postcode[1]);
		$data['plaats']		= end($postcode);
		$data['thumb']		= trim(str_replace('_1080x720.jpg', '_360x240.jpg', $foto[0]));
		$data['makelaar']	= trim($makelaar[0]);
		$data['prijs']		= cleanPrice($prijs[0]);
		$data['verkocht']	= $verkocht;
	}
	
	if($contents != "") {		
		# Omschrijving
		//$omschrijving = getString('<div data-object-description-body class="object-description-body">', '</div>', $contents, 0);
		$omschrijving = getString('<div class="object-description-body" data-object-description-strip-markup data-object-description-body>', '</div>', $contents, 0);
		
		$KenmerkData['descr']	= trim($omschrijving[0]);	
	} else {
		$KenmerkData['descr']	= '';
	}

	# Kenmerken
	$content_kenmerk	= getString('<section class="object-kenmerken is-expanded" aria-expanded="true" data-object-kenmerken>', '</section>', $contents, 0);
	$kenmerken				= explode('<dt>', $content_kenmerk[0]);
	array_shift($kenmerken);
	
	foreach($kenmerken as $kenmerk) {
		$Record = getString('', '</dt>', $kenmerk, 0);
		$Waarde = getString('<dd>', '</dd>', $kenmerk, 0);
		
		$key = trim($Record[0]);
		$KenmerkData[$key] = trim(strip_tags($Waarde[0]));
	}
	
	# Foto	
	$content_fotos	= getString('<section class="object-media" data-object-media>', '</section>', $contents, 0);
	
	if($content_fotos[0] != "") {
		$picture		= array();
		$carousel		= explode('<div class="object-media-foto">', $content_fotos[0]);
		array_shift($carousel);
			
		foreach($carousel as $key => $value) {		
			$thumb = getString('src="', '"', $value, 0);
			//$picture[] = trim($thumb[0]);
			$picture[] = trim(str_replace('_1080x720.jpg', '_360x240.jpg', $thumb[0]));
		}
		
		$KenmerkData['foto']		= implode('|', $picture);
	}	else {
		$KenmerkData['foto']		= '';
	}
	
	return array($data, $KenmerkData);
}

function extractDetailedFundaData_old($URL, $alreadyKnown=false) {
	$contents		= file_get_contents_retry($URL);
	
	# Als het geen string is, is de pagina offline
	# Dan kan gelijk een twee-tal lege arrays teruggegeven worden
	if(!is_string($contents)) {
		return array(array(), array());
	}
	# Als er een class item-sold is, is hij onder voorbehoud verkocht => $verkocht = 2
	# Als er een class item-sold-label-large is, is hij verkocht => $verkocht = 1
	# Als geen van beide het geval is, is hij nog beschikbaar => $verkocht = 0
	if(strpos($contents, '<span class="item-sold">')) {
		$verkocht		= 2;
	}elseif(strpos($contents, '<span class="item-sold-label-large" title="Verkocht">')) {
		$verkocht		= 1;
	} else {
		$verkocht		= 0;
	}
	
	if($verkocht == 1) {
		$afmeld			= getString('<span class="txt-sft">&nbsp;|&nbsp;Afmelddatum: ','</span>', $contents, 0);
		$delen = explode('-', $afmeld[0]);
		$data['afmeld'] = mktime(12,0,0,$delen[1],$delen[0],$delen[2]);
	}
	
	# Navigatie-gedeelte
	$navigatie	= getString('<p class="section path-nav">', '</p>', $contents, 0);
	$stappen		= explode('&gt;', $navigatie[0]);
	$wijk				= getString('<span itemprop="title">', '</span>', $stappen[(count($stappen)-1)], 0);
	$data['wijk']			= trim($wijk[0]);
	
	
	
	# Moeten gegevens die al bekend zijn opnieuw opgevraagd worden
	# Meestal niet, maar soms is dat nodig
	if($alreadyKnown) {
		$adres			= getString('<h1>', '</h1>', $navigatie[1], 0);
		$PC					= getString('<p>', '</p>', $adres[1], 0);
		$prijs			= getString('<span class="price">', '</span>', $PC[1], 0);
		$rel_info		= getString('<h3>', '</h3>', $contents, 0);
		
		if($verkocht == 1) {
			$foto				=	getString('" src="http:', '"', $adres[1], 0);
		} else {
			$foto				=	getString('" src="http:', '"', $prijs[1], 0);
		}
	
		if(strpos($PC[0], '<span class="item')) {
			$dummy_PC	= getString('', '<span class="item', $PC[0], 0);
			$PC[0] = $dummy_PC[0];							
		}
	
		$postcode		= explode(" ", trim($PC[0]));
	
		$HuisPrijs	= $prijs[0];			
		$HuisPrijs	= str_ireplace('&euro;&nbsp;', '' , $HuisPrijs);
		$HuisPrijs	= str_ireplace('.', '' , $HuisPrijs);
		
		$makelaar	= getString('">', '</a>', $rel_info[0], 0);
		
		$data['adres']		= trim(str_replace('<span class="item-sold-label-large" title="Verkocht">VERKOCHT</span>', '', $adres[0]));
		$data['PC_c']			= trim($postcode[0]);
		$data['PC_l']			= trim($postcode[1]);
		$data['plaats']		= end($postcode);
		$data['thumb']		= 'http:'.trim($foto[0]);
		$data['makelaar']	= trim($makelaar[0]);
		$data['prijs']		= $HuisPrijs;
		$data['verkocht']	= $verkocht;
	}
	
	if($contents != "") {		
		# Omschrijving
		$contents_omschrijving		= file_get_contents_retry($URL.'omschrijving/');
				
		if(strpos($contents_omschrijving, '<div class="description-full">')) {			
			$omschrijving = getString('<div class="description-full">', '</div>', $contents_omschrijving, 0);
		} else {
			$contents		= file_get_contents_retry($URL);
			$omschrijving = getString('<p id="PVolledigeOmschrijving" style="display:none">', '<a id="linkKorteOmschrijving"', $contents, 0);
		}
		
		$KenmerkData['descr']	= trim($omschrijving[0]);	
	} else {
		$KenmerkData['descr']	= '';
	}
	# Kenmerken
	$contents		= file_get_contents_retry($URL.'kenmerken/');
	$contents		= getString('<table class="specs specs-cats" border="0">', '</table>', $contents, 0);
	$kenmerken12	= explode('12"  class="', $contents[0]);	array_shift($kenmerken12);
	$kenmerken13	= explode('13"  class="', $contents[0]);	array_shift($kenmerken13);
	$kenmerkenBla	= explode('blabla"  class="', $contents[0]);	array_shift($kenmerkenBla);
	$kenmerken		= array_merge($kenmerken12, $kenmerken13, $kenmerkenBla);
	
	foreach($kenmerken as $kenmerk) {
		$Record = getString('<th scope="row">', '</th>', $kenmerk, 0);
		$Waarde = getString('<span class="specs-val">', '</span>', $kenmerk, 0);
		
		$key = trim($Record[0]);
		$KenmerkData[$key] = trim(strip_tags($Waarde[0]));
	}
	
	# Foto	
	$contents		= file_get_contents_retry($URL.'fotos/');
	
	if($contents != "") {
		$picture		= array();
		$carousel		= explode('class="thumb-media"><span>', $contents);
		array_shift($carousel);
			
		foreach($carousel as $key => $value) {		
			$thumb = getString('<img src="', '" onerror', $value, 0);
			$picture[] = trim($thumb[0]);
		}
		
		$KenmerkData['foto']		= implode('|', $picture);
	}	else {
		$KenmerkData['foto']		= '';
	}
	
	return array($data, $KenmerkData);
}


function knownHouse($key) {
	global $TableHuizen, $HuizenID;	
	connect_db();
	
	$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenID like '$key'";
			
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) == 1) {
		return true;
	} else {
		return false;
	}
}


function soldHouse($key) {
	global $TableHuizen, $HuizenID, $HuizenVerkocht;	
	connect_db();
	
	$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenID like '$key' AND $HuizenVerkocht like '1'";
			
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) == 1) {
		return true;
	} else {
		return false;
	}
}


function soldHouseTentative($key) {
	global $TableHuizen, $HuizenID, $HuizenVerkocht;	
	connect_db();
	
	$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenID like '$key' AND $HuizenVerkocht like '2'";
			
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) == 1) {
		return true;
	} else {
		return false;
	}
}


function newHouse($key, $opdracht) {
	global $TableResultaat, $ResultaatID, $ResultaatZoekID;	
	connect_db();
	
	$sql		= "SELECT * FROM $TableResultaat WHERE $ResultaatID like '$key' AND $ResultaatZoekID like '$opdracht'";
			
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) == 1) {
		return false;
	} else {
		return true;
	}
}


function saveHouse($data, $moreData) {	
	global $TableHuizen, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenStart, $HuizenEind;
	global $TableKenmerken, $KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue;
	
	connect_db();
	
	if(!isset($data['begin'])) {
		$begin_tijd = mktime(0, 0, 1);
	} else {
		$begin_tijd = $data['begin'];
	}
	
	if(!isset($data['eind'])) {
		$eind_tijd = mktime(23, 59, 59);
	} else {
		$eind_tijd = $data['eind'];
	}	
	
	$sql  = "INSERT INTO $TableHuizen ";
	$sql .= "($HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenStart, $HuizenEind) ";
	$sql .= "VALUES ";
	$sql .= "('". $data['id'] ."', '". urlencode($data['url']) ."', '". urlencode($data['adres']) ."', '". urlencode($data['PC_c']) ."', '". urlencode($data['PC_l']) ."', '". urlencode($data['plaats']) ."', '". urlencode($data['wijk']) ."', '". urlencode($data['thumb']) ."', '". urlencode($data['makelaar']) ."', '$begin_tijd', '$eind_tijd')";
			
	if(!mysql_query($sql)) {		
		return false;
	}
	
	foreach($moreData as $key => $value) {
		$sql = "INSERT INTO $TableKenmerken ($KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue) VALUES ('". $data['id'] ."', '". urlencode($key) ."', '". urlencode($value) ."')";
						
		if(!mysql_query($sql)) {
			return false;			
		}
	}
	
	return true;
}


function updateHouse($data, $kenmerken, $erase = false) {
	global $TableHuizen, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenAfmeld, $HuizenVerkocht;
	global $TableKenmerken, $KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue;
	
	connect_db();
	
	$velden = array(
		'id'				=> $HuizenID,       
		'url'				=> $HuizenURL,    
		'adres'			=> $HuizenAdres,  
		'PC_c'			=> $HuizenPC_c,   
		'PC_l'			=> $HuizenPC_l,   
		'plaats'		=> $HuizenPlaats, 
		'wijk'			=> $HuizenWijk,  
		'thumb'			=> $HuizenThumb,  
		'makelaar'	=> $HuizenMakelaar,
		'verkocht'	=> $HuizenVerkocht,
		'afmeld'		=> $HuizenAfmeld
	);
		
	foreach($data as $key => $value) {
		if(array_key_exists($key, $velden)) {
			$sql[] = $velden[$key] ." = '". urlencode($value) ."'";
		}
	}
	$query = "UPDATE $TableHuizen SET ". implode(', ', $sql) ." WHERE $HuizenID like '". $data['id'] ."'";
	
	if(!mysql_query($query)) {
		echo $query ."<br>\n";
	}	
	
	if($erase) {
		$query = "DELETE FROM $TableKenmerken WHERE $KenmerkenID like '". $data['id'] ."'";
		
		if(!mysql_query($query)) {
			echo $query ."<br>\n";
		}	
	}
	
	foreach($kenmerken as $key => $value) {
		mysql_query("DELETE FROM $TableKenmerken WHERE $KenmerkenID like '". $data['id'] ."' AND $KenmerkenKenmerk like '". urlencode($key) ."'");
		$query = "INSERT INTO $TableKenmerken ($KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue) VALUES ('". $data['id'] ."', '". urlencode($key) ."', '". urlencode($value) ."')";
		
		if(!mysql_query($query)) {
			echo $query ."<br>\n";
		}
	}
}


function soldBefore($id) {
	global $TableHuizen, $HuizenAdres, $HuizenPC_c, $HuizenID, $HuizenVerkocht;
	connect_db();
	
	$data = getFundaData($id);
	
	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '". urlencode($data['adres']) ."' AND $HuizenPC_c like '". $data['PC_c'] ."' AND $HuizenVerkocht like '1' AND $HuizenID not like '$id'";
		
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) == 0) {
		return false;
	} else {
		$row = mysql_fetch_array($result);
		return $row[$HuizenID];
	}	
}


function onlineBefore($id) {
	global $TableHuizen, $HuizenAdres, $HuizenPC_c, $HuizenID, $HuizenOffline, $HuizenVerkocht;
	connect_db();
	
	$data = getFundaData($id);
	
	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '". urlencode($data['adres']) ."' AND $HuizenPC_c like '". $data['PC_c'] ."' AND $HuizenOffline like '1' AND $HuizenVerkocht like '0' AND $HuizenID not like '$id'";
		
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) == 0) {
		return false;
	} else {
		$row = mysql_fetch_array($result);
		return $row[$HuizenID];
	}	
}


function alreadyOnline($id) {
	global $TableHuizen, $HuizenAdres, $HuizenPC_c, $HuizenID, $HuizenOffline, $HuizenVerkocht;
	connect_db();
	
	$data = getFundaData($id);
	
	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '". urlencode($data['adres']) ."' AND $HuizenPC_c like '". $data['PC_c'] ."' AND $HuizenOffline like '0' AND $HuizenVerkocht like '0' AND $HuizenID not like '$id'";
		
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) == 0) {
		return false;
	} else {
		$row = mysql_fetch_array($result);
		return $row[$HuizenID];
	}	
}


function addHouse($data, $id) {
	global $TableResultaat, $ResultaatZoekID, $ResultaatID, $ResultaatPrijs;

	connect_db();
	$sql = "INSERT INTO $TableResultaat ($ResultaatZoekID, $ResultaatID, $ResultaatPrijs) VALUES ($id, '". $data['id'] ."', '". $data['prijs'] ."')";

	if(!mysql_query($sql)) {
		return false;
	} else {
		return true;
	}
}


function updateAvailability($id) {
	global $TableHuizen, $HuizenEind, $HuizenOffline, $HuizenID;
	connect_db();
	
	$eind_tijd = mktime(23, 59, 59);
	
	$sql = "UPDATE $TableHuizen SET $HuizenEind = $eind_tijd, $HuizenOffline = '0' WHERE $HuizenID like '$id'";
	
	if(!mysql_query($sql)) {
		return false;
	} else {
		return true;
	}
}


function newPrice($key, $price) {
	$history = getPriceHistory($key);
		
	if($price != current($history)) {
		return true;
	} else {
		return false;
	}		
}


function updatePrice($id, $price, $tijd = 0) {
	global $TablePrijzen, $PrijzenID, $PrijzenPrijs, $PrijzenTijd;	
	connect_db();
	
	if($tijd == 0) {
		$tijd = time();
	}
		
	$sql = "INSERT INTO $TablePrijzen ($PrijzenID, $PrijzenPrijs, $PrijzenTijd) VALUES ('$id', $price, ". $tijd .")";
		
	if(!mysql_query($sql)) {
		echo $sql;
		return false;
	} else {
		return true;
	}
}


function changedPrice($id, $price, $opdracht) {
	global $TableResultaat, $ResultaatZoekID, $ResultaatID, $ResultaatPrijs;
	connect_db();
	
	$sql = "SELECT * FROM $TableResultaat WHERE $ResultaatZoekID like '$opdracht' AND $ResultaatID like '$id'";
	$result = mysql_query($sql);
	$row = mysql_fetch_array($result);
	
	if($price == $row[$ResultaatPrijs]) {
		return false;
	} else {
		$sql = "UPDATE $TableResultaat SET $ResultaatPrijs = '$price' WHERE $ResultaatZoekID like '$opdracht' AND $ResultaatID like '$id'";
		mysql_query($sql);
		return true;
	}
}


function getFundaData($id) {
	global $TableHuizen, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenLat, $HuizenLon, $HuizenStart, $HuizenEind, $HuizenOffline, $HuizenVerkocht, $HuizenOpenHuis;
	connect_db();
  
  if($id != 0) {
  	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenID = $id";
		$result = mysql_query($sql);
	
		if(mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
			
			$data['id']			= urldecode($row[$HuizenID]);
			$data['url']			= urldecode($row[$HuizenURL]);
			$data['adres']		= urldecode($row[$HuizenAdres]);
			$data['PC_c']			= $row[$HuizenPC_c];	
			$data['PC_l']			= $row[$HuizenPC_l];		
			$data['plaats']		= urldecode($row[$HuizenPlaats]);
			$data['wijk']			= urldecode($row[$HuizenWijk]);
			$data['thumb']		= urldecode($row[$HuizenThumb]);
			$data['makelaar']	= urldecode($row[$HuizenMakelaar]);
			$data['lat']			= $row[$HuizenLat];
			$data['long']			= $row[$HuizenLon];
			$data['start']		= $row[$HuizenStart];
			$data['eind']			= $row[$HuizenEind];
			$data['verkocht']	= $row[$HuizenVerkocht];
			$data['offline']	= $row[$HuizenOffline];
			$data['openhuis']	= $row[$HuizenOpenHuis];
			
			return $data;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


function getFundaKenmerken($id) {
	global $TableKenmerken, $KenmerkenID, $KenmerkenValue, $KenmerkenKenmerk;
	connect_db();
  
  if($id != 0) {
  	$sql = "SELECT * FROM $TableKenmerken WHERE $KenmerkenID = $id";
		$result = mysql_query($sql);
	
		if($row = mysql_fetch_array($result)) {
			do {
				$key = urldecode($row[$KenmerkenKenmerk]);
				$data[$key] = urldecode($row[$KenmerkenValue]);
			} while($row = mysql_fetch_array($result));			
		}
		
		ksort($data);
		
		return $data;
	} else {
		return false;
	}
}


function getHuizen($opdracht, $excludeVerkocht = false, $excludeOffline = false) {
	global $TableHuizen, $HuizenID, $HuizenAdres, $HuizenVerkocht, $HuizenOffline;
	global $TableResultaat, $ResultaatID, $ResultaatZoekID;
	connect_db();
	
	$sql = "SELECT * FROM $TableHuizen, $TableResultaat WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID like '$opdracht' ";
	if($excludeVerkocht) {
		$sql .= "AND $TableHuizen.$HuizenVerkocht NOT like '1' ";
	}
	if($excludeOffline) {
		$sql .= "AND $TableHuizen.$HuizenOffline NOT like '1' ";
	}
	$sql .= "ORDER BY $TableHuizen.$HuizenAdres";
	
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
		
	do {
		$output[] = $row[$HuizenID];
	} while($row = mysql_fetch_array($result));
	
	return $output;
}


function getPriceHistory($input) {
	global $TablePrijzen, $PrijzenTijd, $PrijzenID, $PrijzenPrijs;	
	connect_db();
	
	$sql		= "SELECT $PrijzenTijd, $PrijzenPrijs FROM $TablePrijzen WHERE $PrijzenID like '$input' ORDER BY $PrijzenTijd DESC";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	
	do {
		$index						= $row[$PrijzenTijd];
		$PriceTable[$index] = $row[$PrijzenPrijs];		
	} while($row = mysql_fetch_array($result));
	
	return $PriceTable;
}


function getHuidigePrijs($input) {
	$prijzen			= getPriceHistory($input);
	$HuidigePrijs	= array_slice ($prijzen, 0, 1);
	
	return $HuidigePrijs[0];
}


function getOrginelePrijs($input) {
	$prijzen			= getPriceHistory($input);
	$OriginelePrijs	= array_slice ($prijzen, (count($prijzen)-1), 1);
	
	return $OriginelePrijs[0];
}


function formatPrice($input, $euro = true) {
	if($euro) {
		return "&euro;&nbsp;". number_format($input, 0,'','.');
	} else {
		return number_format($input, 0,'','.');
	}
}


function formatPercentage($input) {
	if(is_int($input)) {
		$dec = 0;
	} else {
		$dec = 1;
	}
	return number_format($input, $dec, ',','') .'%';
}


function getFullPriceHistory($input) {
	$afname = $percentage = $overall_afname = $overall_percentage = array();
	$prizeArray = getPriceHistory($input);
		
	$HuidigePrijs		= array_slice ($prizeArray, 0, 1);
	$OriginelePrijs	= array_slice ($prizeArray, (count($prizeArray)-1), 1);
	
	$prijzenRev	= array_reverse($prizeArray, true);
	$vorige			= $OriginelePrijs[0];
	current($prijzenRev);
	
	if($vorige == 0) {
		toLog('error', '', $input, 'Onjuiste prijs-historie');
	}
	
	foreach($prijzenRev as $key => $prijs) {	
		$afname[$key]			= 100*($vorige - $prijs)/$vorige;
		$percentage[$key]	= 100*$prijs/$vorige;
		$vorige				= $prijs;
		
		$overall_afname[$key]			= 100*($OriginelePrijs[0] - $prijs)/$OriginelePrijs[0];
		$overall_percentage[$key]	= 100*$prijs/$OriginelePrijs[0];
	}
	
	$output[0] = $prijzenRev;
	$output[1] = $percentage;
	$output[2] = $afname;
	$output[3] = $overall_percentage;
	$output[4] = $overall_afname;
	$output[5] = 100*$HuidigePrijs[0]/$OriginelePrijs[0];
		
	return $output;
}


function toLog($type, $opdracht, $huis, $message) {
	global $TableLog, $LogTime, $LogType, $LogOpdracht, $LogHuis, $LogMessage;

	connect_db();
 	
	$tijd = time();	
	$sql = "INSERT INTO $TableLog ($LogTime, $LogType, $LogOpdracht, $LogHuis, $LogMessage) VALUES ($tijd, '$type', '$opdracht', '$huis', '". addslashes($message) ."')";
	if(!mysql_query($sql)) {
		echo "log-error : ". $sql;
	}
}


function getTimeBetween($start, $einde) {
	$dagE		= date("d", $einde);
	$maandE	= date("m", $einde);
	$jaarE	= date("Y", $einde);
	
	$dagB		= date("d", $start)-1;
	$maandB	= date("m", $start);
	$jaarB	= date("Y", $start);
	
	$dag		= $dagE - $dagB;
	$maand	= $maandE - $maandB;
	$jaar		= $jaarE - $jaarB;
		
	if($dag < 0) {
		$dag = $dag + date("t", mktime(0,0,0,$maandE-1,$jaarE,$dagE));
		$maand = $maand - 1;
	}
	
	if($maand < 0) {
		$maand = $maand + 12;
		$jaar = $jaar - 1;
	}
	
	# Druk het uit in weken
	if($dag >= 7) {
		$week = floor($dag/7);
		$dag = 0;
	}
	
	# De doorlooptijd wordt maximaal uitgedrukt in 2 'eenheden'.
	# Dus niet 1j & 8m & 3wk & 1d maar gewoon 1j & 9m
	if($jaar != 0) {
		$output[] = $jaar ."j";		
		if($maand != 0 AND $week >= 2) {
			$output[] = ($maand+1) ."m";
		} elseif($maand != 0) {
			$output[] = $maand ."m";
		} elseif($week != 0) {
			$output[] = $week ."wk";
		}
	} elseif($maand != 0) {
		$output[] = $maand ."m";		
		if($week != 0) {
			$output[] = $week ."wk";
		} elseif($dag != 0) {
			$output[] = $dag ."d";
		}
	} elseif($week != 0) {
		$output[] = $week ."wk";		
		if($dag != 0) {
			$output[] = $dag ."d";
		}
	} else {
		$output[] = $dag ."d";
	}
	
	return $output;
}


function getDoorloptijd($id) {	
	$data = getFundaData($id);
	
	$output = getTimeBetween($data['start'], $data['eind']);
			
	return implode(" & ", $output);
}


function changeThumbLocation($string) {
	$string = str_replace('valentinamedia', 'valentina_media', $string);
	$string = str_replace('images.funda.nl/valentina', 'cloud.funda.nl/valentina', $string);
	return $string;
}


function changeURLLocation($string) {
	if(substr($string, 0, 6) == '/koop/' AND substr($string, 0, 15) != '/koop/verkocht/') {
		return '/koop/verkocht/'. substr($string, 6);
	} else {
		return $string;
	}
}


function guessDate($string) {	
	$string = str_ireplace('zondag ', '', $string);
	$string = str_ireplace('maandag ', '', $string);
	$string = str_ireplace('dinsdag ', '', $string);
	$string = str_ireplace('woensdag ', '', $string);
	$string = str_ireplace('donderdag ', '', $string);
	$string = str_ireplace('vrijdag ', '', $string);
	$string = str_ireplace('zaterdag ', '', $string);
		
	$string = str_ireplace('januari', '-01-', $string);
	$string = str_ireplace('februari', '-02-', $string);
	$string = str_ireplace('maart', '-03-', $string);
	$string = str_ireplace('april', '-04-', $string);
	$string = str_ireplace('mei', '-05-', $string);
	$string = str_ireplace('juni', '-06-', $string);
	$string = str_ireplace('juli', '-07-', $string);
	$string = str_ireplace('augustus', '-08-', $string);
	$string = str_ireplace('september', '-09-', $string);
	$string = str_ireplace('oktober', '-10-', $string);
	$string = str_ireplace('november', '-11-', $string);
	$string = str_ireplace('december', '-12-', $string);
	$string = str_ireplace('sept', '-09-', $string);
	$string = str_ireplace('jan', '-01-', $string);
	$string = str_ireplace('feb', '-02-', $string);
	$string = str_ireplace('mrt', '-03-', $string);
	$string = str_ireplace('apr', '-04-', $string);
	$string = str_ireplace('mei', '-05-', $string);
	$string = str_ireplace('jun', '-06-', $string);
	$string = str_ireplace('jul', '-07-', $string);
	$string = str_ireplace('aug', '-08-', $string);
	$string = str_ireplace('sep', '-09-', $string);
	$string = str_ireplace('okt', '-10-', $string);
	$string = str_ireplace('nov', '-11-', $string);
	$string = str_ireplace('dec', '-12-', $string);
	
	$string = str_replace(' -', '-', $string);
	$string = str_replace('- ', '-', $string);
	
	$delen = explode('-', $string);
	if(count($delen) == 3) {
		if($delen[2] == '') {
			if(mktime(0,0,0,$delen[1],$delen[0],date('Y')) < time()){
				$delen[2] = date('Y')+1;
			} else {
				$delen[2] = date('Y');
			}
		}		
		$string = implode('-', $delen);
	}
	
	return $string;
}


function getLijsten($id, $active) {
	global $TableList, $ListID, $ListUser, $ListActive, $ListNaam;
	$Lijsten = array();
	connect_db();
			
	if($active != '') {
		$where[] = "$ListActive = '$active'";
	}
	
	if($id != '') {
		$where[] = "$ListUser = '$id'";
	}
	
	$sql = "SELECT $ListID FROM $TableList WHERE ". implode(" AND ", $where) ." ORDER BY $ListNaam";
			
	$result = mysql_query($sql);
	
	if($row = mysql_fetch_array($result)) {
		do {
			$Lijsten[] = $row[$ListID];
		} while($row = mysql_fetch_array($result));
	}
	
	return $Lijsten;
}


function getLijstData($id) {
	global $TableList, $ListID, $ListActive, $ListNaam;
		
	$sql = "SELECT * FROM $TableList WHERE $ListID = '$id'";
	$result = mysql_query($sql);
	$row = mysql_fetch_array($result);
	
	$data['id']			= $row[$ListID];
	$data['active'] = $row[$ListActive];
	$data['naam']		= urldecode($row[$ListNaam]);
	
	return $data;	
}


function getLijstHuizen($list, $excludeVerkocht = false, $excludeOffline = false) {
	global $TableListResult, $TableHuizen, $ListResultHuis, $ListResultList, $HuizenID, $HuizenAdres, $HuizenVerkocht, $HuizenOffline;
	
	$from		= "$TableListResult, $TableHuizen";
	$where	= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $list";
	if($excludeVerkocht) {
		$where .= " AND $TableHuizen.$HuizenVerkocht NOT like '1'";
	}	
	if($excludeOffline ) {
		$where .= " AND $HuizenOffline NOT like '1'";
	}	
	$sql		= "SELECT $TableHuizen.$HuizenID FROM $from WHERE $where ORDER BY $TableHuizen.$HuizenAdres";
	$result = mysql_query($sql);
	
	$Huizen = array();

	if($row = mysql_fetch_array($result)) {
		do {
			$Huizen[] = $row[$HuizenID];
		} while($row = mysql_fetch_array($result));
	}
	
	return $Huizen;		
}


function addHouse2List($huis, $list) {
	global $TableListResult, $ListResultList, $ListResultHuis;
	
	$sql_check = "SELECT * FROM $TableListResult WHERE $ListResultList like $list AND $ListResultHuis like '$huis'";
	$result	= mysql_query($sql_check);
			
	if(mysql_num_rows($result) == 0) {
		$data = getFundaData($huis);
		
		$sql_insert = "INSERT INTO $TableListResult ($ListResultList, $ListResultHuis) VALUES ($list, $huis)";
		if(!mysql_query($sql_insert)) {
			# huis niet toegevoegd
			$output = "<b>". $data['adres'] ." niet toegevoegd</b><br>";
		} else {
			# huis toegevoegd
			$output = $data['adres'] ." toegevoegd<br>";
		}
	} else {
		# huis bestaat al
		$output = "";
	}
	
	return $output;
}


function saveUpdateList($id, $user, $actief, $naam) {
	global $TableList, $ListUser, $ListActive, $ListNaam, $ListID;
	
	if($id == '') {
		$sql = "INSERT INTO $TableList ($ListUser, $ListActive, $ListNaam) VALUES ('$user', '". ($actief == '1' ? '1' : '0') ."', '". urlencode($naam) ."')";
	} else {
		$sql = "UPDATE $TableList SET $ListActive = '". ($actief == '1' ? '1' : '0') ."', $ListUser = '$user', $ListNaam = '". urlencode($naam) ."' WHERE $ListID = ". $id;
	}
	
	$result = mysql_query($sql);
	
	if($id == '') {
		return mysql_insert_id();
	} else {
		return $result;
	}		
}


function getUsers() {
	global $TableUsers, $UsersID;
	
	$Users = array();
	
	$sql = "SELECT * FROM $TableUsers";
	$result = mysql_query($sql);
	
	if($row = mysql_fetch_array($result)) {
		do {
			$Users[] = $row[$UsersID];
		} while($row = mysql_fetch_array($result));
	}
	
	return $Users;	
}


function getMemberDetails($id) {
	global $TableUsers, $UsersID, $UsersName, $UsersUsername, $UsersPassword, $UsersLevel, $UsersAdres, $UsersAccount, $UsersLastLogin, $UsersPOKey, $UsersPOToken;
	
	$sql		= "SELECT * FROM $TableUsers WHERE $UsersID like '$id'";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
		
	$data['id']				= $row[$UsersID];
	$data['naam']			= $row[$UsersName];
	$data['username']	= $row[$UsersUsername];
	$data['password']	= $row[$UsersPassword];
	$data['level']		= $row[$UsersLevel];
	$data['mail']			= $row[$UsersAdres];	
	$data['userkey']	= $row[$UsersPOKey];
	$data['token']		= $row[$UsersPOToken];	
	$data['account']	= $row[$UsersAccount];
	$data['login']		= $row[$UsersLastLogin];
	
	return $data;
}


function saveUpdateMember($id, $name, $username, $wachtwoord, $mail, $po_key, $po_token, $level, $gebruiker) {
	global $TableUsers, $UsersID, $UsersName, $UsersUsername, $UsersPassword, $UsersLevel, $UsersAdres, $UsersPOKey, $UsersPOToken, $UsersAccount;
	
	if($level == 1) {
		$account = $gebruiker;
	} else {
		$account = 0;
	}
	
	if($id == 0) {
		$sql = "INSERT INTO $TableUsers ($UsersName, $UsersUsername, $UsersPassword, $UsersLevel, $UsersAdres, $UsersPOKey, $UsersPOToken". ($account != 0 ? ", $UsersAccount" : '') .") VALUES ('$name', '$username', '". md5($wachtwoord) ."', $level, '$mail', '$po_key', '$po_token'". ($account != 0 ? ", '$account'" : '') .")";
	} else {
		$sql = "UPDATE $TableUsers SET $UsersName = '$name', $UsersUsername = '$username', ". ($wachtwoord != '' ? "$UsersPassword = '". md5($wachtwoord) ."', " : '') ."$UsersLevel = $level, $UsersAdres = '$mail', $UsersPOKey = '$po_key', $UsersPOToken = '$po_token'". ($account != 0 ? ", $UsersAccount = '$account'" : '') ." WHERE $UsersID = ". $id;
	}
				
	$result = mysql_query($sql);
	
	if($id == '') {
		return mysql_insert_id();
	} else {
		return $result;
	}		
}


function getMembers4Opdracht($OpdrachtID, $type) {
	global $TableAbo, $AboZoekID, $AboUserID, $AboType;
	
	$sql = "SELECT * FROM $TableAbo WHERE $AboZoekID like '$OpdrachtID' AND $AboType like '$type'";
	$result = mysql_query($sql);
	
	$Members = array();

	if($row = mysql_fetch_array($result)) {
		do {
			$Members[] = $row[$AboUserID];
		} while($row = mysql_fetch_array($result));
	}
	
	return $Members;
}


function addMember2Opdracht($opdracht, $user, $type) {
	global $TableAbo, $AboZoekID, $AboUserID, $AboType;
	
	$sql = "INSERT INTO $TableAbo ($AboZoekID, $AboUserID, $AboType) VALUES ($opdracht, $user, '$type')";
	return mysql_query($sql);
}


function removeMember4Opdracht($opdracht, $user, $type) {
	global $TableAbo, $AboZoekID, $AboUserID, $AboType;
	
	$sql = "DELETE FROM $TableAbo WHERE $AboZoekID = $opdracht AND $AboUserID = $user AND $AboType like '$type'";
	return mysql_query($sql);
}


function extractAndUpdateVerkochtData($fundaID, $opdrachtID = '') {
	global $TableHuizen, $HuizenStart, $HuizenEind, $HuizenAfmeld, $HuizenVerkocht, $HuizenOffline, $HuizenID;
	
	# Alles weer opnieuw initialiseren.
	unset($prijs, $naam);
	unset($Aanmelddatum, $Verkoopdatum, $AangebodenSinds, $startdata);
	unset($OorspronkelijkeVraagprijs, $LaatsteVraagprijs, $Vraagprijs);
	$offline = false;
	
	$FundaData = getFundaData($fundaID);
	$url			= "http://www.funda.nl". urldecode($FundaData['url']);
	
	# Via de kenmerkenpagina
	$allData	= extractDetailedFundaData($url);
	$generalData = $allData[0];
	$data			= $allData[1];
	
	if($generalData['afmeld'] != "") {
		$sql_update = "UPDATE $TableHuizen SET $HuizenAfmeld = ". $generalData['afmeld'] ." WHERE $HuizenID like $fundaID";
		if(mysql_query($sql_update)) {
			$HTML[] = " -> afgemeld<br>";
		}			
	}
			
	# Als de array 'data' groter is dan 3 is er data gevonden in de kenmerken-pagina
	if(count($data) > 3) {
		# Reeds verkochte huizen
		if($data['Aanmelddatum'] != '') {
			$guessStartDatum	= guessDate($data['Aanmelddatum']);
			$startDatum	= explode("-", $guessStartDatum);
			$Aanmelddatum = mktime(0, 0, 1, $startDatum[1], $startDatum[0], $startDatum[2]);
		}
							
		if($data['Verkoopdatum'] != '') {
			$guessVerkoopDatum = guessDate($data['Verkoopdatum']);
			$verkoopDatum	= explode("-", $guessVerkoopDatum);
			$Verkoopdatum = mktime(23, 59, 59, $verkoopDatum[1], $verkoopDatum[0], $verkoopDatum[2]);			
		}			

		if($data['Laatste vraagprijs'] != '') {
			$prijzen		= explode(" ", $data['Laatste vraagprijs']);				
			$LaatsteVraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
		}
									
		# Huizen die nog niet verkocht zijn
		if($data['Aangeboden sinds'] != '') {
			if($data['Aangeboden sinds'] == '5 maanden') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-5, date('d'), date('Y'));
			} elseif($data['Aangeboden sinds'] == '4 maanden') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-4, date('d'), date('Y'));
			} elseif($data['Aangeboden sinds'] == '3 maanden') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-3, date('d'), date('Y'));
			} elseif($data['Aangeboden sinds'] == '2 maanden') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-2, date('d'), date('Y'));
			} elseif($data['Aangeboden sinds'] == '8 weken') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-56, date('Y'));
			} elseif($data['Aangeboden sinds'] == '7 weken') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-49, date('Y'));
			} elseif($data['Aangeboden sinds'] == '6 weken') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-42, date('Y'));
			} elseif($data['Aangeboden sinds'] == '5 weken') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-35, date('Y'));
			} elseif($data['Aangeboden sinds'] == '4 weken') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-28, date('Y'));
			} elseif($data['Aangeboden sinds'] == '3 weken') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-21, date('Y'));
			} elseif($data['Aangeboden sinds'] == '2 weken') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m'), date('d')-14, date('Y'));
			} elseif($data['Aangeboden sinds'] == '6+ maanden') {
				$AangebodenSinds = mktime(date('H'), date('i'), date('s'), date('m')-7, date('d'), date('Y'));
			} else {
				$guessDatum = guessDate($data['Aangeboden sinds']);
				$AangebodenDatum	= explode("-", $guessDatum);
				$AangebodenSinds = mktime(0, 0, 1, $AangebodenDatum[1], $AangebodenDatum[0], $AangebodenDatum[2]);
			}
		}
					
		if($data['Oorspronkelijke vraagprijs'] != '') {
			$prijzen		= explode(" ", $data['Oorspronkelijke vraagprijs']);
			$OorspronkelijkeVraagprijs = str_ireplace('.', '' , substr($prijzen[0], 5));
		}
					
		if($data['Vraagprijs'] != '') {
			$prijzen						= explode(" ", $data['Vraagprijs']);				
			$Vraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
		}
		
		if($data['Status'] == 'Verkocht onder voorbehoud') {
			$sql_update = "UPDATE $TableHuizen SET $HuizenVerkocht = '2' WHERE $HuizenID like $fundaID";
			if(mysql_query($sql_update)) {
				$HTML[] = " -> onder voorbehoud verkocht<br>";
			}			
		}		
	} else {		
		# De "standaard"-pagina... maar daar staat niet alles op.
		# Aan de andere kant, de kenmerken-pagina werkt niet overal
		$contents = file_get_contents_retry($url);
		
		if(strpos($contents, 'item-sold')) {
			$prop_transaction = getString('<div class="prop-transaction">', '</div>', $contents, 0);
			
			$transaction_date = getString('<span class="transaction-date">', '</span>', $prop_transaction[0], 0);
			$tempAanmelddatum			= getString('<strong>', '</strong>', $transaction_date[0], 0);
												
			$transaction_date_lst = getString('transaction-date lst', '</span>', $contents, 0);
			$tempVerkoopdatum			= getString('<strong>', '</strong>', $transaction_date_lst[0], 0);
								
			$tempLaatstevraagprijs			= getString('<span class="price-wrapper">', '</span>', $contents, 0);
	  	
			$sDatum				= explode("-", $tempAanmelddatum[0]);
			$Aanmelddatum = mktime(0, 0, 1, $sDatum[1], $sDatum[0], $sDatum[2]);
	  	
			$eDatum				= explode("-", $tempVerkoopdatum[0]);
			$Verkoopdatum = mktime(23, 59, 59, $eDatum[1], $eDatum[0], $eDatum[2]);
	  	
			$prijzen						= explode(" ", strip_tags($tempLaatstevraagprijs[0]));
			$LaatsteVraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 12));
		} elseif(!is_string($contents)) {			
			$sql_update = "UPDATE $TableHuizen SET $HuizenOffline = '1' WHERE $HuizenID like $fundaID";
		
			if(mysql_query($sql_update)) {
				$HTML[] = " -> is offline<br>";
			}
			$offline = true;			
		}
	}
	
	# Van de 3 bekende data de laagste opzoeken
	if($Aanmelddatum > 10)		{ $startdata[] = $Aanmelddatum;	}
	if($AangebodenSinds > 10)	{ $startdata[] = $AangebodenSinds; }
															$startdata[] = $FundaData['start'];
															
	$startDatum = min($startdata);
			
	# Soms wordt een huis erafgehaald en dan paar dagen later er weer opgezet.
	# Om te zorgen dat de 'valse' informatie op de site de data in de dB niet overschrijft wordt de check gedaan.
	if($OorspronkelijkeVraagprijs > 0 AND $Aanmelddatum  == $startDatum) {
		$tijdstip = $Aanmelddatum;
		$prijs[$tijdstip]	= $OorspronkelijkeVraagprijs;
		$naam[$tijdstip]	= 'Oorspronkelijke vraagprijs';
	}

	# Bij aangeboden sinds gaat men niet verder dan 6 maanden.
	# Om te zorgen dat bij een huis wat al twee jaar te koop staat en 9 maanden geleden in prijs is gedaald,
	# niet de oorspronkelijke vraagprijs wordt ingevoerd even de check.
	if($OorspronkelijkeVraagprijs > 0 AND $AangebodenSinds  == $startDatum) {
		$tijdstip = $AangebodenSinds;
		$prijs[$tijdstip]	= $OorspronkelijkeVraagprijs;
		$naam[$tijdstip]	= 'Oorspronkelijke vraagprijs';
	}
			
	# We gaan er vanuit dat de laatste vraagprijs ook de verkoopdatum is
	if($LaatsteVraagprijs > 0 AND $Verkoopdatum > 10) {
		$tijdstip = $Verkoopdatum;
		$prijs[$tijdstip]	= $LaatsteVraagprijs;
		$naam[$tijdstip]	= 'Laatste vraagprijs';				
	}			
			
	# Sommige huizen verdwijnen van de radar, als ze nog wel online zijn het prijsverloop monitoren.
	if($Vraagprijs > 0) {
		$tijdstip = time();
		$prijs[$tijdstip]	= $Vraagprijs;
		$naam[$tijdstip]	= 'Vraagprijs';	
	}
	
	# Alle gevonden prijzen incl. tijdstippen invoeren			
	foreach($prijs as $key => $value) {				
		if(updatePrice($fundaID, $value, $key)) {
			$HTML[] = " -> ". $naam[$key] ." toegevoegd ($value / ". date("d-m-y", $key) .")<br>";
			toLog('debug', $opdrachtID, $fundaID, $naam[$key] ." toegevoegd");
		} else {
			toLog('error', $opdrachtID, $fundaID, "Error met toevoegen $value als ". $naam[$key]);
		}
	}
	
	# Als er een startdatum gevonden is die verder terugligt dan die bekend was => invoegen
	if($startDatum != $FundaData['start'] AND !$offline) {
		$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum WHERE $HuizenID like $fundaID";
		
		if(mysql_query($sql_update)) {
			$HTML[] = " -> begintijd aangepast<br>";
		} else {
			toLog('error', $opdrachtID, $fundaID, "Error met verwerken begintijd");
		}				
	}

	# Als er geen verkoopdatum bekend is, is hij niet verkocht en dus nog online
	if($Verkoopdatum == '' AND !$offline) {
		$sql_update = "UPDATE $TableHuizen SET $HuizenEind = ". time() ." WHERE $HuizenID like $fundaID";
		
		if(mysql_query($sql_update)) {
			$HTML[] = " -> eindtijd aangepast<br>";
		} else {
			toLog('error', $opdrachtID, $fundaID, "Error met verwerken begintijd");
		}				
	}
		
	# Als er een verkoopdatum bekend is => die datum als eindtijd invoeren
	if($Verkoopdatum > 10) {
		$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum, $HuizenEind = $Verkoopdatum, $HuizenVerkocht = '1' WHERE $HuizenID like $fundaID";
				
		if(mysql_query($sql_update)) {
			$HTML[] = " -> begin- en eindtijd aangepast (verkocht)<br>";
			toLog('info', $opdrachtID, $fundaID, "Huis is verkocht");
		} else {
			toLog('error', $opdrachtID, $fundaID, "Error met verwerken verkocht huis");
		}			
	}
	
	return $HTML;
}


function makeDateSelection($bUur, $bMin, $bDag, $bMaand, $bJaar, $eUur, $eMin, $eDag, $eMaand, $eJaar) {
	$maandNamen = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mrt', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dec');
		
	$begin[] = "<select name='bDag'>";
	for($d=1 ; $d<=31 ; $d++)	$begin[] = "	<option value='$d'". ($d == $bDag ? ' selected' : '') .">$d</option>";
	$begin[] = "	</select>";
	$begin[] = "	<select name='bMaand'>";
	for($m=1 ; $m<=12 ; $m++)	$begin[] = "	<option value='$m'". ($m == $bMaand ? ' selected' : '') .">". $maandNamen[$m] ."</option>";
	$begin[] = "	</select>";
	$begin[] = "	<select name='bJaar'>";
	for($j=1995 ; $j<=date("Y") ; $j++)	$begin[] = "	<option value='$j'". ($j == $bJaar ? ' selected' : '') .">$j</option>";
	$begin[] = "	</select>";
	
	if(is_numeric($bUur) AND is_numeric($bMin)) {
		$begin[] = "	<select name='bUur'>";
		for($u=0 ; $u<=23 ; $u++)	$begin[] = "	<option value='$u'". ($u == $bUur ? ' selected' : '') .">". substr('0'.$u, -2) ."</option>";
		$begin[] = "	</select>:";
		$begin[] = "	<select name='bMin'>";
		for($m=0 ; $m<=59 ; $m++)	$begin[] = "	<option value='$m'". ($m == $bMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
		$begin[] = "	</select>";
	}
	
	$eind[] = "<select name='eDag'>";
	for($d=1 ; $d<=31 ; $d++)	$eind[] = "	<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>";
	$eind[] = "	</select>";
	$eind[] = "	<select name='eMaand'>";
	for($m=1 ; $m<=12 ; $m++)	$eind[] = "	<option value='$m'". ($m == $eMaand ? ' selected' : '') .">". $maandNamen[$m] ."</option>";
	$eind[] = "	</select>";
	$eind[] = "	<select name='eJaar'>";
	for($j=1995 ; $j<=date("Y") ; $j++)	$eind[] = "	<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>";
	$eind[] = "	</select>";
	
	if(is_numeric($eUur) AND is_numeric($eMin)) {
		$eind[] = "	<select name='eUur'>";
		for($u=0 ; $u<=23 ; $u++)	$eind[] = "	<option value='$u'". ($u == $eUur ? ' selected' : '') .">". substr('0'.$u, -2) ."</option>";
		$eind[] = "	</select>:";
		$eind[] = "	<select name='eMin'>";
		for($m=0 ; $m<=59 ; $m++)	$eind[] = "	<option value='$m'". ($m == $eMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
		$eind[] = "	</select>";
	}
	
	return array(implode("\n", $begin), implode("\n", $eind));
}


function makeSelectionSelection($disableList, $blankOption, $preSelect = 0) {
	# Vraag alle actieve opdrachten en lijsten op en zet die in een pull-down menu
	# De value is Z... voor een zoekopdracht en L... voor een lijst		
	$Opdrachten = getZoekOpdrachten($_SESSION['account'], '', true);
	$Lijsten		= getLijsten($_SESSION['UserID'], 1);
	$Lijsten_2	= getLijsten($_SESSION['account'], 1);
	
	# Als er geen lijsten zijn of als er huizen aan een lijst worden toegevoegd
	# (het is zinloos om dan lijsten te laten zien) de lijsten disablen
	if(count($Lijsten) == 0 || $disableList) {
		$showList = false;
	} else {
		$showList = true;
	}
	
	$HTML[] = "	<select name='selectie'>";
	if($blankOption)	$HTML[] = "		<option value=''>Alle opdrachten</option>";
	$HTML[] = "	<optgroup label='Zoekopdrachten'>";
			
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		$HTML[] = "		<option value='Z$OpdrachtID'". ('Z'.$OpdrachtID == $preSelect ? ' selected' : '') .">". $OpdrachtData['naam'] ."</option>";
	}
	
	$HTML[] = "	</optgroup>";
	$HTML[] = "	<optgroup label='Lijsten'". ($showList ? '' : ' disabled') .">";
	
	foreach($Lijsten as $LijstID) {
		$LijstData = getLijstData($LijstID);
		$HTML[] = "		<option value='L$LijstID'". ('L'.$LijstID == $preSelect ? ' selected' : '') .">". $LijstData['naam'] ."</option>";
	}
	
	$HTML[] = "	</optgroup>";

	if($_SESSION['account'] != $_SESSION['UserID']) {		
		$MemberData = getMemberDetails($_SESSION['account']);
	
		$HTML[] = "	<optgroup label='Lijsten van ". $MemberData['naam'] ."'>";
	
		foreach($Lijsten_2 as $LijstID) {
			$LijstData = getLijstData($LijstID);
			$HTML[] = "		<option value='L$LijstID'". ($LijstID == $preSelect ? ' selected' : '') .">". $LijstData['naam'] ."</option>";
		}
	
		$HTML[] = "	</optgroup>";
	}
	
	$HTML[] = "	</select>";
	
	return implode("\n", $HTML);
}


function updateMakelaar($data) {
	global $TableHuizen, $HuizenMakelaar, $HuizenID;
	
	$sql = "UPDATE $TableHuizen SET $HuizenMakelaar = '". urlencode($data['makelaar']) ."' WHERE $HuizenID = '". $data['id'] ."'";
	mysql_query($sql);
}


function createXLS($kolomen, $prefixen, $huizen, $scheiding = ';') {
	# Maak de de eerste regel aan
	if(count($kolomen) > 0 || count($prefixen) > 0) {
		$CSV_kop = array('Adres');
	
		foreach($prefixen as $dummy => $prefix) {
			$CSV_kop[] = $prefix;
		}
		
		foreach($kolomen as $dummy => $kenmerk) {
			if($kenmerk == 'Achtertuin' || $kenmerk == 'Voortuin' || $kenmerk == 'Plaats') {
				$CSV_kop[] = $kenmerk;
				$CSV_kop[] = $kenmerk .' (diep)';
				$CSV_kop[] = $kenmerk .' (breed)';
			} elseif($kenmerk == 'Wonen (= woonoppervlakte)') {
				$CSV_kop[] = 'Woonoppervlakte';
			} else {
				$CSV_kop[] = $kenmerk;
			}
		}
		$CSV[] = implode($scheiding, $CSV_kop);
	}
	
	# Doorloop alle huizen en geef de waarde van het kenmerk weer
	foreach($huizen as $huisID) {
		$data				= getFundaData($huisID);
		$kenmerken	= getFundaKenmerken($huisID);
		
		if($data['offline'] == 1 AND $data['verkocht'] == 1) {
			$status = 'verkocht, offline';
		} elseif($data['offline'] == 1) {
			$status = 'offline';
		} elseif($data['verkocht'] == 1) {
			$status = 'verkocht';
		} elseif($data['verkocht'] == 2) {
			$status = 'onder voorbehoud';
		} else {
			$status = 'beschikbaar';
		}
		
		$CSV_regel = array();												$CSV_regel[] = convertToReadable($data['adres']);				
		if(in_array('ID', $prefixen))								$CSV_regel[] = $huisID;
		if(in_array('url', $prefixen))							$CSV_regel[] = 'http://funda.nl/'.$huisID;
		if(in_array('Kadaster', $prefixen))					$CSV_regel[] = 'http://funda.nl/kadaster/?ref='.$huisID;
		if(in_array('Huidige Prijs', $prefixen))		$CSV_regel[] = getHuidigePrijs($huisID);
		if(in_array('Orginele Prijs', $prefixen))		$CSV_regel[] = getOrginelePrijs($huisID);
		if(in_array('Status', $prefixen))						$CSV_regel[] = $status;
		if(in_array('Open Huis', $prefixen))				$CSV_regel[] = $data['openhuis'];
		if(in_array('Makelaar', $prefixen))					$CSV_regel[] = $data['makelaar'];
		if(in_array('Wijk', $prefixen))							$CSV_regel[] = $data['wijk'];
		if(in_array('Latitude', $prefixen))					$CSV_regel[] = str_replace('.', ',', $data['lat']);
		if(in_array('Longitude', $prefixen))				$CSV_regel[] = str_replace('.', ',', $data['long']);
		if(in_array('Energielabel (D)', $prefixen))	$CSV_regel[] = $data['Energielabel'];
		if(in_array('Energielabel (V)', $prefixen))	$CSV_regel[] = $data['Voorlopig energielabel'];
		if(in_array('Energielabel', $prefixen))			if($data['Energielabel'] != '') { $CSV_regel[] = $data['Energielabel']; } else {	$CSV_regel[] = $data['Voorlopig energielabel'];	}
		
		foreach($kolomen as $dummy => $kenmerk) {				
			$string = convertToReadable($kenmerken[$kenmerk]);
						
			if($kenmerk == 'Achtertuin' || $kenmerk == 'Voortuin' || $kenmerk == 'Plaats') {
				if(strlen($string) > 10) {
					$string = str_replace(' m²', '', $string);
					$temp = getString('', '(', $string, 0);						$CSV_regel[] = trim($temp[0]);
					$temp = getString('(', 'm diep', $string, 0);			$CSV_regel[] = trim($temp[0]);
					$temp = getString('en ', 'm breed', $string, 0);	$CSV_regel[] = trim($temp[0]);
				} else {
					$CSV_regel[] = '';
					$CSV_regel[] = '';
					$CSV_regel[] = '';
				}
			} else {					
				$string = str_replace('m�', '', $string);
				$string = str_replace('m�', '', $string);
				$CSV_regel[] = trim($string);
			}
			
		}
		$CSV[] = implode($scheiding, $CSV_regel);
	}
	
	return implode("\n", $CSV);	
}


function hasOpenHuis($id) {
	global $TableHuizen, $HuizenOpenHuis, $HuizenID;
	
	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenOpenHuis = '1' AND $HuizenID = '$id'";
	$result	= mysql_query($sql);
	
	if(mysql_num_rows($result) == 1) {
		return true;
	} else {
		return false;
	}
}


function extractOpenHuisData($id) {
	$data			= getFundaData($id);
	$contents	= file_get_contents_retry('http://www.funda.nl'.$data['url']);
	
	$propertie	= getString('<ul class="object-open-huis-description" data-object-open-huis-description>', '</ul>', $contents, 0);
	$datum			= getString('<li>', ' van ', $propertie[0], 0);
	$tijden			= getString(' van ', ' uur.</span>', $datum[1], 0);
		
	$temp				= explode('-', guessDate($datum[0]));
		
	$dag			= $temp[0];
	$maand		= $temp[1];
	$jaar			= $temp[2];	
	$beginUur	= substr($tijden[0], 0, 2);
	$beginMin	= substr($tijden[0], 3, 2);
	$eindUur	= substr($tijden[0], 10, 2);
	$eindMin	= substr($tijden[0], 13, 2);
		
	$start = mktime($beginUur, $beginMin, 0, $maand, $dag, $jaar);
	$eind = mktime($eindUur, $eindMin, 0, $maand, $dag, $jaar);
	
	return array($start, $eind);
}


function removeOpenHuis($id) {
	global $TableHuizen, $HuizenOpenHuis, $HuizenID, $TableResultaat, $ResultaatOpenHuis, $ResultaatID;
	
	$sql = "UPDATE $TableHuizen SET $HuizenOpenHuis = '0' WHERE $HuizenID = '$id'";
	mysql_query($sql);
	
	$sql = "UPDATE $TableResultaat SET $ResultaatOpenHuis = '0' WHERE $ResultaatID = '$id'";
	mysql_query($sql);
}


function getNextOpenhuis($id) {
	global $TableCalendar, $CalendarHuis, $CalendarStart, $CalendarEnd;
	
	$nu			= mktime(0,0,0);	
	$sql		= "SELECT * FROM $TableCalendar WHERE $CalendarStart > $nu AND $CalendarHuis = '$id'";

	$result = mysql_query($sql);
	$row		= mysql_fetch_array($result);
	
	return array($row[$CalendarStart], $row[$CalendarEnd]);
}


function makeHuizenZoekerURL($data) {	
	$string	= findProv($data['PC_c']) ."###". $data['plaats'] ."###". $data['adres'];
	$string = strtolower($string);
	$string = str_replace(".", "", $string);
	$string = str_replace(",", "", $string);
	$string = str_replace("/", "", $string);
	$string = str_replace(" -", "-", $string);
	$string = str_replace("- ", "-", $string);
	$string = str_replace(" ", "-", $string);
	
	$string = str_replace("###", "/", $string);
	
	return 'http://www.huizenzoeker.nl/koop/'. $string ."/details.html";
}


function findProv($postcode) {	
	$maxPostcode[1299] = 'Noord-Holland';
	$maxPostcode[1379] = 'Flevoland';
	$maxPostcode[1383] = 'Noord-Holland';
	$maxPostcode[1393] = 'Utrecht';
	$maxPostcode[1394] = 'Noord-Holland';
	$maxPostcode[1396] = 'Utrecht';
	$maxPostcode[1425] = 'Noord-Holland';
	$maxPostcode[1427] = 'Utrecht';
	$maxPostcode[1429] = 'Zuid-Holland';
	$maxPostcode[2158] = 'Noord-Holland';
	$maxPostcode[2164] = 'Zuid-Holland';
	$maxPostcode[2165] = 'Noord-Holland';
	$maxPostcode[3381] = 'Zuid-Holland';
	$maxPostcode[3464] = 'Utrecht';
	$maxPostcode[3466] = 'Zuid-Holland';
	$maxPostcode[3769] = 'Utrecht';
	$maxPostcode[3794] = 'Gelderland';
	$maxPostcode[3836] = 'Utrecht';
	$maxPostcode[3888] = 'Gelderland';
	$maxPostcode[3899] = 'Flevoland';
	$maxPostcode[3999] = 'Utrecht';
	$maxPostcode[4119] = 'Gelderland';
	$maxPostcode[4125] = 'Utrecht';
	$maxPostcode[4129] = 'Zuid-Holland';
	$maxPostcode[4139] = 'Utrecht';
	$maxPostcode[4146] = 'Zuid-Holland';
	$maxPostcode[4162] = 'Gelderland';
	$maxPostcode[4169] = 'Zuid-Holland';
	$maxPostcode[4199] = 'Gelderland';
	$maxPostcode[4209] = 'Zuid-Holland';
	$maxPostcode[4212] = 'Gelderland';
	$maxPostcode[4213] = 'Zuid-Holland';
	$maxPostcode[4219] = 'Gelderland';
	$maxPostcode[4249] = 'Zuid-Holland';
	$maxPostcode[4299] = 'Noord-Brabant';
	$maxPostcode[4599] = 'Zeeland';
	$maxPostcode[4671] = 'Noord-Brabant';
	$maxPostcode[4679] = 'Zeeland';
	$maxPostcode[4681] = 'Noord-Brabant';
	$maxPostcode[4699] = 'Zeeland';
	$maxPostcode[5299] = 'Noord-Brabant';
	$maxPostcode[5335] = 'Gelderland';
	$maxPostcode[5765] = 'Noord-Brabant';
	$maxPostcode[5817] = 'Limburg';
	$maxPostcode[5846] = 'Noord-Brabant';
	$maxPostcode[6019] = 'Limburg';
	$maxPostcode[6029] = 'Noord-Brabant';
	$maxPostcode[6499] = 'Limburg';
	$maxPostcode[6584] = 'Gelderland';
	$maxPostcode[6599] = 'Limburg';
	$maxPostcode[7399] = 'Gelderland';
	$maxPostcode[7438] = 'Overijssel';
	$maxPostcode[7439] = 'Gelderland';
	$maxPostcode[7739] = 'Overijssel';
	$maxPostcode[7766] = 'Drenthe';
	$maxPostcode[7799] = 'Overijssel';
	$maxPostcode[7949] = 'Drenthe';
	$maxPostcode[7955] = 'Overijssel';
	$maxPostcode[7999] = 'Drenthe';
	$maxPostcode[8049] = 'Overijssel';
	$maxPostcode[8054] = 'Gelderland';
	$maxPostcode[8069] = 'Overijssel';
	$maxPostcode[8099] = 'Gelderland';
	$maxPostcode[8159] = 'Overijssel';
	$maxPostcode[8195] = 'Gelderland';
	$maxPostcode[8199] = 'Overijssel';
	$maxPostcode[8259] = 'Flevoland';
	$maxPostcode[8299] = 'Overijssel';
	$maxPostcode[8322] = 'Flevoland';
	$maxPostcode[8349] = 'Overijssel';
	$maxPostcode[8354] = 'Drenthe';
	$maxPostcode[8379] = 'Overijssel';
	$maxPostcode[8387] = 'Drenthe';
	$maxPostcode[9299] = 'Friesland';
	$maxPostcode[9349] = 'Drenthe';
	$maxPostcode[9399] = 'Groningen';
	$maxPostcode[9499] = 'Drenthe';
	$maxPostcode[9999] = 'Groningen';
	
	$pc = key($maxPostcode);
	
	while($pc <= $postcode) {
		next($maxPostcode);
		$pc = key($maxPostcode);
	}
	
	return strtolower($maxPostcode[$pc]);
}


function corrigeerPrice($t1, $p1, $t2 = '') {
	global $TablePBK, $PBKStart, $PBKEind, $PBKWaarde;
	
	if($t2 == '') {
		$t2 = time();
	}
	
	$sql_2 = "SELECT * FROM $TablePBK WHERE $t2 BETWEEN $PBKStart AND $PBKEind";
	$result_2 = mysql_query($sql_2);
	if(mysql_num_rows($result_2) == 1) {
		$row = mysql_fetch_array($result_2);
    		$factor_2 = $row[$PBKWaarde];
	} else {
		$sql_3 = "SELECT * FROM $TablePBK ORDER BY $PBKStart DESC LIMIT 0,1";
		$result_3 = mysql_query($sql_3);
		$row = mysql_fetch_array($result_3);
		
		if($t2 > $row[$PBKEind]) {
			$factor_2 = $row[$PBKWaarde];
		}		
	}
	
	
	$sql_1 = "SELECT * FROM $TablePBK WHERE $t1 BETWEEN $PBKStart AND $PBKEind";
	$result_1 = mysql_query($sql_1);
	if(mysql_num_rows($result_1) == 1) {
		$row = mysql_fetch_array($result_1);
    		$factor_1 = $row[$PBKWaarde];
	} else {
		$factor_1 = $factor_2;	
	}
		
	return (($factor_2/$factor_1)*$p1);
}

function ignoreHouse4Combine($id) {
	global $TableIgnore, $IgnoreID;
	
	$sql = "SELECT * FROM $TableIgnore WHERE $IgnoreID like '$id'";
	
	$result = mysql_query($sql);
	if(mysql_num_rows($result) > 0) {
		return true;
	} else {
		return false;
	}
}

function send2Pushover($dataArray, $recipients) {	
	foreach($recipients as $memberID) {
		$MemberData = getMemberDetails($memberID);
		
		if($MemberData['userkey'] != '' AND $MemberData['token'] != '') {
			$push = new Pushover();
			$push->setUser($MemberData['userkey']);
			$push->setToken($MemberData['token']);			
			$push->setTitle($dataArray['title']);
			$push->setMessage($dataArray['message']);
			if($dataArray['url'] != '')				$push->setUrl($dataArray['url']);
			if($dataArray['urlTitle'] != '')	$push->setUrlTitle($dataArray['urlTitle']);
			if($dataArray['priority'] != 0)		$push->setPriority ($dataArray['priority']);
			$push->setHtml(1);
			$push->setDebug(true);
			$push->setTimestamp(time());
			$push->send();		
		}
	}
}