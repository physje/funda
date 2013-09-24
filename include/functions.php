<?php

# Geef de id's van zoekopdrachten
#
#	INPUT
#		$active : 0 = niet actief, 1 = actief, '' = alle
#
# OUTPUT
#		array met ids van zoekopdracht 
function getZoekOpdrachten($id, $active) {
	global $TableZoeken, $ZoekenUser, $ZoekenKey, $ZoekenActive;
	$Opdrachten = array();
	
	connect_db();
	
	$sql = "SELECT $ZoekenKey FROM $TableZoeken";
	
	if($active != '') {
		$where[] = "$ZoekenActive = '$active'";
	}
	
	if($id != '') {
		$where[] = "$ZoekenUser = '$id'";
	}
	
	if(count($where) > 0) {
		$sql .= ' WHERE '. implode(" AND ", $where);
	}
	
	$result = mysql_query($sql);	
	if($row = mysql_fetch_array($result)) {
		do {
			$Opdrachten[] = $row[$ZoekenKey];
		} while($row = mysql_fetch_array($result));
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
	global $TableZoeken, $ZoekenKey, $ZoekenActive, $ZoekenUser, $ZoekenNaam, $ZoekenURL, $ZoekenMail, $ZoekenAdres;
	
	$data = array();
	
	if($id != '') {
		$sql		= "SELECT * FROM $TableZoeken WHERE $ZoekenKey = $id";
		$result	= mysql_query($sql);
		$row		= mysql_fetch_array($result);
			
		$data['active']	= $row[$ZoekenActive];
		$data['user']		= $row[$ZoekenUser];
		//$data['mail']		= $row[$ZoekenMail];
		//$data['adres']	= $row[$ZoekenAdres];
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
	//global $TableHuizen, $HuizenNdeg, $HuizenNdec, $HuizenOdeg, $HuizenOdec, $HuizenID;
	global $TableHuizen, $HuizenLat, $HuizenLon, $HuizenID;
			
	if(is_numeric($coord[1])) {
		$lat = $coord[0].'.'.$coord[1];
		$lng = $coord[2].'.'.$coord[3]; 
		$sql = "UPDATE $TableHuizen SET $HuizenLat = '$lat', $HuizenLon = '$lng' WHERE $HuizenID = '$huisID'";
		//$sql = "UPDATE $TableHuizen SET $HuizenNdeg = '$coord[0]', $HuizenNdec = '$coord[1]', $HuizenOdeg = '$coord[2]', $HuizenOdec = '$coord[3]' WHERE $HuizenID = '$huisID'";
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
function showBlock($String) {
	$Text = "<table width='95%' cellpadding='8' cellspacing='1' bgcolor='#636367'>\n";
	$Text .= "<tr>\n";
	$Text .= "	<td bgcolor='#EAEAEA'>\n";
	$Text .= "	<!-- BEGIN BLOK INHOUD -->\n";	
	$Text .= $String;	
	$Text .= "	<!-- EIND BLOK INHOUD -->\n";	
	$Text .= "	</td>\n";	
	$Text .= "</tr>\n";
	$Text .= "</table>\n";
	
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
	$foto		= getString('<img src="', '" alt="" title="" class="', $HuisURL[1], 0);
	$adres	= getString('<a href="'. $HuisURL[0] .'" class="object-street" >', '</a>', $foto[1], 0);
	$PC			= getString('<li>', '<', $adres[1], 0);
	$param	= getString('<li>', '</li>', $adres[1], 0);
		
	if(strpos($HuisText, '<a class="realtor" href="')) {
		$R_url	= getString('<a class="realtor" href="', '">', $PC[1], 0);
		$R_naam	= getString('">', '</a>', $R_url[1], 0);
	} else {
		$R_url	= array('', '');
		$R_naam	= getString('<span class="realtor">', '</span>', $PC[1], 0);
	}
	$prijs	= getString('<span class="price">', '</span>', $R_naam[1], 0);
		
	if(strpos($param[0], 'Verkocht onder voorbehoud')) {
		$voorbehoud = 1;
	} else {
		$voorbehoud = 0;
	}
	
	if(strpos($param[0], '<span class="item-open" title="')) {
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
	
	//foreach($data as $key => $value) {
	//	echo $key .'|'.makeTextBlock($value, 100) .'<br>';
	//}
	//echo '------------------------------';
	
	return $data;
}


function convertToReadable($string) {
	$string = str_replace('&nbsp;m&sup2;', '', $string);
	$string = str_replace('&nbsp;m&sup3;', '', $string);
	$string = str_replace('&#235;', '�', $string);
	$string = str_replace('&#39;', '', $string);
	$string = str_replace('&amp;', '&', $string);
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
	//$KML_file[] = '			<coordinates>'. $data['O_deg'] .'.'. $data['O_dec'] .','. $data['N_deg'] .'.'. $data['N_dec'] .',0</coordinates>';
	$KML_file[] = '			<coordinates>'. $data['long'] .','. $data['lat'] .',0</coordinates>';
	$KML_file[] = '		</Point>';
	$KML_file[] = '	</Placemark>';
	
	return implode("\n", $KML_file);
}


function extractDetailedFundaData($URL) {
	$contents		= file_get_contents_retry($URL);
	
	# Navigatie-gedeelte
	$navigatie	= getString('<p class="section path-nav">', '</p>', $contents, 0);
	$stappen		= explode('&gt;', $navigatie[0]);
	//$provincie	= getString('/">', '</a>', $stappen[(count($stappen)-3)], 0);	
	$wijk				= getString('/">', '</a>', $stappen[(count($stappen)-1)], 0);
	//$data['prov']	= trim($provincie[0]);	
	$data['wijk']	= trim($wijk[0]);	
	
	if($contents != "") {		
		# Omschrijving
		$contents		= file_get_contents_retry($URL.'omschrijving/');
		
		if(strpos($contents, '<div class="description-full">')) {
			$omschrijving = getString('<div class="description-full">', '</div>', $contents, 0);
		} else {
			$contents		= file_get_contents_retry($URL);
			$omschrijving = getString('<p id="PVolledigeOmschrijving" style="display:none">', '<a id="linkKorteOmschrijving"', $contents, 0);
		}
		
		$data['descr']	= trim($omschrijving[0]);	
	} else {
		$data['descr']	= '';
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
		$data[$key] = trim(strip_tags($Waarde[0]));
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
		
		$data['foto']		= implode('|', $picture);
	}	else {
		$data['foto']		= '';
	}
	
	return $data;
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
	$sql .= "('". $data['id'] ."', '". urlencode($data['url']) ."', '". urlencode($data['adres']) ."', '". urlencode($data['PC_c']) ."', '". urlencode($data['PC_l']) ."', '". urlencode($data['plaats']) ."', '". urlencode($moreData['wijk']) ."', '". urlencode($data['thumb']) ."', '". urlencode($data['makelaar']) ."', '$begin_tijd', '$eind_tijd')";
		
	if(!mysql_query($sql)) {
		$deel[] = false;
	} else {
		$deel[] = true;
	}
	
	foreach($moreData as $key => $value) {
		if($key != 'wijk') {
			$sql = "INSERT INTO $TableKenmerken ($KenmerkenID, $KenmerkenKenmerk, $KenmerkenValue) VALUES ('". $data['id'] ."', '". urlencode($key) ."', '". urlencode($value) ."')";
						
			if(!mysql_query($sql)) {
				$deel[] = false;
			} else {
				$deel[] = true;
			}
		}
	}
	
	return true;	
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
	global $TableHuizen, $HuizenEind, $HuizenID;
	connect_db();
	
	$eind_tijd = mktime(23, 59, 59);
	
	$sql = "UPDATE $TableHuizen SET $HuizenEind = $eind_tijd WHERE $HuizenID like '$id'";
	
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
	//global $TableHuizen, $HuizenOpdracht, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenNdeg, $HuizenNdec, $HuizenOdeg, $HuizenOdec, $HuizenStart, $HuizenEind, $HuizenOffline, $HuizenVerkocht;
	global $TableHuizen, $HuizenOpdracht, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenMakelaar, $HuizenLat, $HuizenLon, $HuizenStart, $HuizenEind, $HuizenOffline, $HuizenVerkocht;
	connect_db();
  
  if($id != 0) {
  	$sql = "SELECT * FROM $TableHuizen WHERE $HuizenID = $id";
		$result = mysql_query($sql);
	
		if(mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
			
			$data['url']			= urldecode($row[$HuizenURL]);
			$data['adres']		= urldecode($row[$HuizenAdres]);
			$data['PC_c']			= $row[$HuizenPC_c];	
			$data['PC_l']			= $row[$HuizenPC_l];		
			$data['plaats']		= urldecode($row[$HuizenPlaats]);
			$data['wijk']			= urldecode($row[$HuizenWijk]);
			$data['thumb']		= urldecode($row[$HuizenThumb]);
			$data['makelaar']	= urldecode($row[$HuizenMakelaar]);
			//$data['N_deg']		= $row[$HuizenNdeg];
			//$data['N_dec']		= $row[$HuizenNdec];
			//$data['O_deg']		= $row[$HuizenOdeg];
			//$data['O_dec']		= $row[$HuizenOdec];
			$data['lat']			= $row[$HuizenLat];
			$data['long']			= $row[$HuizenLon];
			$data['start']		= $row[$HuizenStart];
			$data['eind']			= $row[$HuizenEind];
			$data['verkocht']	= $row[$HuizenVerkocht];
			$data['offline']	= $row[$HuizenOffline];
			
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
		
		return $data;
	} else {
		return false;
	}
}


function getHuizen($opdracht, $excludeVerkocht = false) {
	global $TableHuizen, $HuizenOpdracht, $HuizenID, $HuizenAdres, $HuizenVerkocht, $HuizenOffline;
	global $TableResultaat, $ResultaatID, $ResultaatZoekID;
	connect_db();
	
	$sql .= "SELECT * FROM $TableHuizen, $TableResultaat WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID like '$opdracht' ";
	if($excludeVerkocht) {
		$sql .= "AND $TableHuizen.$HuizenVerkocht NOT like '1' AND $HuizenOffline NOT like '1' ";
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
	global $TableLog, $LogID, $LogTime, $LogType, $LogOpdracht, $LogHuis, $LogMessage;	

	connect_db();
 	
	$tijd = time();	
	$sql = "INSERT INTO $TableLog ($LogTime, $LogType, $LogOpdracht, $LogHuis, $LogMessage) VALUES ($tijd, '$type', '$opdracht', '$huis', '". addslashes($message) ."')";
	if(!mysql_query($sql)) {
		echo "log-error : ". $sql;
	}
}


function getDoorloptijd($id) {	
	$data = getFundaData($id);
	
	$start = $data['start'];
	$einde = $data['eind'];
	
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
		
	# Druk het niet uit in jaren maar in maanden
	if($jaar > 0) {
		$maand	= $maand + (12*$jaar);
		$jaar		= 0;
	}
	
	# Druk het uit in weken
	if($dag > 7) {
		$week = floor($dag/7);
		$dag = 0;
	}

	if($jaar > 0)		$output[] = $jaar ."j";
	if($maand > 0)	$output[] = $maand ."m";
	if($week > 0)		$output[] = $week ."wk";
	
	if(($jaar == 0 AND $maand == 0 AND $week == 0) OR ($dag > 0)) {
		$output[] = $dag ."d";
	}
	
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


function getLijsten($id, $active, $extended = false) {
	global $TableList, $ListID, $ListUser, $ListActive;
	$Lijsten = array();
	connect_db();
	
	$data = getMemberDetails($id);
	
	$sql = "SELECT $ListID FROM $TableList";
	
	if($active != '') {
		$where[] = "$ListActive = '$active'";
	}
	
	if($id != '') {
		$where[] = "$ListUser = '$id'";
	}
	
	$sql .= ' WHERE '. implode(" AND ", $where);
			
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


function getLijstHuizen($list, $excludeVerkocht = false) {
	global $TableListResult, $TableHuizen, $ListResultHuis, $ListResultList, $HuizenID, $HuizenAdres, $HuizenVerkocht, $HuizenOffline;
	
	$from		= "$TableListResult, $TableHuizen";
	$where	= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $list";
	if($excludeVerkocht) {
		$where .= " AND $TableHuizen.$HuizenVerkocht NOT like '1' AND $HuizenOffline NOT like '1'";
		
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
	
	//echo $sql;
	
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
	global $TableUsers, $UsersID, $UsersName, $UsersUsername, $UsersPassword, $UsersLevel, $UsersAdres, $UsersAccount, $UsersLastLogin;
	
	$sql		= "SELECT * FROM $TableUsers WHERE $UsersID like '$id'";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
		
	$data['id']				= $row[$UsersID];
	$data['naam']			= $row[$UsersName];
	$data['username']	= $row[$UsersUsername];
	$data['password']	= $row[$UsersPassword];
	$data['level']		= $row[$UsersLevel];
	$data['mail']			= $row[$UsersAdres];
	$data['account']	= $row[$UsersAccount];
	$data['login']		= $row[$UsersLastLogin];
	
	return $data;
}


function saveUpdateMember($id, $name, $username, $wachtwoord, $mail, $level, $gebruiker) {
	global $TableUsers, $UsersID, $UsersName, $UsersUsername, $UsersPassword, $UsersLevel, $UsersAdres, $UsersAccount;
	
	if($level == 1) {
		$account = $gebruiker;
	} else {
		$account = 0;
	}
	
	if($id == 0) {
		$sql = "INSERT INTO $TableUsers ($UsersName, $UsersUsername, $UsersPassword, $UsersLevel, $UsersAdres". ($account != 0 ? ", $UsersAccount" : '') .") VALUES ('$name', '$username', '". md5($wachtwoord) ."', $level, '$mail'". ($account != 0 ? ", '$account'" : '') .")";
	} else {
		$sql = "UPDATE $TableUsers SET $UsersName = '$name', $UsersUsername = '$username', ". ($wachtwoord != '' ? "$UsersPassword = '". md5($wachtwoord) ."', " : '') ."$UsersLevel = $level, $UsersAdres = '$mail' ". ($account != 0 ? ", $UsersAccount = '$account'" : '') ." WHERE $UsersID = ". $id;
	}
			
	$result = mysql_query($sql);
	
	if($id == '') {
		return mysql_insert_id();
	} else {
		return $result;
	}		
}


function getMembers4Opdracht($OpdrachtID) {
	global $TableAbo, $AboZoekID, $AboUserID;
	
	$sql = "SELECT * FROM $TableAbo WHERE $AboZoekID like '$OpdrachtID'";
	$result = mysql_query($sql);
	
	$Members = array();

	if($row = mysql_fetch_array($result)) {
		do {
			$Members[] = $row[$AboUserID];
		} while($row = mysql_fetch_array($result));
	}
	
	return $Members;
}


function addMember2Opdracht($opdracht, $user) {
	global $TableAbo, $AboZoekID, $AboUserID;
	
	$sql = "INSERT INTO $TableAbo ($AboZoekID, $AboUserID) VALUES ($opdracht, $user)";
	return mysql_query($sql);
}

function removeMember4Opdracht($opdracht, $user) {
	global $TableAbo, $AboZoekID, $AboUserID;
	
	$sql = "DELETE FROM $TableAbo WHERE $AboZoekID = $opdracht AND $AboUserID = $user";
	return mysql_query($sql);
}


function extractAndUpdateVerkochtData($fundaID) {
	global $TableHuizen, $HuizenStart, $HuizenEind, $HuizenVerkocht, $HuizenID;
	
	# Alles weer opnieuw initialiseren.
	unset($prijs, $naam);
	unset($Aanmelddatum, $Verkoopdatum, $AangebodenSinds, $startdata);
	unset($OorspronkelijkeVraagprijs, $LaatsteVraagprijs, $Vraagprijs);
	$verkocht = false;
	
	$FundaData = getFundaData($fundaID);
	$url			= "http://www.funda.nl". urldecode($FundaData['url']);
	
	# Via de kenmerkenpagina
	$data			= extractDetailedFundaData($url);
	
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
								
			//$transaction_price	= getString('transaction-price', '', $prop_transaction[0], 0);
			$tempLaatstevraagprijs			= getString('<span class="price-wrapper">', '</span>', $contents, 0);
	  	
			$sDatum				= explode("-", $tempAanmelddatum[0]);
			$Aanmelddatum = mktime(0, 0, 1, $sDatum[1], $sDatum[0], $sDatum[2]);
	  	
			$eDatum				= explode("-", $tempVerkoopdatum[0]);
			$Verkoopdatum = mktime(23, 59, 59, $eDatum[1], $eDatum[0], $eDatum[2]);
	  	
			$prijzen						= explode(" ", strip_tags($tempLaatstevraagprijs[0]));
			$LaatsteVraagprijs	= str_ireplace('.', '' , substr($prijzen[0], 12));
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
		//echo date('d-m-Y', $Aanmelddatum ) .' : '. $OorspronkelijkeVraagprijs ."<br>\n";
		$tijdstip = $Aanmelddatum;
		$prijs[$tijdstip]	= $OorspronkelijkeVraagprijs;
		$naam[$tijdstip]	= 'Oorspronkelijke vraagprijs';
	}

	# Bij aangeboden sinds gaat men niet verder dan 6 maanden.
	# Om te zorgen dat bij een huis wat al twee jaar te koop staat en 9 maanden geleden in prijs is gedaald,
	# niet de oorspronkelijke vraagprijs wordt ingevoerd even de check.
	if($OorspronkelijkeVraagprijs > 0 AND $AangebodenSinds  == $startDatum) {
		//echo date('d-m-Y', $AangebodenSinds ) .' : '. $OorspronkelijkeVraagprijs ."<br>\n";
		$tijdstip = $AangebodenSinds;
		$prijs[$tijdstip]	= $OorspronkelijkeVraagprijs;
		$naam[$tijdstip]	= 'Oorspronkelijke vraagprijs';
	}
			
	# We gaan er vanuit dat de laatste vraagprijs ook de verkoopdatum is
	if($LaatsteVraagprijs > 0 AND $Verkoopdatum > 10) {
		//echo date('d-m-Y', $Verkoopdatum) .' : '. $LaatsteVraagprijs ."<br>\n";
		$tijdstip = $Verkoopdatum;
		$prijs[$tijdstip]	= $LaatsteVraagprijs;
		$naam[$tijdstip]	= 'Laatste vraagprijs';				
	}			
			
	# Sommige huizen verdwijnen van de radar, als ze nog wel online zijn het prijsverloop monitoren.
	if($Vraagprijs > 0) {
		//echo date('d-m-Y') .' : '. $Vraagprijs ."<br>\n";
		$tijdstip = time();
		$prijs[$tijdstip]	= $Vraagprijs;
		$naam[$tijdstip]	= 'Vraagprijs';	
	}
	
	# Alle gevonden prijzen incl. tijdstippen invoeren			
	foreach($prijs as $key => $value) {				
		if(updatePrice($fundaID, $value, $key)) {
			$HTML[] = " -> ". $naam[$key] ." toegevoegd ($value / ". date("d-m-y", $key) .")<br>";
			toLog('debug', '', $fundaID, $naam[$key] ." toegevoegd");
		} else {
			toLog('error', '', $fundaID, "Error met toevoegen $value als ". $naam[$key]);
		}
	}
	
	# Als er een startdatum gevonden is die verder terugligt dan die bekend was => invoegen
	if($startDatum != $FundaData['start']) {
		$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum WHERE $HuizenID like $fundaID";
		
		if(mysql_query($sql_update)) {
			$HTML[] = " -> begintijd aangepast<br>";
		} else {
			toLog('error', '', $fundaID, "Error met verwerken begintijd");
		}				
	}

	# Als er geen verkoopdatum bekend is, is hij niet verkocht en dus nog online
	if($Verkoopdatum == '') {
		$sql_update = "UPDATE $TableHuizen SET $HuizenEind = ". time() ." WHERE $HuizenID like $fundaID";
		
		if(mysql_query($sql_update)) {
			$HTML[] = " -> eindtijd aangepast<br>";
		} else {
			toLog('error', '', $fundaID, "Error met verwerken begintijd");
		}				
	}
		
	# Als er een verkoopdatum bekend is => die datum als eindtijd invoeren
	if($Verkoopdatum > 10) {
		$sql_update = "UPDATE $TableHuizen SET $HuizenStart = $startDatum, $HuizenEind = $Verkoopdatum, $HuizenVerkocht = '1' WHERE $HuizenID like $fundaID";
				
		if(mysql_query($sql_update)) {
			$HTML[] = " -> begin- en eindtijd aangepast (verkocht)<br>";
			toLog('info', '', $fundaID, "Huis is verkocht");
		} else {
			toLog('error', '', $fundaID, "Error met verwerken verkocht huis");
		}			
	}
	
	return $HTML;
}


function makeDateSelection($bDag, $bMaand, $bJaar, $eDag, $eMaand, $eJaar) {
	$maandNamen = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mrt', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dec');
		
	$begin[] = "<select name='bDag'>";
	for($d=1 ; $d<=31 ; $d++)	$begin[] = "	<option value='$d'". ($d == $bDag ? ' selected' : '') .">$d</option>";
	$begin[] = "	</select>";
	$begin[] = "	<select name='bMaand'>";
	for($m=1 ; $m<=12 ; $m++)	$begin[] = "	<option value='$m'". ($m == $bMaand ? ' selected' : '') .">". $maandNamen[$m] ."</option>";
	$begin[] = "	</select>";
	$begin[] = "	<select name='bJaar'>";
	for($j=2004 ; $j<=date("Y") ; $j++)	$begin[] = "	<option value='$j'". ($j == $bJaar ? ' selected' : '') .">$j</option>";
	$begin[] = "	</select>";
	
	$eind[] = "<select name='eDag'>";
	for($d=1 ; $d<=31 ; $d++)	$eind[] = "	<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>";
	$eind[] = "	</select>";
	$eind[] = "	<select name='eMaand'>";
	for($m=1 ; $m<=12 ; $m++)	$eind[] = "	<option value='$m'". ($m == $eMaand ? ' selected' : '') .">". $maandNamen[$m] ."</option>";
	$eind[] = "	</select>";
	$eind[] = "	<select name='eJaar'>";
	for($j=2004 ; $j<=date("Y") ; $j++)	$eind[] = "	<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>";
	$eind[] = "	</select>";
	
	return array(implode("\n", $begin), implode("\n", $eind));
}


function makeSelectionSelection($disableList, $blankOption, $preSelect = 0) {
	# Vraag alle actieve opdrachten en lijsten op en zet die in een pull-down menu
	# De value is Z... voor een zoekopdracht en L... voor een lijst		
	$Opdrachten = getZoekOpdrachten($_SESSION['account'], 1);
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


function generatePassword ($length = 8) {
	// start with a blank password
	$password = "";
	$possible = "";
	
	// define possible characters - any character in this string can be
	// picked for use in the password, so if you want to put vowels back in
  // or add special characters such as exclamation marks, this is where
  // you should do it
  //$possible = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#$%&";
  $possible .= "1234567890";
  $possible .= "bcdfghjkmnpqrtvwxyz";
  $possible .= "BCDFGHJKLMNPQRTVWXYZ";
  $possible .= "!#$%&";
  
  // we refer to the length of $possible a few times, so let's grab it now
  $maxlength = strlen($possible);
  
  // check for length overflow and truncate if necessary
  if ($length > $maxlength) {
  	$length = $maxlength;
  }
  
  // set up a counter for how many characters are in the password so far
  $i = 0;
  
  // add random characters to $password until $length is reached
  while ($i < $length) { 
  	// pick a random character from the possible ones
  	$char = substr($possible, mt_rand(0, $maxlength-1), 1);
  	
  	// have we already used this character in $password?
  	if (!strstr($password, $char)) {
  		// no, so it's OK to add it onto the end of whatever we've already got...
  		$password .= $char;
      // ... and increase the counter by one
      $i++;
    }
  }
  
  // done!
  return $password;
}

function updateMakelaar($data) {
	global $TableHuizen, $HuizenMakelaar, $HuizenID;
	
	$sql = "UPDATE $TableHuizen SET $HuizenMakelaar = '". urlencode($data['makelaar']) ."' WHERE $HuizenID = '". $data['id'] ."'";
	$result = mysql_query($sql);
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
		
		if($data['verkocht'] == 1) {
			$status = 'verkocht';
		} elseif($data['verkocht'] == 2) {
			$status = 'onder voorbehoud';
		} elseif($data['offline'] == 1) {
			$status = 'offline';	
		} else {
			$status = 'beschikbaar';
		}
		
		$CSV_regel = array($data['adres']);		
		if(in_array('ID', $prefixen))							$CSV_regel[] = $huisID;
		if(in_array('url', $prefixen))						$CSV_regel[] = 'http://funda.nl/'.$huisID;
		if(in_array('Kadaster', $prefixen))				$CSV_regel[] = 'http://funda.nl/kadaster/?ref='.$huisID;
		if(in_array('Huidige Prijs', $prefixen))	$CSV_regel[] = getHuidigePrijs($huisID);
		if(in_array('Orginele Prijs', $prefixen))	$CSV_regel[] = getOrginelePrijs($huisID);
		if(in_array('Status', $prefixen))					$CSV_regel[] = $status;
		if(in_array('Makelaar', $prefixen))				$CSV_regel[] = $data['makelaar'];
		if(in_array('Wijk', $prefixen))						$CSV_regel[] = $data['wijk'];
		if(in_array('Latitude', $prefixen))				$CSV_regel[] = str_replace('.', ',', $data['lat']);
		if(in_array('Longitude', $prefixen))			$CSV_regel[] = str_replace('.', ',', $data['long']);
		
		foreach($kolomen as $dummy => $kenmerk) {				
			$string = $kenmerken[$kenmerk];
			$string = str_replace('&nbsp;m&sup2;', '', $string);
			$string = str_replace('&nbsp;m&sup3;', '', $string);
			$string = str_replace('&#235;', '�', $string);
			$string = str_replace('&amp;', '&', $string);
			$string = html_entity_decode($string);
			
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
	
	$propertie	= getString('<div class="prop-oh">', '</div>', $contents, 0);
	$datum			= getString('</strong> ', ' van ', $propertie[0], 0);
	$tijden			= getString(' van ', ' uur', $datum[1], 0);
	
	$temp				= explode('-', guessDate($datum[0]));
		
	$dag			= $temp[0];
	$maand		= $temp[1];
	$jaar			= $temp[2];	
	$beginUur	= substr($tijden[0], 0, 2);
	$beginMin	= substr($tijden[0], 3, 2);
	$eindUur	= substr($tijden[0], 10, 2);
	$eindMin	= substr($tijden[0], 13, 2);
	
	//echo $dag .'|'. $maand .'|'. $jaar .'|'. $beginUur .'|'. $beginMin .'|'. $eindUur .'|'. $eindMin;
	
	$start = mktime($beginUur, $beginMin, 0, $maand, $dag, $jaar);
	$eind = mktime($eindUur, $eindMin, 0, $maand, $dag, $jaar);
	
	return array($start, $eind);
}

function removeOpenHuis($id) {
	global $TableHuizen, $HuizenOpenHuis, $HuizenID, $TableResultaat, $ResultaatOpenHuis, $ResultaatID;
	
	$sql = "UPDATE $TableHuizen SET $HuizenOpenHuis = '0' WHERE $HuizenID = '$id'";
	$result = mysql_query($sql);
	
	$sql = "UPDATE $TableResultaat SET $ResultaatOpenHuis = '0' WHERE $ResultaatID = '$id'";
	$result = mysql_query($sql);	
}

function getNextOpenhuis($id) {
	global $TableCalendar, $CalendarHuis, $CalendarStart, $CalendarEnd;
	
	$nu			= time();	
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
?>
