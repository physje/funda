<?php

# Helper-functies

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


function toLog($type, $opdracht, $huis, $message) {
	global $db, $TableLog, $LogTime, $LogType, $LogOpdracht, $LogHuis, $LogMessage;
	 	
	$tijd = time();	
	$sql = "INSERT INTO $TableLog ($LogTime, $LogType, $LogOpdracht, $LogHuis, $LogMessage) VALUES ($tijd, '$type', '$opdracht', '$huis', '". addslashes($message) ."')";
	if(!mysqli_query($db, $sql)) {
		echo "log-error : ". $sql;
	}
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
		# Als dit niet de 1ste keer is, even 3 seconden wachten
		if($counter > 0)	sleep(3);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_COOKIESESSION, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
		$contents = curl_exec($curl);
		curl_close($curl);
		
		$counter++;
	}

	return $contents;
}
/*
function file_get_contents_retry($url, $maxTry = 3, $curl = false) {
	$contents = false;
	$counter = 0;
	
	$useragents = array('Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727)','Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)','Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/124 (KHTML, like Gecko) Safari/125','Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.30 Safari/525.13','Opera/7.23 (Windows 98; U) [en]','Mozilla/5.0 (Windows; U; Windows NT 5.1; nl; rv:1.9) Gecko/2008052906 Firefox/3.0','Mozilla/5.0 (compatible; Konqueror/3.5; Linux) KHTML/3.5.4 (like Gecko)');

	while($contents === false AND $counter < $maxTry) {
		if($counter > 0)	{	sleep(2);	}		
		if($curl) {
			$curl_handle=curl_init();
			curl_setopt($curl_handle, CURLOPT_URL,$url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_POST, false);
			curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl_handle, CURLOPT_USERAGENT, $useragents[rand(0, (count($useragents))-1)]);
			//curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Konqueror/3.5; Linux) KHTML/3.5.4 (like Gecko)');
			$contents = curl_exec($curl_handle);
			curl_close($curl_handle);
		} else {
			$contents = file_get_contents($url);
		}
		
		$counter++;
		
		//echo '{'. $contents .'}';		
	}
	
	return $contents;
}
*/

function guessDate($string, $number = false) {	
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
		$output = implode('-', $delen);
	} else {
		return false;
	}
	
	if($number) {
		$output = mktime(0, 0, 1, $delen[1], $delen[0], $delen[2]);
	}
	
	return $output;
}

function convert2FundaStyle($string) {	
	$string = str_replace ('.', '',$string);	
	$string = str_replace ('(', '',$string);
	$string = str_replace (')', '',$string);
	$string = str_replace ('/', '-',$string);
	$string = str_replace (' ', '-',$string);
	$string = str_replace ('é', 'e',$string);
	$string = str_replace ('ë', 'e',$string);
	$string = str_replace ('ä', 'a',$string);
	$string = str_replace ('ü', 'u',$string);
	$string = str_replace ('ö', 'o',$string);	
	$string = str_replace ('&#224;', 'a',$string);	# à
	$string = str_replace ('&#225;', 'a',$string);	# á
	$string = str_replace ('&#226;', 'a',$string);	# â
	$string = str_replace ('&#227;', 'a',$string);	# ã
	$string = str_replace ('&#228;', 'a',$string);	# ä
	$string = str_replace ('&#229;', 'a',$string);	# å	
	#$string = str_replace ('&#230;', '',$string);	# æ 
	$string = str_replace ('&#231;', 'c',$string);	# ç
	$string = str_replace ('&#232;', 'e',$string);	# è
	$string = str_replace ('&#233;', 'e',$string);	# é
	$string = str_replace ('&#234;', 'e',$string);	# ê
	$string = str_replace ('&#235;', 'e',$string);	# ë
	$string = str_replace ('&#236;', 'i',$string);	# ì
	$string = str_replace ('&#237;', 'i',$string);	# í
	$string = str_replace ('&#238;', 'i',$string);	# î
	$string = str_replace ('&#239;', 'i',$string);	# ï
	#$string = str_replace ('&#240;', '',$string);	# ð
	#$string = str_replace ('&#241;', '',$string);	# ñ
	$string = str_replace ('&#242;', 'o',$string);	# ò
	$string = str_replace ('&#243;', 'o',$string);	# ó
	$string = str_replace ('&#244;', 'o',$string);	# ô
	$string = str_replace ('&#245;', 'o',$string);	# õ
	$string = str_replace ('&#246;', 'o',$string);	# ö
	#$string = str_replace ('&#247;', '',$string);	# ÷
	$string = str_replace ('&#248;', 'o',$string);	# ø
	$string = str_replace ('&#249;', 'u',$string);	# ù
	$string = str_replace ('&#250;', 'u',$string);	# ú
	$string = str_replace ('&#251;', 'u',$string);	# û	
	$string = str_replace ('&#252;', 'u',$string);	# ü	
	$string = str_replace ('&#39;', '',$string);
				
	return strtolower($string);
}


function getTimeBetween($start, $einde) {
	$dagE		= date("d", $einde);
	$maandE	= date("m", $einde);
	$jaarE	= date("Y", $einde);
	
	$dagB		= date("d", $start)-1;
	$maandB	= date("m", $start);
	$jaarB	= date("Y", $start);
	
	$week		= 0;
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

# Geef de id's van zoekopdrachten
#
#	INPUT
#		$user
#		$active : 0 = niet actief, 1 = actief, '' = alle
#
# OUTPUT
#		array met ids van zoekopdracht
function getZoekOpdrachten($user, $active = '') {
	global $db, $TableZoeken, $ZoekenKey, $ZoekenActive, $ZoekenUser;
	$where = $Opdrachten = array();
					
	if($user != '') {		
		$where[] = "$ZoekenUser = '$user'";
	}
	
	if($active != '') {		
		$where[] = "$ZoekenActive = '$active'";
	}
	
	$sql = "SELECT $ZoekenKey FROM $TableZoeken WHERE ". implode(" AND ", $where);
			
	$result = mysqli_query($db, $sql);	
	if($row = mysqli_fetch_array($result)) {
		do {
			$Opdrachten[] = $row[$ZoekenKey];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $Opdrachten;
}

# Zoek de gegevens van een zoekopdracht op basis van id
#
#	INPUT
#		$id	id van de zoekopdracht
#
# OUTPUT
#		array met gegevens
function getOpdrachtData($id) {
	global $db, $TableZoeken, $ZoekenKey, $ZoekenUser, $ZoekenNaam, $ZoekenURL;
	$data = array();
	
	if($id != '') {
		$sql		= "SELECT * FROM $TableZoeken WHERE $ZoekenKey = $id";
		$result	= mysqli_query($db, $sql);
		$row		= mysqli_fetch_array($result);
			
#		$data['active']	= $row[$ZoekenActive];
		$data['user']		= $row[$ZoekenUser];
		$data['naam']		= urldecode($row[$ZoekenNaam]);
		$data['url']		= urldecode($row[$ZoekenURL]);
	}
	
	return $data;	
}

function getHuizen($opdracht, $excludeVerkocht = false, $excludeOffline = false) {
	global $db, $TableHuizen, $HuizenID, $HuizenAdres, $HuizenVerkocht, $HuizenOffline;
	global $TableResultaat, $ResultaatID, $ResultaatZoekID;
	$output = array();
		
	$sql = "SELECT * FROM $TableHuizen, $TableResultaat WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID like '$opdracht' ";
	if($excludeVerkocht) {
		$sql .= "AND $TableHuizen.$HuizenVerkocht NOT like '1' ";
	}
	if($excludeOffline) {
		$sql .= "AND $TableHuizen.$HuizenOffline NOT like '1' ";
	}
	$sql .= "AND $TableHuizen.$HuizenID > 0 ORDER BY $TableHuizen.$HuizenAdres";
	
	#echo $sql;
	
	$result	= mysqli_query($db, $sql);
	$row		= mysqli_fetch_array($result);
	
	do {
		$output[] = $row[$HuizenID];
	} while($row = mysqli_fetch_array($result));
	
	return $output;
}







# Extraheer gegevens van een huis uit de ruwe HTML-code van de overzichtspagina van funda.nl
#
#	INPUT
#		$string		De ruwe HTML-code
#
# OUTPUT
#		array met de gegevens van het huis
function extractFundaData($HuisText, $verkocht = false) {		
	
	if($verkocht) {		
		$data['verkocht']			= 1;
	} else {
		$data['verkocht']			= 0;
	}
	
	# Overzichtspagina
	$HuisURL	= getString('href="', '"', $HuisText, 0);
	
	$cleanURL	= $HuisURL[0];
	$cleanURL	= str_replace('?navigateSource=resultlist', '', $cleanURL);
	$cleanURL	= str_replace('https://www.funda.nl', '', $cleanURL);
	
	$mappen			= explode("/", $cleanURL);
	
	# Funda zit volgens mij in een overgangsfase ofzo van URL's
	# Er komen namelijk verschillende types voor
	# Op basis van deze if-else-statements lijkt het mogelijk deze te onderscheiden
	# Hopelijk kan op termijn dit weer gewoon 1 expressie worden
	if(strpos($cleanURL, 'detail')) {		
		$id			= $mappen[5];
	} elseif(count($mappen) < 6) {		
		$key				= $mappen[3];
		$key_parts	= explode("-", $key);	
		$id					= $key_parts[1];
	} else {		
		$id			= $mappen[4];
	}
		
	$foto		= getString('calc(100vw - 2rem)" srcset="', ' ', $HuisText, 0);
	$adres	= getString('sm:mt-0">', '</h', $HuisURL[1], 0);
	$PC			= getString('mb-2">', '</div', $adres[1], 0);
	$prijs	= getString('class="font-semibold">', '</p>', $PC[1], 0);
		
	if(strpos($HuisText, '<div class="my-2 ml-auto">')) {
		$R_naam	= getString('<button class="flex">', '<div class="my-2 ml-auto">', $HuisText, 0);
	} else {
		$R_naam	= getString('<button class="flex">', '<div class="ml-auto"', $HuisText, 0);
	}
		
	# Nu al het knippen geweest is kan de geknipte data "geprocesed" worden		
	if(strpos($HuisText, 'Verkocht onder voorbehoud')) {
		$voorbehoud = 1;
	} else {
		$voorbehoud = 0;
	}
	
	if(strpos($HuisText, '<li class="label label-nvm-open-huizen-dag">') OR strpos($HuisText, '<li class="label label-open-huis">') OR strpos($HuisText, '<li class="mb-1 mr-1 rounded-sm px-1 py-0.5 text-xs font-semibold bg-[#ACC700] text-white">')) {
		$openhuis = 1;
	} else {
		$openhuis = 0;
	}
	
	$postcode = explode(' ', trim($PC[0]));
	$onderdelen		= splitStreetAndNumberFromAdress($adres[0]);
		
	$data['id']				= $id;
	$data['url']			= trim($cleanURL);
	$data['adres']		= trim($adres[0]);
	$data['straat']			= $onderdelen['straat'];
	$data['nummer']			= $onderdelen['nummer'];
	$data['letter']			= $onderdelen['letter'];
	$data['toevoeging']	= $onderdelen['toevoeging'];	
	$data['PC_c']			= trim($postcode[0]);
	$data['PC_l']			= trim($postcode[1]);
	$data['wijk']			= '';
	$data['plaats']		= end($postcode);
	$data['thumb']		= trim($foto[0]);
	$data['makelaar']	= trim(strip_tags($R_naam[0]));
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

function extractFundaDataFromPage($offlineHTML) {	
	$HTML = getString('<body>', '<h2 class="related-objects__title">', $offlineHTML, 0);
	$contents = $HTML[0];
	
	# Als er een class item-sold is, is hij onder voorbehoud verkocht => $verkocht = 2
	# Als er een class item-sold-label-large is, is hij verkocht => $verkocht = 1
	# Als geen van beide het geval is, is hij nog beschikbaar => $verkocht = 0
	if(strpos($contents, '<li class="label-transactie-voorbehoud">')) {
		$verkocht		= 2;
	}elseif(strpos($contents, '<li class="label-transactie-definitief fd-p-horizontal-xs fd-border-radius fd-m-right-2xs">')) {
		$verkocht		= 1;
	} else {
		$verkocht		= 0;
	}
	
	# Als er een class object-promolabel__open-huis-dates is heeft openhuis => $openhuis = 1
	# Als geen van beide het geval is, is hij nog beschikbaar => $openhuis = 0
	if(strpos($contents, 'class="object-promolabel__open-huis')) {
		$openhuis		= 1;
	} else {
		$openhuis		= 0;
	}
		
	# Navigatie-gedeelte
	$navigatie	= getString('<ol class="breadcrumb-list fd-flex fd-align-items-center fd-p-vertical-2xs fd-container-full fd-container fd-m-auto">', '</ol>', $contents, 0);
	$stappen		= explode('<span class="fd-text--ellipsis fd-text--nowrap fd-overflow-hidden">', $navigatie[0]);
	$wijk				= getString('', '</span>', $stappen[3], 0);	
	//$id					= getString('tinyId=', '&amp;', $contents, 0);
	$id					= getString('"tinyid":"', '"', $offlineHTML, 0);

	$adres	= getString('aria-current="page">', '</span>', $contents, 0);
	$adresClean = str_replace('<span class="item-sold-label-large" title="Verkocht">VERKOCHT</span>', '', $adres[0]);
		
	if($verkocht == 1) {
		$prijs			= getString('<strong class="object-header__price--historic">', '</strong>', $contents, 0);
	} else {
		$prijs			= getString('<strong class="object-header__price">', '</strong>', $contents, 0);
	}
	
	$makelHTML	= getString('<h3 class="object-contact-aanbieder-name">', '</h3>', $contents, 0);
	$PC					= getString('<span class="object-header__subtitle fd-color-neutral-40">', '<a class="', $contents, 0);
	$makelaar		= getString('">', '</a>', $makelHTML[0], 0);
	$foto				=	getString('<meta itemprop="image" content="', '"', $offlineHTML, 0);
	$start			= getString("aangebodensinds=", "&", $offlineHTML, 0);
		
	$postcode		= explode(" ", trim($PC[0]));
	$onderdelen		= splitStreetAndNumberFromAdress($adresClean);
	
	$data['id']				= trim($id[0]);
	$data['wijk']			= trim($wijk[0]);	
	$data['adres']		= trim($adresClean);
	$data['straat']			= $onderdelen['straat'];
	$data['nummer']			= $onderdelen['nummer'];
	$data['letter']			= $onderdelen['letter'];
	$data['toevoeging']	= $onderdelen['toevoeging'];
	$data['PC_c']			= trim($postcode[0]);
	$data['PC_l']			= trim($postcode[1]);	
	$data['plaats']		= implode(' ', array_slice($postcode, 2));	
	$data['thumb'] = preg_replace ('/_(\d+).jpg/', '_360x240.jpg', $foto[0]);
	$data['makelaar']	= trim($makelaar[0]);
	$data['start']		= guessDate($start[0], true);
	$data['prijs']		= cleanPrice($prijs[0]);
	$data['verkocht']	= $verkocht;
	$data['openhuis']	= $openhuis;
	
	
	if($verkocht == 1) {
		$oldData = getFundaData($data['id']);
		$AangebodenHTML	= getString('<dt>Aangeboden sinds</dt>','</dd>', $contents, 0);
		
		if(strpos($AangebodenHTML[0], '<span class="fd-m-right-xs">')) {			
			$Aangeboden			= getString('<span class="fd-m-right-xs">', '</span>', $AangebodenHTML[0], 0);
		} else {
			$Aangeboden	= getString('<dt>Aangeboden sinds</dt>','</dd>', $contents, 0);
		}				
		
		$KenmerkData['Aangeboden sinds'] = substr(trim($Aangeboden[0]), 4);
				
		$Verkoopdatum	= getString('<dt>Verkoopdatum</dt>','</dd>', $contents, 0);		
		$KenmerkData['Verkoopdatum'] = substr(trim($Verkoopdatum[0]), 4);
		
		if(!isset($oldData['afmeld']) OR $oldData['afmeld'] == 0) {
			if(isset($oldData['eind'])) {
				$data['afmeld'] = $oldData['eind'];
			} else {
				$data['afmeld'] = 0;
			}
		}
	}
	
	if($openhuis == 1) {
		$data['oh-tijden'] = extractOpenHuisData($contents);
	}	else {
		$data['oh-tijden'] = 0;
	}
	
	# Omschrijving		
	$descrHTML		= getString('<div class="object-description-body"', '</div>', $contents, 0);
	$omschrijving = getString('>', '</div>', $descrHTML[0], 0);
	
	$KenmerkData['descr']	= trim($omschrijving[0]);
	
	# Kenmerken
	$content_kenmerk	= getString('<h2 class="object-kenmerken-title">Kenmerken</h2>', '</section>', $contents, 0);
	$kenmerken				= explode('<dt>', $content_kenmerk[0]);
	array_shift($kenmerken);
	
	foreach($kenmerken as $kenmerk) {
		$Record = getString('', '</dt>', $kenmerk, 0);
		//$Waarde = getString('<dd class="fd-flex--bp-m fd-flex-wrap fd-align-items-center">', '</dd>', $kenmerk, 0);
		$Waarde = getString('<span class="fd-m-right-xs">', '</span>', $kenmerk, 0);
		
		if(strpos($Waarde[0], '<span class="">') OR strpos($Waarde[0], '<span class>')) {
			$Waarde = getString('<dd class="fd-flex--bp-m fd-flex-wrap fd-align-items-center">', '</dd>', $kenmerk, 0);
		}
				
		$key = trim($Record[0]);
		$KenmerkData[$key] = trim(strip_tags($Waarde[0]));
	}
	
	# Foto	
	$content_fotos	= getString('<ol class="grid list-none ', '</section>', $offlineHTML, 0);
		
	if($content_fotos[0] != "") {
		$picture		= array();		
		#$cleanFotoContent 	= str_replace('<div class="object-media-foto ">', '<div class="object-media-foto">', $content_fotos[0]);
		$cleanFotoContent 	= $content_fotos[0];
		$carousel		= explode('<li class=', $cleanFotoContent);
		array_shift($carousel);
		
		foreach($carousel as $key => $value) {
			if(strpos($value, 'data-lazy-srcset=')) {
				$thumb = getString('data-lazy-srcset="', ' ', $value, 0);
				#$picture[] = preg_replace ('/_(\d+).jpg/', '_180x120.jpg', $thumb[0]);
				$picture[] = preg_replace ('/_(\d+).jpg/', '_360x240.jpg', $thumb[0]);
			} elseif(!strpos($value, 'data:')) {
				$thumb = getString('src="', '"', $value, 0);
				#$picture[] = preg_replace ('/_(\d+)x(\d+).jpg/', '_180x120.jpg', $thumb[0]);
				$picture[] = preg_replace ('/_(\d+)x(\d+).jpg/', '_360x240.jpg', $thumb[0]);
			}
		}
		
		$KenmerkData['foto']		= implode('|', $picture);
	}	else {
		$KenmerkData['foto']		= '';
	}
	
	return array($data, $KenmerkData);
}

function updateVerkochtDataFromPage($generalData, $data) {
	global $db, $TableHuizen, $HuizenStart, $HuizenEind, $HuizenAfmeld, $HuizenVerkocht, $HuizenOffline, $HuizenID;
	
	# Alles weer opnieuw initialiseren.
	$Aanmelddatum = $Verkoopdatum = $LaatsteVraagprijs = $AangebodenSinds = $OorspronkelijkeVraagprijs = $Vraagprijs = 0;
	$naam = '';
	$offline = $changed_end = $changed_start = false;	
	$prijs = $startdata = array();
	
	$fundaID = $generalData['id'];
	$FundaData = getFundaData($fundaID);
			
	if(isset($generalData['afmeld']) AND $generalData['afmeld'] != "") {
		$sql_update = "UPDATE $TableHuizen SET $HuizenAfmeld = ". $generalData['afmeld'] ." WHERE $HuizenID like $fundaID";
		//echo $sql_update ."<br>\n";
		if(mysqli_query($db, $sql_update)) {
			$HTML[] = " -> afgemeld";
		}			
	}
			
	# Als de array 'data' groter is dan 3 is er data gevonden in de kenmerken-pagina
	if(count($data) > 3) {
		# Reeds verkochte huizen
		if(isset($data['Aanmelddatum']) AND $data['Aanmelddatum'] != '') {
			$guessStartDatum	= guessDate($data['Aanmelddatum']);
			$startDatum	= explode("-", $guessStartDatum);
			$Aanmelddatum = mktime(0, 0, 1, $startDatum[1], $startDatum[0], $startDatum[2]);
		}
									
		if(isset($data['Verkoopdatum']) AND $data['Verkoopdatum'] != '') {
			$guessVerkoopDatum = guessDate($data['Verkoopdatum']);
			$verkoopDatum	= explode("-", $guessVerkoopDatum);
			$Verkoopdatum = mktime(23, 59, 59, $verkoopDatum[1], $verkoopDatum[0], $verkoopDatum[2]);
		}			

		if(isset($data['Laatste vraagprijs']) AND $data['Laatste vraagprijs'] != '') {
			$prijzen		= explode(" ", $data['Laatste vraagprijs']);				
			$LaatsteVraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
		}
									
		# Huizen die nog niet verkocht zijn
		if(isset($data['Aangeboden sinds']) AND $data['Aangeboden sinds'] != '') {
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
					
		if(isset($data['Oorspronkelijke vraagprijs']) AND $data['Oorspronkelijke vraagprijs'] != '') {
			$prijzen		= explode(" ", $data['Oorspronkelijke vraagprijs']);
			$OorspronkelijkeVraagprijs = str_ireplace('.', '' , substr($prijzen[0], 5));
		}
					
		if(isset($data['Vraagprijs']) AND $data['Vraagprijs'] != '') {
			$prijzen						= explode(" ", $data['Vraagprijs']);				
			$Vraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 5));
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
			$HTML[] = " -> ". $naam[$key] ." toegevoegd ($value / ". date("d-m-y", $key) .")";
			toLog('debug', '0', $fundaID, $naam[$key] ." toegevoegd");
		} else {
			toLog('error', '0', $fundaID, "Error met toevoegen $value als ". $naam[$key]);
		}
	}
			
	# Als er een verkoopdatum bekend is => die datum als eindtijd invoeren
	if($Verkoopdatum > 10) {
		$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum, $HuizenEind = $Verkoopdatum, $HuizenVerkocht = '1' WHERE $HuizenID like $fundaID";
		
		if(mysqli_query($db, $sql_update)) {
			$HTML[] = " -> begin- en eindtijd aangepast (verkocht)";
			toLog('info', '0', $fundaID, "Huis is verkocht");
			$changed_end = $changed_start = true;
		} else {
			toLog('error', '0', $fundaID, "Error met verwerken verkocht huis");
			$HTML[] = $sql_update;
		}
	}
	
	# Als er een startdatum gevonden is die verder terugligt dan die bekend was => invoegen
	if($startDatum != $FundaData['start'] AND !$offline AND !$changed_start) {
		$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum WHERE $HuizenID like $fundaID";
				
		if(mysqli_query($db, $sql_update)) {
			$HTML[] = " -> begintijd aangepast";
		} else {
			toLog('error', '0', $fundaID, "Error met verwerken begintijd");
			$HTML[] = $sql_update;
		}					
	}

	# Als er geen verkoopdatum bekend is, is hij niet verkocht en dus nog online
	if($Verkoopdatum == '' AND !$offline AND !$changed_end) {
		$sql_update = "UPDATE $TableHuizen SET $HuizenEind = ". time() ." WHERE $HuizenID like $fundaID";
		
		if(mysqli_query($db, $sql_update)) {
			$HTML[] = " -> eindtijd aangepast<br>";
		} else {
			toLog('error', '0', $fundaID, "Error met verwerken begintijd");
			$HTML[] = $sql_update;
		}
	}
	
	return $HTML;
}

function splitStreetAndNumberFromAdress($adres) {
	$straat = $nummer = $letter = $toevoeging = '';
	$nogStraat = true;
	$i = 0;
	
	# Het adres 'Laan van Borgele 40 F206' moet worden opgesplitst in
	# Straat : Laan van Borgele
	# Nummer : 40
	# Letter : F
	# Toevoeging : 206
	
	$delen = explode(' ', trim($adres));
	$i_max = count($delen);
	
	while($nogStraat) {
		if(is_numeric($delen[$i])) {
			$nogStraat = false;
		} else {
			$i++;
		}
		
		if($i > $i_max) {
			$nogStraat = false;
		}
	}
	
	$straat = implode(' ', array_slice($delen, 0, $i));
	$nummer = $delen[$i];
	
	if($i < ($i_max-1)) {
	   if(($i_max-$i) == 2) {
	       $temp = $delen[($i+1)];
	       
	       if(is_numeric($temp[0])) {
	           $toevoeging = $temp;
	           $letter = '';
	       } else {
	           $letter = $temp[0];
	           $toevoeging = substr($temp, 1);
	       }
	   } elseif(($i_max-$i) == 3) {
	       $letter = $delen[($i+1)];
	       $toevoeging = $delen[($i+2)];
	   }
	} else {
	   $letter = $toevoeging = '';
	}
	
	$output['straat'] = $straat;
	$output['nummer'] = $nummer;
	$output['letter'] = $letter;
	$output['toevoeging'] = $toevoeging;
	
	return $output;
}

function formatStreetAndNumber($id) {
	$data = getFundaData($id);
	
	if($data['toevoeging'] == '') {
		return $data['straat'].' '.$data['nummer'].strtoupper($data['letter']);
	} else {
		return $data['straat'].' '.$data['nummer'].strtoupper($data['letter']).' '.$data['toevoeging'];
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
	global $db, $TableHuizen, $HuizenLat, $HuizenLon, $HuizenID;
			
	if(is_numeric($coord[1]) AND $coord[0] != 0) {
		if(count($coord) > 3) {
			$lat = $coord[0].'.'.$coord[1];
			$lng = $coord[2].'.'.$coord[3]; 
		} else {
			$lat = str_ireplace(',', '.', $coord[0]);
			$lng = str_ireplace(',', '.', $coord[1]);
		}
		$sql = "UPDATE $TableHuizen SET $HuizenLat = '$lat', $HuizenLon = '$lng' WHERE $HuizenID = '$huisID'";
				
		if(!mysqli_query($db, $sql)) {
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}



# Functies met betrekking tot het online/offline/beschikbaar zijn van huizen
function updateAvailability($id, $begin = '') {
	global $db, $TableHuizen, $HuizenStart, $HuizenEind, $HuizenOffline, $HuizenID, $HuizenID2;
	
	$sql = "UPDATE $TableHuizen SET $HuizenEind = ". mktime(23, 59, 59) .", ";
	if($begin != '')	$sql .= "$HuizenStart = $begin, ";
	$sql .= "$HuizenOffline = '0' WHERE $HuizenID like '$id' OR $HuizenID2 like '$id'";
		
	if(!mysqli_query($db, $sql)) {
		return false;
	} else {
		return true;
	}
	
}

function setOnline($id) {
	global $db, $TableHuizen, $HuizenOffline, $HuizenID, $HuizenID2;
				
	$sql = "UPDATE $TableHuizen SET $HuizenOffline = '0' WHERE $HuizenID like '$id' OR $HuizenID2 like '$id'";
	
	if(!mysqli_query($db, $sql)) {
		return false;
	} else {
		return true;
	}
}

function alreadyOnline($id) {
	global $db, $TableHuizen, $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging, $HuizenPlaats, $HuizenID, $HuizenOffline, $HuizenVerkocht;
		
	$data = getFundaData($id);
	
	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenStraat like '". urlencode($data['straat']) ."' AND $HuizenNummer = ". $data['nummer'] ." AND $HuizenLetter like '". urlencode($data['letter']) ."' AND $HuizenToevoeging = '". $data['toevoeging'] ."' AND $HuizenPlaats like '". urlencode($data['plaats']) ."' AND $HuizenOffline like '0' AND $HuizenVerkocht like '0' AND $HuizenID not like '$id'";		
	$result	= mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 0) {
		return false;
	} else {
		$row = mysqli_fetch_array($result);
		return $row[$HuizenID];
	}	
}

function onlineBefore($id) {
	global $db, $TableHuizen, $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging, $HuizenPlaats, $HuizenID, $HuizenOffline, $HuizenVerkocht;
	
	$data = getFundaData($id);
	
	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenStraat like '". urlencode($data['straat']) ."' AND $HuizenNummer = ". $data['nummer'] ." AND $HuizenLetter like '". urlencode($data['letter']) ."' AND $HuizenToevoeging = '". $data['toevoeging'] ."' AND $HuizenPlaats like '". urlencode($data['plaats']) ."' AND $HuizenOffline like '1' AND $HuizenVerkocht like '0' AND $HuizenID not like '$id'";

	$result	= mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 0) {
		return false;
	} else {
		$row = mysqli_fetch_array($result);
		return $row[$HuizenID];
	}	
}



# Functies met betrekking tot het verkopen van huizen
function soldHouse($key) {
	global $db, $TableHuizen, $HuizenID, $HuizenID2, $HuizenVerkocht;	
		
	$sql		= "SELECT * FROM $TableHuizen WHERE ($HuizenID like '$key' OR $HuizenID2 like '$key') AND $HuizenVerkocht like '1'";		
	$result	= mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 1) {
		return true;
	} else {
		return false;
	}
}

function soldHouseTentative($key) {
	global $db, $TableHuizen, $HuizenID, $HuizenID2, $HuizenVerkocht;	
		
	$sql		= "SELECT * FROM $TableHuizen WHERE ($HuizenID like '$key' OR $HuizenID2 like '$key') AND $HuizenVerkocht like '2'";			
	$result	= mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 1) {
		return true;
	} else {
		return false;
	}
}

function soldBefore($id) {
	global $db, $TableHuizen, $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging, $HuizenPlaats, $HuizenID, $HuizenVerkocht;
	
	$data = getFundaData($id);
	
	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenStraat like '". urlencode($data['straat']) ."' AND $HuizenNummer = ". $data['nummer'] ." AND $HuizenLetter like '". urlencode($data['letter']) ."' AND $HuizenToevoeging = '". $data['toevoeging'] ."' AND $HuizenPlaats like '". urlencode($data['plaats']) ."' AND $HuizenVerkocht like '1' AND $HuizenID not like '$id'";
		
	$result	= mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 0) {
		return false;
	} else {
		$row = mysqli_fetch_array($result);
		return $row[$HuizenID];
	}	
}



# Functies met betrekking tot huizen-prijzen
function changedPrice($id, $price, $opdracht) {
	global $db, $TableResultaat, $ResultaatZoekID, $ResultaatID, $ResultaatPrijs;
		
	$sql = "SELECT * FROM $TableResultaat WHERE $ResultaatZoekID like '$opdracht' AND $ResultaatID like '$id'";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	if($price == $row[$ResultaatPrijs]) {
		return false;
	} else {
		$sql = "UPDATE $TableResultaat SET $ResultaatPrijs = '$price' WHERE $ResultaatZoekID like '$opdracht' AND $ResultaatID like '$id'";
		mysqli_query($db, $sql);
		return true;
	}
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

function newPrice($key, $price) {
	$history = getPriceHistory($key);
		
	if($price != current($history)) {
		return true;
	} else {
		return false;
	}		
}

function updatePrice($id, $price, $tijd = 0) {
	global $db, $TablePrijzen, $PrijzenID, $PrijzenPrijs, $PrijzenTijd;	
		
	if($tijd == 0) {
		$tijd = time();
	}
		
	$sql = "INSERT INTO $TablePrijzen ($PrijzenID, $PrijzenPrijs, $PrijzenTijd) VALUES ('$id', $price, ". $tijd .")";
		
	if(!mysqli_query($db, $sql)) {
		echo $sql;
		return false;
	} else {
		return true;
	}
}

function getPriceHistory($input) {
	global $db, $TablePrijzen, $PrijzenTijd, $PrijzenID, $PrijzenPrijs;	
	$PriceTable = array();
		
	$sql		= "SELECT $PrijzenTijd, $PrijzenPrijs FROM $TablePrijzen WHERE $PrijzenID like '$input' ORDER BY $PrijzenTijd DESC";
	$result	= mysqli_query($db, $sql);
	if($row	= mysqli_fetch_array($result)) {
		do {
			$index						= $row[$PrijzenTijd];
			$PriceTable[$index] = $row[$PrijzenPrijs];		
		} while($row = mysqli_fetch_array($result));
	}
	
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

function getFullPriceHistory($input) {
	$afname = $percentage = $overall_afname = $overall_percentage = array();
	$prizeArray = getPriceHistory($input);
		
	$HuidigePrijs		= array_slice ($prizeArray, 0, 1);
	$OriginelePrijs	= array_slice ($prizeArray, (count($prizeArray)-1), 1);
	
	$prijzenRev	= array_reverse($prizeArray, true);
	$vorige			= $OriginelePrijs[0];
	current($prijzenRev);
	
	if($vorige == 0) {
		toLog('error', '0', $input, 'Onjuiste prijs-historie');
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



# Functies WOZ-waarde
function extractWOZwaarde($fundaID) {
	$data = getFundaData($fundaID);
	
	$adres = $data['straat'].' '.$data['nummer'].$data['letter'];
		
	if($data['toevoeging'] != '') {
		$adres .= ' '.$data['toevoeging'];
	}
	
	if($data['PC_c'] == '' OR $data['PC_l'] == '') {
		$postcode = findPCbyAdress($data['straat'], $data['nummer'], $data['letter'], $data['toevoeging'], $data['plaats']);
		toLog('debug', '0', $fundaID, 'PC onbekend voor WOZ-waarde; '. $postcode);
	} else {
		$postcode = $data['PC_c'].$data['PC_l'];
	}
	
	$url = "https://drimble.nl/adres/". strtolower($data['plaats']) ."/$postcode/". convert2FundaStyle(formatStreetAndNumber($fundaID)) .".html";
	$contents = file_get_contents_retry($url, 3, true);
		
	if(strpos($contents, 'Page not found / Adres niet gevonden.')) {
		toLog('debug', '0', $fundaID, 'Adres bestaat niet voor WOZ; '. $adres);
		toLog('debug', '0', $fundaID, $url);
		return false;
	} elseif(strpos($contents, '<title>404 Page not found</title>')) {
		toLog('debug', '0', $fundaID, 'URL voor WOZ niet goed opgebouwd; '. $url);
		return false;
	} elseif(strpos($contents, 'Maximum aanvragen van dit soort ')) {
		toLog('debug', '0', $fundaID, 'Maximum aantal aanvragen bereikt');
		return false;	
	} else {			
		$WOZ = getString('<td colspan="2" style="font-size:18px;padding-top:3px;padding-bottom:3px;">WOZ-waarde', '<td colspan="2" style="font-size:16px;padding-top:3px;padding-bottom:3px;background-color:#404040;color:#fff">', $contents, 0);
		$aWOZ = explode('style="width:20%;">Peildatum ', $WOZ[0]);
				
		# Een array van 1 betekent dat er geen WOZ-waardes bekend zijn
		if(count($aWOZ) < 2) {
			toLog('debug', '0', $fundaID, 'Geen WOZ-waardes bekend');
			return false;
		}
		
		array_shift($aWOZ);
	
		foreach($aWOZ as $key => $value) {
			if(strpos($value, 'Belastingjaar')) {
				$jaar = getString('Belastingjaar)', ':', $value, 0);
				$jaar[0] = $jaar[0]-1;
			} else {
				$jaar = getString('', ':', $value, 0);
			}
						
			$bedrag = getString('&euro; ', '</td>', $value, 0);
			$export[trim($jaar[0])] = trim(str_replace('.', '', $bedrag[0]));		
		}
		return $export;
	}	
}

function getWOZHistory($id) {
	global $db, $TableWOZ, $WOZFundaID, $WOZJaar, $WOZPrijs;
	
	$data = array();
	
	$sql = "SELECT * FROM $TableWOZ WHERE $WOZFundaID like $id";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$jaar		= $row[$WOZJaar];
			$bedrag = $row[$WOZPrijs];
			$data[$jaar] = $bedrag;
		} while($row = mysqli_fetch_array($result));
	}
	
	return $data;
}





# Functies met betrekking tot open huis
function hasOpenHuis($id) {
	global $db, $TableHuizen, $HuizenOpenHuis, $HuizenID;
	
	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenOpenHuis = '1' AND $HuizenID = '$id'";
	$result	= mysqli_query($db, $sql);
	
	if(mysqli_num_rows($result) == 1) {
		return true;
	} else {
		return false;
	}
}

function setOpenHuis($id) {
	global $db, $TableHuizen, $HuizenOpenHuis, $HuizenID;
	
	$sql = "UPDATE $TableHuizen SET $HuizenOpenHuis = '1' WHERE $HuizenID = '$id'";
		
	return mysqli_query($db, $sql);	
}

function removeOpenHuis($id) {
	global $db, $TableHuizen, $HuizenOpenHuis, $HuizenID, $TableResultaat, $ResultaatOpenHuis, $ResultaatID;
	
	$sql = "UPDATE $TableHuizen SET $HuizenOpenHuis = '0' WHERE $HuizenID = '$id'";
	mysqli_query($db, $sql);
	
	$sql = "UPDATE $TableResultaat SET $ResultaatOpenHuis = '0' WHERE $ResultaatID = '$id'";
	mysqli_query($db, $sql);
}

function getNextOpenhuis($id) {
	global $db, $TableCalendar, $CalendarHuis, $CalendarStart, $CalendarEnd;
	
	$nu			= mktime(0,0,0);	
	$sql		= "SELECT * FROM $TableCalendar WHERE $CalendarStart > $nu AND $CalendarHuis = '$id'";

	$result = mysqli_query($db, $sql);
	$row		= mysqli_fetch_array($result);
	
	return array($row[$CalendarStart], $row[$CalendarEnd]);
}

function deleteOpenhuis($fundaID, $begin) {
	global $db, $TableCalendar, $CalendarHuis, $CalendarStart;
	
	$sql = "DELETE FROM $TableCalendar WHERE $CalendarHuis like $fundaID AND $CalendarStart like $begin";
	return mysqli_query($db, $sql);
}

function addOpenhuis($fundaID, $tijden) {
	global $db, $TableCalendar, $CalendarHuis, $CalendarStart, $CalendarEnd;
	
	$begin = $tijden[0];
	$eind = $tijden[1];
	
	$sql = "INSERT INTO $TableCalendar ($CalendarHuis, $CalendarStart, $CalendarEnd) VALUES ($fundaID, $begin, $eind)";
	return mysqli_query($db, $sql);
}



# Functies met betrekking tot het opslaan en opvragen van huizen in de database
function knownHouse($key) {
	global $db, $TableHuizen, $HuizenID, $HuizenID2;	
		
	$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenID like '$key' OR $HuizenID2 like '$key'";
	$result	= mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 1) {
		return true;
	} else {
		return false;
	}
}

function saveHouse($data, $moreData) {	
	global $db, $TableHuizen, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenStart, $HuizenEind;
	global $TableKenmerken, $KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue;
			
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
	$sql .= "($HuizenID, $HuizenURL, $HuizenAdres, $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenStart, $HuizenEind) ";
	$sql .= "VALUES ";
	$sql .= "('". $data['id'] ."', '". urlencode($data['url']) ."', '". urlencode($data['adres']) ."', '". urlencode($data['straat']) ."', '". $data['nummer'] ."', '". urlencode($data['letter']) ."', '". $data['toevoeging'] ."', '". $data['PC_c'] ."', '". $data['PC_l'] ."', '". urlencode($data['plaats']) ."', '". urlencode($data['wijk']) ."', '". urlencode($data['thumb']) ."', '". urlencode($data['makelaar']) ."', '$begin_tijd', '$eind_tijd')";
				
	if(!mysqli_query($db, $sql)) {		
		return false;
	}
		
	return true;
}

function newHouse($key, $opdracht) {
	global $db, $TableResultaat, $ResultaatID, $ResultaatZoekID;	
		
	$sql		= "SELECT * FROM $TableResultaat WHERE $ResultaatID like '$key' AND $ResultaatZoekID like '$opdracht'";			
	$result	= mysqli_query($db, $sql);
	if(mysqli_num_rows($result) == 0) {
		return true;
	} elseif(mysqli_num_rows($result) > 1) {
		toLog('error', $opdacht, $key, 'Huis-opdracht-combinatie komt vaker voor');
		if(mysqli_query($db, "DELETE FROM $TableResultaat WHERE $ResultaatID like '$key' AND $ResultaatZoekID like '$opdracht' LIMIT 1")){
			toLog('error', $opdacht, $key, 'Huis-opdracht-combinatie opgeschoond');
		}
		return false;
	} else {
		return false;
	}
}

function addHouse($data, $id) {
	global $db, $TableResultaat, $ResultaatZoekID, $ResultaatID, $ResultaatPrijs, $ResultaatPrijsMail;

	$sql = "INSERT INTO $TableResultaat ($ResultaatZoekID, $ResultaatID, $ResultaatPrijs, $ResultaatPrijsMail) VALUES ($id, '". $data['id'] ."', '". $data['prijs'] ."', '". $data['prijs'] ."')";
	
	if(!mysqli_query($db, $sql)) {
		return false;
	} else {
		return true;
	}
}

function updateHouse($data, $kenmerken, $erase = false) {
	global $db, $TableHuizen, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenVerkocht, $HuizenOpenHuis;
	global $TableKenmerken, $KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue;
			
	$onderdelen = splitStreetAndNumberFromAdress($data['adres']);
	
	$data['straat']			= $onderdelen['straat'];
	$data['nummer']			= $onderdelen['nummer'];
	$data['letter']			= $onderdelen['letter'];
	$data['toevoeging']	= $onderdelen['toevoeging'];
			
	$velden = array(
		'url'				=> $HuizenURL,    
		'adres'			=> $HuizenAdres,		
		'straat'		=> $HuizenStraat,
		'nummer'		=> $HuizenNummer,
		'letter'		=> $HuizenLetter,
		'toevoeging'=> $HuizenToevoeging,		 
		'PC_c'			=> $HuizenPC_c,   
		'PC_l'			=> $HuizenPC_l,   
		'plaats'		=> $HuizenPlaats, 
		'wijk'			=> $HuizenWijk,  
		'thumb'			=> $HuizenThumb,  
		'makelaar'	=> $HuizenMakelaar,		
		'verkocht'	=> $HuizenVerkocht,
		'openhuis'	=> $HuizenOpenHuis
	);
		
	foreach($data as $key => $value) {
		if(array_key_exists($key, $velden)) {
			$sql[] = $velden[$key] ." = '". urlencode($value) ."'";
		}
	}
	$query = "UPDATE $TableHuizen SET ". implode(', ', $sql) ." WHERE $HuizenID like '". $data['id'] ."'";
	
	if(!mysqli_query($db, $query)) {
		echo $query ."<br>\n";
	}	
	
	if($erase) {
		$query = "DELETE FROM $TableKenmerken WHERE $KenmerkenID like '". $data['id'] ."'";
		
		if(!mysqli_query($db, $query)) {
			echo $query ."<br>\n";
		}	
	}
	
	foreach($kenmerken as $key => $value) {
		mysqli_query($db, "DELETE FROM $TableKenmerken WHERE $KenmerkenID like '". $data['id'] ."' AND $KenmerkenKenmerk like '". urlencode($key) ."'");
		$query = "INSERT INTO $TableKenmerken ($KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue) VALUES ('". $data['id'] ."', '". urlencode($key) ."', '". urlencode($value) ."')";
		
		if(!mysqli_query($db, $query)) {
			echo $query ."<br>\n";
		}
	}
}

function getFundaData($id) {
	global $db, $TableHuizen, $HuizenID, $HuizenID2, $HuizenURL, $HuizenAdres, $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenLat, $HuizenLon, $HuizenStart, $HuizenEind, $HuizenAfmeld, $HuizenOffline, $HuizenVerkocht, $HuizenOpenHuis, $HuizenDetails;
	$data = array();
	 
  if($id != 0) {
  	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenID = $id OR $HuizenID2 = $id";
		$result = mysqli_query($db, $sql);
	
		if(mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result);
			
			$data['id']			= urldecode($row[$HuizenID]);
			$data['id_2']			= urldecode($row[$HuizenID2]);
			$data['url']			= urldecode($row[$HuizenURL]);
			$data['adres']		= urldecode($row[$HuizenAdres]);			
			$data['straat']		= urldecode($row[$HuizenStraat]);
			$data['letter']		= urldecode($row[$HuizenLetter]);
			if($row[$HuizenNummer] != 0)			{ $data['nummer']		= $row[$HuizenNummer]; } else { $data['nummer'] = ''; }
			if($row[$HuizenToevoeging] != 0)	{ $data['toevoeging']	= $row[$HuizenToevoeging]; } else { $data['toevoeging'] = ''; }
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
			$data['afmeld']		= $row[$HuizenAfmeld];
			$data['verkocht']	= $row[$HuizenVerkocht];
			$data['offline']	= $row[$HuizenOffline];
			$data['openhuis']	= $row[$HuizenOpenHuis];
			$data['details']	= $row[$HuizenDetails];
			
			return $data;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function getFundaKenmerken($id) {
	global $db, $TableKenmerken, $KenmerkenID, $KenmerkenValue, $KenmerkenKenmerk;
	$data = array();
	  
  if($id != 0) {
  	$sql = "SELECT * FROM $TableKenmerken WHERE $KenmerkenID = $id";
		$result = mysqli_query($db, $sql);
	
		if($row = mysqli_fetch_array($result)) {
			do {
				$key = urldecode($row[$KenmerkenKenmerk]);
				$data[$key] = urldecode($row[$KenmerkenValue]);
			} while($row = mysqli_fetch_array($result));			
		}
		
		ksort($data);
		
		return $data;
	} else {
		return false;
	}
}



# Functies met betrekking tot het in- en uitschrijven van members
function getMembers4Opdracht($OpdrachtID, $type) {
	global $db, $TableAbo, $AboZoekID, $AboUserID, $AboType;
	$Members = array();
	
	$sql = "SELECT * FROM $TableAbo WHERE $AboZoekID like '$OpdrachtID' AND $AboType like '$type'";
	$result = mysqli_query($db, $sql);
		
	if($row = mysqli_fetch_array($result)) {
		do {
			$Members[] = $row[$AboUserID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $Members;
}

function addMember2Opdracht($opdracht, $user, $type) {
	global $db, $TableAbo, $AboZoekID, $AboUserID, $AboType;
	
	$sql = "INSERT INTO $TableAbo ($AboZoekID, $AboUserID, $AboType) VALUES ($opdracht, $user, '$type')";
	return mysqli_query($db, $sql);
}

function removeMember4Opdracht($opdracht, $user, $type) {
	global $db, $TableAbo, $AboZoekID, $AboUserID, $AboType;
	
	$sql = "DELETE FROM $TableAbo WHERE $AboZoekID = $opdracht AND $AboUserID = $user AND $AboType like '$type'";
	return mysqli_query($db, $sql);
}

function getMemberDetails($id) {
	global $db, $TableUsers, $UsersID, $UsersName, $UsersUsername, $UsersPassword, $UsersLevel, $UsersAdres, $UsersAccount, $UsersLastLogin, $UsersPOKey, $UsersPOToken;
	
	$sql		= "SELECT * FROM $TableUsers WHERE $UsersID like '$id'";
	$result	= mysqli_query($db, $sql);
	$row		= mysqli_fetch_array($result);
		
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
	global $db, $TableUsers, $UsersID, $UsersName, $UsersUsername, $UsersPassword, $UsersLevel, $UsersAdres, $UsersPOKey, $UsersPOToken, $UsersAccount;
	
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
			
	$result = mysqli_query($db, $sql);
	
	if($id == '') {
		return mysqli_insert_id($db);
	} else {
		return $result;
	}		
}



# Functies met betrekking tot het wel of niet inladen van details
function mark4Details($fundaID) {
	global $db, $TableHuizen, $HuizenDetails, $HuizenID, $HuizenID2;
	
	$sql 		= "UPDATE $TableHuizen SET $HuizenDetails = '1' WHERE $HuizenID = $fundaID OR $HuizenID2 = $fundaID";
	return mysqli_query($db, $sql);	
}

function remove4Details($fundaID) {
	global $db, $TableHuizen, $HuizenDetails, $HuizenID;
	
	$sql 		= "UPDATE $TableHuizen SET $HuizenDetails = '0' WHERE $HuizenID = $fundaID";	
	return mysqli_query($db, $sql);
}



# Functies voor data-selectie
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
		
		# Omdat we in stappen van 5 minuten lopen, even afronden om 5 minuten.
		$selectMin = (floor($bMin/5)*5);
		for($m=0 ; $m<=59 ; $m=$m+5)	$begin[] = "	<option value='$m'". ($m == $selectMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
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
		
		# Omdat we in stappen van 5 minuten lopen, even afronden om 5 minuten.
		$selectMin = (floor($eMin/5)*5);
		for($m=0 ; $m<=59 ; $m=$m+5)	$eind[] = "	<option value='$m'". ($m == $selectMin ? ' selected' : '') .">". substr('0'.$m, -2) ."</option>";
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



# Lijsten
function getLijsten($id, $active) {
	global $db, $TableList, $ListID, $ListUser, $ListActive, $ListNaam;
	$Lijsten = $where = array();
				
	if($active != '')	$where[] = "$ListActive = '$active'";	
	if($id != '')			$where[] = "$ListUser = '$id'";
	
	$sql = "SELECT $ListID FROM $TableList WHERE ". implode(" AND ", $where) ." ORDER BY $ListNaam";
	$result = mysqli_query($db, $sql);
		
	if($row = mysqli_fetch_array($result)) {
		do {
			$Lijsten[] = $row[$ListID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $Lijsten;
}


function getLijstData($id) {
	global $db, $TableList, $ListID, $ListActive, $ListNaam;
		
	$sql = "SELECT * FROM $TableList WHERE $ListID = '$id'";
	$result = mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
	
	$data['id']			= $row[$ListID];
	$data['active'] = $row[$ListActive];
	$data['naam']		= urldecode($row[$ListNaam]);
	
	return $data;	
}


function getLijstHuizen($list, $excludeVerkocht = false, $excludeOffline = false) {
	global $db, $TableListResult, $TableHuizen, $ListResultHuis, $ListResultList, $HuizenID, $HuizenAdres, $HuizenVerkocht, $HuizenOffline;
	$Huizen = array();
	
	$from		= "$TableListResult, $TableHuizen";
	$where	= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $list";
	if($excludeVerkocht) {
		$where .= " AND $TableHuizen.$HuizenVerkocht NOT like '1'";
	}	
	if($excludeOffline ) {
		$where .= " AND $HuizenOffline NOT like '1'";
	}	
	$sql		= "SELECT $TableHuizen.$HuizenID FROM $from WHERE $where ORDER BY $TableHuizen.$HuizenAdres";
	$result = mysqli_query($db, $sql);
	
	if($row = mysqli_fetch_array($result)) {
		do {
			$Huizen[] = $row[$HuizenID];
		} while($row = mysqli_fetch_array($result));
	}
	
	return $Huizen;		
}


function addHouse2List($huis, $list) {
	global $db, $TableListResult, $ListResultList, $ListResultHuis;
	
	$sql_check = "SELECT * FROM $TableListResult WHERE $ListResultList like $list AND $ListResultHuis like '$huis'";
	$result	= mysqli_query($db, $sql_check);
			
	if(mysqli_num_rows($result) == 0) {
		$data = getFundaData($huis);
		
		$sql_insert = "INSERT INTO $TableListResult ($ListResultList, $ListResultHuis) VALUES ($list, $huis)";
		if(!mysqli_query($db, $sql_insert)) {
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
	global $db, $TableList, $ListUser, $ListActive, $ListNaam, $ListID;
	
	if($id == '') {
		$sql = "INSERT INTO $TableList ($ListUser, $ListActive, $ListNaam) VALUES ('$user', '". ($actief == '1' ? '1' : '0') ."', '". urlencode($naam) ."')";
	} else {
		$sql = "UPDATE $TableList SET $ListActive = '". ($actief == '1' ? '1' : '0') ."', $ListUser = '$user', $ListNaam = '". urlencode($naam) ."' WHERE $ListID = ". $id;
	}
	
	$result = mysqli_query($db, $sql);
	
	if($id == '') {
		return mysqli_insert_id($db);
	} else {
		return $result;
	}		
}




# Functies met betrekking tot het versturen van Pushover-berichten
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

function sendPushoverNewHouse($fundaID, $OpdrachtID) {
	$PushMembers		= getMembers4Opdracht($OpdrachtID, 'push');
				
	# Pushover-bericht opstellen
	if(count($PushMembers) > 0) {
		$data						= getFundaData($fundaID);
		$data['prijs']	= getHuidigePrijs($fundaID);
		$OpdrachtData		= getOpdrachtData($OpdrachtID);
	
		$soldBefore			= soldBefore($fundaID);
		$alreadyOnline	= alreadyOnline($fundaID);
		$onlineBefore		= onlineBefore($fundaID);
		
		$WOZwaardes			= extractWOZwaarde($fundaID);		
		$WOZwaarde			= current($WOZwaardes);
				
		$push = array();
		$push['title']		= "Nieuw huis voor '". $OpdrachtData['naam'] ."'";
		$push['message']	= $data['straat'] .' '. $data['nummer'] .' in '. $data['plaats'] .' is te koop voor '. formatPrice($data['prijs']);
		
		if(is_numeric($WOZwaarde)) {
			$push['message'] .= "\nLaatst bekende WOZ-waarde is ".formatPrice($WOZwaarde);
		}
		
		if(is_numeric($soldBefore)) {
			$extraData = getFundaData($soldBefore);
			$push['message'] .= "\n\nAl eerder verkocht op ". date("d-m-Y", $extraData['eind'])." voor ". formatPrice(getHuidigePrijs($soldBefore));
		} elseif(is_numeric($alreadyOnline)) {
			$extraData = getFundaData($alreadyOnline);
			$push['message'] .= "\n\nOok online bij ". $extraData['makelaar']." ($alreadyOnline)";
		} elseif(is_numeric($onlineBefore)) {
			$extraData = getFundaData($onlineBefore);
			$push['message'] .= "\n\n".implode(" & ", getTimeBetween($extraData['eind'], $data['start'])) ." offline geweest ($onlineBefore)";
		}
				
		$push['url']			= 'http://funda.nl/'. $fundaID;
		$push['urlTitle']	= $data['adres'];
		$push['priority']	= 0;
		
		send2Pushover($push, $PushMembers);
		toLog('debug', $OpdrachtID, $fundaID, 'Pushover-bericht nieuw huis verstuurd');
	}
}

function sendPushoverChangedPrice($fundaID, $OpdrachtID) {
	$fundaData			= getFundaData($fundaID);
	$OpdrachtData		= getOpdrachtData($OpdrachtID);
	$PriceHistory		= getFullPriceHistory($fundaID);
	$prijzen_array	= $PriceHistory[0];
	$prijzen_perc 	= $PriceHistory[3];
	end($prijzen_array);	# De pointer op de laatste waarde (=laatste prijs) zetten
			
	$PushMembers		= getMembers4Opdracht($OpdrachtID, 'push');
			
	# Pushover-bericht opstellen
	if(count($PushMembers) > 0) {
		$push = array();
		$push['title']		= formatStreetAndNumber($fundaID) ." is in prijs verlaagd voor '". $OpdrachtData['naam'] ."'";
		$push['message']	= formatStreetAndNumber($fundaID) ." is van ". formatPrice(prev($prijzen_array)) .' naar '. formatPrice(end($prijzen_array)) .' gegaan';
		
		if(prev($prijzen_array) != reset($prijzen_array)) {
		    $push['message']	.= ", oorspronkelijke vraagprijs was ". formatPrice(reset($prijzen_array));
		}
		
		$push['url']			= 'http://funda.nl/'. $fundaID;
		$push['urlTitle']	= $fundaData['adres'];
		$push['priority']	= 0;
		
		send2Pushover($push, $PushMembers);
		toLog('debug', $OpdrachtID, $fundaID, 'Pushover-bericht prijsdaling verstuurd');
	}
}

function sendPushoverOpenHuis($fundaID, $OpdrachtID) {
	$fundaData			= getFundaData($fundaID);
	$OpdrachtData		= getOpdrachtData($OpdrachtID);
	$OpenHuisData		= getNextOpenhuis($fundaID);
				
	$PushMembers		= getMembers4Opdracht($OpdrachtID, 'push');
			
	# Pushover-bericht opstellen
	if(count($PushMembers) > 0) {
		$push = array();
		$push['title']		= formatStreetAndNumber($fundaID) ." heeft open huis voor '". $OpdrachtData['naam'] ."'";
		$push['message']	= "Open huis op ". strftime('%a %e %b', $OpenHuisData[0]) .' van '. strftime('%H:%M', $OpenHuisData[0]) .' tot '. strftime('%H:%M', $OpenHuisData[1]);
				
		$push['url']			= 'http://funda.nl/'. $fundaID;
		$push['urlTitle']	= $fundaData['adres'];
		$push['priority']	= 0;
		
		send2Pushover($push, $PushMembers);
		toLog('debug', $OpdrachtID, $fundaID, 'Pushover-bericht open huis verstuurd');
	}
}



# Functies met betrekking tot de administratie van welke overzichts-pagina geladen moet worden
function setPageToLoadNext($opdracht, $page, $verkocht, $nextPage) {
	global $db, $TablePage, $PageOpdracht, $PagePage, $PageSold, $PageTime;	
	
	#echo "INPUT<br>";
	#echo "opdracht : $opdracht<br>";
	#echo "page : $page<br>";
	#echo "verkocht : $verkocht<br>";
	#echo "nextPage : $nextPage<br>";

	# Er is geen nieuwe pagina op de lijst met te koop staande huizen
	#	-> pagina 1 met verkochte woningen openen
	if(!$nextPage AND !$verkocht) {
		$newOpdracht = $opdracht;
		$newPage = 1;
		$verkocht = true;
	
	# Er is wel een nieuwe pagina op de lijst met te koop staande huizen
	#	-> volgende pagina van te koop staande woningen openen
	} elseif($nextPage AND !$verkocht) {
		$newOpdracht = $opdracht;
		$newPage = $page+1;
		$verkocht = false;
	
	# Er is wel een nieuwe pagina op de lijst met verkochte huizen
	#	-> volgende pagina van verkochte woningen openen
	# Om niet elke keer alle pagina's in te laden
	# doen we alleen de eerste 4 pagina's van verkochten 
	} elseif($nextPage AND $verkocht AND $page < 4) {
		
		$newOpdracht = $opdracht;
		$newPage = $page+1;
		$verkocht = true;				
	
	# Niks van dat alles
	#	-> pagina 1 van de volgende opdracht openen
	} else {
		$allOpdrachten = getZoekOpdrachten('', '1');
		$key = array_search($opdracht, $allOpdrachten);
		
		# Als $key al de laatste index van de array is
		# moet weer van voorafaan begonnen worden
		if(($key+1) < count($allOpdrachten)) {
			$newOpdracht = $allOpdrachten[($key+1)];
		} else {
			$newOpdracht = $allOpdrachten[0];
		}
		$newPage = 1;
		$verkocht = false;
	}
	
	#echo "OUTPUT<br>";
	#echo "key : $key<br>";
	#echo "newOpdracht : $newOpdracht<br>";
	#echo "newPage : $newPage<br>";        	
	#echo "verkocht : $verkocht<br>";
	
	toLog('debug', $opdracht, '', 'Volgende ronde: opdracht '. $newOpdracht .', pagina '. $newPage .', verkocht '. ($verkocht ? 1 : 0));
	
	$sql = "UPDATE $TablePage SET $PageOpdracht = $newOpdracht, $PagePage = $newPage, $PageSold = ". ($verkocht ? 1 : 0) .", $PageTime = ". time();
		
	if(!mysqli_query($db, $sql)) {
		return false;
	} else {
		return true;
	}	
}

function getPageToLoadNext() {
	global $db, $TablePage, $PageOpdracht, $PagePage, $PageSold, $PageTime;	
	
	$sql		= "SELECT * FROM $TablePage";
	$result	= mysqli_query($db, $sql);
	$row		= mysqli_fetch_array($result);
	
	$OpdrachtID = $row[$PageOpdracht];
	$page				= $row[$PagePage];
	$sold				= $row[$PageSold];
		
	$OpdrachtData	= getOpdrachtData($OpdrachtID);
		
	$data['opdracht'] 		= $OpdrachtID;
	$data['page'] 				= $page;
	$data['verkocht'] 		= $sold;
	$data['url_opdracht']	= $OpdrachtData['url'];
	$url_open							= $OpdrachtData['url'];
		
	if($sold) {
		$url_open .= '&availability=%5B%22unavailable%22%5D';
	}
	
	$url_open .= "&search_result=$page";
	
	$data['url_open'] 		= $url_open;
	
	return $data;
}

?>
