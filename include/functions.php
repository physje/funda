<?

function getZoekOpdrachten($active) {
	global $TableZoeken, $ZoekenKey, $ZoekenActive;
	connect_db();
	
	$sql = "SELECT $ZoekenKey FROM $TableZoeken";
	
	if($active == '1' OR $active == '0') {
		$sql .= " WHERE $ZoekenActive = '$active'";
	} elseif($active == '2') {
		$sql .= " WHERE $ZoekenKey = 3";
	}
		
	$result = mysql_query($sql);
	
	if($row = mysql_fetch_array($result)) {
		do {
			$Opdrachten[] = $row[$ZoekenKey];
		} while($row = mysql_fetch_array($result));
	}
	
	return $Opdrachten;
}

function getOpdrachtData($id) {
	global $TableZoeken, $ZoekenKey, $ZoekenActive, $ZoekenNaam, $ZoekenURL, $ZoekenMail, $ZoekenAdres;
	
	$sql		= "SELECT * FROM $TableZoeken WHERE $ZoekenKey = $id";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	
	$data['active']	= $row[$ZoekenActive];
	$data['mail']		= $row[$ZoekenMail];
	$data['adres']	= $row[$ZoekenAdres];
	$data['naam']		= urldecode($row[$ZoekenNaam]);
	$data['url']		= urldecode($row[$ZoekenURL]);
	
	return $data;
}

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

/*
function getLocation($string) {
	// URL : http://code.google.com/intl/nl/apis/maps/documentation/geocoding/
	// URL : http://mapki.com/wiki/Google_Map_Parameters

	$API_Key = 'ABQIAAAAu47B1qoFTFNYK9JqxGBuxhQfNzYv9ePMZnPGoYxKOXwffJ0ddxTfMVDQR2ZJAk6U34HnUZWm1ntUgw';
	$URL_geocode = "http://maps.google.com/maps/api/geocode/xml?address=". urlencode($string) ."&sensor=false&key=". $API_Key;
	
	//echo "<a href='$URL_geocode'>$string</a> ";

	$XML = file_get_contents($URL_geocode);

	$loc = getString('<location>', '</location>', $XML, 0);

	$lat = getString('<lat>', '</lat>', $loc[0], 0);
	$lon = getString('<lng>', '</lng>', $loc[0], 0);
	
	$coord_lon = explode('.', $lon[0]);
	$coord_lat = explode('.', $lat[0]);

	return array($coord_lat[0], $coord_lat[1], $coord_lon[0], $coord_lon[1]);
}
*/

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
	
	/*	
	if(is_numeric($coord[1])) {
		$sql = "UPDATE $TableHuizen SET $HuizenNdeg = '$coord[0]', $HuizenNdec = '$coord[1]', $HuizenOdeg = '$coord[2]', $HuizenOdec = '$coord[3]' WHERE $HuizenID = '$huisID'";
		if(!mysql_query($sql)) {
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}*/
	
	if(!addKnowCoordinates($coord, $huisID)) {
		return false;
	} else {
		return true;
	}	
}

function addKnowCoordinates($coord, $huisID) {
	global $TableHuizen, $HuizenNdeg, $HuizenNdec, $HuizenOdeg, $HuizenOdec, $HuizenID;
			
	if(is_numeric($coord[1])) {
		$sql = "UPDATE $TableHuizen SET $HuizenNdeg = '$coord[0]', $HuizenNdec = '$coord[1]', $HuizenOdeg = '$coord[2]', $HuizenOdec = '$coord[3]' WHERE $HuizenID = '$huisID'";
		if(!mysql_query($sql)) {
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}


/*
function addWijk($wijk, $huisID) {
	global $TableHuizen, $HuizenWijk, $HuizenID;

	$sql = "UPDATE $TableHuizen SET $HuizenWijk = '$wijk' WHERE $HuizenID = '$huisID'";
	if(!mysql_query($sql)) {
		return false;
	} else {
		return true;
	}
}
*/

function showBlock($String) {
	$Text = "<table width='95%' cellpadding='8' cellspacing='1' bgcolor='#636367'>\n";
	$Text .= "<tr>\n";
	$Text .= "	<td bgcolor='#EAEAEA'>\n";
	$Text .= "<!-- BEGIN BLOK INHOUD -->\n";	
	$Text .= $String;	
	$Text .= "<!-- EIND BLOK INHOUD -->\n";	
	$Text .= "</td>\n";	
	$Text .= "</tr>\n";
	$Text .= "</table>\n";
	
	return $Text;
}

function makeTextBlock($string, $length) {
	if(strlen($string) > $length) {
		$titel = substr($string, 0, $length-5) . "<br>\n.....";
	} else {
		$titel = $string;
	}
	
	return $titel;
}

function extractFundaData($HuisText) {	
	// Overzichtspagina
	$HuisURL= getString('<a href="', '"', $HuisText, 0);
	$mappen = explode("/", $HuisURL[0]);
	$key		= $mappen[3];	$key_parts = explode("-", $key);
	$id			= $key_parts[1];
	$foto		= getString('<img src="', '" alt="" title="" class="photo"', $HuisURL[1], 0);
	$adres	= getString('<a href="'. $HuisURL[0] .'"', '</a>', $foto[1], 0);
	$PC			= getString('<li>', '<span class="', $adres[1], 0);		
	$prijs	= getString('<span class="price">', '</span>', $PC[1], 0);
	
	$postcode = explode(' ', trim($PC[0]));
		
	$HuisPrijs		= $prijs[0];			
	$HuisPrijs		= str_ireplace('&euro;&nbsp;', '' , $HuisPrijs);
	$HuisPrijs		= str_ireplace('.', '' , $HuisPrijs);
		
	if(!is_numeric($HuisPrijs)) {
		$HuisPrijs		= '0';
	}
	
	$data['id']			= $id;
	$data['url']		= trim($HuisURL[0]);
	$data['adres']	= trim(substr(trim($adres[0]), 1));
	//$data['PC_geheel']		= count($postcode);
	$data['PC_c']		= trim($postcode[0]);
	$data['PC_l']		= trim($postcode[1]);
	$data['plaats']	= end($postcode);
	$data['thumb']	= trim($foto[0]);
	$data['prijs']	= $HuisPrijs;
	
	return $data;
}

function convertToReadable($string) {
	$string = str_replace('&#235;', 'ë', $string);
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
	//$KML_file[] = '		<name>'. $data['adres'] .'</name>';
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
	$KML_file[] = '			<coordinates>'. $data['O_deg'] .'.'. $data['O_dec'] .','. $data['N_deg'] .'.'. $data['N_dec'] .',0</coordinates>';
	$KML_file[] = '		</Point>';
	$KML_file[] = '	</Placemark>';
	
	return implode("\n", $KML_file);
}

function extractDetailedFundaData($URL) {
	$contents		= file_get_contents_retry($URL);
	
	// Navigatie-gedeelte
	$navigatie	= getString('<p class="section path-nav">', '</p>', $contents, 0);
	$stappen		= explode('&gt;', $navigatie[0]);
	$wijk				= getString('/">', '</a>', $stappen[(count($stappen)-1)], 0);
	$data['wijk']	= trim($wijk[0]);	
	
	if($contents != "") {		
		// Omschrijving
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

	// Kenmerken
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
	
	/*
	if($contents != "") {
		$temp				= getString('<tr id="twwo13"  class="sub-cat">', '<span class="specs-ad">', $contents, 0);
		$Woonoppervlakte = getString('<span class="specs-val">', '</span>', $temp[0], 0);
	
		$temp				= getString('<tr id="perc12"  class="">', '<span class="specs-ad">', $contents, 0);
		$Perceeloppervlakte = getString('<span class="specs-val">', '</span>', $temp[0], 0);
	
		$temp				= getString('<tr id="twih12"  class="">', '<span class="specs-ad">', $contents, 0);
		$Inhoud			= getString('<span class="specs-val">', '</span>', $temp[0], 0);
			
		$temp				= getString('<tr id="aaka12"  class="">', '<span class="specs-ad">', $contents, 0);
		$Kamers			= getString('<span class="specs-val">', '</span>', $temp[0], 0);
		
		$data['oppervlakte']	= trim($Woonoppervlakte[0]);
		$data['perceel']= trim($Perceeloppervlakte[0]);
		$data['inhoud']	= trim($Inhoud[0]);
		$data['kamers']	= trim($Kamers[0]);		
	} else {
		$data['oppervlakte']	= '';
		$data['perceel']= '';
		$data['inhoud']	= '';
		$data['kamers']	= '';
	}	*/	
			
	// Foto	
	$contents		= file_get_contents_retry($URL.'fotos/');
	
	if($contents != "") {
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
	global $TableHuizen, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenStart, $HuizenEind;
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
	$sql .= "($HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenStart, $HuizenEind) ";
	$sql .= "VALUES ";
	$sql .= "('". $data['id'] ."', '". urlencode($data['url']) ."', '". urlencode($data['adres']) ."', '". urlencode($data['PC_c']) ."', '". urlencode($data['PC_l']) ."', '". urlencode($data['plaats']) ."', '". urlencode($moreData['wijk']) ."', '". urlencode($data['thumb']) ."', '$begin_tijd', '$eind_tijd')";
		
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

/*
function newPrice($key, $price) {
	global $TablePrijzen, $PrijzenKey, $PrijzenPrijs;	
	connect_db();
	
	$sql		= "SELECT * FROM $TablePrijzen WHERE $PrijzenKey like '$key' AND $PrijzenPrijs = $price";
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) > 0) {
		return false;
	} else {
		return true;
	}
}*/

function newPrice($key, $price) {
	$history = getPriceHistory($key);
	//$laatste = each($history);
		
	if($price != current($history)) {
		//echo $price .'|'. current($history) .'<br>';
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
	global $TableHuizen, $HuizenOpdracht, $HuizenID, $HuizenURL, $HuizenAdres, $HuizenPC_c, $HuizenPC_l, $HuizenPlaats, $HuizenWijk, $HuizenThumb, $HuizenNdeg, $HuizenNdec, $HuizenOdeg, $HuizenOdec, $HuizenStart, $HuizenEind, $HuizenVerkocht;
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
			$data['N_deg']		= $row[$HuizenNdeg];
			$data['N_dec']		= $row[$HuizenNdec];
			$data['O_deg']		= $row[$HuizenOdeg];
			$data['O_dec']		= $row[$HuizenOdec];
			$data['start']		= $row[$HuizenStart];
			$data['eind']			= $row[$HuizenEind];
			$data['verkocht']	= $row[$HuizenVerkocht];
			
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

function getHuizen($opdracht) {
	global $TableHuizen, $HuizenOpdracht, $HuizenID, $HuizenAdres;
	global $TableResultaat, $ResultaatID, $ResultaatZoekID;
	connect_db();
	
	$sql		= "SELECT * FROM $TableHuizen, $TableResultaat WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID like '$opdracht' ORDER BY $TableHuizen.$HuizenAdres";
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

function toLog($type, $opdracht, $huis, $message) {
	global $TableLog, $LogID, $LogTime, $LogType, $LogOpdracht, $LogHuis, $LogMessage;	

	connect_db();
 	
	$tijd = time();	
	$sql = "INSERT INTO $TableLog ($LogTime, $LogType, $LogOpdracht, $LogHuis, $LogMessage) VALUES ($tijd, '$type', '$opdracht', '$huis', '". addslashes($message) ."')";
	if(!mysql_query($sql)) {
		echo "log-error : ". $sql;
	}
}

/*
function getHuisnummers($straat, $plaats) {
	$url ='http://www.postcode.nl/index?action=search&goto=postcoderesult&TreeID=1&address='. urlencode($plaats) .'%2C+'. urlencode($straat) .'&x=0&y=0';
		
	$contents	= file_get_contents($url);
	
	//echo "<a href='$url'>$url</a><br>\n";
	//echo $contents;
	
	$inhoud		= getString('resultaten opge', 'resultaten opge', $contents, 0);
	$nummers	= explode('<td>', $inhoud[1]);
	$aantal		= count($nummers);
	
	//echo "<hr>\n". $aantal ."<hr>\n".$inhoud[1]."<hr>\n";
	
	//for($i=2; $i < $aantal ; $i = $i+1) {
	//	echo $i ." => ".strip_tags($nummers[$i]) ."<br>\n";
	//}
	
	for($i=3; $i < $aantal ; $i = $i+5) {
		$min_max = explode('t/m', strip_tags($nummers[$i]));
		$nummer_array[] = $min_max[0];
		$nummer_array[] = $min_max[1];
	}
		
	$output_array[0] = min($nummer_array);
	$output_array[1] = max($nummer_array);
	
	return $output_array;	
}
*/

/*
function getDoorloptijd($id) {	
	$data = getFundaData($id);
	
	$start = $data['start'];
	$einde = $data['eind'];
	
	$jaren	= date("Y", $einde) - date("Y", $start);
	$dagen	= date("z", $einde) - date("z", $start) + 1;
	
	if($dagen < 0) {
		$dagen = $dagen + 365;
		$jaren = $jaren - 1;
	}
	
	$maanden	= floor($dagen/30);
	$dagen		= $dagen - ($maanden*30);
	
	if($jaren > 0)		$output[] = $jaren ."j";
	if($maanden > 0)	$output[] = $maanden ."m";
	if($dagen > 0)		$output[] = $dagen ."d";
	
	return implode(" ", $output);
}
*/

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
	
	//echo $dag .'|'. $maand .'|'. $jaar;
	
	if($dag < 0) {
		$dag = $dag + date("t", mktime(0,0,0,$maandE-1,$jaarE,$dagE));
		$maand = $maand - 1;
	}
	
	if($maand < 0) {
		$maand = $maand + 12;
		$jaar = $jaar - 1;
	}
	
	
	// Druk het niet uit in jaren maar in maanden
	if($jaar > 0) {
		$maand	= $maand + (12*$jaar);
		$jaar		= 0;
	}
	
	/*
	// Funda rond het altijd naar beneden af op halve maand
	if($dag > 15) {
		$maand = $maand + 0.5;
	}
	$dag = 0;
	*/
	
	// Druk het uit in weken
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
	return str_replace('images.funda.nl/valentinamedia', 'cloud.funda.nl/valentina_media', $string);
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

?>