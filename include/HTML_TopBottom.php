<?php

# Header
$HTMLHeader	 = "<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->\n\n";
$HTMLHeader	.= "<html>\n";
$HTMLHeader	.= "<head>\n";

if(isset($userInteraction) AND !$userInteraction) {
	$HTMLHeader	.= "	<meta http-equiv='refresh' content='2; url=$forwardURL' />\n";
}

$HTMLHeader	.= "	<title>$ScriptTitle $Version</title>\n";
$HTMLHeader	.= "	<link rel='stylesheet' type='text/css' href='". $ScriptURL ."extern/style.css'>\n";

if(isset($autocomplete)) {
	$HTMLHeader .= "	<link rel='stylesheet' type='text/css' href='http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css'>\n";
	$HTMLHeader .= "	<script src=\"http://code.jquery.com/jquery-1.9.1.js\"></script>\n";
	$HTMLHeader .= "	<script src=\"http://code.jquery.com/ui/1.10.2/jquery-ui.js\"></script>\n";
	$HTMLHeader .= "	<link rel=\"stylesheet\" href=\"/resources/demos/style.css\" />\n";
	$HTMLHeader .= "		<script>\n";
	$HTMLHeader .= "		$(function() {\n";
	
	/*
	$sql		= "SELECT ";
	$sql		.= "$TableHuizen.$HuizenID, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenPlaats ";
	$sql		.= "FROM ";
	$sql		.= "$TableHuizen, $TableZoeken, $TableResultaat, $TableVerdeling ";
	$sql		.= "WHERE ";
	$sql		.= "$TableZoeken.$ZoekenKey = $TableResultaat.$ResultaatZoekID AND ";
	$sql		.= "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND ";
	$sql		.= "$TableVerdeling.$VerdelingOpdracht = $TableZoeken.$ZoekenKey AND ";
	$sql		.= "$TableZoeken.$ZoekenUser = ". $_SESSION['account'];
	$sql		.= " GROUP BY $TableHuizen.$HuizenID";
	*/
	
	$sql		= "SELECT ";
	$sql		.= "$HuizenID, $HuizenAdres, $HuizenPlaats ";
	$sql		.= "FROM ";
	$sql		.= "$TableHuizen ";
	$sql		.= "GROUP BY $HuizenID ";
	$sql		.= "ORDER BY $HuizenStraat, $HuizenNummer, $HuizenLetter, $HuizenToevoeging";

	$result	= mysqli_query($db, $sql);
	$row		= mysqli_fetch_array($result);
	
	do {
		$return_arr[] = convertToReadable(urldecode($row[$HuizenAdres]).', '.urldecode($row[$HuizenPlaats]).' ['.urldecode($row[$HuizenID]) .']');
	} while($row = mysqli_fetch_array($result));
			
	$HTMLHeader .= '		var availableTags = ["'. implode('","', $return_arr).'"];'.NL;	
	$HTMLHeader .= "		$( \"#huizen\" ).autocomplete({\n";
	$HTMLHeader .= "		source: availableTags\n";
	$HTMLHeader .= "		});\n";
	$HTMLHeader .= "	});\n";
	$HTMLHeader .= "</script>\n";
}

if(isset($googleMaps)) {
	$HTMLHeader .= "	<script src='../include/if_gmap.js'></script>\n";
	$HTMLHeader .= "	<script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false'></script>\n";
}

if(isset($leaflet)) {
	$HTMLHeader	.= "        <link rel=\"stylesheet\" href=\"https://leafletjs-cdn.s3.amazonaws.com/content/leaflet/master/leaflet.css\" />\n";
	$HTMLHeader	.= "        <script src=\"https://leafletjs-cdn.s3.amazonaws.com/content/leaflet/master/leaflet.js\"></script>\n";
	$HTMLHeader	.= "        <script src=\"https://tiles.unwiredmaps.com/js/leaflet-unwired.js\"></script>\n";
	$HTMLHeader	.= "        <style>\n";
	$HTMLHeader	.= "            #map { height: 630px; }\n";
	$HTMLHeader	.= "        </style>\n";
}

if(isset($mapbox)) {
	$HTMLHeader	.= "<meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />\n";
	$HTMLHeader	.= "<script src='https://api.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.js'></script>\n";
	$HTMLHeader	.= "<link href='https://api.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.css' rel='stylesheet' />\n";
	$HTMLHeader	.= "<style>\n";
	$HTMLHeader	.= "	body { margin:0px; padding:0px; }\n";
	$HTMLHeader	.= "	#map { position:absolute; top:0px; bottom:0px; width:100%; }\n";
	$HTMLHeader	.= "</style>\n";	
}


$HTMLHeader	.= "</head>\n";

if(isset($googleMaps)) {
	$HTMLHeader	.= "<body onload='if_gmap_init();'>\n";
} else {
	$HTMLHeader	.= "<body>\n";
}

if(isset($userInteraction) AND $userInteraction) {
	$HTMLHeader	.= "Ga handmatig naar <a href='$forwardURL'>$forwardURL</a>\n";
}

$HTMLHeader	.= "<center>\n";
$HTMLHeader	.= "<table width='100%' align='center' border=0>\n";



# PreFooter (alleen gebruikt in mail volgens mij)
$HTMLPreFooter = "<tr>\n";
$HTMLPreFooter .= "	<td colspan='2' align='center'>". (isset($FooterText) ? showBlock($FooterText) : '&nbsp;' ) ."</td>\n";
$HTMLPreFooter .= "</tr>\n";



# Footer
$HTMLFooter = "</table>\n";			
$HTMLFooter .= "</body>\n";
$HTMLFooter .= "</html>\n";
$HTMLFooter .= "\n\n<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";