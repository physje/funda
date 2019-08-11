<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');

$db = connect_db();

$pageDir = '../'.$offlineDir.'huis/';

$String = $block = array();

if ($handle = opendir($pageDir)) {
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." && $entry != "..") {
			$files[] = $entry;
		}
  }
	closedir($handle);
}

# Doorloop alle zoekopdrachten
foreach($files as $file) {
	# Alles initialiseren
	set_time_limit (60);
	$bestand = $pageDir.$file;
	
	$fp = fopen($bestand, 'r+');
	$contents = fread($fp, filesize($bestand));
	fclose($fp);
	
	$data = extractFundaDataFromPage($contents);
	$HTML[] = "<a href='". $ScriptURL ."admin/edit.php?id=". $data[0]['id'] ."'>". $data[0]['adres'] ."</a><br>\n";
	
	# Als wij een huis niet kennen klopt er iets niet
	if(!knownHouse($data[0]['id'])) {
		toLog('error', '', $data[0]['id'], 'Huis niet bekend');
		
	# Meestal zal het huis wel bekend zijn
	} else {
		updateHouse($data[0], $data[1]);
		addCoordinates($data[0]['adres'], $data[0]['PC_c'], $data[0]['plaats'], $data[0]['id']);
		updatePrice($data[0]['id'], $data[0]['prijs'], time());
		
		# Als hij nog niet verkocht is moeten wij dat aangeven
		if($data[0]['verkocht'] != 1) {
			updateAvailability($data[0]['id']);
		
		# Als hij wel verkocht is moeten we de administratie daarvan even bijwerken
		} else {
			$temp = updateVerkochtDataFromPage($data[0], $data[1]);
			$HTML[] = implode("<br>\n", $temp)."<br>\n";
		}
		toLog('info', '', $data[0]['id'], 'Offline pagina ingeladen');
		remove4Details($data[0]['id']);
	}
	unlink($bestand);
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='8%'>&nbsp;</td>\n";
echo "<td width='84%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $HTML));
echo "</td>\n";
echo "<td width='8%'>&nbsp;</td>\n";
echo "</tr>\n";
echo "</tr>\n";
echo $HTMLFooter;
