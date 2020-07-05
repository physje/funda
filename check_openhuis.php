<?php
include_once(__DIR__.'/include/config.php');
include_once(__DIR__ .'/include/HTML_TopBottom.php');
$db = connect_db();

$Opdrachten = getZoekOpdrachten(1, '', true);

$block = array();

# Doorloop alle zoekopdrachten
foreach($Opdrachten as $OpdrachtID) {
	# Alles initialiseren
	$String = array();
	
	$OpdrachtData		= getOpdrachtData($OpdrachtID);
	$OpdrachtURL	= "http://partnerapi.funda.nl/feeds/Aanbod.svc/rss/?type=koop&zo=". getSearchString($OpdrachtData['url'], true) .'open-huis/';
	
	$content			= file_get_contents_retry($OpdrachtURL);
		
	$Huizen = explode('<item>', $content);
	array_shift($Huizen);
	
	if(count($Huizen) > 0) {
		$String[] = "<h1>". $OpdrachtData['naam'] ."</h1><br>";
		$String[] = "(<a href='". $OpdrachtData['url'] .'open-huis/' ."'>funda.nl</a>; <a href='$OpdrachtURL'>RSS</a>)";
		$String[] = "<ol>";
	}
			
	foreach($Huizen as $huis) {
		$data			= RSS2Array($huis);
		$fundaID	= $data['id'];
		
		$String[] = "<li><a href='". $data['link'] ."'>". formatStreetAndNumber($fundaID) ."</a> (<a href='admin/edit.php?id=$fundaID'>$fundaID</a>)</li>";
		
		if(!hasOpenHuis($fundaID)) {
			toLog('info', $OpdrachtID, $fundaID, 'Open huis aangekondigd');
			setOpenHuis($fundaID);
			mark4Details($fundaID);			
			sendPushoverOpenHuis($fundaID, $OpdrachtID);
		}
	}
	
	if(count($String) > 0) {
		$String[] = '</ol>';
		$block[] = implode("\n", $String);
	}
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
foreach($block as $key => $value) {
	echo showBlock($value);
	echo '<p>';
}
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
