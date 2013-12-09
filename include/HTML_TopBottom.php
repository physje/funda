<?php

# Header
$HTMLHeader	 = "<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->\n\n";
$HTMLHeader	.= "<html>\n";
$HTMLHeader	.= "<head>\n";
$HTMLHeader	.= "	<title>$ScriptTitle $Version</title>\n";
$HTMLHeader	.= "	<link rel='stylesheet' type='text/css' href='". $ScriptURL ."extern/style.css'>\n";

if($autocomplete) {
	$HTMLHeader .= "	<link rel='stylesheet' type='text/css' href='http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css'>\n";
	$HTMLHeader .= "	<script src=\"http://code.jquery.com/jquery-1.9.1.js\"></script>\n";
	$HTMLHeader .= "	<script src=\"http://code.jquery.com/ui/1.10.2/jquery-ui.js\"></script>\n";
	$HTMLHeader .= "	<link rel=\"stylesheet\" href=\"/resources/demos/style.css\" />\n";
	$HTMLHeader .= "		<script>\n";
	$HTMLHeader .= "		$(function() {\n";
	
	$sql		= "SELECT $TableHuizen.$HuizenID, $TableHuizen.$HuizenAdres, $TableHuizen.$HuizenPlaats FROM $TableHuizen, $TableZoeken, $TableResultaat, $TableVerdeling WHERE $TableZoeken.$ZoekenKey = $TableResultaat.$ResultaatZoekID AND $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableVerdeling.$VerdelingOpdracht = $TableZoeken.$ZoekenKey AND $TableZoeken.$ZoekenUser = ". $_SESSION['account'] ." GROUP BY $TableHuizen.$HuizenID";
	$result	= mysql_query($sql);
	$row		= mysql_fetch_array($result);
	
	do {
		$return_arr[] = convertToReadable(urldecode($row[$HuizenAdres]).', '.urldecode($row[$HuizenPlaats]).' ['.urldecode($row[$HuizenID]) .']');
	} while($row = mysql_fetch_array($result));
			
	$HTMLHeader .= '		var availableTags = ["'. implode('","', $return_arr).'"];'.NL;	
	$HTMLHeader .= "		$( \"#huizen\" ).autocomplete({\n";
	$HTMLHeader .= "		source: availableTags\n";
	$HTMLHeader .= "		});\n";
	$HTMLHeader .= "	});\n";
	$HTMLHeader .= "</script>\n";
}

if($googleMaps) {
	$HTMLHeader .= "	<script src='../include/if_gmap.js'></script>\n";
	$HTMLHeader .= "	<script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false'></script>\n";
}

$HTMLHeader	.= "</head>\n";

if($googleMaps) {
	$HTMLHeader	.= "<body onload='if_gmap_init();'>\n";
} else {
	$HTMLHeader	.= "<body>\n";
}
$HTMLHeader	.= "<center>\n";
$HTMLHeader	.= "<table width='100%' align='center' border=0>\n";



# PreFooter (alleen gebruikt in mail volgens mij)
$HTMLPreFooter = "<tr>\n";
$HTMLPreFooter .= "	<td colspan='2' align='center'>". showBlock($FooterText) ."</td>\n";
$HTMLPreFooter .= "</tr>\n";



# Footer
$HTMLFooter = "</table>\n";			
$HTMLFooter .= "</body>\n";
$HTMLFooter .= "</html>\n";
$HTMLFooter .= "\n\n<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";
		

?>